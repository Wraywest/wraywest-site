<?php
/* Plugin Name: WrayWest Hero Masthead
 * Description: The "Copper Masthead" post/page/archive banner redesign (2026-07-06 design panel). CSS-only, no images. Prints LAST in wp_head (priority 999) so it wins the (0,0,2,2) source-order tie against the ww-redesign-fixes-r2 / litespeed-ccss banner rules and overrides the flat ww-banner-bg band. Reversible: delete this file and the old flat band returns.
 * Version: 1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
add_action( 'wp_head', function () {
    echo "<style id=\"ww-hero-masthead\">\n" . <<<'WWCSS'
/* =========================================================================
   WRAYWEST HERO - "COPPER MASTHEAD" (final, production)  2026-07-06
   DEPLOYED as mu-plugin ww-hero-masthead.php: this CSS is printed inline in
   an <style id="ww-hero-masthead"> at wp_head priority 999 (built by
   build_muplugin.py, which strips any BOM and normalizes punctuation).
   Priority 999 makes it the LAST .ww-site-hero background block in document
   order so it wins the (0,0,2,2) source-order tie vs the ww-redesign-fixes-r2
   / litespeed-ccss dunes rules. Reversible: delete the mu-plugin.
   2026-07-06 (later) TYPOGRAPHY PASS (/typeset + /overdrive): single-family
   Plus Jakarta system pushed to editorial-masthead craft - bigger display
   H1, numeral control (lining figures in the title, tabular in the meta),
   tighter label>title coupling, defined micro-type on the meta line.
   ========================================================================= */

