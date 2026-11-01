<?php
/**
 * Puts the blog sections on the pages you chose, in the place you chose, without editing the
 * theme. A section is built in the normal page flow just before the footer; when it belongs
 * higher up (before the final call to action, or before the FAQ) it is moved there in the
 * finished page. If that spot does not exist on a page, the section simply stays above the
 * footer. Also hides the theme's own homepage blog block (to avoid showing posts twice) and
 * fixes the alignment of the blog's category buttons.
 *
 * @package A2E_Blog_Posts
 */

namespace A2E\BlogPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Placement.
 */
class Placement {

	/**
	 * Sections that belong on this page.
	 *
	 * @var array
	 */
	protected static $matched = array();

	/**
	 * True when the page is buffered so sections can be moved up.
	 *
	 * @var bool
	 */
	protected static $buffering = false;

	/**
	 * True once the sections were printed (the footer action can run more than once).
	 *
	 * @var bool
	 */
	protected static $done = false;

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'prepare' ), 99 );
		add_action( 'get_footer', array( __CLASS__, 'output' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_filter( 'option_' . THEME_OPT, array( __CLASS__, 'theme_option' ) );
	}

	/**
	 * Does a section belong on the page being viewed?
	 *
	 * @param array $section Section.
	 * @return bool
	 */
	public static function matches( $section ) {
		if ( '1' !== $section['enabled'] ) {
			return false;
		}
		$where = (array) $section['where'];
		if ( in_array( 'front', $where, true ) && is_front_page() ) {
			return true;
		}
		if ( in_array( 'blog', $where, true ) && is_home() && ! is_front_page() ) {
			return true;
		}
		if ( in_array( 'all_pages', $where, true ) && is_page() ) {
			return true;
		}
		if ( in_array( 'services', $where, true ) && is_singular( 'a2e_service' ) ) {
			return true;
		}
		if ( in_array( 'posts', $where, true ) && is_singular( 'post' ) ) {
			return true;
		}
		if ( in_array( 'categories', $where, true ) && is_category() ) {
			return true;
		}
		$pages = array_filter( array_map( 'intval', (array) $section['pages'] ) );
		return $pages && is_page( $pages );
	}

	/**
	 * Work out which sections this page gets, and start the page buffer if any must move up.
	 */
	public static function prepare() {
		if ( is_admin() || is_feed() || is_robots() || is_trackback() || is_embed() || is_404() || wp_doing_ajax() ) {
			return;
		}
		foreach ( settings()['sections'] as $section ) {
			if ( self::matches( $section ) ) {
				self::$matched[] = $section;
			}
		}
		foreach ( self::$matched as $section ) {
			if ( 'footer' !== $section['position'] ) {
				ob_start( array( __CLASS__, 'move' ) );
				self::$buffering = true;
				break;
			}
		}
	}

	/**
	 * Print the sections at the end of the page content (just above the footer). Sections that
	 * belong higher up are wrapped in markers and moved by move().
	 */
	public static function output() {
		if ( self::$done || ! self::$matched ) {
			return;
		}
		self::$done = true;
		foreach ( self::$matched as $section ) {
			$html = Render::section_html( $section );
			if ( '' === $html ) {
				continue;
			}
			if ( 'footer' === $section['position'] || ! self::$buffering ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				continue;
			}
			echo '<!--a2ebp:' . esc_attr( $section['position'] ) . '-->' . $html . '<!--/a2ebp-->'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Move marked sections up to their spot in the finished page.
	 *
	 * @param string $html Page HTML.
	 * @return string
	 */
	public static function move( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, '<!--a2ebp:' ) ) {
			return $html;
		}
		$blocks = array();
		$html   = preg_replace_callback(
			'#<!--a2ebp:([a-z_]+)-->(.*?)<!--/a2ebp-->#s',
			function ( $m ) use ( &$blocks ) {
				$blocks[] = array( $m[1], $m[2] );
				return '<!--a2ebp-slot:' . ( count( $blocks ) - 1 ) . '-->';
			},
			$html
		);
		foreach ( $blocks as $i => $block ) {
			$slot = '<!--a2ebp-slot:' . $i . '-->';
			$at   = strpos( $html, $slot );
			if ( false === $at ) {
				continue;
			}
			$anchor = self::anchor( substr( $html, 0, $at ), $block[0] );
			if ( false === $anchor ) {
				// No such spot on this page: keep it above the footer.
				$html = str_replace( $slot, $block[1], $html );
				continue;
			}
			$html = str_replace( $slot, '', $html );
			$html = substr_replace( $html, $block[1] . "\n", $anchor, 0 );
		}
		return $html;
	}

	/**
	 * Where a section goes, searching the page above the section's default spot.
	 *
	 * @param string $head     Page HTML before the section.
	 * @param string $position before_cta|before_faq.
	 * @return int|false Offset to insert at.
	 */
	protected static function anchor( $head, $position ) {
		if ( 'before_faq' === $position ) {
			$faq = strrpos( $head, 'id="faq"' );
			if ( false !== $faq ) {
				$start = strrpos( substr( $head, 0, $faq ), '<section' );
				if ( false !== $start ) {
					return $start;
				}
			}
		}
		return strrpos( $head, '<section class="cta-band"' );
	}

	/**
	 * Front-end only: hide the theme's own homepage blog block while a section of this plugin
	 * is on the homepage, so posts are not shown twice. Your theme setting is not changed.
	 *
	 * @param mixed $value Theme settings.
	 * @return mixed
	 */
	public static function theme_option( $value ) {
		if ( is_admin() || ! is_array( $value ) || wp_doing_ajax() ) {
			return $value;
		}
		$s = settings();
		if ( '1' === $s['hide_theme_block'] && homepage_section_on() ) {
			$value['show_blog'] = '0';
		}
		return $value;
	}

	/**
	 * Styles: section columns and in-content use, plus the category button fix.
	 */
	public static function assets() {
		wp_enqueue_style( 'a2ebp', A2EBP_URL . 'assets/front.css', wp_style_is( 'a2e-main', 'registered' ) ? array( 'a2e-main' ) : array(), VERSION );
		if ( '1' === settings()['fix_chips'] ) {
			// The blog's category buttons are links with a fixed height, so their text sat at the
			// top. Centre it (the free tools filter uses real buttons, which centre on their own).
			wp_add_inline_style( 'a2ebp', 'a.chip{display:inline-flex;align-items:center;justify-content:center;line-height:1.2;text-decoration:none;white-space:nowrap}' );
		}
	}
}
