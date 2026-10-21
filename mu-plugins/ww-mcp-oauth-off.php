<?php
/**
 * Plugin Name: WW MCP OAuth Off
 * Description: Disables the WP MCP Ultimate 2.1.0+ bundled OAuth server (open dynamic client registration). Site uses Application Passwords only. Added 2026-10-18 by Website Ops (fix-01). Remove this file to re-enable OAuth.
 */
defined( 'ABSPATH' ) || exit;
add_filter( 'wp_mcp_ultimate_enable_oauth', '__return_false' );
