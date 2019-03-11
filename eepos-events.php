<?php
/**
 * Plugin Name: Eepos Events
 */

// Init
require_once( __DIR__ . '/init.php' );

// Install
require_once( __DIR__ . '/install.php' );

// Admin panel
if (is_admin()) {
	require_once( __DIR__ . '/admin.php' );
}

// Public
require_once( __DIR__ . '/public.php' );

// Widgets
require_once( __DIR__ . '/widget-upcoming.php' );
require_once( __DIR__ . '/widget-list.php' );

register_activation_hook( __FILE__, 'eepos_events_install' );
