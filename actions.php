<?php

function eepos_events_register_actions_url() {
	add_rewrite_rule(
		'^eepos-events/actions/(.+)',
		'index.php?eepos_events_action=$matches[1]',
		'top'
	);
}

add_action( 'init', 'eepos_events_register_actions_url' );

function eepos_events_add_action_query_var( $vars ) {
	$vars[] = 'eepos_events_action';

	return $vars;
}

add_filter( 'query_vars', 'eepos_events_add_action_query_var' );

function eepos_events_flush_rewrite_rules() {
	flush_rewrite_rules();
}

function eepos_events_handle_action( $wp ) {
	$action = $wp->query_vars['eepos_events_action'] ?? null;

	$jsonResponse = function ( $data ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $data );
		exit;
	};

	if ( $action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		$serverKey = get_option( 'eepos_events_import_key', null );
		$userKey   = $_GET['key'] ?? null;

		if ( ! $serverKey || ! $userKey || ! hash_equals( $serverKey, $userKey ) ) {
			$jsonResponse( [ 'error' => 'Invalid key' ] );
		}

		$eeposUrl = get_option('eepos_events_eepos_url', null);
		if (!$eeposUrl) {
			$jsonResponse( [ 'error' => 'No Eepos URL specified' ] );
		}

		try {
			eepos_events_import( $eeposUrl );
		} catch ( EeposEventsImportException $e ) {
			$jsonResponse( [ 'error' => $e->getMessage() ] );
		}

		$jsonResponse( [ 'result' => 'success' ] );
	}
}

add_action( 'parse_request', 'eepos_events_handle_action' );
