<?php

function eepos_events_install() {
	global $wpdb;

	eepos_events_init();

	$logTableSql = "
		CREATE TABLE {$wpdb->eepos_events->log} (
			`event_id` INT(10) UNSIGNED NOT NULL,
			`post_id` BIGINT(10) UNSIGNED NOT NULL,
			PRIMARY KEY (`event_id`),
			INDEX `fk_posts` (`post_id`)
		) COLLATE='utf8mb4_swedish_ci'
    ";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $logTableSql );

	$wpdb->query("
		ALTER TABLE {$wpdb->eepos_events->log}
		ADD CONSTRAINT `fk_posts`
		  FOREIGN KEY (`post_id`) REFERENCES `wp_posts` (`ID`)
		    ON UPDATE CASCADE
		    ON DELETE CASCADE
	");
}