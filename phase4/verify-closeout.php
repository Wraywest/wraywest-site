<?php
/**
 * Phase 4 closeout. Labels on every captured post, leak checks, invariants.
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
	preg_match_all( '#<script type="application/ld\+json"[^>]*>.*?</script>#s', $html, $m );
	return $m[0];
}
function form_bytes( $html ) {
	preg_match_all( '#<form\b[\s\S]*?</form>#', $html, $m );
	return $m[0];
}
function slug_of( $html ) {
	if ( preg_match( '#<link rel="canonical" href="https://wraywestblog.com/blog/([^"/]+)/?"#', $html, $m ) ) {
		return $m[1];
	}
	return '';
}

$expected = array(
	'leadsleap-review-honest-2026'                 => array( 'used', 'Used' ),
	'systeme-io-review-all-in-one-platform'        => array( 'used', 'Used' ),
	'hostinger-horizons-review-ai-website-builder' => array( 'used', 'Used' ),
	'klarna-review'                                => array( 'used', 'Used' ),
	'wise-international-money-transfers'           => array( 'used', 'Used' ),
	'robinhood-review'                             => array( 'used', 'Used' ),
	'dataforseo-review'                            => array( 'used', 'Used' ),
	'convertkit-review-email-marketing-creators'   => array( 'researched', 'Researched' ),
	'convertkit-alternatives'                      => array( 'researched', 'Researched' ),
	'notion-review-creator-workspace-2026'         => array( 'researched', 'Researched' ),
	'repurpose-io-content-automation'              => array( 'researched', 'Researched' ),
	'olsp-system-review-2026'                      => array( 'receipts', 'Affiliate receipts' ),
	'systeme-vs-convertkit'                        => array( 'mixed', 'Systeme used, Kit researched' ),
	'make-vs-n8n-side-hustlers'                    => array( 'narrow', 'Some posts made with n8n; Make researched' ),
	'n8n-side-hustle-automation'                   => array( 'narrow', 'Some posts made with n8n' ),
);

$cap   = '/tmp/ww-captures';
$posts = 0;
$bad   = 0;
$seen  = array();
foreach ( glob( $cap . '/blog_*.html' ) as $file ) {
	if ( false !== strpos( $file, 'category' ) ) {
		continue;
	}
	$html = file_get_contents( $file );
	if ( ! preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
		continue;
	}
	$posts++;
	$slug = slug_of( $html );
	$seen[ $slug ] = true;
	$out  = ww_p4_transform_post_html( $html );
	$want = isset( $expected[ $slug ] ) ? $expected[ $slug ] : null;
	if ( null === $want ) {
		if ( false !== strpos( $out, 'ww-use-label' ) && false === strpos( $html, 'ww-use-label' ) ) {
			$bad++;
			echo "FAIL label invented on $slug\n";
		}
	} else {
		$needle = 'data-ww-use="' . $want[0] . '">' . $want[1] . '<';
		if ( false === strpos( $out, $needle ) ) {
			$bad++;
			echo "FAIL label $slug wanted $needle\n";
		}
	}
	if ( substr_count( $html, '$810.95' ) !== substr_count( $out, '$810.95' ) || substr_count( $html, '$17.77' ) !== substr_count( $out, '$17.77' ) ) {
		$bad++;
		echo "FAIL receipt $slug\n";
	}
	if ( ld_blocks( $html ) !== ld_blocks( $out ) || form_bytes( $html ) !== form_bytes( $out ) ) {
		$bad++;
		echo "FAIL jsonld or form $slug\n";
	}
	if ( ww_p4_transform_calculator_html( $html ) !== $html || ww_p4_transform_tools_html( $html ) !== $html || ww_p4_transform_home_html( $html ) !== $html ) {
		$bad++;
		echo "FAIL leak $slug\n";
	}
}
check( '42 captured posts', 42 === $posts );
check( '42 label receipt jsonld form invariants', 0 === $bad );
foreach ( $expected as $slug => $pair ) {
	check( "mapped $slug", isset( $seen[ $slug ] ) );
}

$tools = file_get_contents( $cap . '/tools.html' );
$tout  = ww_p4_transform_tools_html( $tools );
check( 'tools robinhood still used hands-on', false !== strpos( $tout, '>Robinhood</h4>' ) && false !== strpos( $tout, 'Robinhood</h4><p class="ti-meta">' ) && 1 === substr_count( $tout, '>Robinhood</h4>' ) );
check( 'tools dataforseo still used hands-on', false !== strpos( $tout, '>DataForSEO</h4><p class="ti-meta">Keyword Research <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span>' ) );
check( 'tools robinhood label text', false !== strpos( $tout, '>Robinhood</h4><p class="ti-meta">Stock Broker <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span>' ) );
check( 'tools olsp still affiliate receipts', false !== strpos( $tout, '>Affiliate receipts<' ) );
check( 'tools wise still used', false !== strpos( $tout, '>Wise</h4><p class="ti-meta">Money Transfer <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span>' ) );

$home = file_get_contents( $cap . '/HOME.html' );
$hout = ww_p4_transform_home_html( $home );
check( 'homepage headline unchanged', false !== strpos( $hout, 'A second income, built' ) && substr_count( $home, '$810.95' ) === substr_count( $hout, '$810.95' ) && substr_count( $home, '$17.77' ) === substr_count( $hout, '$17.77' ) );
check( 'post transform leaves homepage', ww_p4_transform_post_html( $home ) === $home );

$ids = array( 'ww-design-tokens', 'wraywest-layout-fix', 'wraywest-custom-css', 'wraywest-adapt-polish', 'ww-header-tweaks', 'ww-typography-fix', 'wp-custom-css', 'ww-layout-fixes', 'ww-mobile-actions-css', 'ww-redesign-pass', 'ww-typography', 'ww-redesign-fixes-r2', 'ww-archive-hero-height', 'ww-craft-fixes', 'ww-hero-masthead' );
$missing = 0;
foreach ( $ids as $id ) {
	if ( 1 !== substr_count( $home, 'id="' . $id . '"' ) ) {
		$missing++;
	}
}
check( '15 style ids still absent', 15 === $missing );
$manifest = json_decode( file_get_contents( __DIR__ . '/../uploads/ww-css/manifest.json' ), true );
check( 'october hash untouched', '369d75f97f7e56a084a24ebff85770b1' === $manifest['md5']['ww-design-tokens'] );
check( 'css has no transition all declaration', false === strpos( file_get_contents( __DIR__ . '/../uploads/ww-css/wraywest.css' ), 'transition:all' ) );

$quit = file_get_contents( $cap . '/quit-9-to-5-calculator.html' );
$qout = ww_p4_transform_calculator_html( $quit );
check( 'quit scripts still identical', preg_match_all( '#<script\b[^>]*>.*?</script>#s', $quit, $a ) === preg_match_all( '#<script\b[^>]*>.*?</script>#s', $qout, $b ) && $a[0] === $b[0] );

$robin = '';
foreach ( glob( $cap . '/blog_robinhood-review.html' ) as $file ) {
	$robin = ww_p4_transform_post_html( file_get_contents( $file ) );
}
if ( '' !== $robin ) {
	file_put_contents( '/tmp/ww-render-robinhood.html', $robin );
}

echo $fail === 0 ? "ALL PASS\n" : "FAILURES $fail\n";
exit( $fail === 0 ? 0 : 1 );
