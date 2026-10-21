<?php
/*
Plugin Name: WrayWest Affiliate Click Tracking
Description: Fires a GA4 "affiliate_click" event (gtag) when a visitor clicks any rel="sponsored" link. Closes the money-funnel measurement gap found in the 2026-07-10 GA check (proposal P-004). Capture-phase + transport:beacon so the event survives the outbound navigation. No-op if gtag is absent (e.g. an ad-blocked visitor).
Version: 1.0
*/
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_footer', function () {
	// data-no-optimize keeps LiteSpeed from combining/deferring it past the click. ?>
<script data-no-optimize="1" data-cfasync="false">
(function () {
  if (window.__wwAffTrack) return; window.__wwAffTrack = 1;
  document.addEventListener('click', function (e) {
    var t = e.target;
    var a = (t && t.closest) ? t.closest('a[rel~="sponsored"]') : null;
    if (!a) return;
    var href = a.getAttribute('href') || a.href || '';
    if (!href || href.charAt(0) === '#') return;
    var domain = '';
    try { domain = new URL(href, location.href).hostname.replace(/^www\./, ''); } catch (_) {}
    if (typeof window.gtag === 'function') {
      window.gtag('event', 'affiliate_click', {
        link_url: href,
        link_domain: domain,
        link_text: (a.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 100),
        page_path: location.pathname,
        transport_type: 'beacon'
      });
    }
  }, true); // capture phase: run before the browser leaves the page
})();
</script>
<?php }, 99 );
