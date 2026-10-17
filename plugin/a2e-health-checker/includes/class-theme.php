<?php
/**
 * A2E Prime integration: makes the checker a first-class tool of the theme.
 *
 * - Registers it in the theme's tool list (a2e_tools), so it appears everywhere the
 *   theme lists tools: header Free Tools mega menu, mobile menu, footer, the Free Tools
 *   page, homepage, 404 and search grids, related tools and service guides.
 * - Moves its page under /free-tools/ so it uses the theme's tool page, like the others.
 * - Fills the tool box on that page with the checker (the theme has no engine for it).
 * - Updates every "13 free tools" heading to the live tool count.
 *
 * Nothing in the theme is edited. Every change is a filter or a targeted, no-op-if-not-
 * found replacement on the page HTML.
 *
 * @package A2E_Health
 */

namespace A2E\Health;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme integration.
 */
class Theme {

	const SLUG = 'ebay-account-health-checker';

	/**
	 * Per-request context for the HTML pass.
	 *
	 * @var array
	 */
	private static $ctx = array();

	/**
	 * Cached tool count.
	 *
	 * @var int|null
	 */
	private static $count = null;

	/**
	 * Recursion guard for counting.
	 *
	 * @var bool
	 */
	private static $counting = false;

	/**
	 * Hooks. Filters only take effect when the A2E Prime theme calls them.
	 */
	public static function init() {
		if ( '1' !== setting( 'theme_tool' ) ) {
			return;
		}
		add_filter( 'a2e_tools', array( __CLASS__, 'register_tool' ), 5 );
		add_filter( 'a2e_services', array( __CLASS__, 'services' ) );
		add_filter( 'gettext', array( __CLASS__, 'gettext' ), 10, 3 );
		add_filter( 'a2e_settings_schema', array( __CLASS__, 'schema' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_sync_page' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_old_page' ), 2 );
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 6 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
	}

	/**
	 * Is the A2E Prime theme (with its tool system) running?
	 *
	 * @return bool
	 */
	public static function active() {
		return function_exists( 'a2e_tools' ) && function_exists( 'a2e_tool_url' );
	}

	/**
	 * The tool's URL on the theme (/free-tools/{slug}/).
	 *
	 * @return string
	 */
	public static function url() {
		return self::active() ? a2e_tool_url( self::SLUG ) : '';
	}

	/**
	 * Live number of tools, computed once and safe against recursion.
	 *
	 * @return int
	 */
	public static function count() {
		if ( null !== self::$count ) {
			return self::$count;
		}
		if ( self::$counting || ! function_exists( 'a2e_tools' ) ) {
			return 0;
		}
		self::$counting = true;
		$n              = count( a2e_tools() );
		self::$counting = false;
		self::$count    = $n;
		return $n;
	}

