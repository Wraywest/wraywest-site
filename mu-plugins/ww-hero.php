<?php
/**
 * Plugin Name:       WrayWest Hero
 * Plugin URI:        https://wraywestblog.com
 * Description:       Typography-led hero above content on singular posts and pages. Removes GeneratePress native featured-image auto-render and the duplicate entry-header so there is no duplication and no dual H1.
 * Version:           3.0.0
 * Author:            WrayWest
 * License:           GPL-2.0-or-later
 *
 * Changelog
 * ---------
 * 3.0.0 - front page gets a cinematic light hero (.ww-cine-hero: Instrument
 *         Serif display type, fade-rise entrance, self-hosted looping video
 *         background with rAF fade in/out, gradient legibility overlays).
 *         Front page no longer uses the .ww-site-hero class, so every legacy
 *         body.home override in the Customizer/option CSS goes inert without
 *         being edited. Posts/pages/archives keep the v2 hero untouched.
 * 2.1.0 — adds generate_show_entry_header filter to remove GP's duplicate H1
 *         on singular views (GP's <header class="entry-header"> wrapper now
 *         absent from DOM, not just CSS-hidden — fixes Rank Math dual-H1 flag)
 * 2.0.0 — Direction 1 typography-only hero. Removes GP native featured-image.
 */

/*
 * ============================================================================
 * WRAYWEST HERO — mu-plugin v2 (Direction 1: typography-led, no image)
 * ============================================================================
 *
 * Purpose
 * -------
 * Replaces GeneratePress's native featured-image auto-render on singular
 * views with a clean typography hero: copper-bar tag + H1 + subtitle + meta.
 * No image. No duplication. No split-panel. No photography.
 *
 * How it avoids the "dual hero" / "duplicated featured image" problem
 * -------------------------------------------------------------------
 * On the `wp` action (priority 9, before any template-part hook fires),
 * remove the two GeneratePress free (3.6.x) callbacks that render the
 * native featured-image blocks:
 *
 *   remove_action( 'generate_before_content', 'generate_featured_page_header_inside_single', 10 );
 *   remove_action( 'generate_after_header',   'generate_featured_page_header',                10 );
 *
 * The first kills the INSIDE-article variant (div.featured-image.page-header-image-single).
 * The second kills the OUTSIDE-article variant (div.featured-image.page-header-image)
 * that GeneratePress emits between </header> and the page wrapper when the
 * Customizer "Page Hero" featured-image layout is active.
 *
 * This mu-plugin then renders its own typography-only hero. The featured
 * image data remains on the post (og:image, archive cards, schema all keep
 * working) — only the front-end DOM rendering on singular views is removed.
 *
 * How to disable
 * --------------
 *
 *   SITE-WIDE
 *     Add to wp-config.php above the "That's all, stop editing!" line:
 *       define('WW_HERO_DISABLED', true);
 *     Plugin returns early. GP's native image auto-render comes back
 *     (the remove_action never runs).
 *
 *   PER-POST / PAGE
 *     Set post meta `_ww_suppress_hero` to `1` on any post/page. That view
 *     will skip our hero. Note: GP's native featured-image is also removed
 *     via remove_action(), so the page/post will render with no top image
 *     at all — which is what we want (title leads).
 *
 *   TEMPLATE-LEVEL (automatic)
 *     Auto-skipped on: search results, 404, author archive, category/tag
 *     archives, anything that is not is_singular(). Only singular views
 *     trigger both the remove_action and the render.
 *
 *   KILL SWITCH
 *     Rename /wp-content/mu-plugins/ww-hero.php to ww-hero.php.off via
 *     Hostinger File Manager. WordPress stops loading it on next request.
 *     GP native featured-image auto-render resumes automatically.
 *
 * Subtitle source priority
 * ------------------------
 *   1. post_excerpt (native WordPress excerpt)
 *   2. Rank Math meta description (post meta `rank_math_description`)
 *   3. No subtitle — hero renders title + meta only
 *
 * Hero contents by context
 * ------------------------
 *   POSTS:         tag (category) + H1 + subtitle + meta [read-time · date · by author]
 *   PAGES:         tag (page title) + H1 + subtitle + meta [By author · Writing since · Contact]
 *   FRONT PAGE:    H1 + subtitle ONLY (no tag, no meta)
 *
 * Filters
 * -------
 *   apply_filters('ww_hero_writing_since', 'August 2025'),
 *   apply_filters('ww_hero_contact_url', home_url('/contact/'))
 *   Override either to customize the page meta row.
 *
 * ============================================================================
 */

// ------- CONFIG-LEVEL KILL SWITCH -----------------------------------------
if ( defined( 'WW_HERO_DISABLED' ) && WW_HERO_DISABLED ) {
	return;
}

// ------- SHOULD-RENDER CHECK ----------------------------------------------
function ww_hero_should_render() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}
	$cached = false;

	if ( ! is_singular() ) {
		return false;
	}
	if ( is_search() || is_404() || is_author() || is_category() || is_tag() || is_archive() ) {
		return false;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return false;
	}

	if ( get_post_meta( $post_id, '_ww_suppress_hero', true ) ) {
		return false;
	}

	$cached = (int) $post_id;
	return $cached;
}

