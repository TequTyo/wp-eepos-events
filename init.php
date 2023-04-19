<?php

function eepos_events_init() {
	global $wpdb;

	// Tables
	$wpdb->eepos_events_log = $wpdb->prefix . 'eepos_events_log';
}

add_action( 'plugins_loaded', 'eepos_events_init' );

function eepos_events_start_session() {
	session_start();
}

function eepos_events_register_post_type() {
	$labels = array(
		'name'					=> _x( 'Eepos-tapahtumat', 'post type general name' ),
		'singular_name'			=> _x( 'Eepos-tapahtuma', 'post type singular name' ),
		'add_new_item'			=> __( 'Lisää uusi tapahtuma' ),
		'edit_item'				=> __( 'Muokkaa tapahtumaa' ),
		'new_item'				=> __( 'Uusi tapahtuma' ),
		'all_items'				=> __( 'Kaikki tapahtumat' ),
		'view_item'				=> __( 'Katsele tapahtumia' ),
		'search_items'			=> __( 'Etsi tapahtumia' ),
		'not_found'				=> __( 'Ei tapahtumia' ),
		'not_found_in_trash'	=> __( 'No events found in the Trash' ),
		'parent_item_colon'		=> ',',
		'menu_name'				=> 'Eepos events'
	);

	$args = array(
		'labels'				=> $labels,
		'description'			=> 'Holds eepos events data',
		'public'				=> true,
		'publicly_queryable'	=> true,
		'menu_position'			=> 5,
		'supports'				=> array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
		'has_archive'			=> true,
		'menu_icon'				=> 'dashicons-calendar',
		'capability_type'		=> 'page',
		'show_in_rest'			=> true
	);

	register_post_type( 'eepos_event', $args);
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

wp_register_style('template-css', plugins_url('wp-eepos-events/templates/event.css'));

add_action( 'init', 'eepos_events_start_session', 1 );
add_action( 'init', 'eepos_events_register_post_type' );
add_action( 'init', 'eepos_events_register_taxonomy' );

// Elementor dynamic tag specifications

function register_new_dynamic_tag_group( $dynamic_tags_manager ) {

	$dynamic_tags_manager->register_group(
		'eepos_group',
		[
			'title' => esc_html__( 'Eepos event', 'textdomain' )
		]
	);
}

add_action( 'elementor/dynamic_tags/register', 'register_new_dynamic_tag_group' );

function register_eepos_variable_dynamic_tag( $dynamic_tags_manager ) {
	require_once( __DIR__ . '/dynamic-tags/eepos_variable_dynamic_tag_date.php' );
	require_once( __DIR__ . '/dynamic-tags/eepos_variable_dynamic_tag_location.php' );

	$dynamic_tags_manager->register( new Elementor_Dynamic_Tag_Eepos_Variable_Tag_1 );
	$dynamic_tags_manager->register( new Elementor_Dynamic_Tag_Eepos_Variable_Tag_2 );
}

add_action( 'elementor/dynamic_tags/register', 'register_eepos_variable_dynamic_tag' );
