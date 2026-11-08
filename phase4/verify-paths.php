<?php
/**
 * Start Here and Second Income Engine checks against captured public HTML.
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

function slice_class( $html, $class ) {
	$span = ww_p4_find_class( $html, $class );
	if ( ! $span ) {
		return '';
	}
	return substr( $html, $span[0], $span[1] - $span[0] );
}

function ld_blocks( $html ) {
	preg_match_all( '#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $m );
	return $m[1];
}

$cap = '/tmp/ww-captures';
foreach ( array( 'start-here.html', 'engine.html' ) as $need ) {
	if ( ! is_file( $cap . '/' . $need ) ) {
		fwrite( STDERR, "Missing $need. Refusing to claim this check ran.\n" );
		exit( 2 );
	}
}

$start = file_get_contents( $cap . '/start-here.html' );
$sout  = ww_p4_transform_start_here_html( $start );
check( 'start idempotent', ww_p4_transform_start_here_html( $sout ) === $sout );
check( 'start chef sentence remains in the bio', substr_count( $sout, 'I&#8217;m a full-time chef on the 6:30 AM to 3:00 PM shift' ) === 1 );
check( 'start duplicate shift sentence removed', substr_count( $sout, 'I build WrayWest around a 6:30 AM to 3:00 PM kitchen shift' ) === 0 );
check( 'start next action uses the existing heading', false !== strpos( $sout, 'href="#sh-quiz"' ) && false !== strpos( $sout, 'Which path fits you right now?' ) );
check( 'start six-post links remain', substr_count( $sout, '/blog/why-side-hustles-fail/' ) >= 1 && substr_count( $sout, '/blog/how-to-start-creator-business-no-audience/' ) >= 1 );
check( 'start featured block folded', false !== strpos( $sout, 'ww-p4-folded' ) );
check( 'start 17.77 unchanged', substr_count( $start, '$17.77' ) === substr_count( $sout, '$17.77' ) );
check( 'start deck bytes unchanged', slice_class( $start, 'ww-deck-promo' ) !== '' && slice_class( $start, 'ww-deck-promo' ) === slice_class( $sout, 'ww-deck-promo' ) );
check( 'start jsonld unchanged', ld_blocks( $start ) === ld_blocks( $sout ) );
check( 'start logo image kept', false !== strpos( $sout, 'ww-logo-gold-circ.png' ) );
check( 'start no new offer url', substr_count( $start, 'wpforms' ) === substr_count( $sout, 'wpforms' ) );
check( 'home transform leaves start here', ww_p4_transform_home_html( $start ) === $start );
check( 'post transform leaves start here', ww_p4_transform_post_html( $start ) === $start );

$engine = file_get_contents( $cap . '/engine.html' );
$eout   = ww_p4_transform_engine_html( $engine );
check( 'engine idempotent', ww_p4_transform_engine_html( $eout ) === $eout );
check( 'engine six cases folded', substr_count( $eout, 'ww-p4-case' ) === 6 );
check( 'engine picture copy remains', substr_count( $eout, 'Sarah, a full-time ICU nurse' ) === 1 );
check( 'engine pillar anchors remain', false !== strpos( $eout, 'href="#p1"' ) && false !== strpos( $eout, 'id="p1"' ) && false !== strpos( $eout, 'id="p6"' ) );
check( 'engine next actions remain', false !== strpos( $eout, 'href="/start-here/"' ) && false !== strpos( $eout, 'href="/blog/"' ) );
check( 'engine make line not expanded', substr_count( $engine, 'Use Make.com or similar' ) === substr_count( $eout, 'Use Make.com or similar' ) && substr_count( $eout, 'Use Make.com or similar' ) === 1 );
check( 'engine no n8n or zapier added', substr_count( $engine, 'n8n' ) === substr_count( $eout, 'n8n' ) && substr_count( $engine, 'Zapier' ) === substr_count( $eout, 'Zapier' ) );
check( 'engine chef line remains once', substr_count( $eout, 'real chef schedule' ) === 1 );
$engine_offer = '';
$engine_offer_after = '';
if ( preg_match( '/<section\b[^>]*class=(["\'])[^"\']*\bfw-worksheet\b[^"\']*\1[^>]*>/', $engine, $om, PREG_OFFSET_CAPTURE ) ) {
	$span = ww_p4_element_span( $engine, $om[0][1] );
	$engine_offer = $span ? substr( $engine, $span[0], $span[1] - $span[0] ) : '';
}
if ( preg_match( '/<section\b[^>]*class=(["\'])[^"\']*\bfw-worksheet\b[^"\']*\1[^>]*>/', $eout, $om, PREG_OFFSET_CAPTURE ) ) {
	$span = ww_p4_element_span( $eout, $om[0][1] );
	$engine_offer_after = $span ? substr( $eout, $span[0], $span[1] - $span[0] ) : '';
}
check( 'engine offer form bytes unchanged', '' !== $engine_offer && $engine_offer === $engine_offer_after, 'len ' . strlen( $engine_offer ) );
check( 'engine jsonld unchanged', ld_blocks( $engine ) === ld_blocks( $eout ) );
check( 'engine faqpage still present', false !== strpos( $eout, '"@type":"FAQPage"' ) || false !== strpos( $eout, '"@type": "FAQPage"' ) );
check( 'start transform leaves engine', ww_p4_transform_start_here_html( $engine ) === $engine );

file_put_contents( '/tmp/ww-render-start.html', $sout );
file_put_contents( '/tmp/ww-render-engine.html', $eout );

$posts = 0;
$bad   = 0;
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
	if ( substr_count( $html, '$810.95' ) !== substr_count( $out, '$810.95' ) || substr_count( $html, '$17.77' ) !== substr_count( $out, '$17.77' ) ) {
		$bad++;
		echo 'FAIL receipt ' . basename( $file ) . "\n";
	}
	if ( ld_blocks( $html ) !== ld_blocks( $out ) ) {
		$bad++;
		echo 'FAIL jsonld ' . basename( $file ) . "\n";
	}
	if ( ww_p4_transform_start_here_html( $html ) !== $html || ww_p4_transform_engine_html( $html ) !== $html ) {
		$bad++;
		echo 'FAIL path transform leaked onto ' . basename( $file ) . "\n";
	}
}
check( '42 post captures still present', 42 === $posts, "found $posts" );
check( '42 post regression', 0 === $bad, "bad $bad" );

$home = file_get_contents( $cap . '/HOME.html' );
$ids  = array( 'ww-design-tokens', 'wraywest-layout-fix', 'wraywest-custom-css', 'wraywest-adapt-polish', 'ww-header-tweaks', 'ww-typography-fix', 'wp-custom-css', 'ww-layout-fixes', 'ww-mobile-actions-css', 'ww-redesign-pass', 'ww-typography', 'ww-redesign-fixes-r2', 'ww-archive-hero-height', 'ww-craft-fixes', 'ww-hero-masthead' );
$missing = 0;
foreach ( $ids as $id ) {
	if ( 1 !== substr_count( $home, 'id="' . $id . '"' ) ) {
		$missing++;
	}
}
check( '15 style ids still absent so the bundle fail-closes', count( $ids ) === $missing, "missing $missing" );

echo $fail === 0 ? "ALL PASS\n" : "FAILURES $fail\n";
exit( $fail === 0 ? 0 : 1 );