// ------- REMOVE GP NATIVE FEATURED-IMAGE AUTO-RENDER ----------------------
/**
 * On singular views, kill GeneratePress's `generate_post_image()` which
 * injects the .featured-image.page-header-image block at the top of posts
 * and pages. Our own hero takes over instead. The remove_action must fire
 * AFTER the main query is set up so is_singular() works correctly, which
 * is why we hook into `wp` at priority 9 (before GP's action runs).
 */
function ww_hero_remove_gp_native_image() {
	if ( is_singular() ) {
		// GP free 3.6.x registers two featured-image renderers on singular views.
		// Remove both so the mu-plugin's typography hero is the only hero rendered.
		// Priority 10 matches GP's registration priority exactly — without the
		// explicit priority, remove_action() silently fails (common pitfall).
		remove_action( 'generate_before_content', 'generate_featured_page_header_inside_single', 10 );
		remove_action( 'generate_after_header',   'generate_featured_page_header',                10 );
	}
}
add_action( 'wp', 'ww_hero_remove_gp_native_image', 9 );

/**
 * Suppress GP's entry-header (containing the duplicate H1) on singular views
 * where our mu-plugin hero is rendering. Returning false from
 * `generate_show_entry_header` removes the entire <header class="entry-header">
 * wrapper from the DOM (not just CSS-hidden) — eliminates the dual-H1 issue
 * Rank Math was flagging.
 *
 * Filter is GP-documented and stable since GP 3.0+ (current site runs 3.6.1).
 * Uses ww_hero_should_render() so we only suppress GP's entry-header on pages
 * where our hero is actually taking over.
 *
 * Archive views (blog index, category, tag, search, author, 404) are
 * automatically unaffected because is_singular() returns false there, which
 * makes ww_hero_should_render() return false, which leaves $show unchanged.
 */
add_filter( 'generate_show_entry_header', 'ww_hero_suppress_entry_header' );
function ww_hero_suppress_entry_header( $show ) {
	if ( ww_hero_should_render() ) {
		return false;
	}
	return $show;
}

// ------- SUBTITLE RESOLVER ------------------------------------------------
function ww_hero_subtitle( $post_id ) {
	if ( has_excerpt( $post_id ) ) {
		$subtitle = get_post_field( 'post_excerpt', $post_id );
	} else {
		$subtitle = (string) get_post_meta( $post_id, 'rank_math_description', true );
	}
	return trim( wp_strip_all_tags( (string) $subtitle ) );
}

// ------- PRIMARY CATEGORY -------------------------------------------------
function ww_hero_primary_category( $post_id ) {
	$rm_primary_id = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
	if ( $rm_primary_id ) {
		$term = get_term( $rm_primary_id, 'category' );
		if ( ! is_wp_error( $term ) && $term ) {
			return $term;
		}
	}
	$cats = get_the_category( $post_id );
	if ( ! empty( $cats ) ) {
		return $cats[0];
	}
	return null;
}

// ------- READ TIME --------------------------------------------------------
function ww_hero_read_time_minutes( $post_id ) {
	$content = get_post_field( 'post_content', $post_id );
	if ( empty( $content ) ) {
		return 0;
	}
	$text       = wp_strip_all_tags( $content );
	$word_count = str_word_count( $text );
	if ( 0 === $word_count ) {
		return 0;
	}
	return max( 1, (int) ceil( $word_count / 220 ) );
}

