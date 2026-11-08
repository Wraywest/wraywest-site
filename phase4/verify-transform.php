<?php
/**
 * Local checks for the single-post chrome transform.
 * Fixtures are labelled in their titles. This is not a WordPress boot.
 */
require __DIR__ . '/../mu-plugins/ww-single-template.php';

$fail = 0;
function check( $name, $ok, $detail = '' ) {
	global $fail;
	if ( $ok ) {
		echo "PASS  $name\n";
		return;
	}
	$fail++;
	echo "FAIL  $name" . ( $detail !== '' ? "  $detail" : '' ) . "\n";
}

$welcome = file_get_contents( __DIR__ . '/fixtures/before-welcome.html' );
$once    = ww_p4_transform_post_html( $welcome );
$twice   = ww_p4_transform_post_html( $once );

check( 'welcome idempotent', $once === $twice );
check( 'welcome dek kept', substr_count( $once, 'A 5-email, 7-day welcome sequence template with subject lines, timing, and full copy.' ) === 1 );
check( 'welcome body sentence kept', substr_count( $once, 'The gap between someone joining your list and receiving your first email is the most expensive silence in your business.' ) === 1 );
check( 'welcome open rate kept', substr_count( $once, '83.63%' ) === 1 );
check( 'welcome disclosure removed', substr_count( $once, 'ww-disc' ) === 0 );
check( 'welcome toc collapsed', preg_match( '/<details class="ww-toc"[^>]*\sopen\b/', $once ) !== 1 );
check( 'welcome no use label', strpos( $once, 'ww-use-label' ) === false );
check( 'welcome topic email', strpos( $once, 'data-ww-topic="email"' ) !== false );
check( 'welcome byline order', (bool) preg_match( '/By <a class="ww-byline-author"[^>]*>Dwayne Lindsay<\/a><\/span><span class="ww-site-hero-meta-sep"[^>]*><\/span><span>Updated November 3, 2026<\/span>.*23 min read/s', $once ) );
check( 'welcome one end module', substr_count( $once, 'data-ww-p4="end"' ) === 1 && substr_count( $once, 'class="ww-end"' ) === 1 );
check( 'welcome pillar link', strpos( $once, 'https://wraywestblog.com/blog/email-marketing-playbook-solo-creators/' ) !== false );
check( 'welcome tool link', strpos( $once, 'https://wraywestblog.com/tools/lead-magnet-builder/' ) !== false );
check( 'welcome related kept', substr_count( $once, 'ww-related-card' ) === 3 );
check( 'welcome extra cta removed', strpos( $once, 'ww-related-cta' ) === false );
check( 'welcome short author', substr_count( $once, 'I work the 6:30 AM to 3:00 PM shift and write about what actually works around it.' ) === 1 );
check( 'welcome long credentials removed', strpos( $once, 'Credentials:' ) === false );
check( 'welcome about link', strpos( $once, 'https://wraywestblog.com/about/' ) !== false );
check( 'welcome kit link remains', strpos( $once, 'https://kit.com/' ) !== false );
check( 'welcome no json-ld added', substr_count( $once, 'application/ld+json' ) === substr_count( $welcome, 'application/ld+json' ) );

$review = file_get_contents( __DIR__ . '/fixtures/before-review.html' );
$rout   = ww_p4_transform_post_html( $review );
$r2     = ww_p4_transform_post_html( $rout );
check( 'review idempotent', $rout === $r2 );
check( 'review receipt exact', substr_count( $rout, 'The published figure stays exact: $810.95. LeadsLeap stays $17.77 when a post already prints it.' ) === 1 );
check( 'review olsp label is affiliate receipts', strpos( $rout, 'data-ww-use="receipts"' ) !== false && strpos( $rout, '>Affiliate receipts<' ) !== false && strpos( $rout, 'data-ww-use="used"' ) === false );
check( 'review topic tools', strpos( $rout, 'data-ww-topic="tools"' ) !== false );
check( 'review one disclosure', substr_count( $rout, 'class="ww-disc"' ) === 1 );
check( 'review disclosure kept', false !== strpos( $rout, 'Some links are affiliate links.' ) );
check( 'review disclosure after toc', false !== strpos( $rout, '</details><p class="ww-disc">Some links are affiliate links.</p>' ) );
check( 'review no invented disclosure', false === strpos( $rout, 'I may earn a commission' ) );
check( 'review kit rel stripped', preg_match( '#<a href="https://kit.com/pricing"[^>]*sponsored#', $rout ) !== 1 && strpos( $rout, 'https://kit.com/pricing' ) !== false );
check( 'review make rel kept', (bool) preg_match( '#<a href="https://www.make.com/en/pricing" rel="sponsored nofollow">#', $rout ) );
check( 'review one end', substr_count( $rout, 'data-ww-p4="end"' ) === 1 );
check( 'review tools hub', strpos( $rout, 'https://wraywestblog.com/tools/' ) !== false );
check( 'review scoring link', strpos( $rout, 'https://wraywestblog.com/scoring-methodology/' ) !== false );
check( 'review cta removed', strpos( $rout, 'ww-related-cta' ) === false );

