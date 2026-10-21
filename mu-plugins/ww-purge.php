<?php
/**
 * Plugin Name: WW Purge Endpoint (W-011)
 * Description: POST /wp-json/ww/v1/purge with header X-WW-Token. Purges all LiteSpeed cache and UCSS. 1 call per minute (rate limit in option ww_purge_last, set after purge).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WW_PURGE_TOKEN_SHA256', '4bd458cefcd3ac5220b022c524938693e2098de7332c7bb639f098da3693d7a8' );

add_action( 'rest_api_init', function () {
	register_rest_route( 'ww/v1', '/purge', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'ww_purge_handle',
	) );
} );

function ww_purge_handle( WP_REST_Request $req ) {
	$provided = (string) $req->get_header( 'x_ww_token' );
	if ( '' === $provided ) { $provided = (string) $req->get_param( 'token' ); }
	if ( ! hash_equals( WW_PURGE_TOKEN_SHA256, hash( 'sha256', $provided ) ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 403 );
	}
	$now  = time();
	$last = (int) get_option( 'ww_purge_last', 0 );
	if ( $last && ( $now - $last ) < 60 ) {
		return new WP_REST_Response( array( 'ok' => false ), 429 );
	}
	if ( has_action( 'litespeed_purge_all' ) ) { do_action( 'litespeed_purge_all' ); }
	// Mirrors private \LiteSpeed\Purge::_purge_all_ucss() using its public parts (LSCWP purge.cls.php).
	if ( class_exists( '\\LiteSpeed\\CSS' ) && class_exists( '\\LiteSpeed\\Data' ) ) {
		try {
			do_action( 'litespeed_purged_all_ucss' );
			\LiteSpeed\CSS::cls()->rm_cache_folder( 'ucss' );
			\LiteSpeed\Data::cls()->url_file_clean( 'ucss' );
		} catch ( \Throwable $e ) {}
	}
	update_option( 'ww_purge_last', time(), false );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
