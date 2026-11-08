<?php
/**
 * Calculator layout against captured public HTML.
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
function grab( $html, $pattern ) {
	preg_match_all( $pattern, $html, $m );
	return $m[0];
}

$cap = '/tmp/ww-captures';
$files = array(
	'quit' => 'quit-9-to-5-calculator.html',
	'rate' => 'freelance-rate-calculator.html',
	'lmb'  => 'lead-magnet-builder.html',
);
foreach ( $files as $file ) {
	if ( ! is_file( $cap . '/' . $file ) ) {
		fwrite( STDERR, "Missing $file\n" );
		exit( 2 );
	}
}

$quit_in  = file_get_contents( $cap . '/quit-9-to-5-calculator.html' );
$rate_in  = file_get_contents( $cap . '/freelance-rate-calculator.html' );
$lmb_in   = file_get_contents( $cap . '/lead-magnet-builder.html' );
$quit_out = ww_p4_transform_calculator_html( $quit_in );
$rate_out = ww_p4_transform_calculator_html( $rate_in );
$lmb_out  = ww_p4_transform_calculator_html( $lmb_in );

check( 'quit idempotent', ww_p4_transform_calculator_html( $quit_out ) === $quit_out );
check( 'rate idempotent', ww_p4_transform_calculator_html( $rate_out ) === $rate_out );
check( 'lmb unchanged', $lmb_out === $lmb_in );
check( 'quit hint no longer says typical', false === strpos( $quit_out, 'typical for $75k' ) && false !== strpos( $quit_out, 'Default 28%. Not a tax figure for your return.' ) );
check( 'rate hint no longer says common', false === strpos( $rate_out, 'common starting point' ) && false !== strpos( $rate_out, 'Default is 30 percent. Not a sourced tax rate.' ) );
check( 'quit default values stay', false !== strpos( $quit_out, 'id="qc-w2"' ) && false !== strpos( $quit_out, 'value="75000"' ) && false !== strpos( $quit_out, 'value="28"' ) && false !== strpos( $quit_out, 'value="5"' ) && false !== strpos( $quit_out, 'value="30"' ) && false !== strpos( $quit_out, 'value="6"' ) );
check( 'rate default values stay', false !== strpos( $rate_out, 'value="60000"' ) && false !== strpos( $rate_out, 'value="25"' ) && false !== strpos( $rate_out, 'value="6"' ) && false !== strpos( $rate_out, 'value="6000"' ) && false !== strpos( $rate_out, 'value="30"' ) );
check( 'quit inputs identical', grab( $quit_in, '#<input\b[^>]*>#' ) === grab( $quit_out, '#<input\b[^>]*>#' ) );
check( 'rate inputs identical', grab( $rate_in, '#<input\b[^>]*>#' ) === grab( $rate_out, '#<input\b[^>]*>#' ) );
check( 'lmb inputs identical', grab( $lmb_in, '#<input\b[^>]*>#' ) === grab( $lmb_out, '#<input\b[^>]*>#' ) );
check( 'quit scripts identical', grab( $quit_in, '#<script\b[^>]*>.*?</script>#s' ) === grab( $quit_out, '#<script\b[^>]*>.*?</script>#s' ) );
check( 'rate scripts identical', grab( $rate_in, '#<script\b[^>]*>.*?</script>#s' ) === grab( $rate_out, '#<script\b[^>]*>.*?</script>#s' ) );
check( 'lmb scripts identical', grab( $lmb_in, '#<script\b[^>]*>.*?</script>#s' ) === grab( $lmb_out, '#<script\b[^>]*>.*?</script>#s' ) );
check( 'quit jsonld identical', grab( $quit_in, '#<script type="application/ld\+json"[^>]*>.*?</script>#s' ) === grab( $quit_out, '#<script type="application/ld\+json"[^>]*>.*?</script>#s' ) );
check( 'rate jsonld identical', grab( $rate_in, '#<script type="application/ld\+json"[^>]*>.*?</script>#s' ) === grab( $rate_out, '#<script type="application/ld\+json"[^>]*>.*?</script>#s' ) );
check( 'lmb jsonld identical', grab( $lmb_in, '#<script type="application/ld\+json"[^>]*>.*?</script>#s' ) === grab( $lmb_out, '#<script type="application/ld\+json"[^>]*>.*?</script>#s' ) );
check( 'cfpb sentence kept', false !== strpos( $quit_out, 'CFPB recommends 3-6 months of expenses.' ) );
check( 'growth scale kept', false !== strpos( $quit_out, '3% = passive. 5% = active creator. 10%+ = aggressive growth.' ) );
check( 'quit urls stay', false !== strpos( $quit_out, '/tools/quit-9-to-5-calculator/' ) );
check( 'rate urls stay', false !== strpos( $rate_out, '/tools/freelance-rate-calculator/' ) || false !== strpos( $rate_out, 'id="frc-take"' ) );
check( 'lmb urls stay', false !== strpos( $lmb_out, '/tools/lead-magnet-builder/' ) );

foreach ( array( 'HOME.html', 'tools.html', 'about.html', 'contact.html', 'resources.html', 'start-here.html', 'engine.html' ) as $name ) {
	$html = file_get_contents( $cap . '/' . $name );
	check( "calculator transform leaves $name", ww_p4_transform_calculator_html( $html ) === $html );
}

$home = file_get_contents( $cap . '/HOME.html' );
check( 'homepage headline bytes stay', false !== strpos( $home, 'A second income, built' ) );
$ids = array( 'ww-design-tokens', 'wraywest-layout-fix', 'wraywest-custom-css', 'wraywest-adapt-polish', 'ww-header-tweaks', 'ww-typography-fix', 'wp-custom-css', 'ww-layout-fixes', 'ww-mobile-actions-css', 'ww-redesign-pass', 'ww-typography', 'ww-redesign-fixes-r2', 'ww-archive-hero-height', 'ww-craft-fixes', 'ww-hero-masthead' );
$missing = 0;
foreach ( $ids as $id ) {
	if ( 1 !== substr_count( $home, 'id="' . $id . '"' ) ) {
		$missing++;
	}
}
check( '15 style ids still missing', 15 === $missing );
$manifest = json_decode( file_get_contents( __DIR__ . '/../uploads/ww-css/manifest.json' ), true );
check( 'manifest md5 not refreshed', '369d75f97f7e56a084a24ebff85770b1' === $manifest['md5']['ww-design-tokens'] );

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
	if ( ww_p4_transform_calculator_html( $html ) !== $html ) {
		$bad++;
	}
	$out = ww_p4_transform_post_html( $html );
	if ( substr_count( $html, '$810.95' ) !== substr_count( $out, '$810.95' ) || substr_count( $html, '$17.77' ) !== substr_count( $out, '$17.77' ) ) {
		$bad++;
	}
}
check( '42 posts and calculator transform does not touch them', 42 === $posts && 0 === $bad );

file_put_contents( '/tmp/ww-render-quit.html', $quit_out );
file_put_contents( '/tmp/ww-render-rate.html', $rate_out );
file_put_contents( '/tmp/ww-render-lmb.html', $lmb_out );

echo $fail === 0 ? "ALL PASS\n" : "FAILURES $fail\n";
exit( $fail === 0 ? 0 : 1 );
