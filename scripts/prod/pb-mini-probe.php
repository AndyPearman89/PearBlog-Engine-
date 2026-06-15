<?php
require_once __DIR__ . '/wp-load.php';

header( 'Content-Type: application/json; charset=utf-8' );

$token = isset( $_GET['t'] ) ? (string) $_GET['t'] : '';
if ( ! hash_equals( '__TOKEN__', $token ) ) {
	status_header( 403 );
	echo '{"ok":false}';
	exit;
}

wp_set_current_user( 1 );

$api = (string) get_option( 'pearblog_api_key', '' );

$request = new WP_REST_Request( 'POST', '/pearblog/v1/automation/process-content' );
$request->set_header( 'Authorization', 'Bearer ' . $api );
$request->set_param( 'action', 'process_content' );

$response = rest_do_request( $request );
$data = $response->get_data();

$first = null;
if ( is_array( $data ) && isset( $data['articles'][0] ) && is_array( $data['articles'][0] ) ) {
	$first = $data['articles'][0];
}

echo wp_json_encode(
	[
		'ok' => true,
		'http' => $response->get_status(),
		'success' => is_array( $data ) ? ( $data['success'] ?? null ) : null,
		'message' => is_array( $data ) ? ( $data['message'] ?? null ) : null,
		'first_status' => is_array( $first ) ? ( $first['status'] ?? null ) : null,
		'first_post_id' => is_array( $first ) ? ( $first['post_id'] ?? null ) : null,
		'first_error' => is_array( $first ) && isset( $first['error'] ) ? substr( (string) $first['error'], 0, 180 ) : null,
	]
);