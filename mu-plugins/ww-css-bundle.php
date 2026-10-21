<?php
/*
Plugin Name: WW CSS Bundle (design item 0, 2026-10-19)
Description: Replaces the site-wide inline <style> patch layers with one versioned file, wp-content/uploads/ww-css/wraywest.css. Fail-safe: when any captured block differs from the bundled bytes (md5 manifest), the page is served unchanged with the old inline blocks. Switch: WW_CSS_BUNDLE_MODE = 'off' | 'capture' | 'on'.
*/
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'WW_CSS_BUNDLE_MODE' ) ) define( 'WW_CSS_BUNDLE_MODE', 'on' );

final class WW_CSS_Bundle {
	const DIR = 'ww-css';
	public static function ids() {
		return array( 'ww-design-tokens','wraywest-layout-fix','wraywest-custom-css','wraywest-adapt-polish','ww-header-tweaks','ww-typography-fix','wp-custom-css','ww-layout-fixes','ww-mobile-actions-css','ww-redesign-pass','ww-typography','ww-redesign-fixes-r2','ww-archive-hero-height','ww-craft-fixes','ww-hero-masthead' );
	}
	public static function init() {
		if ( WW_CSS_BUNDLE_MODE === 'off' ) return;
		add_action( 'template_redirect', function () {
			if ( is_admin() || is_feed() || is_customize_preview() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_user_logged_in() ) return;
			ob_start( array( __CLASS__, 'filter' ) );
		}, 0 );
	}
	private static function blocks( $html ) {
		$out = array();
		foreach ( self::ids() as $id ) {
			if ( preg_match_all( '#<style id="' . preg_quote( $id, '#' ) . '"[^>]*>(.*?)</style>#s', $html, $m, PREG_OFFSET_CAPTURE ) !== 1 ) return null; // missing or duplicated: fail safe
			$out[ $id ] = array( 'tag' => $m[0][0][0], 'pos' => $m[0][0][1], 'css' => $m[1][0][0] );
		}
		return $out;
	}
	public static function filter( $html ) {
		if ( stripos( $html, '<html' ) === false ) return $html;
		$b = self::blocks( $html );
		if ( ! $b ) return $html;
		$up = wp_upload_dir(); $dir = trailingslashit( $up['basedir'] ) . self::DIR;
		if ( WW_CSS_BUNDLE_MODE === 'capture' ) {
			$f = $dir . '/capture-' . md5( $_SERVER['REQUEST_URI'] ?? '' ) . '.json';
			if ( is_dir( $dir ) && ! file_exists( $f ) ) {
				$rows = array(); foreach ( $b as $id => $x ) { $rows[] = array( 'id' => $id, 'pos' => $x['pos'], 'md5' => md5( $x['css'] ), 'css' => $x['css'] ); }
				file_put_contents( $f, wp_json_encode( array( 'uri' => $_SERVER['REQUEST_URI'] ?? '', 'blocks' => $rows ) ) );
			}
			return $html;
		}
		$man = $dir . '/manifest.json';
		if ( ! file_exists( $man ) ) return $html;
		$mf = json_decode( file_get_contents( $man ), true );
		if ( empty( $mf['md5'] ) || empty( $mf['ver'] ) ) return $html;
		foreach ( self::ids() as $id ) { if ( ( $mf['md5'][ $id ] ?? '' ) !== md5( $b[ $id ]['css'] ) ) return $html; }
		// order must match the bundle order
		$prev = -1; foreach ( self::ids() as $id ) { if ( $b[ $id ]['pos'] < $prev ) return $html; $prev = $b[ $id ]['pos']; }
		$link = '<link rel="stylesheet" id="wraywest-bundle-css" href="' . esc_url( trailingslashit( $up['baseurl'] ) . self::DIR . '/wraywest.css?ver=' . rawurlencode( $mf['ver'] ) ) . '" media="all" data-no-optimize="1" />';
		$first = true;
		foreach ( self::ids() as $id ) {
			$html = self::replace_once( $html, $b[ $id ]['tag'], $first ? $link : '' );
			$first = false;
		}
		return $html;
	}
	private static function replace_once( $h, $s, $r ) { $p = strpos( $h, $s ); return $p === false ? $h : substr_replace( $h, $r, $p, strlen( $s ) ); }
}
WW_CSS_Bundle::init();
