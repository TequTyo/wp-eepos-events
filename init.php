<?php

function eepos_events_init() {
	global $wpdb;

	// Tables
	$wpdb->eepos_events = (object) [
		'log' => 'eepos_events_log',
	];
}

add_action( 'plugins_loaded', 'eepos_events_init' );

function eepos_events_start_session() {
	session_start();
}

function eepos_events_register_post_type() {
	register_post_type( 'eepos_event', [
		'label'         => 'Eepos-tapahtumat',
		'menu_icon'     => 'dashicons-calendar',
		'menu_position' => 5,
		'public'        => true,
		'has_archive'   => true
	] );
}

function eepos_events_register_taxonomy() {
	register_taxonomy( 'eepos_event_category', [ 'eepos_event' ], [
		'hierarchial'       => true,
		'label'             => 'Tapahtumakategoriat',
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true
	] );
}

add_action( 'init', 'eepos_events_start_session', 1 );
add_action( 'init', 'eepos_events_register_post_type' );
add_action( 'init', 'eepos_events_register_taxonomy' );
