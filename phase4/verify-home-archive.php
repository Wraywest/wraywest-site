<?php
/**
 * Checks the homepage and blog-index transforms against the captured public HTML.
 * LOCAL FIXTURE in the sense that PHP runs here, not inside WordPress.
 * The HTML files are the public pages captured read-only on 2026-11-08.
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

$cap = '/tmp/ww-captures';
if ( ! is_file( $cap . '/HOME.html' ) ) {
	fwrite( STDERR, "Missing captured HTML in /tmp/ww-captures. Refusing to claim the homepage check ran.\n" );
	exit( 2 );
}

$home = file_get_contents( $cap . '/HOME.html' );
$out  = ww_p4_transform_home_html( $home );
$out2 = ww_p4_transform_home_html( $out );

function ww_slice_class( $html, $class ) {
	$span = ww_p4_find_class( $html, $class );
	if ( ! $span ) {
		return '';
	}
	return substr( $html, $span[0], $span[1] - $span[0] );
}

check( 'home idempotent', $out === $out2 );
check( 'headline bytes unchanged', substr_count( $home, '<h1 id="wwl-h">A second income, built <em>after the shift.</em></h1>' ) === 1 && substr_count( $out, '<h1 id="wwl-h">A second income, built <em>after the shift.</em></h1>' ) === 1 );
check( 'chef sentence still once', substr_count( $out, 'full-time chef' ) === substr_count( $home, 'full-time chef' ) );
check( 'receipt 810.95 unchanged', substr_count( $home, '$810.95' ) === substr_count( $out, '$810.95' ) && substr_count( $out, '$810.95' ) === 1 );
check( 'receipt 17.77 unchanged', substr_count( $home, '$17.77' ) === substr_count( $out, '$17.77' ) );
check( 'OLSP source line kept', substr_count( $out, 'Source: my OLSP dashboard' ) === 1 );
check( 'LeadsLeap source line kept', substr_count( $out, 'Source: my LeadsLeap account' ) === 1 );
$deck_before = ww_slice_class( $home, 'ww-deck-promo' );
$deck_after  = ww_slice_class( $out, 'ww-deck-promo' );
check( 'deck form bytes unchanged', $deck_before !== '' && $deck_before === $deck_after, 'len ' . strlen( $deck_before ) . ' vs ' . strlen( $deck_after ) );
check( 'n8n cost teaser removed', false === strpos( $out, 'What it cost, what broke' ) );
check( 'make self-host teaser removed', false === strpos( $out, 'n8n if you can self-host' ) );
check( 'n8n teaser is the confirmed line', false !== strpos( $out, '<p>Some posts on this site were made with n8n.</p>' ) );
check( 'make teaser is the narrow label', false !== strpos( $out, '<p>Some posts made with n8n; Make researched.</p>' ) );
check( 'money category url added', false !== strpos( $out, 'https://wraywestblog.com/blog/category/money-finance/' ) );
check( 'content category url added', false !== strpos( $out, 'https://wraywestblog.com/blog/category/content-strategy/' ) );
check( 'no new tax threshold', substr_count( $home, '20,000' ) === substr_count( $out, '20,000' ) );
check( 'jsonld unchanged', preg_match_all( '#<script type="application/ld\+json"#', $home ) === preg_match_all( '#<script type="application/ld\+json"#', $out ) );
preg_match_all( '#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $home, $ld1 );
preg_match_all( '#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $out, $ld2 );
check( 'jsonld bytes unchanged', $ld1[1] === $ld2[1] );
check( 'consent markup unchanged', substr_count( $home, 'uniconsent' ) === substr_count( $out, 'uniconsent' ) && substr_count( $home, 'cookie' ) === substr_count( $out, 'cookie' ) );
check( 'no 301 inserted', false === strpos( $out, '301' ) || substr_count( $home, '301' ) === substr_count( $out, '301' ) );
check( 'post transform leaves homepage', ww_p4_transform_post_html( $home ) === $home );

$blog = file_get_contents( $cap . '/blog.html' );
$blog_out = ww_p4_transform_blog_index_html( $blog );
check( 'blog idempotent', ww_p4_transform_blog_index_html( $blog_out ) === $blog_out );
check( 'blog dates labeled published', substr_count( $blog_out, '>Published ' ) >= 10 );
check( 'blog datetimes unchanged', preg_match_all( '#datetime="([^"]+)"#', $blog, $d1 ) && $d1[1] === ( preg_match_all( '#datetime="([^"]+)"#', $blog_out, $d2 ) ? $d2[1] : array() ) );
check( 'blog does not gain a new calendar', substr_count( $blog, '<time ' ) === substr_count( $blog_out, '<time ' ) );
check( 'blog n8n cost teaser removed', false === strpos( $blog_out, 'What it cost, what broke' ) );
check( 'blog make self-host teaser removed', false === strpos( $blog_out, 'n8n if you can self-host' ) );
check( 'home transform leaves blog index', ww_p4_transform_home_html( $blog ) === $blog );
check( 'post transform leaves blog index', ww_p4_transform_post_html( $blog ) === $blog );

$money = file_get_contents( $cap . '/blog_category_money-finance.html' );
check( 'home transform leaves category', ww_p4_transform_home_html( $money ) === $money );
check( 'blog transform leaves category', ww_p4_transform_blog_index_html( $money ) === $money );
check( 'post transform leaves category', ww_p4_transform_post_html( $money ) === $money );
check( 'category still has both dates', false !== strpos( $money, 'class="updated"' ) && false !== strpos( $money, 'class="entry-date published"' ) );

$manifest = json_decode( file_get_contents( __DIR__ . '/../uploads/ww-css/manifest.json' ), true );
$ids = array( 'ww-design-tokens', 'wraywest-layout-fix', 'wraywest-custom-css', 'wraywest-adapt-polish', 'ww-header-tweaks', 'ww-typography-fix', 'wp-custom-css', 'ww-layout-fixes', 'ww-mobile-actions-css', 'ww-redesign-pass', 'ww-typography', 'ww-redesign-fixes-r2', 'ww-archive-hero-height', 'ww-craft-fixes', 'ww-hero-masthead' );
$missing = 0;
foreach ( $ids as $id ) {
	if ( substr_count( $home, 'id="' . $id . '"' ) !== 1 ) {
		$missing++;
	}
}
check( 'css bundle fail closed on live homepage', $missing === count( $ids ), "missing or not-exactly-once: $missing" );
check( 'manifest still old md5 capture', isset( $manifest['md5']['ww-hero-masthead'] ) && ( $manifest['ver'] ?? '' ) !== '' );
check( 'not publish-ready flag', false === strpos( strtolower( $manifest['phase4'] ?? '' ), 'publish-ready' ) && false !== strpos( strtolower( $manifest['phase4'] ?? '' ), 're-capture' ) );
$related = file_get_contents( __DIR__ . '/../mu-plugins/ww-related-posts.php' );
check( 'related posts return unless singular post', false !== strpos( $related, "if ( ! is_singular( 'post' ) ) return;" ) );

$manifest_doc = array(
	'captured' => '2026-11-08 public homepage, read-only',
	'local_wordpress_boot' => false,
	'teasers' => array(
		array(
			'url' => 'https://wraywestblog.com/blog/n8n-side-hustle-automation/',
			'before' => 'What it cost, what broke, and when automation actually helps a side hustle.',
			'after' => 'Some posts on this site were made with n8n.',
		),
		array(
			'url' => 'https://wraywestblog.com/blog/make-vs-n8n-side-hustlers/',
			'before' => 'Make if you want it to just run, n8n if you can self-host. Pricing, ease, hosting, and AI…',
			'after' => 'Some posts made with n8n; Make researched.',
		),
		array(
			'url' => 'https://wraywestblog.com/blog/',
			'before' => 'Make if you want it to just run, n8n if you can self-host. Pricing, ease, hosting, and AI compared.',
			'after' => 'Some posts made with n8n; Make researched.',
		),
	),
	'added_links' => array(
		'https://wraywestblog.com/blog/category/money-finance/',
		'https://wraywestblog.com/blog/category/content-strategy/',
		'https://wraywestblog.com/blog/1099-k-explained-creators/',
		'https://wraywestblog.com/blog/klarna-review/',
		'https://wraywestblog.com/blog/managing-creator-income-taxes-tools/',
		'https://wraywestblog.com/blog/ab-testing-one-person-businesses/',
		'https://wraywestblog.com/blog/content-calendar-publish-working-full-time/',
	),
	'unchanged' => array(
		'headline' => 'A second income, built after the shift.',
		'receipts' => array( '$810.95', '$17.77' ),
		'sources' => array( 'Source: my OLSP dashboard', 'Source: my LeadsLeap account' ),
		'deck_bytes' => strlen( $deck_before ),
	),
	'redirects_applied' => array(),
);
file_put_contents( __DIR__ . '/home-teaser-manifest.json', json_encode( $manifest_doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
file_put_contents( '/tmp/ww-render-home.html', $out );
file_put_contents( '/tmp/ww-render-blog.html', $blog_out );

echo $fail === 0 ? "ALL PASS\n" : "FAILURES $fail\n";
exit( $fail === 0 ? 0 : 1 );