// ------- MAIN RENDER ------------------------------------------------------
function ww_hero_render() {
	$post_id = ww_hero_should_render();
	if ( ! $post_id ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}

	$title    = get_the_title( $post_id );
	$subtitle = ww_hero_subtitle( $post_id );

	$is_post  = ( 'post' === $post->post_type );
	$is_page  = ( 'page' === $post->post_type );
	$front_id = (int) get_option( 'page_on_front' );
	$is_front = ( $is_page && (int) $post_id === $front_id );

	// Front page: cinematic hero (v3), completely separate markup/CSS/JS path.
	if ( $is_front ) {
		ww_hero_render_front();
		return;
	}

	// ---- Tag / breadcrumb-style eyebrow ----
	$tag_text = '';
	if ( $is_post ) {
		$cat = ww_hero_primary_category( $post_id );
		if ( $cat ) {
			$tag_text = $cat->name;
		}
	} elseif ( $is_page && ! $is_front ) {
		// Pages: a short SECTION KICKER, never the page title (setting tag = title
		// duplicated the H1 in the banner - "START HERE" over "Start Here...", etc.,
		// owner-reported 2026-07-10). Unmapped pages get no eyebrow (clean H1 + sub).
		$page_eyebrows = apply_filters( 'ww_hero_page_eyebrows', array(
			'start-here'                => 'Get oriented',
			'about'                     => 'The founder',
			'contact'                   => 'Say hello',
			'tools'                     => 'Vetted picks',
			'resources'                 => 'My stack',
			'the-second-income-engine'  => 'The system',
			'scoring-methodology'       => 'How we test',
			'subscribe'                 => 'Newsletter',
			'quit-9-to-5-calculator'    => 'Free tool',
			'freelance-rate-calculator' => 'Free tool',
			'lead-magnet-builder'       => 'Free tool',
			'legal'                     => 'The fine print',
			'privacy-policy'            => 'The fine print',
			'terms-of-service'          => 'The fine print',
		) );
		$slug     = get_post_field( 'post_name', $post_id );
		$tag_text = isset( $page_eyebrows[ $slug ] ) ? $page_eyebrows[ $slug ] : '';
	}
	// Front page: no tag — leaves just H1 + subtitle.

	// Defensive: an eyebrow must never duplicate the H1 (belt-and-suspenders for
	// any future page/category whose label happens to match its own title).
	if ( $tag_text && 0 === strcasecmp( trim( wp_strip_all_tags( $tag_text ) ), trim( wp_strip_all_tags( $title ) ) ) ) {
		$tag_text = '';
	}

	// ---- Meta row ----
	// Posts:   [read-time · date · by author]
	// Pages:   [By author · Writing since … · Contact]
	// Front:   no meta
	$meta_items = array(); // Each item is a plain string, OR an array ['html' => '<a ...>'] for prebuilt markup.

	if ( $is_post ) {
		$rt = ww_hero_read_time_minutes( $post_id );
		if ( $rt ) {
			$meta_items[] = $rt . ' min read';
		}
		// Show "Updated <date>" when the post was meaningfully revised after publish (E-E-A-T
		// freshness signal that matches the schema's dateModified); else the publish date.
		$pub_date = get_the_date( 'F j, Y', $post_id );
		$mod_date = get_the_modified_date( 'F j, Y', $post_id );
		$pub_ts   = (int) get_post_time( 'U', false, $post_id );
		$mod_ts   = (int) get_post_modified_time( 'U', false, $post_id );
		if ( $mod_date && $mod_ts > ( $pub_ts + DAY_IN_SECONDS ) ) {
			$meta_items[] = 'Updated ' . $mod_date;
		} elseif ( $pub_date ) {
			$meta_items[] = $pub_date;
		}
		$author_id = (int) get_post_field( 'post_author', $post_id );
		if ( $author_id ) {
			$author_name = get_the_author_meta( 'display_name', $author_id );
			if ( $author_name ) {
				// Link the byline to the author's E-E-A-T archive (/author/dwayne/): gives that
				// page an inbound link from every post + makes the credentials reachable from any
				// byline. Added 2026-06-26.
				$author_url     = get_author_posts_url( $author_id );
				$meta_items[]   = array( 'html' => 'By <a class="ww-byline-author" href="' . esc_url( $author_url ) . '" rel="author">' . esc_html( $author_name ) . '</a>' );
			}
		}
	} elseif ( $is_page && ! $is_front ) {
		$author_id = (int) get_post_field( 'post_author', $post_id );
		if ( $author_id ) {
			$author_name = get_the_author_meta( 'display_name', $author_id );
			if ( $author_name ) {
				$meta_items[] = 'By ' . $author_name;
			}
		}
	$writing_since = apply_filters( 'ww_hero_writing_since', 'August 2025' );
		if ( $writing_since ) {
			$meta_items[] = 'Writing since ' . $writing_since;
		}
		$contact_url = apply_filters( 'ww_hero_contact_url', home_url( '/contact/' ) );
		if ( $contact_url ) {
			$meta_items[] = array( 'html' => '<a href="' . esc_url( $contact_url ) . '">Contact</a>' );
		}
	}
	// Front page: $meta_items stays empty.

	// ---- Headline size tier by length (2026-07-06) ----
	// The masthead display size is tuned for short editorial titles; pipeline posts
	// can run 80-100 chars, which at display size wrap 5-6 lines and read as a
	// jumble. Newspaper practice: long heads set smaller with looser leading.
	// The tier classes are styled in ww-hero-masthead.php (the Copper Masthead CSS).
	// Decode entities before counting: get_the_title() emits e.g. &#8217; for an
	// apostrophe (7 source chars, 1 visual glyph), which would inflate the count.
	$title_len     = mb_strlen( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
	$title_classes = 'ww-site-hero-title';
	if ( $title_len >= 90 ) {
		$title_classes .= ' ww-title--xlong';
	} elseif ( $title_len >= 62 ) {
		$title_classes .= ' ww-title--long';
	}

	// ---- Emit CSS (once per request) ----
	echo ww_hero_css(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
<section class="ww-site-hero" role="banner">
	<div class="ww-site-hero-inner">
		<?php if ( $is_front ) : ?>
			<span class="ww-site-hero-kicker">Side income &middot; No hype</span>
		<?php endif; ?>
		<?php if ( $tag_text ) : ?>
			<span class="ww-site-hero-tag"><?php echo esc_html( $tag_text ); ?></span>
		<?php endif; ?>
		<h1 class="<?php echo esc_attr( $title_classes ); ?>"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $subtitle ) : ?>
			<p class="ww-site-hero-sub"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
		<?php if ( $is_front ) : ?>
			<div class="ww-site-hero-cta">
				<a class="ww-hero-btn-primary" href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>"><span>Start Here &rarr;</span></a>
				<a class="ww-hero-btn-secondary" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Browse the writing &rarr;</a>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $meta_items ) ) : ?>
			<div class="ww-site-hero-meta">
				<?php foreach ( $meta_items as $i => $item ) : ?>
					<?php if ( $i > 0 ) : ?>
						<span class="ww-site-hero-meta-sep" aria-hidden="true"></span>
					<?php endif; ?>
					<span><?php
					if ( is_array( $item ) && isset( $item['html'] ) ) {
						// Pre-built safe HTML (e.g., anchor with esc_url'd href).
						echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						echo esc_html( $item );
					}
					?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
	<?php
}

