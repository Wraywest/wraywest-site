<?php
/*
Plugin Name: WrayWest Keep-or-Cancel Deck Promo
Description: Embedded (never popup) opt-in blocks for the Keep-or-Cancel Deck lead magnet. Placement 1: LeadsLeap review mid-post card. Placement 2: per-post upgrade block on tool-review posts (that tool's verdict) + generic block on other Tools-category posts. Placement 3: /tools/ page top. Placement 4: homepage + /start-here/. Form 3660 (WPForms "keep-cancel-deck") -> Hostinger Reach group "keep-cancel-deck". Delete this file to remove every placement.
Version: 1.0
*/
if ( ! defined( 'ABSPATH' ) ) exit;

class WW_Deck_Promo {

	const FORM_SHORTCODE = '[wpforms id="3660" title="false"]';

	// slug => [tool, score display, verdict line, tease line]
	private static $cards = array(
		'systeme-io-review-all-in-one-platform'        => array( 'Systeme.io', '8.5/10', 'KEEP', 'Its card carries the stats from my real 15-email campaign, opens and all.' ),
		'convertkit-review-email-marketing-creators'   => array( 'Kit (ConvertKit)', '8.1/10', 'KEEP, if you are email-first', 'Its card weighs the free plan against the $39/month cliff.' ),
		'dataforseo-review'                            => array( 'DataForSEO', '3.5/5', 'KEEP', 'Its card shows the pay-per-penny math against $1,500-a-year suites.' ),
		'notion-review-creator-workspace-2026'         => array( 'Notion', '8.4/10', 'KEEP, on the free plan', 'Its card explains why I skip the AI add-on that doubles the price.' ),
		'hostinger-horizons-review-ai-website-builder' => array( 'Hostinger Horizons', '8.2/10', 'KEEP, to launch', 'Its card includes my real builder session on this very site.' ),
		'repurpose-io-content-automation'              => array( 'Repurpose.io', '7.2/10', 'KEEP at 3+ platforms', 'Its card says plainly: no earnings receipt exists, and why I pay anyway.' ),
		'olsp-system-review-2026'                      => array( 'OLSP System', '6.8/10', 'DOWNGRADE: the $7 entry only', 'Its card shows the $810.95 lifetime receipt and the $0.13-per-lead math.' ),
		'wise-international-money-transfers'           => array( 'Wise', '9.2/10', 'KEEP', 'Its card shows the real +$119 affiliate payout screenshot.' ),
		'klarna-review'                                => array( 'Klarna', '6.8/10', 'KEEP, with rules', 'Its card shows the $411.31 I owe right now. The honest side of BNPL.' ),
	);

	public static function init() {
		add_filter( 'the_content', array( __CLASS__, 'inject' ), 12 );
	}

