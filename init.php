<?php

function eepos_events_init() {
	global $wpdb;

	// Tables
	$wpdb->eepos_events = (object) [
		'log' => 'eepos_events_log',
	];
}

add_action( 'plugins_loaded', 'eepos_events_init' );