// ------- FRONT PAGE: CINEMATIC HERO (v3) -----------------------------------
/**
 * Cinematic light hero for the front page only.
 *
 * Design spec (adapted from the React/Tailwind "cinematic hero" brief):
 * - Display type: Instrument Serif, line-height 0.95, tight tracking,
 *   italic gray emphasis words inside the headline.
 * - Entrance: fade-rise keyframes staggered 0 / 0.2s / 0.4s.
 * - Background: self-hosted looping video anchored top:300px with
 *   bg-to-transparent-to-bg gradient overlays; manual loop with rAF-driven
 *   0.5s fade in/out (no native loop attr so `ended` fires).
 * - Performance: video preload=none, src attached only after window load;
 *   hidden under 768px; reduced-motion users get the static poster.
 * - Uses .ww-cine-hero (NOT .ww-site-hero) so every legacy body.home
 *   override targeting .ww-site-hero is inert here without editing it.
 */
function ww_hero_render_front() {
	/*
	 * Direction C — "Centered search hero" (deployed 2026-06-14), adapted from
	 * hsuper.tools at the owner's request: pill badge, large centered serif
	 * headline (copper accent words), subtitle, a working site-search bar, and
	 * three trust pills (the receipts thesis preserved as a pill). Warm cream
	 * with subtle copper/navy glow; the big bold headline + functional search
	 * is the focal point. Previous receipts-split hero backed up server-side.
	 */
	$action = esc_url( home_url( '/' ) );
	?>
<style id="ww-cs-hero-css">
html { overflow-x: clip; }
body.ww-hero-active .entry-header { display: none !important; }
.ww-cs-hero{
	--cs-bg:#F8F6F2;
	--cs-ink:#1A1714;
	--cs-copper:#9E5E1E;
	--cs-copper-lt:#D4956B;
	--cs-navy:#0F1729;
	position:relative;
	width:100vw;max-width:100vw;
	margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);
	min-height:84svh;
	display:flex;flex-direction:column;align-items:center;justify-content:center;
	text-align:center;
	padding:64px 24px 88px;
	background:
		radial-gradient(ellipse 40% 56% at 0% 42%, rgba(158,94,30,0.20), transparent 60%),
		radial-gradient(ellipse 42% 58% at 100% 54%, rgba(212,149,107,0.22), transparent 60%),
		radial-gradient(ellipse 58% 46% at 50% 120%, rgba(15,23,41,0.12), transparent 62%),
		radial-gradient(ellipse 52% 40% at 50% -12%, rgba(212,149,107,0.12), transparent 56%),
		var(--cs-bg);
	overflow:hidden;
	font-family:'Plus Jakarta Sans',sans-serif;
	border-bottom:1px solid rgba(158,94,30,0.22);
	box-shadow:0 14px 30px -20px rgba(26,23,20,0.16);
}
.ww-cs-inner{position:relative;z-index:2;max-width:1240px;margin:0 auto;display:flex;flex-direction:column;align-items:center;}
.ww-cs-pill{
	display:inline-flex;align-items:center;gap:7px;
	font-size:0.72rem;font-weight:700;letter-spacing:0.06em;
	color:#7A5230;background:rgba(158,94,30,0.10);
	border:1px solid rgba(158,94,30,0.30);
	padding:6px 15px;border-radius:9999px;margin-bottom:1.7rem;
	text-transform:uppercase;
	animation:wwFadeRise .8s ease-out both;
}
.ww-cs-pill::before{content:"\2726";color:var(--cs-copper);font-size:0.8rem;}
.ww-cs-title{
	font-family:'Plus Jakarta Sans',sans-serif !important;
	font-weight:800 !important;
	font-size:clamp(2.9rem,7.4vw,6.4rem);
	line-height:1.02 !important;
	letter-spacing:-0.035em !important;
	color:var(--cs-ink) !important;
	-webkit-text-fill-color:var(--cs-ink) !important;
	background:none !important;border:none !important;border-bottom:none !important;
	margin:0;padding:0 0 .04em;max-width:19ch;
	text-wrap:balance;
	animation:wwFadeRise .8s ease-out both;
}
.ww-cs-title em{
	font-style:normal;color:#9E5E1E;
	background:linear-gradient(100deg,#9E5E1E 0%,#8A5119 60%,#7D4A17 100%);
	-webkit-background-clip:text;background-clip:text;
	-webkit-text-fill-color:transparent;
	padding-right:0.02em;
}
.ww-cs-sub{
	font-size:clamp(1.0625rem,1.5vw,1.25rem);
	color:#3D3732;line-height:1.6;font-weight:500;
	max-width:46rem;margin:1.6rem 0 0;
	animation:wwFadeRise .8s ease-out .15s both;
}
.ww-cs-search{
	display:flex;align-items:center;width:100%;max-width:540px;
	background:#fff;border:1px solid rgba(26,23,20,0.14);
	border-radius:9999px;padding:7px 7px 7px 20px;
	box-shadow:0 8px 26px rgba(26,23,20,0.07);
	margin-top:2.2rem;
	animation:wwFadeRise .8s ease-out .3s both;
}
.ww-cs-search svg{flex:none;width:20px;height:20px;color:#9A8E80;}
.ww-cs-search input[type="search"]{
	flex:1;border:none;outline:none;background:transparent;
	font-size:1rem;color:var(--cs-ink);padding:10px 12px;font-family:inherit;min-width:0;
}
.ww-cs-search input::placeholder{color:#9A8E80;}
.ww-cs-search button{
	flex:none;background:var(--cs-copper);color:#fff;border:none;cursor:pointer;
	font-size:0.9rem;font-weight:600;padding:11px 24px;border-radius:9999px;
	font-family:inherit;letter-spacing:0.01em;
	transition:transform .18s ease,box-shadow .18s ease,background .18s ease;
	box-shadow:0 4px 14px rgba(158,94,30,0.30);
}
.ww-cs-search button:hover{transform:translateY(-1px);background:#8a5119;box-shadow:0 6px 18px rgba(158,94,30,0.42);}
.ww-cs-trust{
	display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:1.8rem;
	animation:wwFadeRise .8s ease-out .45s both;
}
.ww-cs-trust span{
	display:inline-flex;align-items:center;gap:6px;
	font-size:0.8125rem;color:#3D3732;background:#fff;
	border:1px solid rgba(26,23,20,0.12);padding:7px 15px;border-radius:9999px;
}
.ww-cs-trust span::before{content:"\2713";color:var(--cs-copper);font-weight:800;}
.ww-cs-start{
	margin-top:1.5rem;font-size:0.95rem;color:#6B6258;
	animation:wwFadeRise .8s ease-out .55s both;
}
.ww-cs-start a{color:var(--cs-copper);font-weight:700;text-decoration:none;border-bottom:2px solid rgba(158,94,30,0.4);padding-bottom:1px;}
.ww-cs-start a:hover{border-bottom-color:var(--cs-copper);}
@keyframes wwFadeRise{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:768px){
	.ww-cs-hero{min-height:auto;padding:44px 20px 60px;}
	.ww-cs-inner{max-width:100%;}
	.ww-cs-title{font-size:clamp(1.95rem,8.4vw,3.1rem);max-width:100%;letter-spacing:-0.03em;overflow-wrap:break-word;}
	.ww-cs-search button{padding:11px 20px;}
	.ww-cs-sub{font-size:1rem;}
}
@media (prefers-reduced-motion: reduce){
	.ww-cs-pill,.ww-cs-title,.ww-cs-sub,.ww-cs-search,.ww-cs-trust,.ww-cs-start{animation:none !important}
}
</style>
<section class="ww9" aria-labelledby="ww9-h">
 <div class="ww9-in">
  <div>
   <p class="ww9-by ww9-rise"><img src="https://wraywestblog.com/wp-content/uploads/2026/10/dwayne-avatar.webp" width="36" height="36" alt="Dwayne Lindsay, full-time chef and the writer behind WrayWest" fetchpriority="low"><span>Side income</span><span>No hype</span></p>
   <h1 id="ww9-h">A second income, built <em>after the shift.</em></h1>
   <p class="ww9-sub ww9-rise ww9-d1">I'm a full-time chef building a creator business on the side. Real numbers, every failure shown. No hype, no $997 courses.</p>
   <div class="ww9-cta ww9-rise ww9-d2">
    <a class="ww9-btn" href="https://wraywestblog.com/start-here/">Start here <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    <a class="ww9-link" href="#ww-home-band-subscribe">Get the newsletter</a>
   </div>
   <form class="ww9-search ww9-rise ww9-d3" role="search" method="get" action="https://wraywestblog.com/">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input type="search" name="s" placeholder="Search reviews, tools, and guides..." aria-label="Search WrayWest">
    <button type="submit">Search</button>
   </form>
   <ul class="ww9-promise ww9-rise ww9-d3"><li>No $997 courses</li><li>Real receipts, not fake screenshots</li><li>Sourced, never made up</li></ul>
  </div>

  <aside class="ww9-card ww9-rise ww9-d2" aria-label="The receipts so far">
   <span class="ww9-stamp">Started August 2025</span>
   <div class="ww9-clock">
    <svg viewBox="0 0 160 160" role="img" aria-labelledby="ww9-ct">
     <title id="ww9-ct">Illustration: a 24 hour dial. The 6:30 AM to 3:00 PM shift is shaded brown, the hours after it are copper.</title>
     <circle cx="80" cy="80" r="62" fill="none" stroke="#EDE6DC" stroke-width="14"/>
     <!-- 24h dial, 0:00 at top. shift 6.5h->15h = 97.5deg->225deg ; after 15h->24h = 225->360 -->
     <path class="ww9-arc" style="--len:138" d="M141.5 88.1 A62 62 0 0 1 36.2 123.8" fill="none" stroke="#2B2119" stroke-width="14"/>
     <path class="ww9-arc" style="--len:146;animation-delay:.9s" d="M36.2 123.8 A62 62 0 0 1 80 18" fill="none" stroke="#9E5E1E" stroke-width="14"/>
     <g fill="#5C5347" font-family="Plus Jakarta Sans,sans-serif" font-size="9" font-weight="600" text-anchor="middle"><text x="80" y="11">12a</text><text x="153" y="83">6a</text><text x="80" y="156">12p</text><text x="7" y="83">6p</text></g>
     <g class="ww9-hand" id="ww9-hand"><line x1="80" y1="80" x2="80" y2="30" stroke="#1A1714" stroke-width="2.5" stroke-linecap="round"/><circle cx="80" cy="30" r="4" fill="#F8F6F2" stroke="#1A1714" stroke-width="2"/></g>
     <circle cx="80" cy="80" r="4" fill="#1A1714"/>
    </svg>
    <div>
     <p class="ww9-k">The day, as it runs</p>
     <ul class="ww9-legend">
      <li><span class="ww9-sw" style="background:#2B2119"></span><b>6:30 AM to 3:00 PM</b><br>The chef shift</li>
      <li><span class="ww9-sw" style="background:#9E5E1E"></span><b>After 3:00 PM</b><br>Where WrayWest gets built</li>
     </ul>
     <p class="ww9-now" id="ww9-now" aria-live="off"></p>
    </div>
   </div>
   <p class="ww9-k" style="margin-top:20px">The receipts so far</p>
   <dl class="ww9-ledger">
    <div class="ww9-row"><dt>OLSP, lifetime</dt><dd>$810.95</dd><small>Source: my OLSP dashboard</small></div>
    <div class="ww9-row"><dt>LeadsLeap</dt><dd>$17.77</dd><small>Source: my LeadsLeap account</small></div>
   </dl>
  </aside>
 </div>
</section>
<script>
/* ~0.5KB: points the illustrative hand at the visitor's local time. No network, no layout shift. */
(function(){var h=document.getElementById('ww9-hand'),n=document.getElementById('ww9-now');if(!h)return;
function t(){var d=new Date(),m=d.getHours()*60+d.getMinutes();h.style.transform='rotate('+(m/1440*360)+'deg)';
n.textContent='Your time now: '+d.toLocaleTimeString([], {hour:'numeric',minute:'2-digit'});}
t();setInterval(t,60000);})();
</script>
	<?php
}

function ww_hero_render_front_UNUSED_receipts() {
	?>
<style id="ww-rcpt-hero-css">
html { overflow-x: clip; }
body.ww-hero-active .entry-header { display: none !important; }
.ww-rcpt-hero{
	--rc-bg:#F8F6F2;
	--rc-ink:#1A1714;
	--rc-copper:#9E5E1E;
	--rc-copper-lt:#D4956B;
	--rc-navy:#0F1729;
	position:relative;
	width:100vw;max-width:100vw;
	margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);
	min-height:82svh;
	display:grid;
	grid-template-columns:1.08fr 0.92fr;
	background:var(--rc-bg);
	overflow:hidden;
	font-family:'Plus Jakarta Sans',sans-serif;
}
.ww-rcpt-copy{
	display:flex;flex-direction:column;justify-content:center;
	padding:64px clamp(28px,4.5vw,72px) 64px clamp(24px,6vw,96px);
}
.ww-rcpt-kicker{
	display:inline-flex;align-items:center;width:fit-content;
	font-size:0.72rem;font-weight:700;letter-spacing:0.18em;
	text-transform:uppercase;color:#5C5347;margin:0 0 1.5rem;
	animation:wwFadeRise .8s ease-out both;
}
.ww-rcpt-kicker::before{
	content:"";display:inline-block;width:32px;height:2px;background:var(--rc-copper);
	margin-right:12px;transform:translateY(-2px);opacity:.9;
}
.ww-rcpt-title{
	font-family:'Instrument Serif',Georgia,serif !important;
	font-weight:400 !important;
	font-size:clamp(2.3rem,4.6vw,4.1rem);
	line-height:1.0 !important;
	letter-spacing:-0.022em !important;
	color:var(--rc-ink) !important;
	-webkit-text-fill-color:var(--rc-ink) !important;
	background:none !important;border:none !important;border-bottom:none !important;
	margin:0;padding:0 0 .06em;max-width:16ch;
	text-wrap:balance;
	animation:wwFadeRise .8s ease-out both;
}
.ww-rcpt-title em{
	font-style:italic;color:var(--rc-copper);
	-webkit-text-fill-color:var(--rc-copper);
	padding-right:0.04em;
}
.ww-rcpt-sub{
	font-size:clamp(1.0625rem,1.4vw,1.2rem);
	color:#3D3732;line-height:1.6;font-weight:600;
	max-width:34rem;margin:1.6rem 0 0;
	animation:wwFadeRise .8s ease-out .15s both;
}
.ww-rcpt-cta{
	display:flex;flex-wrap:wrap;gap:20px;align-items:center;
	margin-top:2.4rem;
	animation:wwFadeRise .8s ease-out .3s both;
}
.ww-rcpt-btn{
	display:inline-flex;align-items:center;gap:8px;
	background:var(--rc-copper) !important;color:#FFFFFF !important;
	font-size:1rem;font-weight:600;
	padding:16px 40px;border-radius:9999px;
	text-decoration:none !important;
	transition:transform .2s ease,box-shadow .2s ease;
	box-shadow:0 10px 26px rgba(158,94,30,0.26);
}
.ww-rcpt-btn span{color:#FFFFFF !important}
.ww-rcpt-btn:hover{transform:scale(1.03);box-shadow:0 14px 32px rgba(158,94,30,0.32)}
.ww-rcpt-btn:active{transform:scale(0.98)}
.ww-rcpt-link{
	color:#3D3732 !important;font-size:1.0625rem;font-weight:700;
	text-decoration:none !important;padding:12px 4px;
	border-bottom:2px solid rgba(158,94,30,0.4);
	transition:color .2s ease,border-color .2s ease;
}
.ww-rcpt-link:hover{color:var(--rc-copper) !important;border-bottom-color:var(--rc-copper)}
.ww-rcpt-card{
	background:var(--rc-navy);
	display:flex;flex-direction:column;justify-content:center;gap:18px;
	padding:48px clamp(28px,4vw,60px);
	animation:wwFadeRise .8s ease-out .4s both;
}
.ww-rcpt-card-label{
	font-size:0.7rem;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;
	color:var(--rc-copper-lt);margin:0;
}
.ww-rcpt-big{
	font-family:'Instrument Serif',Georgia,serif;
	font-size:clamp(3rem,4.6vw,4rem);line-height:0.95;color:#FFFFFF;margin:0;
}
.ww-rcpt-big-cap{font-size:0.875rem;color:rgba(255,255,255,0.66);margin:6px 0 0;line-height:1.45;}
.ww-rcpt-rule{height:1px;background:rgba(212,149,107,0.28);margin:4px 0;border:0;}
.ww-rcpt-row{display:flex;justify-content:space-between;align-items:baseline;gap:14px;}
.ww-rcpt-row-k{font-size:0.875rem;color:rgba(255,255,255,0.8);}
.ww-rcpt-row-v{font-family:'Instrument Serif',Georgia,serif;font-size:1.25rem;color:var(--rc-copper-lt);white-space:nowrap;}
@keyframes wwFadeRise{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@media (max-width:900px){
	.ww-rcpt-hero{grid-template-columns:1fr;min-height:auto;}
	.ww-rcpt-copy{padding:48px 24px 36px;order:1;}
	.ww-rcpt-title{font-size:clamp(2.4rem,9vw,3.3rem);max-width:none;}
	.ww-rcpt-sub{max-width:none;}
	.ww-rcpt-card{order:2;padding:36px 24px 44px;}
	.ww-rcpt-cta{margin-top:2rem;gap:14px;}
	.ww-rcpt-btn{padding:15px 34px;}
}
@media (prefers-reduced-motion: reduce){
	.ww-rcpt-kicker,.ww-rcpt-title,.ww-rcpt-sub,.ww-rcpt-cta,.ww-rcpt-card{animation:none !important}
}
</style>
<section class="ww-rcpt-hero" role="banner">
	<div class="ww-rcpt-copy">
		<span class="ww-rcpt-kicker">Side income &middot; No hype</span>
		<h1 class="ww-rcpt-title">Honest strategies for building a <em>second income</em> while working <em>full-time</em></h1>
		<p class="ww-rcpt-sub">I&#8217;m a full-time chef building a creator business on the side. Real numbers, every failure shown. No hype, no $997 courses.</p>
		<div class="ww-rcpt-cta">
			<a class="ww-rcpt-btn" href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>"><span>Start Here &rarr;</span></a>
			<a class="ww-rcpt-link" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Browse the writing &rarr;</a>
		</div>
	</div>
	<aside class="ww-rcpt-card" aria-label="The honest receipts">
		<p class="ww-rcpt-card-label">The receipts</p>
		<div>
			<p class="ww-rcpt-big">$17.77</p>
			<p class="ww-rcpt-big-cap">what I earned in my first 18 months. Every number is public.</p>
		</div>
		<hr class="ww-rcpt-rule">
		<div class="ww-rcpt-row"><span class="ww-rcpt-row-k">Median side hustle</span><span class="ww-rcpt-row-v">~$200/mo</span></div>
		<div class="ww-rcpt-row"><span class="ww-rcpt-row-k">What the gurus show you</span><span class="ww-rcpt-row-v">the top 1%</span></div>
	</aside>
</section>
	<?php
}

// ------- BODY CLASS -------------------------------------------------------
function ww_hero_body_class( $classes ) {
	if ( ww_hero_should_render() ) {
		$classes[] = 'ww-hero-active';
	}
	return $classes;
}

// ------- INLINE CSS -------------------------------------------------------
/**
 * Single inline <style> block. Uses :where() for 0-specificity so any
 * Customizer CSS overrides win without requiring !important.
 *
 * Emits only on the first call per request.
 */
function ww_hero_css() {
	static $printed = false;
	if ( $printed ) {
		return '';
	}
	$printed = true;

	ob_start();
	?>
<style id="ww-site-hero-css">
/* =========================================================================
   WRAYWEST HERO CSS v2 — Direction 1: typography-led, no image
   - Tokens in :where(.ww-site-hero), override freely from Customizer
   - Content-width hero (820px max) — no full-bleed trick needed
   - Entry-header hidden only when body has .ww-hero-active class

   CUSTOMIZER OVERRIDE CAVEAT
   --------------------------
   Most rules use :where() (specificity 0). Override in Customizer without
   !important. One exception: `.ww-site-hero-title` uses !important on 6
   properties (color, -webkit-text-fill-color, border, border-bottom,
   background, font-family) to protect against theme/parent bleed on the
   H1. If you need to override any of those six in Customizer, use
   !important in your override too. Everything else is freely overridable.
   ========================================================================= */

/* Hide default GeneratePress entry-header when hero is active */
body.ww-hero-active .entry-header { display: none !important; }

/* Prevent horizontal scroll introduced by the full-bleed hero (100vw breakout) */
html { overflow-x: clip; }

:where(.ww-site-hero){
	/* -------- TOKENS (tune here) -------- */
	--ww-copper: #9E5E1E;
	--ww-copper-lt: #D4956B;
	--ww-ink: #1A1A1A;
	--ww-ink-2: #3D3D3D;
	--ww-ink-3: #6B6B6B;
	--ww-border: #E8E6E3;
	--ww-bg: #FEFEFE;

	/* -------- LAYOUT -------- */
	background: var(--ww-bg);
	border-bottom: 1px solid var(--ww-border);
	padding: 96px 28px 80px;
	font-family: 'Plus Jakarta Sans', sans-serif;
	/* full-bleed: stretch the banner band edge-to-edge across the page (Moz-style) */
	width: 100vw;
	max-width: 100vw;
	margin-left: calc(50% - 50vw);
	margin-right: calc(50% - 50vw);
}
:where(.ww-site-hero-inner){
	max-width: 1080px;
	margin: 0 auto;
}

:where(.ww-site-hero-tag){
	display: inline-block;
	font-size: 13px;
	font-weight: 800;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--ww-copper);
	padding: 5px 4px 9px 0;
	margin: 0 0 22px;
	border-bottom: 2px solid var(--ww-copper);
}

:where(.ww-site-hero-title){
	font-size: clamp(2rem, 4.5vw, 3.25rem);
	font-weight: 800;
	letter-spacing: -0.025em;
	line-height: 1.1;
	color: var(--ww-ink) !important;
	-webkit-text-fill-color: var(--ww-ink) !important;
	margin: 0 0 18px;
	font-family: 'Plus Jakarta Sans', sans-serif !important;
	border: none !important;
	border-bottom: none !important;
	padding: 0;
	background: none !important;
	text-wrap: balance;
	max-width: 780px;
}

:where(.ww-site-hero-sub){
	font-size: 18px;
	color: var(--ww-ink-2);
	line-height: 1.55;
	font-weight: 500;
	max-width: 680px;
	margin: 0 0 22px;
}

:where(.ww-site-hero-meta){
	display: flex;
	align-items: center;
	gap: 12px;
	font-size: 13px;
	color: var(--ww-ink-3);
	font-weight: 600;
	padding-top: 18px;
	border-top: 1px solid var(--ww-border);
	flex-wrap: wrap;
}
:where(.ww-site-hero-meta a){
	color: var(--ww-copper);
	font-weight: 700;
	text-decoration: none;
}
:where(.ww-site-hero-meta a:hover){
	text-decoration: underline;
}
:where(.ww-site-hero-meta-sep){
	width: 3px;
	height: 3px;
	border-radius: 50%;
	background: var(--ww-ink-3);
	opacity: 0.4;
	display: inline-block;
	flex-shrink: 0;
}

/* -------- MOBILE ( <= 768px ) -------- */
@media (max-width: 768px){
	:where(.ww-site-hero){ padding: 56px 20px 44px; }
	:where(.ww-site-hero-tag){
		font-size: 11.5px;
		letter-spacing: 0.16em;
		padding: 4px 4px 7px 0;
		margin-bottom: 14px;
	}
	:where(.ww-site-hero-title){ font-size: 24px; line-height: 1.15; margin-bottom: 12px; }
	:where(.ww-site-hero-sub){ font-size: 15px; margin-bottom: 16px; line-height: 1.5; }
	:where(.ww-site-hero-meta){ font-size: 11px; gap: 8px; padding-top: 14px; }
}

@media (prefers-reduced-motion: reduce){
	:where(.ww-site-hero, .ww-site-hero *){
		transition: none !important;
		animation: none !important;
	}
}
</style>
	<?php
	return ob_get_clean();
}

/* ----------------------------------------------------------------------------
 * HOOKS
 * -------------------------------------------------------------------------- */
add_action( 'generate_before_content', 'ww_hero_render' );
add_filter( 'body_class', 'ww_hero_body_class' );
