<?php
/*
Plugin Name: WrayWest Self-Hosted Fonts
Description: Loads Instrument Serif + Plus Jakarta Sans self-hosted. The Google Fonts stylesheet was being stripped (LiteSpeed Remove Google Fonts), so brand typefaces never loaded and the site fell back to Georgia/system sans. data-no-optimize prevents LiteSpeed removing it again.
Version: 1.0
*/
if (!defined('ABSPATH')) exit;

/* ===== Font preloads (2026-07-08 perf pass) =====
   PSI measured a 0.269 CLS on /blog/ caused by late-arriving woff2 files swapping
   in (font-display:swap reflow). Preloading the three above-the-fold faces makes
   them arrive before first paint, so the swap never becomes a visible shift.
   crossorigin is REQUIRED on font preloads even same-origin, or the browser
   double-fetches. Priority 2 = before the @font-face block below. */
add_action('wp_head', function () {
    $base = 'https://wraywestblog.com/wp-content/uploads/ww-fonts/';
    foreach ( array( '9f6257e36e.woff2', 'fdb05bf0ea.woff2', '2d243d02e5.woff2' ) as $f ) {
        echo '<link rel="preload" as="font" type="font/woff2" href="' . $base . $f . '" crossorigin>' . "\n";
    }
}, 2);

/* Fonts are self-hosted: drop WordPress's dns-prefetch for fonts.googleapis.com
   (dead hint; the matching hardcoded preconnects were removed from wraywest-css.php). */
add_filter('wp_resource_hints', function ($urls, $relation) {
    if ('dns-prefetch' === $relation) {
        $urls = array_filter($urls, function ($u) {
            $href = is_array($u) ? ($u['href'] ?? '') : $u;
            return strpos($href, 'fonts.googleapis') === false && strpos($href, 'fonts.gstatic') === false;
        });
    }
    return $urls;
}, 10, 2);

add_action('wp_head', function () {
    $css = <<<'WWCSS'
/* latin-ext */
@font-face {
  font-family: 'Instrument Serif';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/28cfb3cb62.woff2) format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Instrument Serif';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/2d243d02e5.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/9f6257e36e.woff2) format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/fdb05bf0ea.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/9f6257e36e.woff2) format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/fdb05bf0ea.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/9f6257e36e.woff2) format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/fdb05bf0ea.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
/* latin-ext */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/9f6257e36e.woff2) format('woff2');
  unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
/* latin */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url(https://wraywestblog.com/wp-content/uploads/ww-fonts/fdb05bf0ea.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
WWCSS;
    echo '<style id="ww-fonts" data-no-optimize="1">' . $css . '</style>';
}, 1);