	/**
	 * The tool entry, in the same shape as the theme's own tools. Plain strings on
	 * purpose: the theme's tool list can be built before translations load.
	 *
	 * @return array
	 */
	public static function entry() {
		return array(
			'slug'      => self::SLUG,
			'name'      => 'eBay Account Health Checker',
			'icon'      => 'shield',
			'cat'       => 'growth',
			'short'     => 'See your exact eBay seller level and how many clean sales it takes to reach Top Rated.',
			'seo_title' => 'eBay Account Health Checker: Check Your Seller Level (Free)',
			'meta'      => 'Free eBay account health checker. See whether you are Below Standard, Above Standard or Top Rated, and exactly how many clean sales it takes to reach Top Rated.',
			'intro'     => 'Enter the numbers from your eBay Seller Dashboard to see your exact seller level, checked against eBay US standards, and the precise number of clean sales it takes to reach Top Rated.',
			'explainer' => '<h2>How eBay decides your seller level</h2>
<p>eBay evaluates every US seller on the 20th of each month and gives one of three levels: <strong>Below Standard</strong>, <strong>Above Standard</strong> or <strong>Top Rated</strong>. If you had 400 or more transactions in the last three months, eBay looks at those three months. Otherwise it looks at the last twelve.</p>
<h3>What puts an account Below Standard</h3>
<ul><li><strong>A transaction defect rate over 2%.</strong> A defect is a cancellation you made because you could not fulfil the order, or a case closed without your resolution. eBay only counts it when 4 or more different buyers are involved.</li><li><strong>Too many cases closed without your resolution:</strong> more than 2, or more than 0.3% of transactions, whichever is higher.</li></ul>
<h3>What Top Rated needs</h3>
<ul><li>A transaction defect rate of 0.5% or less.</li><li>Cases closed without your resolution: no more than 2, or 0.3%, whichever is higher.</li><li>Late shipments: no more than 5, or 3%, whichever is higher.</li><li>Valid tracking uploaded within your handling time on at least 95% of transactions.</li><li>An account at least 90 days old, with 100 or more transactions and $1,000 or more in sales to US buyers over the last 12 months.</li></ul>
<p>Miss any one of these and the account stays Above Standard, even if everything else is perfect. Tracking is the one sellers miss most often.</p>
<h3>Top Rated Plus</h3>
<p>Top Rated Plus is a badge on individual listings, not an account level. Once you are Top Rated, listings that offer same-day or 1-business-day handling and free returns within 30 days or more get the badge and a 10% discount on final value fees.</p>
<h3>How the clean-sales number works</h3>
<p>Every new sale with no defect, no case, no late shipment and valid on-time tracking lowers your rates. The checker works out the smallest number of those sales that brings every metric inside the Top Rated limits, and estimates the date from your recent sales pace. eBay uses a rolling window, so recovery can be faster as old problems age out.</p>',
			'faqs'      => array(
				array(
					'q' => 'Why does my account show Above Standard when my defect rate is low?',
					'a' => 'Top Rated needs every requirement at the same time. The most common miss is tracking: valid tracking must be uploaded within your handling time and scanned by the carrier on at least 95% of orders. Late shipments over the limit, or not yet meeting the 100 sales and $1,000 minimums, also keep an account at Above Standard.',
				),
				array(
					'q' => 'How often does eBay update my seller level?',
					'a' => 'Every month on the 20th. Your Seller Dashboard also shows a projected level based on your current numbers, which is what this checker mirrors.',
				),
				array(
					'q' => 'Can a Below Standard account recover?',
					'a' => 'Yes. Stop the cause of defects (usually stock problems and cancellations), resolve buyer issues before they become cases, and upload valid tracking on time. Clean months steadily replace bad ones in the evaluation window. Recovering and protecting account health is a core part of our account management service.',
				),
				array(
					'q' => 'Is Top Rated Plus a seller level?',
					'a' => 'No. Top Rated is the account level. Top Rated Plus is a badge on listings that offer 1-day handling and 30-day free returns once you are Top Rated, and it earns a 10% final value fee discount on those listings.',
				),
				array(
					'q' => 'Where do I find these numbers?',
					'a' => 'In Seller Hub, open Performance and then Seller level or Service metrics. It shows your transactions, defects, late shipments, cases closed without seller resolution and tracking for the current evaluation period.',
				),
			),
		);
	}

	/**
	 * Add the checker to the theme's tools (first, as the newest tool, unless set otherwise).
	 *
	 * @param array $tools Tools keyed by slug.
	 * @return array
	 */
	public static function register_tool( $tools ) {
		if ( ! is_array( $tools ) ) {
			return $tools;
		}
		unset( $tools[ self::SLUG ] ); // Replace the theme's own entry where one exists.
		$entry = array( self::SLUG => self::entry() );
		return '1' === setting( 'tool_first' ) ? $entry + $tools : $tools + $entry;
	}

	/**
	 * Show the checker on account-health related service guides.
	 *
	 * @param array $services Services.
	 * @return array
	 */
	public static function services( $services ) {
		if ( ! is_array( $services ) ) {
			return $services;
		}
		foreach ( $services as $slug => $svc ) {
			if ( isset( $svc['tools'] ) && is_array( $svc['tools'] ) && in_array( 'ebay-defect-rate-calculator', $svc['tools'], true ) && ! in_array( self::SLUG, $svc['tools'], true ) ) {
				array_unshift( $services[ $slug ]['tools'], self::SLUG );
			}
		}
		return $services;
	}

	/**
	 * The theme's headings that say "13" become the live count.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function gettext( $translation, $text, $domain ) {
		if ( 'a2e' !== $domain ) {
			return $translation;
		}
		static $strings = array(
			'All 13 tools →'                                     => 1,
			'13 free seller tools'                                    => 1,
			'See all 13 free tools'                                   => 1,
			'13 free eBay tools to <em>price, list and grow.</em>'    => 1,
			'All pages, 13 free tools and service guides are ready, and every existing URL still works.' => 1,
		);
		if ( ! isset( $strings[ $text ] ) ) {
			return $translation;
		}
		$n = self::count();
		return $n ? str_replace( '13', (string) $n, $translation ) : $translation;
	}

	/**
	 * The announcement bar's default text (A2E > Site Settings) counts the new tool too.
	 * No tool counting here: the schema is read while the theme builds its options.
	 *
	 * @param array $schema Settings schema.
	 * @return array
	 */
	public static function schema( $schema ) {
		if ( ! is_array( $schema ) ) {
			return $schema;
		}
		foreach ( $schema as $g => $group ) {
			if ( isset( $group['fields']['announcement'][2] ) ) {
				$schema[ $g ]['fields']['announcement'][2] = str_replace( '13 free eBay seller tools', '14 free eBay seller tools', $schema[ $g ]['fields']['announcement'][2] );
			}
		}
		return $schema;
	}

