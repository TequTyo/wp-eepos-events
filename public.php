<?php

function eepos_events_single_template( $template ) {
	global $post;

	if ( $post->post_type === 'eepos_event' && $template !== locate_template( [ 'single-eepos_event.php' ] ) ) {
		return plugin_dir_path( __FILE__ ) . '/templates/event.php';
	}

	return $template;
}

add_filter( 'single_template', 'eepos_events_single_template' );
