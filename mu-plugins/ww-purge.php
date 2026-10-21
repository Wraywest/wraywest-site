<?php
/**
 * Plugin Name: WW Purge Endpoint (W-011)
 * Description: POST /wp-json/ww/v1/purge with header X-WW-Token. Purges all LiteSpeed cache and UCSS. 1 call per minute.
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
	if ( get_transient( 'ww_purge_rl' ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 429 );
	}
	set_transient( 'ww_purge_rl', 1, 60 );
	if ( has_action( 'litespeed_purge_all' ) ) { do_action( 'litespeed_purge_all' ); }
	if ( has_action( 'litespeed_purge_ucss' ) ) { do_action( 'litespeed_purge_ucss' ); }
	elseif ( class_exists( '\LiteSpeed\Purge' ) && method_exists( '\LiteSpeed\Purge', 'purge_ucss' ) ) { \LiteSpeed\Purge::purge_ucss(); }
	if ( class_exists( '\LiteSpeed\UCSS' ) && method_exists( '\LiteSpeed\UCSS', 'cls' ) ) {
		try { $u = \LiteSpeed\UCSS::cls(); if ( method_exists( $u, 'rm_cache' ) ) { $u->rm_cache( true ); } } catch ( \Throwable $e ) {}
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