	/**
	 * Once per version, move the checker's page under /free-tools/ so it is a real tool
	 * page like the others (in the sitemap, with the theme's tool layout). The old URL is
	 * then 301-redirected by the theme's own redirect map.
	 */
	public static function maybe_sync_page() {
		$key = VERSION . '|' . get_template();
		if ( get_option( 'a2ehc_tool_sync' ) === $key ) {
			return;
		}
		self::sync_page();
		update_option( 'a2ehc_tool_sync', $key, false );
	}

	/**
	 * Make sure a page exists at /free-tools/ebay-account-health-checker/.
	 */
	public static function sync_page() {
		if ( 'a2e-prime' !== get_template() ) {
			return;
		}
		$hub = get_page_by_path( 'free-tools' );
		if ( ! $hub ) {
			return; // The virtual /free-tools/{slug}/ route still serves the tool.
		}
		$id   = (int) get_option( 'a2ehc_page_id' );
		$page = $id ? get_post( $id ) : null;
		$at   = get_page_by_path( 'free-tools/' . self::SLUG );

		// A page already sits at the tool URL (for example one the theme created).
		if ( $at && ( ! $page || (int) $at->ID !== (int) $page->ID ) ) {
			if ( $page && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
				update_option( 'a2ehc_old_page_id', (int) $page->ID, false );
			}
			update_option( 'a2ehc_page_id', (int) $at->ID, false );
			// Give an empty page the checker, so it still works if the theme option is turned off.
			if ( '' === trim( (string) $at->post_content ) ) {
				wp_update_post(
					array(
						'ID'           => (int) $at->ID,
						'post_content' => "<!-- wp:shortcode -->\n[a2e_health_checker]\n<!-- /wp:shortcode -->",
					)
				);
			}
			return;
		}

		if ( ! $page || 'page' !== $page->post_type || 'trash' === $page->post_status ) {
			$new = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => 'eBay Account Health Checker',
					'post_name'    => self::SLUG,
					'post_parent'  => (int) $hub->ID,
					'post_content' => "<!-- wp:shortcode -->\n[a2e_health_checker]\n<!-- /wp:shortcode -->",
				)
			);
			if ( $new && ! is_wp_error( $new ) ) {
				update_option( 'a2ehc_page_id', (int) $new, false );
			}
			return;
		}

		if ( (int) $page->post_parent !== (int) $hub->ID ) {
			wp_update_post(
				array(
					'ID'          => (int) $page->ID,
					'post_parent' => (int) $hub->ID,
					'post_name'   => self::SLUG,
				)
			);
		}
	}

	/**
	 * Send an old standalone copy of the page to the tool page (only when one was kept).
	 */
	public static function redirect_old_page() {
		$old = (int) get_option( 'a2ehc_old_page_id' );
		if ( ! $old || ! self::active() || ! is_page( $old ) || is_preview() ) {
			return;
		}
		wp_safe_redirect( self::url(), 301 );
		exit;
	}

	/**
	 * Load the checker's assets on its tool page (the box is filled after the page renders).
	 */
	public static function enqueue() {
		if ( self::is_tool_page() ) {
			wp_enqueue_style( 'a2ehc' );
			wp_enqueue_script( 'a2ehc' );
		}
	}

	/**
	 * Are we on the checker's tool page?
	 *
	 * @return bool
	 */
	public static function is_tool_page() {
		return function_exists( 'a2e_current_tool' ) && self::SLUG === a2e_current_tool();
	}

	/**
	 * Prepare the replacements, then buffer the page so they can be applied.
	 */
	public static function start_buffer() {
		if ( ! self::active() || is_admin() || is_feed() || is_robots() || is_trackback() || wp_doing_ajax() ) {
			return;
		}
		self::$ctx = array(
			'n'      => self::count(),
			'pairs'  => array(),
			'widget' => '',
		);

		// The tool page: render the checker now (not inside the buffer callback).
		if ( self::is_tool_page() ) {
			ob_start();
			Widget::render( '1' === setting( 'highlight' ), true );
			self::$ctx['widget'] = '<div class="a2ehc-toolwrap">' . ob_get_clean() . '</div>';
		}

		$tools = a2e_tools();
		if ( isset( $tools[ self::SLUG ] ) && function_exists( 'a2e_tool_card' ) ) {
			// Homepage tools section: feature the checker first, keep the grid at five cards.
			if ( is_front_page() ) {
				$featured = array( 'amazon-to-ebay-profit-calculator', 'ebay-title-generator', 'ebay-product-research-scorecard', 'ebay-bulk-profit-calculator', 'ebay-dropshipping-income-calculator' );
				$old      = '';
				$new      = a2e_tool_card( $tools[ self::SLUG ], 0 );
				foreach ( $featured as $i => $slug ) {
					if ( ! isset( $tools[ $slug ] ) ) {
						$old = '';
						break;
					}
					$old .= a2e_tool_card( $tools[ $slug ], $i );
					if ( $i < 4 ) {
						$new .= a2e_tool_card( $tools[ $slug ], $i + 1 );
					}
				}
				if ( $old ) {
					self::$ctx['pairs'][] = array( $old, $new, 'tools-teaser__grid' );
				}
			}
			// Blog posts: the "Free tools" sidebar list starts with the checker. The same
			// list markup is used in the header menus, so anchor to the sidebar card.
			if ( is_singular( 'post' ) && function_exists( 'a2e_icon' ) && isset( $tools['ebay-fee-calculator'] ) ) {
				$li = function ( $slug ) use ( $tools ) {
					return '<li><a href="' . esc_url( a2e_tool_url( $slug ) ) . '">' . a2e_icon( $tools[ $slug ]['icon'] ) . esc_html( $tools[ $slug ]['name'] ) . '</a></li>';
				};
				self::$ctx['pairs'][] = array(
					$li( 'ebay-fee-calculator' ),
					$li( self::SLUG ) . "\n\t\t\t\t\t\t\t" . $li( 'ebay-fee-calculator' ),
					'<p class="aside-card__title">' . esc_html__( 'Free tools', 'a2e' ) . '</p>',
				);
			}
		}

		ob_start( array( __CLASS__, 'filter_html' ) );
	}

	/**
	 * Apply the prepared replacements to the finished page.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public static function filter_html( $html ) {
		if ( ! is_string( $html ) || false === stripos( $html, '<html' ) ) {
			return $html;
		}
		if ( ! empty( self::$ctx['widget'] ) ) {
			$html = self::replace_block( $html, '<div class="tool" data-tool="' . self::SLUG . '"', self::$ctx['widget'] );
		}
		if ( ! empty( self::$ctx['pairs'] ) ) {
			foreach ( self::$ctx['pairs'] as $pair ) {
				// Each replacement only applies inside its own section (after its anchor).
				$from = strpos( $html, $pair[2] );
				if ( false === $from ) {
					continue;
				}
				$at = strpos( $html, $pair[0], $from );
				if ( false !== $at ) {
					$html = substr_replace( $html, $pair[1], $at, strlen( $pair[0] ) );
				}
			}
		}
		// Announcement bar and the Free Tools page description.
		if ( ! empty( self::$ctx['n'] ) ) {
			$html = preg_replace( '/\b13(\s+free\s+eBay\s+seller\s+tools)/i', self::$ctx['n'] . '$1', $html );
			// The theme's built-in header has "13" typed into the Free Tools badge.
			$html = str_replace( '<span class="nav__badge">13</span>', '<span class="nav__badge">' . (int) self::$ctx['n'] . '</span>', $html );
		}
		return $html;
	}

	/**
	 * Replace a whole <div> element (with everything nested inside it) that starts with
	 * $start. Returns the HTML unchanged if the element is not found.
	 *
	 * @param string $html        HTML.
	 * @param string $start       Opening tag prefix.
	 * @param string $replacement Replacement HTML.
	 * @return string
	 */
	public static function replace_block( $html, $start, $replacement ) {
		$pos = strpos( $html, $start );
		if ( false === $pos ) {
			return $html;
		}
		$depth = 0;
		$i     = $pos;
		$len   = strlen( $html );
		while ( $i < $len ) {
			$open  = strpos( $html, '<div', $i );
			$close = strpos( $html, '</div>', $i );
			if ( false === $close ) {
				return $html;
			}
			if ( false !== $open && $open < $close ) {
				++$depth;
				$i = $open + 4;
			} else {
				--$depth;
				$i = $close + 6;
				if ( 0 === $depth ) {
					return substr( $html, 0, $pos ) . $replacement . substr( $html, $i );
				}
			}
		}
		return $html;
	}
}