$home = '<html><head></head><body class="home"><h1 id="wwl-h">A second income, built <em>after the shift.</em></h1><p>$810.95</p></body></html>';
check( 'home untouched', ww_p4_transform_post_html( $home ) === $home );

$pillar = str_replace( 'welcome-email-sequence-template', 'email-marketing-playbook-solo-creators', $welcome );
$pout   = ww_p4_transform_post_html( $pillar );
check( 'pillar does not link to itself as the next step', substr_count( $pout, 'https://wraywestblog.com/blog/email-marketing-playbook-solo-creators/' ) === 1 );
check( 'pillar points at start here', strpos( $pout, 'https://wraywestblog.com/start-here/' ) !== false );

check( 'make vs n8n label', ww_p4_use_label( 'make-vs-n8n-side-hustlers' )['text'] === 'Some posts made with n8n; Make researched' );
check( 'n8n post label is narrow', ww_p4_use_label( 'n8n-side-hustle-automation' )['text'] === 'Some posts made with n8n' );
check( 'kit comparison label', ww_p4_use_label( 'systeme-vs-convertkit' )['text'] === 'Systeme used, Kit researched' );
check( 'guide has no label', ww_p4_use_label( 'welcome-email-sequence-template' ) === null );
$unclosed = '<html><head><link rel="canonical" href="https://wraywestblog.com/blog/olsp-system-review-2026/"></head><body class="single-post"><div class="entry-content"><p>The published figure stays exact: $810.95.</p><p class="ww-disc">Some links are affiliate links. Each review says whether I used the tool myself or researched it. <a href="https://wraywestblog.com/legal/">Full disclosure</a></p><a href="https://olspfunnels.com/megalink-2-front-end?olsp=965107" rel="sponsored noopener nofollow">OLSP</a></div><section class="ww-end"><p>Old pitch</p><section class="ww-related"><h2>Keep reading</h2><span class="ww-related-title">LeadsLeap Review (2026): I Made $17.77.</span></section></section><footer><a href="https://wraywestblog.com/legal/">Disclaimer</a></footer></body></html>';
$uout = ww_p4_transform_post_html( $unclosed );
check( 'unclosed end keeps related', false !== strpos( $uout, 'ww-related-title' ) && false !== strpos( $uout, '$17.77' ) );
check( 'unclosed end keeps receipt', false !== strpos( $uout, '$810.95' ) );
check( 'sponsored boilerplate kept', false !== strpos( $uout, 'Each review says whether I used the tool myself or researched it.' ) );
$home_cap = '<html><body class="home"><h1 id="wwl-h">A second income, built after the shift.</h1></body></html>';
$cat_cap  = '<html><body class="archive category"><nav class="ww-cat-chips"><a class="ww-chip" href="https://wraywestblog.com/blog/category/money-finance/">Money &amp; Finance</a></nav></body></html>';
check( 'home not rewritten by post transform', ww_p4_transform_post_html( $home_cap ) === $home_cap );
check( 'category not rewritten by post transform', ww_p4_transform_post_html( $cat_cap ) === $cat_cap );
check( 'notion researched', ww_p4_use_label( 'notion-review-creator-workspace-2026' )['text'] === 'Researched' );
check( 'wise stays used', ww_p4_use_label( 'wise-international-money-transfers' )['text'] === 'Used' );
check( 'klarna stays used', ww_p4_use_label( 'klarna-review' )['text'] === 'Used' );
check( 'olsp label is affiliate receipts', ww_p4_use_label( 'olsp-system-review-2026' )['text'] === 'Affiliate receipts' );
check( 'kit review stays researched', ww_p4_use_label( 'convertkit-review-email-marketing-creators' )['text'] === 'Researched' );

file_put_contents( __DIR__ . '/fixtures/after-welcome.html', str_replace( 'css/before.css', 'css/after.css', $once ) );
file_put_contents( __DIR__ . '/fixtures/after-review.html', $rout );

echo $fail === 0 ? "ALL PASS\n" : "FAILURES $fail\n";
exit( $fail === 0 ? 0 : 1 );
