<?php

class EeposEventsImportException extends Exception {}

function eepos_events_import($eeposUrl) {
	global $wpdb;

	$match = preg_match( '/^[a-zA-Z0-9\-]+\.eepos\.fi$/', $eeposUrl );
	if ( $match !== 1 ) {
		throw new EeposEventsImportException('Virheellinen Eepoksen osoite');
	}

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, "https://{$eeposUrl}/ext/api/events" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$data  = curl_exec( $ch );
	$errno = curl_errno( $ch );
	$err   = curl_error( $ch );
	curl_close( $ch );

	if ( $errno !== 0 ) {
		throw new EeposEventsImportException('Virhe tietoja haettaessa: ' . $err);
	}

	$parsed = json_decode( $data );
	if ( $parsed === false ) {
		throw new EeposEventsImportException('Virhe tietoja haettaessa: tieto on väärässä formaatissa' );
	}

	if ( $parsed->error ) {
		throw new EeposEventsImportException('Virhe tietoja haettaessa: ' . $parsed->error);
	}

	foreach ($parsed as $event) {
		// If this event has already been imported, update it - unless it's been modified on WP's side
		$sanitizedId = intval($event->id);
		$existingEvent = $wpdb->get_row("SELECT * FROM {$wpdb->eepos_events_log} WHERE event_id={$sanitizedId}");
		$existingPostId = 0;
		if ($existingEvent) {
			$post = get_post($existingEvent->post_id);
			if ($post) {
				$existingPostId = $post->ID;

				// Following skip disabled for now, make sure it works before re-enabling
				// if ($post->post_modified !== $post->post_date) continue; // Modified, skip
			}
		}

		$postId = wp_insert_post([
			'ID' => $existingPostId,
			'post_type' => 'eepos_event',
			'post_title' => $event->name,
			'post_content' => $event->description,
			'post_status' => 'publish'
		]);

		$catName = $event->category_name;
		if ($catName) {
			wp_insert_term($catName, 'eepos_event_category');
			wp_set_post_terms($postId, [$catName], 'eepos_event_category');
		}

		update_post_meta($postId, 'event_start_date', $event->start_date);
		update_post_meta($postId, 'event_end_date', $event->end_date);
		update_post_meta($postId, 'event_start_time', $event->start_time);
		update_post_meta($postId, 'event_end_time', $event->end_time);
		update_post_meta($postId, 'location', $event->location);
		update_post_meta($postId, 'instances', json_encode($event->instances));
		update_post_meta($postId, 'organizers', json_encode($event->organizers));

		$wpdb->query("REPLACE INTO {$wpdb->eepos_events_log} (event_id, post_id) VALUES ({$sanitizedId}, {$postId})");
	}

	// Remove upcoming events that have been deleted in Eepos
	$eventIds = array_map(function($ev) { return intval($ev->id); }, $parsed);
	$toRemove = $wpdb->get_results("
		SELECT eventLog.event_id, eventLog.post_id FROM {$wpdb->eepos_events_log} AS eventLog
		INNER JOIN {$wpdb->prefix}postmeta AS endDateMeta
		    ON endDateMeta.post_id = eventLog.post_id
		    AND endDateMeta.meta_key = 'event_end_date'
		WHERE endDateMeta.meta_value >= CURDATE()
		" . (count($eventIds) ? "AND events.id NOT IN (" . implode(', ', $eventIds) . ")" : '') . "
	");

	foreach ($toRemove as $item) {
		wp_delete_post($item->post_id, true);
	}
}