<?php
/**
 * Plugin Name: Eepos Events
 */

// Init
require_once( __DIR__ . '/init.php' );

// Install
require_once( __DIR__ . '/install.php' );

// Admin panel
require_once( __DIR__ . '/admin.php' );

register_activation_hook( __FILE__, 'eepos_events_install' );
