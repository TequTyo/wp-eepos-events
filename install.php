<?php

function eepos_events_install_current_site() {
	global $wpdb;

	eepos_events_init();

	$LATEST_DB_VERSION = 2;
	$dbVersion         = intval(get_option( "eepos_events_db_version", 0 ));

	if ( $dbVersion < 1 ) {
		$logTableSql = "
			CREATE TABLE {$wpdb->eepos_events_log} (
				`event_id` INT(10) UNSIGNED NOT NULL,
				`post_id` BIGINT(10) UNSIGNED NOT NULL,
				PRIMARY KEY (`event_id`)
			) COLLATE='utf8mb4_swedish_ci'
	    ";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $logTableSql );
	}

	if ( $dbVersion < 2 ) {
		$oldSuppressValue = $wpdb->suppress_errors;
		$wpdb->suppress_errors(true);

		// If one of the old broken foreign keys still exist, delete them
		$wpdb->query( "
			ALTER TABLE {$wpdb->eepos_events_log}
				DROP FOREIGN KEY `fk_posts`
		" );

		$wpdb->query( "
			ALTER TABLE {$wpdb->eepos_events_log}
				DROP FOREIGN KEY `fk_eepos_events_log_posts`
		" );

		$wpdb->suppress_errors($oldSuppressValue);

		$wpdb->query( "
			ALTER TABLE {$wpdb->eepos_events_log}
			ADD CONSTRAINT `{$wpdb->prefix}fk_eepos_events_log_posts`
				FOREIGN KEY (`post_id`) REFERENCES `{$wpdb->posts}` (`ID`)
					ON UPDATE CASCADE
					ON DELETE CASCADE
		" );
	}

	update_option( "eepos_events_db_version", $LATEST_DB_VERSION, 'yes' );
}

function eepos_events_install( $network_wide ) {
	if ( $network_wide ) {
		$site_ids = get_sites( [ 'fields' => 'ids', 'network_id' => get_current_network_id() ] );
		foreach ( $site_ids as $site_id ) {
			// Setup on other site
			switch_to_blog( $site_id );
			eepos_events_install_current_site();

			// Restore current site
			restore_current_blog();
			eepos_events_init();
		}
	} else {
		eepos_events_install_current_site();
	}
}
