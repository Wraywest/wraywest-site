<?php
/**
 * Plugin Name: WW Single Post Template (Phase 4)
 * Description: Single-post chrome only. Readable dek and byline, Used or Researched label from the claim lock, affiliate disclosure only when a non-Kit rel=sponsored link remains, one end module, short author box, TOC collapsed. Does not emit JSON-LD. Does not change the homepage headline, the deck form, calculator formulas, or legal copy. Not a deploy.
 *
 * Locks: Make.com stays rel=sponsored (affiliation unconfirmed). Kit and ConvertKit links are plain. n8n use is limited to the confirmed line. Receipts $810.95 and $17.77 are not rewritten here. Rank Math remains the only JSON-LD emitter; this file does not print or delete a graph.
 */

if ( ! function_exists( 'ww_p4_use_label' ) ) {
	/**
	 * Validated use label for a post slug. Null when this post is not a review
	 * the claim lock has classified. Text is the byline label.
	 *
	 * @return array{key:string,text:string}|null
	 */
	function ww_p4_use_label( $slug ) {
		$slug = (string) $slug;
		$used = array(
			'leadsleap-review-honest-2026'              => 'Used',
			'olsp-system-review-2026'                   => 'Used',
			'systeme-io-review-all-in-one-platform'     => 'Used',
			'hostinger-horizons-review-ai-website-builder' => 'Used',
			'klarna-review'                             => 'Used',
			'n8n-side-hustle-automation'                => 'Used',
		);
		$researched = array(
			'convertkit-review-email-marketing-creators' => 'Researched',
			'convertkit-alternatives'                    => 'Researched',
			'notion-review-creator-workspace-2026'       => 'Researched',
			'repurpose-io-content-automation'            => 'Researched',
			'wise-international-money-transfers'         => 'Researched',
			'robinhood-review'                           => 'Researched',
			'dataforseo-review'                          => 'Researched',
		);
		if ( isset( $used[ $slug ] ) ) {
			return array( 'key' => 'used', 'text' => $used[ $slug ] );
		}
		if ( isset( $researched[ $slug ] ) ) {
			return array( 'key' => 'researched', 'text' => $researched[ $slug ] );
		}
		if ( 'systeme-vs-convertkit' === $slug ) {
			return array( 'key' => 'mixed', 'text' => 'Systeme used, Kit researched' );
		}
		if ( 'make-vs-n8n-side-hustlers' === $slug ) {
			return array( 'key' => 'mixed', 'text' => 'n8n used, Make researched' );
		}
		return null;
	}
}

