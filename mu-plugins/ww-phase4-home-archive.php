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

if ( ! function_exists( 'ww_p4_transform_tools_html' ) ) {
	/**
	 * Tools index labels only. Kit, Notion, and Repurpose.io are researched.
	 * OLSP is affiliate receipts, not hands-on product use. Make.com stays
	 * Researched. Wise and Klarna stay Used hands-on. No link rel is changed.
	 */
	function ww_p4_transform_tools_html( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, 'class="ti-row"' ) && false === strpos( $html, 'class="ti-name"' ) ) {
			return $html;
		}
		if ( preg_match( '/<body[^>]*\bsingle-post\b/', $html ) || false === strpos( $html, 'page-id-105' ) ) {
			return $html;
		}
		$swaps = array(
			'>ConvertKit</h4><p class="ti-meta">Email Marketing <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span></p>'
				=> '>ConvertKit</h4><p class="ti-meta">Email Marketing <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Researched</span></p>',
			'>Notion</h4><p class="ti-meta">Productivity <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span></p>'
				=> '>Notion</h4><p class="ti-meta">Productivity <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Researched</span></p>',
			'>Repurpose.io</h4><p class="ti-meta">Content Automation <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span></p>'
				=> '>Repurpose.io</h4><p class="ti-meta">Content Automation <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Researched</span></p>',
			'>OLSP System</h4><p class="ti-meta">Affiliate Training <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-used">Used hands-on</span></p>'
				=> '>OLSP System</h4><p class="ti-meta">Affiliate Training <span class="ti-dot" aria-hidden="true">&middot;</span> <span class="ti-researched">Affiliate receipts</span></p>',
		);
		foreach ( $swaps as $from => $to ) {
			if ( false !== strpos( $html, $from ) ) {
				$html = str_replace( $from, $to, $html );
			}
		}
		return $html;
	}
}

if ( ! function_exists( 'ww_p4_transform_start_here_html' ) ) {
	/**
	 * Start Here: the chef shift is told in the bio card only. The long
	 * "six posts" block stays in the page, folded, so every existing link
	 * remains. One in-page link points at the path quiz. The deck form is
	 * not rewritten.
	 */
	function ww_p4_transform_start_here_html( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, 'class="sh-tldr"' ) ) {
			return $html;
		}
		if ( preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
			return $html;
		}
		$dup = 'I build WrayWest around a 6:30 AM to 3:00 PM kitchen shift. ';
		if ( false !== strpos( $html, $dup ) ) {
			$html = str_replace( $dup, '', $html );
		}
		if ( false === strpos( $html, 'id="sh-quiz"' ) ) {
			$html = str_replace( '<section class="sh-quiz">', '<section class="sh-quiz" id="sh-quiz">', $html );
		}
		if ( false === strpos( $html, 'href="#sh-quiz"' ) ) {
			$link = '<p class="sh-next"><a href="#sh-quiz">Which path fits you right now?</a></p>';
			$html = str_replace( '</div></div></section><section class="sh-bio">', $link . '</div></div></section><section class="sh-bio">', $html );
		}
		if ( false === strpos( $html, 'ww-p4-folded' ) && function_exists( 'ww_p4_element_span' ) ) {
			if ( preg_match( '/<section\b[^>]*class=(["\'])[^"\']*\bsh-featured\b[^"\']*\1[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE ) ) {
				$start = $m[0][1];
				$span  = ww_p4_element_span( $html, $start );
				if ( $span ) {
					$block   = substr( $html, $span[0], $span[1] - $span[0] );
					$wrapped = '<details class="ww-p4-folded"><summary>Six posts most readers start with</summary>' . $block . '</details>';
					$html    = substr( $html, 0, $span[0] ) . $wrapped . substr( $html, $span[1] );
				}
			}
		}
		return $html;
	}
}

