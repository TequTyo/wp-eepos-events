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

// Widget
require_once( __DIR__ . '/EeposEventsWidget.php' );

// Actions/custom endpoints
require_once( __DIR__ . '/actions.php' );

register_activation_hook( __FILE__, 'eepos_events_install' );
register_activation_hook( __FILE__, 'eepos_events_flush_rewrite_rules' );