if ( ! function_exists( 'ww_p4_topic_for_category_slug' ) ) {
	function ww_p4_topic_for_category_slug( $slug ) {
		$map = array(
			'email-marketing' => 'email',
			'side-income'     => 'income',
			'tools'           => 'tools',
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
	}
}

if ( ! function_exists( 'ww_p4_topic_from_name' ) ) {
	function ww_p4_topic_from_name( $name ) {
		$n = strtolower( trim( wp_strip_all_tags_fallback( (string) $name ) ) );
		if ( false !== strpos( $n, 'email' ) ) {
			return 'email';
		}
		if ( false !== strpos( $n, 'tool' ) ) {
			return 'tools';
		}
		if ( false !== strpos( $n, 'side income' ) || 'income' === $n ) {
			return 'income';
		}
		return '';
	}
}

if ( ! function_exists( 'wp_strip_all_tags_fallback' ) ) {
	function wp_strip_all_tags_fallback( $text ) {
		if ( function_exists( 'wp_strip_all_tags' ) ) {
			return wp_strip_all_tags( $text );
		}
		return trim( preg_replace( '/\s+/', ' ', strip_tags( $text ) ) );
	}
}

if ( ! function_exists( 'ww_p4_cluster' ) ) {
	/**
	 * One end module: the cluster pillar and one existing tool.
	 * On the pillar itself, the first link is Start Here so the module does not point at itself.
	 *
	 * @return array{links:array<int,array{href:string,text:string}>}|null
	 */
	function ww_p4_cluster( $slug ) {
		$slug = (string) $slug;
		$groups = array(
			array(
				'pillar' => array( 'https://wraywestblog.com/the-second-income-engine/', 'The Second Income Engine' ),
				'tool'   => array( 'https://wraywestblog.com/tools/quit-9-to-5-calculator/', 'Quit My 9-to-5 Calculator' ),
				'slugs'  => array(
					'how-to-start-creator-business-no-audience',
					'why-side-hustles-fail',
					'mindset-shift-creator-business',
					'creator-burnout-truth',
					'find-your-creator-niche',
					'profitable-creator-niches-full-time-employees-2026',
					'second-income-product-blueprint',
					'content-calendar-publish-working-full-time',
					'solo-creator-metrics-that-matter',
					'ab-testing-one-person-businesses',
					'creator-business-blueprint-second-income-2026',
				),
			),
			array(
				'pillar' => array( 'https://wraywestblog.com/blog/email-marketing-playbook-solo-creators/', 'Email Marketing Playbook for Solo Creators' ),
				'tool'   => array( 'https://wraywestblog.com/tools/lead-magnet-builder/', 'Lead Magnet Builder' ),
				'slugs'  => array(
					'email-marketing-playbook-solo-creators',
					'email-list-building-100-subscribers',
					'lead-magnet-ideas',
					'welcome-email-sequence-template',
					'how-to-write-emails-people-want-to-open',
					'email-deliverability-small-senders',
					'convertkit-review-email-marketing-creators',
					'convertkit-alternatives',
					'systeme-vs-convertkit',
				),
			),
			array(
				'pillar' => array( 'https://wraywestblog.com/tools/', 'Tools' ),
				'tool'   => array( 'https://wraywestblog.com/scoring-methodology/', 'How We Score Tools' ),
				'slugs'  => array(
					'leadsleap-review-honest-2026',
					'olsp-system-review-2026',
					'systeme-io-review-all-in-one-platform',
					'dataforseo-review',
					'hostinger-horizons-review-ai-website-builder',
					'notion-review-creator-workspace-2026',
					'repurpose-io-content-automation',
					'make-vs-n8n-side-hustlers',
					'n8n-side-hustle-automation',
					'google-analytics-creators-setup-guide',
				),
			),
			array(
				'pillar' => array( 'https://wraywestblog.com/blog/best-side-hustles-2026/', 'Best Side Hustles for 2026' ),
				'tool'   => array( 'https://wraywestblog.com/tools/freelance-rate-calculator/', 'Freelance Rate Calculator' ),
				'slugs'  => array(
					'best-side-hustles-2026',
					'ai-side-hustles-that-work',
					'affiliate-marketing-side-hustle',
					'freelance-writing-side-hustle',
					'freelancing-as-creator-trading-time-for-money',
					'how-to-price-freelance-services-day-job',
				),
			),
			array(
				'pillar' => array( 'https://wraywestblog.com/blog/managing-creator-income-taxes-tools/', 'Creator Taxes: Managing Income and Not Going Broke' ),
				'tool'   => array( 'https://wraywestblog.com/tools/quit-9-to-5-calculator/', 'Quit My 9-to-5 Calculator' ),
				'slugs'  => array(
					'managing-creator-income-taxes-tools',
					'1099-k-explained-creators',
					'investing-your-side-income-practical-starting-point',
					'robinhood-review',
					'klarna-review',
					'wise-international-money-transfers',
				),
			),
		);
		$start = array( 'https://wraywestblog.com/start-here/', 'Start Here' );
		foreach ( $groups as $group ) {
			if ( ! in_array( $slug, $group['slugs'], true ) ) {
				continue;
			}
			$pillar_slug = ww_p4_slug_from_url( $group['pillar'][0] );
			$first       = ( $pillar_slug === $slug ) ? $start : $group['pillar'];
			return array(
				'links' => array(
					array( 'href' => $first[0], 'text' => $first[1] ),
					array( 'href' => $group['tool'][0], 'text' => $group['tool'][1] ),
				),
			);
		}
		return null;
	}
}

if ( ! function_exists( 'ww_p4_slug_from_url' ) ) {
	function ww_p4_slug_from_url( $url ) {
		$path = (string) parse_url( $url, PHP_URL_PATH );
		$path = trim( $path, '/' );
		if ( '' === $path ) {
			return '';
		}
		$bits = explode( '/', $path );
		return (string) end( $bits );
	}
}

if ( ! function_exists( 'ww_p4_element_span' ) ) {
	function ww_p4_element_span( $html, $open_pos ) {
		if ( ! preg_match( '/^<([a-zA-Z0-9]+)/', substr( $html, $open_pos ), $m ) ) {
			return null;
		}
		$tag  = strtolower( $m[1] );
		$self = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' );
		$gt   = strpos( $html, '>', $open_pos );
		if ( false === $gt ) {
			return null;
		}
		if ( in_array( $tag, $self, true ) || '/' === substr( $html, $gt - 1, 1 ) ) {
			return array( $open_pos, $gt + 1 );
		}
		$depth = 1;
		$i     = $gt + 1;
		$len   = strlen( $html );
		while ( $i < $len && $depth > 0 ) {
			$next = strpos( $html, '<', $i );
			if ( false === $next ) {
				return null;
			}
			$rest = substr( $html, $next );
			if ( preg_match( '/^<\/' . $tag . '\b/i', $rest ) ) {
				$depth--;
				$end = strpos( $html, '>', $next );
				if ( false === $end ) {
					return null;
				}
				if ( 0 === $depth ) {
					return array( $open_pos, $end + 1 );
				}
				$i = $end + 1;
				continue;
			}
			if ( preg_match( '/^<' . $tag . '\b/i', $rest ) ) {
				$depth++;
			}
			$i = $next + 1;
		}
		return null;
	}
}

if ( ! function_exists( 'ww_p4_find_class' ) ) {
	function ww_p4_find_class( $html, $class, $from = 0 ) {
		$offset = $from;
		$len    = strlen( $html );
		while ( $offset < $len && preg_match( '/class=(["\'])([^"\']*)\1/', $html, $m, PREG_OFFSET_CAPTURE, $offset ) ) {
			$tokens = preg_split( '/\s+/', trim( $m[2][0] ) );
			if ( in_array( $class, $tokens, true ) ) {
				$attr  = $m[0][1];
				$start = strrpos( substr( $html, 0, $attr ), '<' );
				if ( false === $start ) {
					return null;
				}
				return ww_p4_element_span( $html, $start );
			}
			$offset = $m[0][1] + strlen( $m[0][0] );
		}
		return null;
	}
}

if ( ! function_exists( 'ww_p4_remove_class' ) ) {
	function ww_p4_remove_class( $html, $class ) {
		$guard = 0;
		while ( $guard < 12 ) {
			$guard++;
			$span = ww_p4_find_class( $html, $class );
			if ( ! $span ) {
				break;
			}
			$html = substr( $html, 0, $span[0] ) . substr( $html, $span[1] );
		}
		return $html;
	}
}

if ( ! function_exists( 'ww_p4_strip_kit_sponsored' ) ) {
	function ww_p4_strip_kit_sponsored( $html ) {
		return preg_replace_callback(
			'#<a\b[^>]*>#i',
			function ( $m ) {
				$tag = $m[0];
				if ( ! preg_match( '#\bhref\s*=\s*("|\')([^"\']+)\1#i', $tag, $h ) ) {
					return $tag;
				}
				$host = strtolower( (string) parse_url( html_entity_decode( $h[2], ENT_QUOTES, 'UTF-8' ), PHP_URL_HOST ) );
				if ( ! preg_match( '/(^|\.)kit\.com$|(^|\.)convertkit\.com$/', $host ) ) {
					return $tag;
				}
				$tag = preg_replace_callback(
					'#\srel\s*=\s*("|\')([^"\']*)\1#i',
					function ( $r ) {
						$parts = preg_split( '/\s+/', trim( $r[2] ) );
						$parts = array_values(
							array_filter(
								$parts,
								function ( $part ) {
									return 'sponsored' !== strtolower( $part );
								}
							)
						);
						if ( ! $parts ) {
							return '';
						}
						return ' rel=' . $r[1] . implode( ' ', $parts ) . $r[1];
					},
					$tag
				);
				return $tag;
			},
			$html
		);
	}
}

if ( ! function_exists( 'ww_p4_has_sponsored_link' ) ) {
	function ww_p4_has_sponsored_link( $html ) {
		return (bool) preg_match( '#<a\b[^>]*\brel\s*=\s*("|\')[^"\']*\bsponsored\b[^"\']*\1[^>]*>#i', $html );
	}
}

if ( ! function_exists( 'ww_p4_content_region' ) ) {
	function ww_p4_content_region( $html ) {
		$start = stripos( $html, 'entry-content' );
		if ( false === $start ) {
			return $html;
		}
		$end = strlen( $html );
		foreach ( array( 'ww-author-bio', 'ww-end', 'ww-related', 'entry-meta' ) as $marker ) {
			$at = stripos( $html, $marker, $start + 12 );
			if ( false !== $at && $at < $end ) {
				$end = $at;
			}
		}
		return substr( $html, $start, $end - $start );
	}
}

if ( ! function_exists( 'ww_p4_disclosure_html' ) ) {
	function ww_p4_disclosure_html() {
		return '<p class="ww-disc">Some links in this piece are affiliate links, and I may earn a commission if you use them (<a href="https://wraywestblog.com/legal/">full disclosure</a>).</p>';
	}
}

if ( ! function_exists( 'ww_p4_slug_from_html' ) ) {
	function ww_p4_slug_from_html( $html ) {
		if ( preg_match( '#<link rel="canonical" href="https://wraywestblog.com/blog/([^"/]+)/?"#', $html, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '#property="og:url" content="https://wraywestblog.com/blog/([^"/]+)/?"#', $html, $m ) ) {
			return $m[1];
		}
		return '';
	}
}

if ( ! function_exists( 'ww_p4_end_html' ) ) {
	function ww_p4_end_html( $slug ) {
		$cluster = ww_p4_cluster( $slug );
		if ( ! $cluster ) {
			return '';
		}
		$items = '';
		foreach ( $cluster['links'] as $link ) {
			$items .= '<li><a href="' . htmlspecialchars( $link['href'], ENT_QUOTES, 'UTF-8' ) . '">' . htmlspecialchars( $link['text'], ENT_QUOTES, 'UTF-8' ) . '</a></li>';
		}
		return '<section class="ww-end" data-ww-p4="end" aria-label="What to do next"><p class="ww-end-kicker">Next</p><ul class="ww-end-links">' . $items . '</ul></section>';
	}
}

if ( ! function_exists( 'ww_p4_author_html' ) ) {
	function ww_p4_author_html( $picture ) {
		$media = $picture ? '<div class="ww-author-media">' . $picture . '</div>' : '';
		return '<div class="ww-author-bio ww-author-short">' . $media
			. '<div class="ww-author-copy"><p class="ww-author-name">Dwayne Lindsay</p>'
			. '<p>I work the 6:30 AM to 3:00 PM shift and write about what actually works around it.</p>'
			. '<p><a href="https://wraywestblog.com/about/">About</a></p></div></div>';
	}
}

if ( ! function_exists( 'ww_p4_reorder_meta' ) ) {
	function ww_p4_reorder_meta( $html, $label ) {
		$span = ww_p4_find_class( $html, 'ww-site-hero-meta' );
		if ( ! $span ) {
			return $html;
		}
		$block = substr( $html, $span[0], $span[1] - $span[0] );
		if ( ! preg_match( '/^<div\b[^>]*>/i', $block, $open ) ) {
			return $html;
		}
		$inner = substr( $block, strlen( $open[0] ), -strlen( '</div>' ) );
		if ( ! preg_match_all( '#<span\b([^>]*)>(.*?)</span>#s', $inner, $found, PREG_SET_ORDER ) ) {
			return $html;
		}
		$by = '';
		$updated = '';
		$read = '';
		$existing_label = '';
		foreach ( $found as $item ) {
			$attrs = $item[1];
			$body  = $item[2];
			if ( false !== strpos( $attrs, 'ww-site-hero-meta-sep' ) ) {
				continue;
			}
			if ( false !== strpos( $body, 'ww-use-label' ) || false !== strpos( $attrs, 'ww-use-label' ) ) {
				$existing_label = '<span' . $attrs . '>' . $body . '</span>';
				continue;
			}
			$text = strtolower( wp_strip_all_tags_fallback( $body ) );
			if ( false !== strpos( $body, 'ww-byline-author' ) || 0 === strpos( $text, 'by ' ) ) {
				$by = $body;
			} elseif ( false !== strpos( $text, 'min read' ) ) {
				$read = $body;
			} else {
				$updated = $body;
			}
		}
		if ( '' === $by && '' === $updated && '' === $read ) {
			return $html;
		}
		$parts = array();
		if ( '' !== $by ) {
			$parts[] = '<span>' . $by . '</span>';
		}
		if ( '' !== $updated ) {
			$parts[] = '<span>' . $updated . '</span>';
		}
		if ( '' !== $read ) {
			$parts[] = '<span>' . $read . '</span>';
		}
		if ( '' !== $existing_label ) {
			$parts[] = $existing_label;
		} elseif ( is_array( $label ) && ! empty( $label['text'] ) ) {
			$parts[] = '<span class="ww-use-label" data-ww-use="' . htmlspecialchars( $label['key'], ENT_QUOTES, 'UTF-8' ) . '">' . htmlspecialchars( $label['text'], ENT_QUOTES, 'UTF-8' ) . '</span>';
		}
		$sep   = '<span class="ww-site-hero-meta-sep" aria-hidden="true"></span>';
		$rebuilt = $open[0] . implode( $sep, $parts ) . '</div>';
		return substr( $html, 0, $span[0] ) . $rebuilt . substr( $html, $span[1] );
	}
}

if ( ! function_exists( 'ww_p4_collapse_toc' ) ) {
	function ww_p4_collapse_toc( $html ) {
		return preg_replace_callback(
			'#<details\b([^>]*\bclass=(["\'])[^"\']*\bww-toc\b[^"\']*\2[^>]*)>#i',
			function ( $m ) {
				$attrs = preg_replace( '/\sopen(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?/i', '', $m[1] );
				return '<details' . $attrs . '>';
			},
			$html
		);
	}
}

if ( ! function_exists( 'ww_p4_set_topic_attr' ) ) {
	function ww_p4_set_topic_attr( $html ) {
		return preg_replace_callback(
			'#<span class="ww-site-hero-tag"([^>]*)>(.*?)</span>#s',
			function ( $m ) {
				if ( false !== strpos( $m[1], 'data-ww-topic' ) ) {
					return $m[0];
				}
				$topic = ww_p4_topic_from_name( $m[2] );
				if ( '' === $topic ) {
					return $m[0];
				}
				return '<span class="ww-site-hero-tag" data-ww-topic="' . $topic . '"' . $m[1] . '>' . $m[2] . '</span>';
			},
			$html,
			1
		);
	}
}

if ( ! function_exists( 'ww_p4_transform_post_html' ) ) {
	/**
	 * Template chrome for a single post. Returns $html unchanged for every other view.
	 * Idempotent. Does not edit JSON-LD, the deck form, or article sentences other than
	 * the repeated author-box chrome and the blanket disclosure paragraph.
	 */
	function ww_p4_transform_post_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		if ( ! preg_match( '/<body[^>]*\bsingle-post\b/', $html ) ) {
			return $html;
		}

		$html = ww_p4_strip_kit_sponsored( $html );
		$sponsored = ww_p4_has_sponsored_link( ww_p4_content_region( $html ) );
		$html = ww_p4_remove_class( $html, 'ww-disc' );
		if ( $sponsored ) {
			$disc = ww_p4_disclosure_html();
			$toc  = ww_p4_find_class( $html, 'ww-toc' );
			if ( $toc ) {
				$html = substr( $html, 0, $toc[1] ) . $disc . substr( $html, $toc[1] );
			} else {
				$at = stripos( $html, 'entry-content' );
				$gt = false === $at ? false : strpos( $html, '>', $at );
				if ( false !== $gt ) {
					$html = substr( $html, 0, $gt + 1 ) . $disc . substr( $html, $gt + 1 );
				}
			}
		}

		$slug  = ww_p4_slug_from_html( $html );
		$label = ww_p4_use_label( $slug );
		$html  = ww_p4_reorder_meta( $html, $label );
		$html  = ww_p4_set_topic_attr( $html );
		$html  = ww_p4_collapse_toc( $html );

		$author = ww_p4_find_class( $html, 'ww-author-bio' );
		if ( $author ) {
			$block = substr( $html, $author[0], $author[1] - $author[0] );
			if ( false === strpos( $block, 'ww-author-short' ) ) {
				$picture = '';
				if ( preg_match( '#<picture\b.*?</picture>#s', $block, $pic ) ) {
					$picture = $pic[0];
				} elseif ( preg_match( '#<img\b[^>]*>#', $block, $img ) ) {
					$picture = $img[0];
				}
				$html = substr( $html, 0, $author[0] ) . ww_p4_author_html( $picture ) . substr( $html, $author[1] );
			}
		}

		$html = ww_p4_remove_class( $html, 'ww-related-cta' );
		$html = ww_p4_remove_class( $html, 'ww-end' );
		$end  = ww_p4_end_html( $slug );
		if ( '' !== $end ) {
			$related = ww_p4_find_class( $html, 'ww-related' );
			if ( $related ) {
				$html = substr( $html, 0, $related[0] ) . $end . substr( $html, $related[0] );
			} else {
				$close = strripos( $html, '</article>' );
				if ( false !== $close ) {
					$html = substr( $html, 0, $close ) . $end . substr( $html, $close );
				} else {
					$html .= $end;
				}
			}
		}
		return $html;
	}
}

if ( defined( 'ABSPATH' ) ) {
	add_action(
		'template_redirect',
		function () {
			if ( is_admin() || is_feed() || ! is_singular( 'post' ) ) {
				return;
			}
			ob_start( 'ww_p4_transform_post_html' );
		},
		3
	);
}
