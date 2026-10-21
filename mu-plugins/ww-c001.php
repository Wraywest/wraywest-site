<?php
/*
Plugin Name: WrayWest C-001 signup fixes
Description: C-001 (approved 2026-10-21). (4) LeadsLeap copy: "$17.77 over 18 months" to "$17.77 since August 2025" at render time. (1) /deck-thanks/ shows "check your inbox to confirm" with no PDF link. (3) GA4 deck_signup event (campaign=c001, placement) after a successful WPForms 3660 submit. Delete this file to roll back all three.
Version: 1.0
*/
if ( ! defined( 'ABSPATH' ) ) exit;

/* (4) Copy fix. Durable fix is the post content itself; this filter covers it until then. */
add_filter( 'the_content', function ( $c ) {
	return str_replace( '$17.77 over 18 months', '$17.77 since August 2025', $c );
}, 20 );

/* (1) Thank-you page: confirm first, PDF only after confirmation (Reach email). */
add_filter( 'the_content', function ( $c ) {
	if ( ! is_page( 'deck-thanks' ) || ! in_the_loop() || ! is_main_query() ) return $c;
	return '<div class="ww-deck-thanks"><h2>Check your inbox to confirm</h2>'
		. '<p>I just sent you a confirmation email. Click the confirm button in it, and the Keep-or-Cancel Deck (PDF) arrives in your inbox right after.</p>'
		. '<p>Nothing there in a few minutes? Check spam or promotions, then move it to your inbox so the next one lands.</p>'
		. '<p>While you wait: the deck is the printable version of the live <a href="/tools/">tools directory</a>, which gets updated as scores change.</p></div>';
}, 20 );

/* (3) GA4 deck_signup. Placement is stored on submit, the event fires on /deck-thanks/ (reached only after a successful submit). */
add_action( 'wp_footer', function () { ?>
<script data-no-optimize="1" data-cfasync="false">
(function () {
  var K = 'ww_c001_pl';
  function placement() {
    var q = new URLSearchParams(location.search), s = (q.get('utm_source') || '').toLowerCase();
    if (/leadsleap-review/.test(location.pathname)) return 'leadsleap-review';
    if (s === 'newsletter' || s === 'reach' || s === 'email') return 'newsletter';
    if (s === 'bluesky' || /bsky/.test(document.referrer)) return 'bluesky';
    return 'other';
  }
  try {
    if (!sessionStorage.getItem('ww_c001_src')) sessionStorage.setItem('ww_c001_src', placement());
  } catch (_) {}
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || f.id !== 'wpforms-form-3660') return;
    try {
      var p = /leadsleap-review/.test(location.pathname) ? 'leadsleap-review' : (sessionStorage.getItem('ww_c001_src') || 'other');
      sessionStorage.setItem(K, p);
    } catch (_) {}
  }, true);
  if (/^\/deck-thanks\/?$/.test(location.pathname)) {
    var p = null; try { p = sessionStorage.getItem(K); sessionStorage.removeItem(K); } catch (_) {}
    if (p) {
      var n = 0, fire = function () { if (typeof window.gtag === 'function') window.gtag('event', 'deck_signup', { campaign: 'c001', placement: p }); else if (++n < 20) setTimeout(fire, 500); };
      fire();
    }
  }
})();
</script>
<?php }, 99 );