/* ---- 1. THE BAND: warm layered paper, compact, copper top-rule ---- */
html body:not(.home) .ww-site-hero,
html body.blog    .ww-site-hero-archive,
html body.archive .ww-site-hero-archive,
html body.search  .ww-site-hero-archive {
  position: relative !important;
  overflow: hidden !important;
  isolation: isolate !important;
  display: flex !important;
  flex-direction: column !important;
  justify-content: center !important;
  align-items: center !important;                /* centered masthead (owner 2026-07-06) */
  text-align: center !important;
  aspect-ratio: auto !important;                 /* kill the 4/1 aspect rule */
  min-height: clamp(240px, 22vw, 340px) !important;
  padding: clamp(44px,6vw,76px) clamp(20px,5vw,72px) !important;
  border-top: 2px solid #9E5E1E !important;      /* copper printed-plate edge */
  border-bottom: 1px solid #E7E0D5 !important;
  /* Single `background` SHORTHAND so it cleanly beats the incumbent `background:` shorthand
     (#ww-redesign-fixes-r2) at equal specificity by source order. Copper corner-light (6% alpha,
     warmth never glow) + weighted warm gradient (lighter top, deeper foot = mass) over a warm base.
     Paper grain lives on ::before so no single-layer parse error can drop the whole background. */
  background:
    radial-gradient(120% 130% at 10% 0%, rgba(158,94,30,0.06) 0%, rgba(158,94,30,0) 44%) no-repeat,
    linear-gradient(180deg, #FBF9F5 0%, #F3EEE4 58%, #EDE4D5 100%) no-repeat,
    #F3EEE4 !important;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.6),          /* lit top edge */
              0 8px 22px -14px rgba(125,74,23,0.28) !important; /* elevation */
}

/* Grain veil on ::before, multiplied over cream, behind content */
html body:not(.home) .ww-site-hero::before,
html body.blog    .ww-site-hero-archive::before,
html body.archive .ww-site-hero-archive::before,
html body.search  .ww-site-hero-archive::before {
  content: "" !important;
  position: absolute !important;
  inset: 0 !important;
  z-index: 0 !important;                          /* positive z, NOT -1 */
  pointer-events: none !important;
  opacity: 0.5 !important;
  mix-blend-mode: multiply !important;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.045'/%3E%3C/svg%3E") !important;
}

/* Inner column above grain; left axis, capped measure */
html body:not(.home) .ww-site-hero .ww-site-hero-inner,
html body.blog    .ww-site-hero-archive .ww-site-hero-inner,
html body.archive .ww-site-hero-archive .ww-site-hero-inner,
html body.search  .ww-site-hero-archive .ww-site-hero-inner {
  position: relative !important;
  z-index: 1 !important;
  width: 100% !important;
  max-width: 1080px !important;
  margin: 0 auto !important;
  text-align: center !important;
}

/* ---- 2. EYEBROW as copper index-tab; rule draws in on load ---- */
html body:not(.home) .ww-site-hero-tag,
html body.blog    .ww-site-hero-archive .ww-site-hero-tag,
html body.archive .ww-site-hero-archive .ww-site-hero-tag,
html body.search  .ww-site-hero-archive .ww-site-hero-tag {
  position: relative !important;
  display: inline-block !important;
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-size: 13px !important;
  font-weight: 800 !important;
  letter-spacing: 0.18em !important;              /* open tracking = masthead index-tab */
  text-transform: uppercase !important;
  font-variant-numeric: normal !important;
  color: #7D4A17 !important;                      /* copper-dark, AA (>=5.82:1) */
  -webkit-text-fill-color: #7D4A17 !important;
  background: none !important;
  border: none !important;                        /* replace base border w/ ::after */
  padding: 0 0 9px 0 !important;
  margin: 0 0 16px 0 !important;                  /* tight to the H1 = one title unit */
  animation: ww-rise 0.55s cubic-bezier(.2,.7,.2,1) both !important;
}
html body:not(.home) .ww-site-hero-tag::after,
html body.blog    .ww-site-hero-archive .ww-site-hero-tag::after,
html body.archive .ww-site-hero-archive .ww-site-hero-tag::after,
html body.search  .ww-site-hero-archive .ww-site-hero-tag::after {
  content: "" !important;
  position: absolute !important;
  left: 0 !important; bottom: 0 !important;
  width: 100% !important; height: 2px !important;
  background: #9E5E1E !important;                 /* bright copper: graphic, >=4.09:1 */
  transform: scaleX(0) !important;
  transform-origin: left center !important;
  animation: ww-draw 0.6s cubic-bezier(.2,.7,.2,1) 0.14s forwards !important;
}

/* ---- 3. THE H1: bold ink command, big scale (the POP) ---- */
html body:not(.home) .ww-site-hero-title,
html body.blog    .ww-site-hero-archive .ww-site-hero-title,
html body.archive .ww-site-hero-archive .ww-site-hero-title,
html body.search  .ww-site-hero-archive .ww-site-hero-title {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-weight: 800 !important;
  font-size: clamp(2.4rem, 5vw, 3.85rem) !important;   /* bigger command = more pop */
  line-height: 1.06 !important;                        /* tight display leading (1-3 line heads) */
  letter-spacing: -0.03em !important;                  /* optical tracking for large display */
  color: #1A1714 !important;                      /* 14.16-16.97:1 (AAA) */
  -webkit-text-fill-color: #1A1714 !important;
  background: none !important;
  border: none !important;
  margin: 0 auto 18px auto !important;   /* auto L/R centers the measure-capped title */
  padding: 0 !important;
  max-width: 22ch !important;    /* 22ch: audit-verified <=3 lines for every sub-62-char title */
  text-wrap: balance !important;
  hyphens: none !important;                            /* never hyphenate a title */
  font-kerning: normal !important;
  font-variant-numeric: lining-nums proportional-nums !important; /* "2026" at cap height */
  text-rendering: optimizeLegibility !important;       /* real kerning on the display line */
  animation: ww-rise 0.55s cubic-bezier(.2,.7,.2,1) 0.08s both !important;
}

/* ---- 3b. HEADLINE LENGTH TIERS (2026-07-06 "jumbled title" fix) ----
   The display size above is tuned for short editorial heads (1-3 lines).
   Pipeline posts can run 80-100 chars, which at full display size wrap
   into a 5-6 line wall with display-tight leading = reads as jumble.
   Newspaper practice: long heads step DOWN in size and UP in leading.
   Classes are set by ww-hero.php from the title's character count:
   >=62 chars -> --long, >=90 chars -> --xlong. Extra class = higher
   specificity (0,0,3,2), so these cleanly beat the base rule above. */
html body:not(.home) .ww-site-hero-title.ww-title--long {
  font-size: clamp(2.05rem, 3.9vw, 3rem) !important;
  line-height: 1.12 !important;
  letter-spacing: -0.022em !important;
  max-width: 26ch !important;
}
html body:not(.home) .ww-site-hero-title.ww-title--xlong {
  font-size: clamp(1.8rem, 3.3vw, 2.55rem) !important;
  line-height: 1.16 !important;
  letter-spacing: -0.016em !important;
  max-width: 30ch !important;
}

/* ---- 4. SUB ---- */
html body:not(.home) .ww-site-hero-sub,
html body.blog    .ww-site-hero-archive .ww-site-hero-sub,
html body.archive .ww-site-hero-archive .ww-site-hero-sub,
html body.search  .ww-site-hero-archive .ww-site-hero-sub {
  font-family: 'Plus Jakarta Sans', sans-serif !important;
  font-size: clamp(16.5px, 1.15vw, 18.5px) !important;
  font-weight: 500 !important;
  line-height: 1.5 !important;
  color: #3D3732 !important;                      /* 9.30-11.15:1 (AAA) */
  max-width: 62ch !important;
  margin: 0 auto 22px auto !important;            /* auto L/R centers the deck */
  text-wrap: pretty !important;                   /* no orphans/rivers in the deck */
  hyphens: none !important;                       /* theme hyphenation breaks words ("hus-tles") */
  animation: ww-rise 0.55s cubic-bezier(.2,.7,.2,1) 0.16s both !important;
}

/* ---- 5. META (posts only render it) ---- */
html body:not(.home) .ww-site-hero-meta {
  justify-content: center !important;             /* center the byline row (base is display:flex) */
  font-size: 13px !important;
  font-weight: 500 !important;
  line-height: 1.5 !important;
  letter-spacing: 0.005em !important;
  font-variant-numeric: tabular-nums !important;  /* even figures across read-time + date */
  color: #5C5347 !important;                      /* 5.99-7.18:1 (AA/AAA) */
  border-top: 1px solid #E1D8C8 !important;
  padding-top: 18px !important;
  animation: ww-rise 0.55s cubic-bezier(.2,.7,.2,1) 0.22s both !important;
}
html body:not(.home) .ww-site-hero-meta a {
  color: #7D4A17 !important;
  font-weight: 600 !important;                    /* the byline name carries a touch more weight */
  text-decoration: none !important;
  text-underline-offset: 2px !important;
  text-decoration-thickness: 1px !important;
}
html body:not(.home) .ww-site-hero-meta a:hover,
html body:not(.home) .ww-site-hero-meta a:focus-visible { text-decoration: underline !important; }
html body:not(.home) .ww-site-hero-meta-sep,
html body:not(.home) .ww-site-hero-meta-dot { background: #C9BCA6 !important; }

/* ---- 6. KEYFRAMES (transform/opacity only = compositor-cheap) ---- */
@keyframes ww-draw { to { transform: scaleX(1); } }
@keyframes ww-rise {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ---- 7. MOBILE ( <= 768px ) ---- */
@media (max-width: 768px) {
  html body:not(.home) .ww-site-hero,
  html body.blog    .ww-site-hero-archive,
  html body.archive .ww-site-hero-archive,
  html body.search  .ww-site-hero-archive {
    min-height: clamp(180px, 46vw, 240px) !important;
    padding: 40px 22px 36px !important;
  }
  html body:not(.home) .ww-site-hero::before,
  html body.blog    .ww-site-hero-archive::before,
  html body.archive .ww-site-hero-archive::before,
  html body.search  .ww-site-hero-archive::before { opacity: 0.4 !important; }
  html body:not(.home) .ww-site-hero-tag,
  html body.blog    .ww-site-hero-archive .ww-site-hero-tag,
  html body.archive .ww-site-hero-archive .ww-site-hero-tag,
  html body.search  .ww-site-hero-archive .ww-site-hero-tag {
    font-size: 12px !important; letter-spacing: 0.16em !important; margin-bottom: 12px !important;
  }
  html body:not(.home) .ww-site-hero-title,
  html body.blog    .ww-site-hero-archive .ww-site-hero-title,
  html body.archive .ww-site-hero-archive .ww-site-hero-title,
  html body.search  .ww-site-hero-archive .ww-site-hero-title {
    /* CRITICAL: leading must loosen on mobile. Without this override the title
       inherits the 1.06 desktop display leading while wrapping 3-6 lines at
       phone width - ascenders/descenders nearly touch and it reads as jumble.
       Caps match the desktop clamp minimums so the size never INVERTS (shrinks)
       when the viewport crosses 768px. */
    font-size: clamp(2rem, 8.5vw, 2.4rem) !important; line-height: 1.14 !important;
    max-width: 100% !important; margin-bottom: 14px !important;
  }
  /* Mobile tiers re-declare max-width too: the 3b tier rules are (0,0,3,2) and
     would otherwise keep their 26/30ch measure cap over the (0,0,2,2) base. */
  html body:not(.home) .ww-site-hero-title.ww-title--long {
    font-size: clamp(1.7rem, 7.2vw, 2.05rem) !important; line-height: 1.2 !important;
    max-width: 100% !important;
  }
  html body:not(.home) .ww-site-hero-title.ww-title--xlong {
    font-size: clamp(1.55rem, 6.4vw, 1.8rem) !important; line-height: 1.22 !important;
    max-width: 100% !important;
  }
  html body:not(.home) .ww-site-hero-sub,
  html body.blog    .ww-site-hero-archive .ww-site-hero-sub,
  html body.archive .ww-site-hero-archive .ww-site-hero-sub,
  html body.search  .ww-site-hero-archive .ww-site-hero-sub {
    font-size: 15.5px !important; margin-bottom: 18px !important;
  }
  html body:not(.home) .ww-site-hero-meta { font-size: 12.5px !important; padding-top: 15px !important; }
}

/* ---- 7b. SEARCH-RESULTS TITLE SAFETY ----
   The search hero H1 is "Search results for: <query>" - unbounded length, and
   ww-injectors.php can't add length-tier classes there. Step it down at all
   widths. Same (0,0,3,2) specificity as the base/mobile archive rules; placed
   AFTER both, so it wins the tie by source order everywhere. */
html body.search .ww-site-hero-archive .ww-site-hero-title {
  font-size: clamp(1.7rem, 3.4vw, 2.55rem) !important;
  line-height: 1.18 !important;
  letter-spacing: -0.016em !important;
  max-width: 34ch !important;
}

/* ---- 8. REDUCED MOTION ---- */
@media (prefers-reduced-motion: reduce) {
  html body:not(.home) .ww-site-hero-tag,
  html body:not(.home) .ww-site-hero-title,
  html body:not(.home) .ww-site-hero-sub,
  html body:not(.home) .ww-site-hero-meta,
  html body.blog    .ww-site-hero-archive .ww-site-hero-title,
  html body.archive .ww-site-hero-archive .ww-site-hero-title,
  html body.search  .ww-site-hero-archive .ww-site-hero-title,
  html body.blog    .ww-site-hero-archive .ww-site-hero-tag,
  html body.archive .ww-site-hero-archive .ww-site-hero-tag,
  html body.search  .ww-site-hero-archive .ww-site-hero-tag {
    animation: none !important; opacity: 1 !important; transform: none !important;
  }
  html body:not(.home) .ww-site-hero-tag::after,
  html body.blog    .ww-site-hero-archive .ww-site-hero-tag::after,
  html body.archive .ww-site-hero-archive .ww-site-hero-tag::after,
  html body.search  .ww-site-hero-archive .ww-site-hero-tag::after {
    animation: none !important; transform: scaleX(1) !important;
  }
}
WWCSS
    . "\n</style>\n";
}, 999 );
