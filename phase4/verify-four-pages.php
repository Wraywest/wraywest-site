<?php
/**
 * Tools, Resources, About, Contact against captured public HTML.
 * Captured-HTML fixture. Not a WordPress boot.
 */
require __DIR__ . '/../mu-plugins/ww-single-template.php';
require __DIR__ . '/../mu-plugins/ww-phase4-home-archive.php';

$fail = 0;
function check( $name, $ok, $detail = '' ) {
	global $fail;
	if ( $ok ) {
		echo "PASS  $name\n";
		return;
	}
	$fail++;
	echo "FAIL  $name" . ( '' !== $detail ? "  $detail" : '' ) . "\n";
}
function ld_blocks( $html ) {
	preg_match_all( '#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m );
	return $m[1];
}
function form_bytes( $html ) {
	preg_match_all( '#<form\b[\s\S]*?</form>#', $html, $m );
	return $m[0];
}

$cap = '/tmp/ww-captures';
foreach ( array( 'tools.html', 'resources.html', 'about.html', 'contact.html' ) as $need ) {
	if ( ! is_file( $cap . '/' . $need ) ) {
		fwrite( STDERR, "Missing $need\n" );
		exit( 2 );
	}
}

$tools = file_get_contents( $cap . '/tools.html' );
$tout  = ww_p4_transform_tools_html( $tools );
check( 'tools idempotent', ww_p4_transform_tools_html( $tout ) === $tout );
check( 'kit label researched', 1 === substr_count( $tout, '>ConvertKit</h4><p class="ti-meta">Email Marketing <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Researched</span>' ) );
check( 'notion label researched', false !== strpos( $tout, '>Notion</h4><p class="ti-meta">Productivity <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Researched</span>' ) );
check( 'repurpose label researched', false !== strpos( $tout, '>Repurpose.io</h4><p class="ti-meta">Content Automation <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Researched</span>' ) );
check( 'systeme stays used', false !== strpos( $tout, '>Systeme.io</h4>' ) && false !== strpos( $tout, 'All-in-One' ) && 7 === substr_count( $tout, '>Used hands-on<' ) );
check( 'olsp is affiliate receipts', false !== strpos( $tout, '>OLSP System</h4><p class="ti-meta">Affiliate Training <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Affiliate receipts</span>' ) );
check( 'wise and klarna stay used hands-on', false !== strpos( $tout, '>Wise</h4>' ) && 1 === substr_count( $tout, '>Wise</h4><p class="ti-meta">Money Transfer <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span>' ) && false !== strpos( $tout, '>Klarna</h4>' ) );
check( 'make stays researched', false !== strpos( $tout, '>Make.com</h4>' ) && false !== strpos( $tout, '<span class="ti-researched">Researched</span>' ) );
check( 'used count dropped by four', 11 === substr_count( $tools, '>Used hands-on<' ) && 7 === substr_count( $tout, '>Used hands-on<' ) );
check( 'tools 17.77 unchanged', substr_count( $tools, '$17.77' ) === substr_count( $tout, '$17.77' ) );
check( 'tools forms unchanged', form_bytes( $tools ) === form_bytes( $tout ) );
check( 'tools jsonld unchanged', ld_blocks( $tools ) === ld_blocks( $tout ) );
check( 'tools descriptions unchanged', substr_count( $tools, 'class="ti-desc"' ) === substr_count( $tout, 'class="ti-desc"' ) && substr_count( $tout, 'The backbone of my daily workflow' ) === substr_count( $tools, 'The backbone of my daily workflow' ) );
check( 'no sponsored rel added or removed on tools', substr_count( $tools, 'sponsored' ) === substr_count( $tout, 'sponsored' ) );

foreach ( array( 'resources.html', 'about.html', 'contact.html', 'HOME.html', 'start-here.html', 'engine.html', 'blog.html' ) as $name ) {
	$html = file_get_contents( $cap . '/' . $name );
	check( "tools transform leaves $name", ww_p4_transform_tools_html( $html ) === $html );
}

$about = file_get_contents( $cap . '/about.html' );
$contact = file_get_contents( $cap . '/contact.html' );
$resources = file_get_contents( $cap . '/resources.html' );
check( 'about portrait src kept', false !== strpos( $about, 'dwayne-kitchen-line.webp' ) );
check( 'contact portrait src kept', false !== strpos( $contact, 'dwayne-kitchen-line.webp' ) );
check( 'about and contact html not rewritten', ww_p4_transform_post_html( $about ) === $about && ww_p4_transform_post_html( $contact ) === $contact );
check( 'resources forms and sponsored tags unchanged by post transform', form_bytes( $resources ) === form_bytes( ww_p4_transform_post_html( $resources ) ) && substr_count( $resources, 'sponsored' ) === substr_count( ww_p4_transform_post_html( $resources ), 'sponsored' ) );
check( 'contact form bytes survive tools transform', form_bytes( $contact ) === form_bytes( $tout ) || form_bytes( $contact ) === form_bytes( ww_p4_transform_tools_html( $contact ) ) );
check( 'kit resource link stays unsponsored', false !== strpos( $resources, 'href="https://kit.com/" rel="noopener"' ) );
check( 'resources make hands-on denial stays', false !== strpos( $resources, 'I have not used Make hands-on' ) );

file_put_contents( '/tmp/ww-render-tools.html', $tout );

$posts = 0;
$bad = 0;
foreach ( glob( $cap . '/blog_*.html' ) as $file ) {
	if ( false !== strpos( $file, 'category' ) ) {
		continue;
	}
	$html = file_get_contents( $file );
	if ( ! preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
		continue;
	}
	$posts++;
	$out = ww_p4_transform_post_html( $html );
	if ( substr_count( $html, '$810.95' ) !== substr_count( $out, '$810.95' ) || substr_count( $html, '$17.77' ) !== substr_count( $out, '$17.77' ) || ld_blocks( $html ) !== ld_blocks( $out ) ) {
		$bad++;
	}
	if ( ww_p4_transform_tools_html( $html ) !== $html ) {
		$bad++;
		echo 'FAIL tools leak ' . basename( $file ) . "\n";
	}
}
check( '42 posts present', 42 === $posts );
check( '42 post regression plus tools transform does not touch them', 0 === $bad );

$home = file_get_contents( $cap . '/HOME.html' );
$ids = array( 'ww-design-tokens', 'wraywest-layout-fix', 'wraywest-custom-css', 'wraywest-adapt-polish', 'ww-header-tweaks', 'ww-typography-fix', 'wp-custom-css', 'ww-layout-fixes', 'ww-mobile-actions-css', 'ww-redesign-pass', 'ww-typography', 'ww-redesign-fixes-r2', 'ww-archive-hero-height', 'ww-craft-fixes', 'ww-hero-masthead' );
$missing = 0;
foreach ( $ids as $id ) {
	if ( 1 !== substr_count( $home, 'id="' . $id . '"' ) ) {
		$missing++;
	}
}
check( '15 style ids still missing', 15 === $missing );

echo $fail === 0 ? "ALL PASS\n" : "FAILURES $fail\n";
exit( $fail === 0 ? 0 : 1 );
