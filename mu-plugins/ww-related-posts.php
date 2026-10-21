<?php
/* Plugin Name: WW Related Posts
 * Description: Adds a "Keep reading" block (3 related posts from the same category) + a subscribe nudge after single-post content, so articles don't dead-end. From the 2026-06 site audit (P2). Reversible: delete this file.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function ww_related_primary_cat( $post_id ) {
	$rm = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
	if ( $rm ) { $t = get_term( $rm, 'category' ); if ( $t && ! is_wp_error( $t ) ) return $t; }
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0] : null;
}

add_action( 'generate_after_content', function () {
	if ( ! is_singular( 'post' ) ) return;
	$pid = get_the_ID();
	$cat = ww_related_primary_cat( $pid );

	$args = array(
		'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 3,
		'post__not_in' => array( $pid ), 'ignore_sticky_posts' => true,
		'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true,
	);
	if ( $cat ) $args['cat'] = $cat->term_id;
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {            // fallback: latest regardless of category
		$args2 = $args; unset( $args2['cat'] ); $q = new WP_Query( $args2 );
	}
	if ( ! $q->have_posts() ) { wp_reset_postdata(); return; }
	?>
<style id="ww-related-css">
.ww-related{max-width:1080px;margin:3.5rem auto 0;padding:0 4px;font-family:'Plus Jakarta Sans',sans-serif;}
.ww-related-h{font-family:'Instrument Serif',Georgia,serif;font-size:clamp(1.5rem,3vw,2rem);color:#1A1714;margin:0 0 1.25rem;letter-spacing:-0.015em;}
.ww-related-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.ww-related-card{display:flex;flex-direction:column;background:#fff;border:1px solid rgba(26,23,20,0.10);border-radius:14px;overflow:hidden;transition:transform .2s ease,box-shadow .2s ease;}
.ww-related-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(26,23,20,0.10);}
.ww-related-card a{text-decoration:none;color:inherit;display:flex;flex-direction:column;height:100%;}
.ww-related-thumb{aspect-ratio:1200/630;background-size:cover;background-position:center;background-color:#EDE8E1;}
.ww-related-body{padding:14px 16px 18px;display:flex;flex-direction:column;gap:7px;flex:1;}
.ww-related-cat{font-size:0.66rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9E5E1E;}
.ww-related-title{font-family:'Instrument Serif',Georgia,serif;font-size:1.18rem;line-height:1.22;color:#1A1714;}
.ww-related-cta{margin-top:2rem;text-align:center;background:#0F1729;border-radius:14px;padding:26px 22px;}
.ww-related-cta p{color:#F4ECE3;font-size:1.05rem;margin:0 0 14px;font-weight:500;}
.ww-related-cta a{display:inline-block;background:#9E5E1E;font-weight:600;font-size:0.92rem;padding:11px 26px;border-radius:9999px;text-decoration:none!important;box-shadow:0 4px 14px rgba(158,94,30,0.3);}
.ww-related-cta a span,.ww-related-cta a:link span,.ww-related-cta a:visited span{color:#ffffff!important;-webkit-text-fill-color:#ffffff!important;}
.ww-related-cta a:hover{background:#8a5119;}
@media(max-width:768px){.ww-related-grid{grid-template-columns:1fr;}}
</style>
<section class="ww-related" aria-label="Keep reading">
	<h2 class="ww-related-h">Keep reading</h2>
	<div class="ww-related-grid">
		<?php while ( $q->have_posts() ) : $q->the_post();
			$rid = get_the_ID();
			// medium_large (768w): thumbs render ~220px desktop / ~400px mobile-1col.
			// 'large' (1024w) shipped ~112KB of never-lazy CSS backgrounds per view (2026-07-08).
			$img = get_the_post_thumbnail_url( $rid, 'medium_large' );
			$rc  = ww_related_primary_cat( $rid );
			?>
		<article class="ww-related-card">
			<a href="<?php the_permalink(); ?>">
				<span class="ww-related-thumb"<?php if ( $img ) echo ' style="background-image:url(\'' . esc_url( $img ) . '\')"'; ?>></span>
				<span class="ww-related-body">
					<?php if ( $rc ) : ?><span class="ww-related-cat"><?php echo esc_html( $rc->name ); ?></span><?php endif; ?>
					<span class="ww-related-title"><?php echo esc_html( get_the_title() ); ?></span>
				</span>
			</a>
		</article>
		<?php endwhile; ?>
	</div>
	<div class="ww-related-cta">
		<p>New here? Get the honest side-income playbook, one email a week.</p>
		<a href="<?php echo esc_url( home_url( '/start-here/' ) ); ?>"><span>Start Here &rarr;</span></a>
	</div>
</section>
	<?php
	wp_reset_postdata();
}, 25 );
