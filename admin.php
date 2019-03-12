<?php

function eepos_events_define_event_list_columns() {
	return [
		'cb'             => '<input type="checkbox">',
		'title'          => 'Tapahtuman nimi',
		'event_category' => 'Kategoria',
		'event_date'     => 'Pvm'
	];
}

add_filter( 'manage_eepos_event_posts_columns', 'eepos_events_define_event_list_columns' );

function eepos_events_get_event_list_column_values( $column, $post_id ) {
	if ( $column === 'event_category' ) {
		$terms = wp_get_post_terms( $post_id, 'eepos_event_category' );
		echo implode(', ', array_map(function($term) { return $term->name; }, $terms));
	} else if ( $column === 'event_date' ) {
		$startDate   = get_post_meta( $post_id, 'event_start_date', true );
		$currentYear = date( 'Y' );

		if ( $startDate ) {
			$startDateDT = new DateTime( $startDate );
			if ( $startDateDT->format( 'Y' ) === $currentYear ) {
				$startDateFormatted = $startDateDT->format( 'd.n. \k\l\o G.i' );
			} else {
				$startDateFormatted = $startDateDT->format( 'd.n.Y \k\l\o G.i' );
			}
		} else {
			$startDateFormatted = null;
		}

		echo $startDateFormatted;
	} else {
		echo '-';
	}
}

add_filter( 'manage_eepos_event_posts_custom_column', 'eepos_events_get_event_list_column_values', 10, 2 );

function eepos_events_add_import_menu_item() {
	add_menu_page( 'Tuo tapahtumat Eepoksesta', 'Tuo tapahtumat Eepoksesta', 'manage_options', 'import-eepos-events', 'eepos_events_import_page' );
}

add_action( 'admin_menu', 'eepos_events_add_import_menu_item' );

function eepos_events_import_page() {
	$formAction = admin_url( 'admin-post.php' );

	$eeposUrl = get_option( 'eepos_events_eepos_url', '' );
	$eeposUrl = esc_attr( $eeposUrl );

	?>
	<div class="wrap">
		<h1>Tuo tapahtumat Eepoksesta</h1>

		<form action="<?= $formAction ?>" method="post">
			<input type="hidden" name="action" value="eepos_events_import">
			<table class="form-table">
				<tr>
					<th>Eepoksen osoite</th>
					<td>
						<input type="text" name="eepos_url" placeholder="demo.eepos.fi" class="regular-text"
						       value="<?= $eeposUrl ?>">
					</td>
				</tr>
			</table>
			<p class="submit">
				<input class="button button-primary" type="submit" value="Tuo tapahtumat">
			</p>
		</form>
	</div>
	<?php
}

function eepos_events_import_action() {
	global $wpdb;

	eepos_events_add_import_menu_item();

	$exit = function ( $success = null, $err = null ) {
		if ( $success ) {
			$_SESSION['eepos_events_success'] = $success;
		}
		if ( $err ) {
			$_SESSION['eepos_events_error'] = $err;
		}

		wp_safe_redirect( menu_page_url( 'import-eepos-events', false ) );
		exit;
	};

	$eeposUrl = $_POST['eepos_url'];
	$match    = preg_match( '/^[a-zA-Z0-9\-]+\.eepos\.fi$/', $eeposUrl );
	if ( $match !== 1 ) {
		$exit( null, 'Virheellinen Eepoksen osoite' );
	}

	update_option( 'eepos_events_eepos_url', $eeposUrl );

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, "https://{$eeposUrl}/ext/api/events" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$data  = curl_exec( $ch );
	$errno = curl_errno( $ch );
	$err   = curl_error( $ch );
	curl_close( $ch );

	if ( $errno !== 0 ) {
		$exit( null, 'Virhe tietoja haettaessa: ' . $err );
	}

	$parsed = json_decode( $data );
	if ( $parsed === false ) {
		$exit( null, 'Virhe tietoja haettaessa: tieto on väärässä formaatissa' );
	}

	if ( $parsed->error ) {
		$exit( null, 'Virhe tietoja haettaessa: ' . $parsed->error );
	}

	foreach ($parsed as $event) {
		// If this event has already been imported, update it - unless it's been modified on WP's side
		$sanitizedId = intval($event->id);
		$existingEvent = $wpdb->get_row("SELECT * FROM {$wpdb->eepos_events->log} WHERE event_id={$sanitizedId}");
		$existingPostId = 0;
		if ($existingEvent) {
			$post = get_post($existingEvent->post_id);
			if ($post) {
				$existingPostId = $post->ID;
				if ($post->post_modified !== $post->post_date) continue; // Modified, skip
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
		update_post_meta($postId, 'instances', json_encode($event->instances));
		update_post_meta($postId, 'organizers', json_encode($event->organizers));

		$wpdb->query("REPLACE INTO {$wpdb->eepos_events->log} (event_id, post_id) VALUES ({$sanitizedId}, {$postId})");
	}

	$exit( 'Tapahtumat haettu!' );
}

add_action( 'admin_post_eepos_events_import', 'eepos_events_import_action' );

function eepos_events_admin_notices() {
	$error = $_SESSION['eepos_events_error'] ?? null;
	unset( $_SESSION['eepos_events_error'] );

	if ( $error ) {
		?>
		<div class="error notice">
			<p><?= esc_html( $error ) ?></p>
		</div>
		<?php
	}

	$success = $_SESSION['eepos_events_success'] ?? null;
	unset( $_SESSION['eepos_events_success'] );

	if ( $success ) {
		?>
		<div class="updated notice">
			<p><?= esc_html( $success ) ?></p>
		</div>
		<?php
	}
}

add_action( 'admin_notices', 'eepos_events_admin_notices' );
