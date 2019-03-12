<?php

function eepos_events_register_actions_url() {
	add_rewrite_rule(
		'^eepos-events/(.+)',
		'index.php?eepos_events_action=$matches[1]',
		'top'
	);
}
add_action('init', 'eepos_events_register_actions_url');

function eepos_events_add_action_query_var( $vars ) {
	$vars[] = 'eepos_events_action';
	return $vars;
}
add_filter('query_vars', 'eepos_events_add_action_query_var');

function eepos_events_flush_rewrite_rules() {
	flush_rewrite_rules();
}

function eepos_events_handle_action($wp) {
	$action = $wp->query_vars['eepos_events_action'] ?? null;

	if ($action === 'auto-sync') {
		exit('It works!');
	}
}
add_action('parse_request', 'eepos_events_handle_action');