	private static function styles() {
		return '<style>
.ww-deck-promo{border:2px solid #E7D8C5;border-radius:14px;background:#FBF6EF;padding:22px 24px 18px;margin:34px auto;max-width:720px;font-family:inherit}
.ww-deck-promo .wwdp-label{color:#9E5E1E;font-weight:800;font-size:12px;letter-spacing:.14em;text-transform:uppercase;margin:0 0 6px}
.ww-deck-promo h3{margin:0 0 10px;font-size:22px;line-height:1.25;color:#1A1714;font-weight:800}
.ww-deck-promo .wwdp-body{color:#3D3833;font-size:15.5px;margin:0 0 8px}
.ww-deck-promo .wwdp-strip{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 12px}
.ww-deck-promo .wwdp-chip{background:#fff;border:1px solid #E7D8C5;border-radius:999px;padding:4px 12px;font-size:12.5px;font-weight:700;color:#7D4A17}
.ww-deck-promo .wwdp-fine{color:#6B655E;font-size:12.5px;margin:8px 0 0}
.ww-deck-promo .wpforms-container{margin:0}
.ww-deck-promo .wpforms-form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.ww-deck-promo .wpforms-field-container{flex:1;min-width:220px}
.ww-deck-promo .wpforms-field{padding:4px 0!important}
.ww-deck-promo .wpforms-field-label{display:none!important}
.ww-deck-promo .wpforms-field-text{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;overflow:hidden!important;flex:none!important}
.ww-deck-promo div[id$="-field_1-container"]{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;overflow:hidden!important;flex:none!important}
.ww-deck-promo input[type=email]{width:100%;border:1.5px solid #E7D8C5;border-radius:8px;padding:11px 14px;font-size:15px;background:#fff}
.ww-deck-promo .wpforms-submit-container{flex:0 0 auto;padding:4px 0!important}
.ww-deck-promo button[type=submit]{background:#9E5E1E!important;color:#fff!important;border:0!important;border-radius:999px!important;font-weight:800!important;font-size:15px!important;padding:12px 26px!important;cursor:pointer}
.ww-deck-promo button[type=submit]:hover{background:#7D4A17!important}
@media(max-width:640px){.ww-deck-promo{padding:18px 16px 14px}.ww-deck-promo h3{font-size:19px}}
</style>';
	}

	private static function block( $label, $heading, $body, $chips = array(), $tease = '' ) {
		$chip_html = '';
		if ( $chips ) {
			$chip_html = '<div class="wwdp-strip">';
			foreach ( $chips as $c ) { $chip_html .= '<span class="wwdp-chip">' . esc_html( $c ) . '</span>'; }
			$chip_html .= '</div>';
		}
		$tease_html = $tease ? '<p class="wwdp-body">' . esc_html( $tease ) . '</p>' : '';
		return self::styles()
			. '<div class="ww-deck-promo">'
			. '<p class="wwdp-label">' . esc_html( $label ) . '</p>'
			. '<h3>' . esc_html( $heading ) . '</h3>'
			. '<p class="wwdp-body">' . wp_kses_post( $body ) . '</p>'
			. $chip_html
			. $tease_html
			. do_shortcode( self::FORM_SHORTCODE )
			. '<p class="wwdp-fine">One email with the PDF, then the weekly notes. Real numbers, no income promises. Unsubscribe anytime.</p>'
			. '</div>';
	}

	private static function generic_block() {
		return self::block(
			'Free download',
			'Keep, Cancel, or Downgrade: my honest tool verdict cards',
			'13 tools I actually ran, one verdict card each: what it really costs in money and hours, what it earned or saved, the score, and the cheaper swap.',
			array( '$17.77: my real LeadsLeap earnings', '13 verdict cards', 'Real account screenshots' )
		);
	}

	private static function insert_before_pattern( $content, $block, $patterns ) {
		foreach ( $patterns as $pat ) {
			if ( '/' === $pat[0] ) { // regex: insert at match start
				if ( preg_match( $pat, $content, $m, PREG_OFFSET_CAPTURE ) ) {
					$pos = $m[0][1];
					return substr( $content, 0, $pos ) . $block . substr( $content, $pos );
				}
				continue;
			}
			$pos = stripos( $content, $pat );
			if ( $pos !== false ) {
				return substr( $content, 0, $pos ) . $block . substr( $content, $pos );
			}
		}
		return $content . $block; // fallback: append
	}

	public static function inject( $content ) {
		if ( ! in_the_loop() || ! is_main_query() || is_feed() ) return $content;
		$id   = get_the_ID();
		$slug = get_post_field( 'post_name', $id );

		// NEVER touch The Second Income Engine page (different, pre-existing promise).
		if ( 102 === $id ) return $content;

		// Placement 1: LeadsLeap review, mid-post (after the 3rd h2 section = before the 4th h2)
		if ( 'leadsleap-review-honest-2026' === $slug ) {
			$block = self::block(
				'Free: the Keep-or-Cancel Deck',
				'This review is one card in my deck. Get the other 12 verdicts.',
				'The LeadsLeap card above is real: <strong>$17.77 earned in 18 months, 35 referrals, 85,010 visits</strong>. The free deck holds that card plus 12 more tools I actually ran, each with real costs in money and hours, an honest verdict, and the cheaper swap.',
				array( '13 verdict cards', 'KEEP / CANCEL / DOWNGRADE', 'Printable worksheet included' )
			);
			$parts = preg_split( '/(<h2\b)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
			// parts: [pre, <h2, rest1, <h2, rest2, ...] -> h2 #4 starts at index 7
			if ( count( $parts ) >= 9 ) {
				$out = '';
				$h2_seen = 0;
				foreach ( $parts as $p ) {
					if ( strcasecmp( $p, '<h2' ) === 0 ) {
						$h2_seen++;
						if ( 4 === $h2_seen ) { $out .= $block; }
					}
					$out .= $p;
				}
				return $out;
			}
			return self::insert_before_pattern( $content, $block, array( '<h2 id="sources"', '/<h2[^>]*>\s*Sources/i' ) );
		}

		// Placement 2: per-post upgrade on mapped tool reviews (incl. Wise/Klarna/Kit outside the Tools category)
		if ( isset( self::$cards[ $slug ] ) ) {
			list( $tool, $score, $verdict, $tease ) = self::$cards[ $slug ];
			$block = self::block(
				'Free: the Keep-or-Cancel Deck',
				sprintf( '%s in my deck: %s (%s)', $tool, $verdict, $score ),
				sprintf( 'This review has its own verdict card in "Keep, Cancel, or Downgrade": 13 tools I actually ran, with real costs in money and hours, honest verdicts, and cheaper swaps. See how %s scored against the other 12.', $tool ),
				array(),
				$tease
			);
			return self::insert_before_pattern( $content, $block, array( '<h2 id="sources"', '/<h2[^>]*>\s*Sources/i', '/<h2[^>]*>[^<]*Frequently/i' ) );
		}

		// Placement 2b: generic deck block on remaining Tools & Software Reviews posts
		if ( is_singular( 'post' ) && has_category( 22, $id ) ) {
			return self::insert_before_pattern( $content, self::generic_block(), array( '<h2 id="sources"', '/<h2[^>]*>\s*Sources/i', '/<h2[^>]*>[^<]*Frequently/i' ) );
		}

		// Placement 3: /tools/ page, top (after the affiliate disclosure note)
		if ( 105 === $id ) {
			$block = self::block(
				'Free download',
				'This page, as printable verdict cards with the receipts attached',
				'The Keep-or-Cancel Deck is the printable version of this directory: 13 tools, each with real costs in money and hours, what it actually earned or saved (screenshots included), and the cheaper swap.',
				array( '$17.77: my real LeadsLeap earnings', 'Real account screenshots', 'Printable worksheet' )
			);
			if ( preg_match( '/<div class="tl-note".*?<\/div>/s', $content, $m, PREG_OFFSET_CAPTURE ) ) {
				$end = $m[0][1] + strlen( $m[0][0] );
				return substr( $content, 0, $end ) . $block . substr( $content, $end );
			}
			return $block . $content;
		}

		// Placement 4: homepage (before the dark newsletter band) + /start-here/ (before the weekly-email close)
		if ( 100 === $id ) {
			return self::insert_before_pattern( $content, self::generic_block(), array( '<section class="ww-nl"' ) );
		}
		if ( 101 === $id ) {
			return self::insert_before_pattern( $content, self::generic_block(), array( '/<h2[^>]*>[^<]*Get the weekly Creator Engine email/i' ) );
		}

		return $content;
	}
}
WW_Deck_Promo::init();