if ( ! function_exists( 'ww_p4_transform_engine_html' ) ) {
	/**
	 * Engine: fold each existing "Picture this" case so the six pillars stay
	 * reachable from the nav. Does not rewrite pillar copy, the Make.com line,
	 * the deck form, or the FAQPage JSON-LD block.
	 */
	function ww_p4_transform_engine_html( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, 'class="fw-tldr"' ) ) {
			return $html;
		}
		if ( preg_match( '/<body[^>]*\bsingle-post\b/', $html ) || ! function_exists( 'ww_p4_element_span' ) ) {
			return $html;
		}
		$guard  = 0;
		$offset = 0;
		while ( $guard < 8 ) {
			$guard++;
			if ( ! preg_match( '/<div\b[^>]*class=(["\'])[^"\']*\bfw-case\b[^"\']*\1[^>]*>/', $html, $m, PREG_OFFSET_CAPTURE, $offset ) ) {
				break;
			}
			$start  = $m[0][1];
			$before = substr( $html, max( 0, $start - 60 ), 60 );
			if ( false !== strpos( $before, 'ww-p4-case' ) ) {
				$offset = $start + 10;
				continue;
			}
			$span = ww_p4_element_span( $html, $start );
			if ( ! $span ) {
				break;
			}
			$block   = substr( $html, $span[0], $span[1] - $span[0] );
			$wrapped = '<details class="ww-p4-case"><summary>Picture this</summary>' . $block . '</details>';
			$html    = substr( $html, 0, $span[0] ) . $wrapped . substr( $html, $span[1] );
			$offset  = $span[0] + strlen( $wrapped );
		}
		return $html;
	}
}

if ( ! function_exists( 'ww_p4_transform_calculator_html' ) ) {
	/**
	 * Calculator pages: helper text only. Defaults, input attributes, scripts,
	 * and WPForms markup stay byte-identical. Percent defaults are not given
	 * a new source.
	 */
	function ww_p4_transform_calculator_html( $html ) {
		if ( ! is_string( $html ) || preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
			return $html;
		}
		$quit = false !== strpos( $html, 'page-id-2302' ) && false !== strpos( $html, 'id="qc-w2"' );
		$rate = false !== strpos( $html, 'page-id-3456' ) && false !== strpos( $html, 'id="frc-take"' );
		if ( ! $quit && ! $rate ) {
			return $html;
		}
		$scripts = array();
		$html    = preg_replace_callback(
			'#<script\b[^>]*>.*?</script>#s',
			function ( $m ) use ( &$scripts ) {
				$key              = '%%WWP4SCRIPT' . count( $scripts ) . '%%';
				$scripts[ $key ] = $m[0];
				return $key;
			},
			$html
		);
		if ( $quit ) {
			$html = str_replace(
				'FICA + federal + state. Default 28% (typical for $75k W-2).',
				'FICA + federal + state. Default 28%. Not a tax figure for your return.',
				$html
			);
		}
		if ( $rate ) {
			$html = str_replace(
				'Set aside for taxes and a margin. 30 percent is a common starting point.',
				'Set aside for taxes and a margin. Default is 30 percent. Not a sourced tax rate.',
				$html
			);
		}
		foreach ( $scripts as $key => $script ) {
			$html = str_replace( $key, $script, $html );
		}
		return $html;
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
				return;
			}
			if ( is_page( 'start-here' ) ) {
				ob_start( 'ww_p4_transform_start_here_html' );
				return;
			}
			if ( is_page( 'the-second-income-engine' ) ) {
				ob_start( 'ww_p4_transform_engine_html' );
				return;
			}
			if ( is_page( 'tools' ) ) {
				ob_start( 'ww_p4_transform_tools_html' );
				return;
			}
			if ( is_page( array( 'quit-9-to-5-calculator', 'freelance-rate-calculator', 'lead-magnet-builder' ) ) ) {
				ob_start( 'ww_p4_transform_calculator_html' );
			}
		},
		4
	);
}
