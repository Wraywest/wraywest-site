<?php
/**
 * Plugin Name: WW Phase 4 Home and Archives
 * Description: Homepage teaser corrections and two archive columns, plus a Published label on blog-index dates that match a captured datePublished. Category date words and chip wrapping are CSS. Does not change the homepage headline, the deck form, consent, or schema. Not a deploy.
 */

if ( ! function_exists( 'ww_p4_published_dates' ) ) {
	function ww_p4_published_dates() {
		static $map = null;
		if ( null === $map ) {
			$map = require __DIR__ . '/ww-phase4-dates.php';
		}
		return $map;
	}
}

if ( ! function_exists( 'ww_p4_home_modules_html' ) ) {
	function ww_p4_home_modules_html() {
		$money = array(
			array( 'https://wraywestblog.com/blog/1099-k-explained-creators/', '1099-K Explained: What Side Income Earners Owe in 2026' ),
			array( 'https://wraywestblog.com/blog/klarna-review/', 'Klarna Review 2026: Should Side Earners Use Buy Now, Pay Later?' ),
			array( 'https://wraywestblog.com/blog/managing-creator-income-taxes-tools/', 'Creator Tax Survival' ),
		);
		$content = array(
			array( 'https://wraywestblog.com/blog/ab-testing-one-person-businesses/', 'A/B Testing for Solo Businesses' ),
			array( 'https://wraywestblog.com/blog/content-calendar-publish-working-full-time/', 'My Content Calendar' ),
		);
		$block = function ( $id, $name, $href, $posts ) {
			$items = '';
			foreach ( $posts as $post ) {
				$items .= '<li><a class="ww18-card ww18-post" href="' . htmlspecialchars( $post[0], ENT_QUOTES, 'UTF-8' ) . '"><h4>' . htmlspecialchars( $post[1], ENT_QUOTES, 'UTF-8' ) . '</h4></a></li>';
			}
			$safe = htmlspecialchars( $name, ENT_QUOTES, 'UTF-8' );
			return '<div class="ww18-cat" data-ww-p4="home-modules"><div class="ww18-cat-head"><h3 id="' . $id . '">' . $safe . '</h3><a href="' . htmlspecialchars( $href, ENT_QUOTES, 'UTF-8' ) . '">All posts<span class="screen-reader-text"> in ' . $safe . '</span> <span aria-hidden="true">→</span></a></div><ul class="ww18-posts-grid" role="list" aria-labelledby="' . $id . '">' . $items . '</ul></div>';
		};
		return $block( 'ww18-cat-money', 'Money & Finance', 'https://wraywestblog.com/blog/category/money-finance/', $money )
			. $block( 'ww18-cat-content', 'Content Strategy', 'https://wraywestblog.com/blog/category/content-strategy/', $content );
	}
}

if ( ! function_exists( 'ww_p4_transform_home_html' ) ) {
	/**
	 * Narrow homepage edits on the captured public markup.
	 * Replaces two overclaiming teaser sentences. Adds Money and Content Strategy
	 * columns using existing category names, URLs, and archive titles. No excerpts,
	 * so no new tax figures. Leaves the headline, receipts, and deck form bytes alone.
	 */
	function ww_p4_transform_home_html( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, 'id="wwl-h"' ) ) {
			return $html;
		}
		if ( preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
			return $html;
		}
		$replacements = array(
			'<p>What it cost, what broke, and when automation actually helps a side hustle.</p>' => '<p>Some posts on this site were made with n8n.</p>',
			'<p>Make if you want it to just run, n8n if you can self-host. Pricing, ease, hosting, and AI…</p>' => '<p>Some posts made with n8n; Make researched.</p>',
		);
		foreach ( $replacements as $from => $to ) {
			if ( false !== strpos( $html, $from ) ) {
				$html = str_replace( $from, $to, $html );
			}
		}
		if ( false === strpos( $html, 'data-ww-p4="home-modules"' ) && false !== strpos( $html, 'id="ww-home-band-subscribe"' ) ) {
			$html = str_replace( '<span id="ww-home-band-subscribe"', ww_p4_home_modules_html() . '<span id="ww-home-band-subscribe"', $html );
		}
		return $html;
	}
}

if ( ! function_exists( 'ww_p4_transform_blog_index_html' ) ) {
	/**
	 * Label a blog-index time "Published" only when its datetime attribute
	 * matches a datePublished captured from that post's JSON-LD. Any other
	 * datetime is left exactly as published on the page.
	 */
	function ww_p4_transform_blog_index_html( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, 'class="vb-meta"' ) ) {
			return $html;
		}
		if ( false !== strpos( $html, 'id="wwl-h"' ) || preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
			return $html;
		}
		$html = str_replace(
			array(
				'<p>What it cost, what broke, and when automation actually helps a side hustle.</p>',
				'<p class="vb-excerpt">What it cost, what broke, and when automation actually helps a side hustle.</p>',
				'<p>Make if you want it to just run, n8n if you can self-host. Pricing, ease, hosting, and AI…</p>',
				'<p class="vb-excerpt">Make if you want it to just run, n8n if you can self-host. Pricing, ease, hosting, and AI compared.</p>',
			),
			array(
				'<p>Some posts on this site were made with n8n.</p>',
				'<p class="vb-excerpt">Some posts on this site were made with n8n.</p>',
				'<p>Some posts made with n8n; Make researched.</p>',
				'<p class="vb-excerpt">Some posts made with n8n; Make researched.</p>',
			),
			$html
		);
		$map = ww_p4_published_dates();
		return preg_replace_callback(
			'#<p class="vb-meta">(.*?)</p>#s',
			function ( $meta ) use ( $map ) {
				$inner = preg_replace_callback(
					'#<time\b([^>]*\bdatetime="([^"]+)"[^>]*)>([^<]*)</time>#',
					function ( $time ) use ( $map ) {
						$kind = $map[ $time[2] ] ?? '';
						$text = $time[3];
						if ( '' === $kind || false !== stripos( $text, $kind ) ) {
							return $time[0];
						}
						return '<time' . $time[1] . '>' . $kind . ' ' . $text . '</time>';
					},
					$meta[1]
				);
				return '<p class="vb-meta">' . $inner . '</p>';
			},
			$html
		);
	}
}

if ( defined( 'ABSPATH' ) ) {
	add_action(
		'template_redirect',
		function () {
			if ( is_admin() || is_feed() || is_singular( 'post' ) ) {
				return;
			}
			if ( is_front_page() ) {
				ob_start( 'ww_p4_transform_home_html' );
				return;
			}
			if ( is_home() ) {
				ob_start( 'ww_p4_transform_blog_index_html' );
			}
		},
		4
	);
}
