<?php
/* Plugin Name: WW Injectors
 * Description: Archive banner + Rank Math sameAs schema + hero preload. Migrated from WPCode snippets 2135/2068/2065 after WPCode auto-insert cache corruption (2026-06-04).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ===== Archive Banner (was WPCode 2135) ===== */
add_action( 'loop_start', function ( $query ) {
    static $injected = false;
    if ( $injected ) {
        return;
    }
    if ( ! $query->is_main_query() ) {
        return;
    }
    if ( is_singular() || is_front_page() ) {
        return;
    }
    if ( ! ( is_home() || is_archive() || is_search() ) ) {
        return;
    }
    $injected = true;

    if ( is_home() ) {
        $title = 'Articles';
        $sub   = 'Practical strategies, honest reviews, and real lessons from building a creator business with a full-time job.';
    } elseif ( is_category() ) {
        $title = single_cat_title( '', false );
        $sub   = wp_strip_all_tags( category_description() );
    } elseif ( is_tag() ) {
        $title = single_tag_title( '', false );
        $sub   = wp_strip_all_tags( tag_description() );
    } elseif ( is_search() ) {
        $title = 'Search results for: ' . esc_html( get_search_query() );
        $sub   = '';
    } else {
        $title = wp_strip_all_tags( get_the_archive_title() );
        $sub   = wp_strip_all_tags( get_the_archive_description() );
    }

    if ( empty( $title ) ) {
        return;
    }

    ?>
<section class="ww-site-hero ww-site-hero-archive" role="banner">
  <div class="ww-site-hero-inner">
    <h1 class="ww-site-hero-title"><?php echo esc_html( $title ); ?></h1>
    <?php if ( ! empty( $sub ) ) : ?>
    <p class="ww-site-hero-sub"><?php echo esc_html( $sub ); ?></p>
    <?php endif; ?>
  </div>
</section>
    <?php
}, 5, 1 );

/* ===== Rank Math sameAs schema (was WPCode 2068) ===== */
add_filter( 'rank_math/json_ld', function ( $data, $jsonld ) {
    // 2026-07-08: +GitHub (github.com/Wraywest, profile links back to the blog);
    // -Mastodon (account banned 2026-06-11, dead URL = negative corroboration).
    $org_same_as = array(
        'https://facebook.com/wraywess',
        'https://instagram.com/wraywess',
        'https://www.threads.net/wraywess',
        'https://x.com/dwaynelindsay18',
        'https://bsky.app/profile/wraywestblog.com',
        'https://github.com/Wraywest',
    );
    // Solo creator: the brand profiles corroborate the author, so the Person carries the full set
    // (external E-E-A-T corroboration - 2026-06-25 audit).
    $person_same_as = array(
        'https://x.com/dwaynelindsay18',
        'https://instagram.com/wraywess',
        'https://facebook.com/wraywess',
        'https://www.threads.net/wraywess',
        'https://bsky.app/profile/wraywestblog.com',
        'https://github.com/Wraywest',
    );
    foreach ( $data as $key => $entity ) {
        if ( ! is_array( $entity ) ) {
            continue;
        }
        $type  = isset( $entity['@type'] ) ? $entity['@type'] : '';
        $types = is_array( $type ) ? $type : array( $type );
        $is_org    = in_array( 'Organization', $types, true );
        $is_person = in_array( 'Person', $types, true );
        if ( $is_org && ! $is_person ) {
            $existing = ( isset( $entity['sameAs'] ) && is_array( $entity['sameAs'] ) ) ? $entity['sameAs'] : array();
            $merged   = array_values( array_unique( array_merge( $existing, $org_same_as ) ) );
            $data[ $key ]['sameAs'] = $merged;
        }
        if ( $is_person && ! $is_org ) {
            $existing = ( isset( $entity['sameAs'] ) && is_array( $entity['sameAs'] ) ) ? $entity['sameAs'] : array();
            $merged   = array_values( array_unique( array_merge( $existing, $person_same_as ) ) );
            $data[ $key ]['sameAs'] = $merged;
        }
        // Cast BreadcrumbList ListItem positions to integers (Rank Math/Astra emit them as quoted
        // strings, which makes the breadcrumb rich-result ineligible on every page). Schema.org
        // requires position to be an Integer. Fix added 2026-06-22 (top audit finding).
        if ( in_array( 'BreadcrumbList', $types, true ) && ! empty( $entity['itemListElement'] ) && is_array( $entity['itemListElement'] ) ) {
            foreach ( $entity['itemListElement'] as $i => $li ) {
                if ( is_array( $li ) && isset( $li['position'] ) ) {
                    $data[ $key ]['itemListElement'][ $i ]['position'] = (int) $li['position'];
                }
            }
        }
        // Remove the stray Article node Rank Math emits on static pages + the homepage/blog
        // index (a hub/tool/home page is not an Article; the WebPage node remains). Keeps real
        // blog posts (BlogPosting) untouched. Added 2026-06-22 (audit: schema H3).
        if ( in_array( 'Article', $types, true ) && ! is_singular( 'post' ) && ( is_page() || is_front_page() || is_home() ) ) {
            unset( $data[ $key ] );
        }
    }
    // Fix the dual-typed Person+Organization publisher node. Rank Math emits the WrayWest
    // publisher as @type ["Person","Organization"] at @id .../#person, which is invalid (one
    // node cannot be both). The real author (Dwayne Lindsay) is already a separate Person node,
    // so re-type the publisher to Organization only, give it a proper #organization @id, and
    // repoint the publisher references. Added 2026-06-23 (audit: schema H2 / E-E-A-T).
    $old_id = untrailingslashit( home_url() ) . '/#person';
    $new_id = untrailingslashit( home_url() ) . '/#organization';
    foreach ( $data as $k => $entity ) {
        if ( ! is_array( $entity ) ) { continue; }
        $t = isset( $entity['@type'] ) ? (array) $entity['@type'] : array();
        if ( in_array( 'Person', $t, true ) && in_array( 'Organization', $t, true ) && ( ( $entity['@id'] ?? '' ) === $old_id ) ) {
            $data[ $k ]['@type'] = 'Organization';
            $data[ $k ]['@id' ]  = $new_id;
        }
    }
    array_walk_recursive( $data, function ( &$v, $key ) use ( $old_id, $new_id ) {
        if ( '@id' === $key && $v === $old_id ) { $v = $new_id; }
    } );
    return $data;
}, 99, 2 );

/* ===== Breadcrumb position integer fix (output level) =====
   Rank Math unsets BreadcrumbList from the main @graph and outputs it as a separate
   JSON-LD block, running it through wp_kses_post_deep() which casts the integer
   ListItem positions to quoted strings ("position":"1"). Schema.org requires an
   Integer, so the breadcrumb rich result is ineligible sitewide. This can only be
   corrected on the final output, so we buffer wp_head and cast position strings back
   to integers inside JSON-LD only. Added 2026-06-22 (top audit finding). */
add_action( 'wp_head', function () { ob_start(); }, 0 );
add_action( 'wp_head', function () {
    $html = ob_get_clean();
    if ( $html === false ) { return; }
    if ( strpos( $html, 'BreadcrumbList' ) !== false ) {
        $html = preg_replace( '/("position"\s*:\s*)"(\d+)"/', '$1$2', $html );
    }
    echo $html;
}, 99999 );

/* ===== Hero image preload (was WPCode 2065) =====
   Non-home banners are now the pure-CSS "Copper Masthead" (2026-07-06) with no hero image,
   so the old non-home preload of wraywest-hero-banner-wide.webp was dead weight (a high-priority
   image fetch nothing rendered) and has been removed. Front-page image hero preload kept. */
add_action( 'wp_head', function() {
    // Front page only: this image is CSS-scoped to body.home. is_home() (the /blog/
    // posts page) was ALSO matching, preloading 21.5KB at top priority that never
    // rendered there (PSI orphan-preload finding, 2026-07-08).
    if ( is_front_page() ) {
        echo '<link rel="preload" as="image" href="/wp-content/uploads/2026/04/Honest-strategies-for-building-a-second-income-while-working-full-time.png.webp" fetchpriority="high" type="image/webp">' . "\n";
        return;
    }
    // /blog/ archive: preload the FIRST card's featured image instead - it is the
    // real LCP element, and LiteSpeed lazy-load otherwise hides it from the parser
    // (818ms measured resource-load delay). imagesrcset mirrors the responsive pick.
    if ( is_home() ) {
        global $wp_query;
        if ( ! empty( $wp_query->posts[0] ) ) {
            $tid = get_post_thumbnail_id( $wp_query->posts[0]->ID );
            if ( $tid ) {
                $src    = wp_get_attachment_image_url( $tid, 'large' );
                $srcset = wp_get_attachment_image_srcset( $tid, 'large' );
                $sizes  = wp_get_attachment_image_sizes( $tid, 'large' );
                if ( $src ) {
                    echo '<link rel="preload" as="image" href="' . esc_url( $src ) . '"'
                        . ( $srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( $sizes ) . '"' : '' )
                        . ' fetchpriority="high">' . "\n";
                }
            }
        }
    }
}, 1 );

/* ===== LiteSpeed lazy-load excludes (2026-07-08 perf pass) =====
   (1) The header logo is above the fold on every page - lazy-loading it just adds
   a JS round-trip before it paints. (2) The /blog/ first-card image is the archive
   LCP element; excluding it lets the preload above actually take effect. */
add_filter( 'litespeed_media_lazy_img_excludes', function( $list ) {
    $list[] = 'ww-logo-gold-circ';
    if ( is_home() ) {
        global $wp_query;
        if ( ! empty( $wp_query->posts[0] ) ) {
            $tid = get_post_thumbnail_id( $wp_query->posts[0]->ID );
            $src = $tid ? wp_get_attachment_image_url( $tid, 'large' ) : '';
            if ( $src ) {
            	$list[] = wp_basename( $src );
            }
        }
    }
    return $list;
} );

/* ===== Header logo: serve the existing 12.8KB WebP twin instead of the 31KB PNG ===== */
add_filter( 'generate_logo_output', 'ww_logo_webp_swap' );
add_filter( 'get_custom_logo', 'ww_logo_webp_swap' );
function ww_logo_webp_swap( $html ) {
    return str_replace( 'ww-logo-gold-circ.png', 'ww-logo-gold-circ.png.webp', $html );
}
