<?php
/**
 * Plugin Name:       A2E Header Menu (Mega Menu Editor)
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       Keeps the A2E Prime designed mega menu and makes it fully editable from Appearance > Menus. Add, rename, reorder or remove any header item (Pricing, Shop, anything), choose which items open the designed Services or Free Tools mega panels, build card dropdowns with icons, and edit the header button. The phone menu follows the same menu. Nothing in the theme is edited.
 * Version:           2.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-header-menu
 *
 * @package A2E_Header_Menu
 */

namespace A2E\HeaderMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION   = '2.0.0';
const OPTION    = 'a2e_hm_settings';
const MENU_NAME = 'Header Menu';
const THEME_OPT = 'a2e_prime';
const LOCATION  = 'primary';

define( 'A2E_HM_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-render.php';
require_once __DIR__ . '/includes/class-walker.php';
require_once __DIR__ . '/includes/class-admin.php';

/**
 * Settings with defaults (empty label / link = the theme's own).
 *
 * @return array
 */
function settings() {
	$defaults = array(
		'mode'        => 'mega',
		'cta_show'    => '1',
		'cta_label'   => '',
		'cta_url'     => '',
		'aside_show'  => '1',
		'aside_label' => '',
		'aside_url'   => '',
		'client_area' => '1',
	);
	$saved = get_option( OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * Is the A2E Prime theme running?
 *
 * @return bool
 */
function theme_active() {
	return function_exists( 'a2e_opt' ) && function_exists( 'a2e_tools' );
}

/**
 * The menu shown in the header (the one assigned to the theme's header location).
 *
 * @return \WP_Term|false
 */
function header_menu() {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations[ LOCATION ] ) ) {
		$menu = wp_get_nav_menu_object( (int) $locations[ LOCATION ] );
		if ( $menu ) {
			return $menu;
		}
	}
	return false;
}

/**
 * A menu name that is not taken yet.
 *
 * @param string $name Wanted name.
 * @return string
 */
function unique_menu_name( $name ) {
	$try = $name;
	$n   = 2;
	while ( wp_get_nav_menu_object( $try ) ) {
		$try = $name . ' ' . $n++;
	}
	return $try;
}

/**
 * Show a menu in the header location.
 *
 * @param int $menu_id Menu id.
 */
function assign_to_location( $menu_id ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$locations = is_array( $locations ) ? $locations : array();
	$locations[ LOCATION ] = (int) $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Add one menu item.
 *
 * @param int    $menu_id Menu id.
 * @param string $title   Title.
 * @param array  $target  array( 'page' => id ) or array( 'url' => url ).
 * @param int    $parent  Parent item id.
 * @param array  $meta    Item meta.
 * @return int Item id.
 */
function add_item( $menu_id, $title, $target, $parent = 0, $meta = array() ) {
	$data = array(
		'menu-item-title'     => $title,
		'menu-item-status'    => 'publish',
		'menu-item-parent-id' => (int) $parent,
	);
	if ( ! empty( $target['page'] ) ) {
		$data['menu-item-type']      = 'post_type';
		$data['menu-item-object']    = 'page';
		$data['menu-item-object-id'] = (int) $target['page'];
	} elseif ( ! empty( $target['url'] ) ) {
		$data['menu-item-type'] = 'custom';
		$data['menu-item-url']  = $target['url'];
	} else {
		return 0;
	}
	$id = wp_update_nav_menu_item( $menu_id, 0, $data );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return (int) $id;
}

/**
 * Build "Header Menu" as an exact copy of the designed header and show it in the header.
 *
 * @return int Menu id (0 on failure).
 */
function seed_menu() {
	if ( ! theme_active() ) {
		return 0;
	}
	$menu_id = wp_create_nav_menu( unique_menu_name( MENU_NAME ) );
	if ( is_wp_error( $menu_id ) ) {
		return 0;
	}
	$page = function ( $path ) {
		$p = get_page_by_path( $path );
		return ( $p && 'publish' === $p->post_status ) ? array( 'page' => (int) $p->ID ) : array( 'url' => a2e_url( $path ) );
	};

	add_item( $menu_id, __( 'Services', 'a2e' ), $page( 'amazon-to-ebay-dropshipping-service' ), 0, array( '_a2e_mega' => 'services' ) );
	add_item( $menu_id, __( 'Free Tools', 'a2e' ), $page( 'free-tools' ), 0, array( '_a2e_mega' => 'tools' ) );
	add_item( $menu_id, __( 'Pricing', 'a2e' ), $page( 'pricing' ) );
	if ( function_exists( 'a2e_shop_page_id' ) && a2e_shop_page_id() ) {
		add_item( $menu_id, __( 'Shop', 'a2e' ), array( 'page' => (int) a2e_shop_page_id() ) );
	}
	$results = add_item( $menu_id, __( 'Results', 'a2e' ), $page( 'portfolio' ) );
	if ( $results ) {
		add_item( $menu_id, __( 'Portfolio', 'a2e' ), $page( 'portfolio' ), $results, array( '_a2e_icon' => 'grid', '_a2e_desc' => __( 'Recent client projects', 'a2e' ) ) );
		$cs = get_post_type_archive_link( 'a2e_case_study' );
		if ( $cs ) {
			add_item( $menu_id, __( 'Case studies', 'a2e' ), array( 'url' => $cs ), $results, array( '_a2e_icon' => 'line', '_a2e_desc' => __( 'Problem, work, result', 'a2e' ) ) );
		}
		$rv = get_post_type_archive_link( 'a2e_testimonial' );
		if ( $rv ) {
			add_item( $menu_id, __( 'Client reviews', 'a2e' ), array( 'url' => $rv ), $results, array( '_a2e_icon' => 'star', '_a2e_desc' => '{rating} ★ from {reviews} reviews' ) );
		}
		add_item( $menu_id, __( 'About A2E', 'a2e' ), $page( 'about' ), $results, array( '_a2e_icon' => 'users', '_a2e_desc' => 'Selling on eBay since {since}' ) );
	}
	$blog = (int) get_option( 'page_for_posts' );
	add_item( $menu_id, __( 'Blog', 'a2e' ), $blog ? array( 'page' => $blog ) : array( 'url' => a2e_blog_url() ) );
	add_item( $menu_id, __( 'Contact', 'a2e' ), $page( 'contact' ) );

	assign_to_location( $menu_id );
	return (int) $menu_id;
}

/**
 * Upgrade from version 1 (which built a plain menu) and first-time setup.
 * The old menu is kept, renamed, so nothing is lost.
 */
function migrate() {
	if ( ! theme_active() ) {
		return;
	}
	$was_v1 = '1' === get_option( 'a2e_hm_seeded' );
	if ( $was_v1 ) {
		$old = wp_get_nav_menu_object( MENU_NAME );
		if ( $old ) {
			wp_update_nav_menu_object( (int) $old->term_id, array( 'menu-name' => unique_menu_name( MENU_NAME . ' (old)' ) ) );
		}
	}
	if ( $was_v1 || ! header_menu() ) {
		$named = wp_get_nav_menu_object( MENU_NAME );
		if ( $named && ! $was_v1 ) {
			assign_to_location( (int) $named->term_id );
		} else {
			seed_menu();
		}
	}
	// The plugin decides the theme's menu mode on the front end, so keep the stored value
	// at the theme default. Deactivating the plugin then restores the designed header.
	$o = get_option( THEME_OPT );
	if ( is_array( $o ) && isset( $o['use_wp_menus'] ) && '1' === $o['use_wp_menus'] ) {
		$o['use_wp_menus'] = '0';
		update_option( THEME_OPT, $o );
	}
	delete_option( 'a2e_hm_seeded' );
	update_option( 'a2e_hm_version', VERSION, false );
}

register_activation_hook(
	__FILE__,
	function () {
		update_option( 'a2e_hm_version', '', false );
	}
);

add_action(
	'admin_init',
	function () {
		if ( get_option( 'a2e_hm_version' ) !== VERSION ) {
			migrate();
		}
	}
);

/**
 * Front end: tell the theme to draw the header from the menu (editable mode) or from its
 * built-in markup. Only on the front end, so A2E > Site Settings shows the stored value.
 *
 * @param mixed $value Theme settings.
 * @return mixed
 */
function front_menu_mode( $value ) {
	if ( is_admin() ) {
		return $value;
	}
	$value                 = is_array( $value ) ? $value : array();
	$value['use_wp_menus'] = 'mega' === settings()['mode'] ? '1' : '0';
	return $value;
}
add_filter( 'option_' . THEME_OPT, __NAMESPACE__ . '\\front_menu_mode' );
add_filter( 'default_option_' . THEME_OPT, __NAMESPACE__ . '\\front_menu_mode' );

/**
 * Draw the header menu with the mega-menu walker.
 */
add_filter(
	'wp_nav_menu_args',
	function ( $args ) {
		if ( is_admin() || empty( $args['theme_location'] ) || LOCATION !== $args['theme_location'] || 'mega' !== settings()['mode'] || ! theme_active() ) {
			return $args;
		}
		$args['walker']      = new Mega_Walker();
		$args['menu_class']  = 'nav__list nav__list--a2e';
		$args['container']   = false;
		$args['depth']       = 2;
		$args['fallback_cb'] = false;
		$args['items_wrap']  = '<ul id="%1$s" class="%2$s">%3$s</ul>';
		return $args;
	}
);

/**
 * The header button markup: null = leave the theme's, '' = hide.
 *
 * @param bool $mobile Phone menu version.
 * @return string|null
 */
function cta_html( $mobile = false ) {
	$s = settings();
	if ( '1' === $s['cta_show'] && '' === $s['cta_label'] && '' === $s['cta_url'] ) {
		return null;
	}
	if ( '1' !== $s['cta_show'] ) {
		return '';
	}
	$label = '' !== $s['cta_label'] ? $s['cta_label'] : ( $mobile ? __( 'Get a free store audit', 'a2e' ) : __( 'Free store audit', 'a2e' ) );
	$url   = '' !== $s['cta_url'] ? $s['cta_url'] : a2e_url( 'quote-request' );
	$cls   = $mobile ? 'btn btn--primary' : 'btn btn--primary btn--sm header-cta';
	return '<a class="' . $cls . '" href="' . esc_url( $url ) . '"><span>' . esc_html( $label ) . '</span>' . Render::icon( 'arrow-right', 'btn__icon' ) . '</a>';
}

/**
 * Prepare the phone menu and header button, then buffer the page to place them. The
 * theme draws those parts in fixed markup, so they are swapped by targeted replacement
 * (left untouched if the markup is not found).
 */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() || ! theme_active() || is_feed() || is_robots() || is_trackback() || wp_doing_ajax() ) {
			return;
		}
		$buf = array(
			'mobile' => 'mega' === settings()['mode'] ? Render::mobile_nav() : '',
			'cta'    => cta_html( false ),
			'mcta'   => cta_html( true ),
		);
		if ( '' === $buf['mobile'] && null === $buf['cta'] ) {
			return;
		}
		$GLOBALS['a2e_hm_buf'] = $buf;
		ob_start( __NAMESPACE__ . '\\filter_html' );
	},
	7
);

/**
 * Apply the phone menu and header button replacements.
 *
 * @param string $html Page HTML.
 * @return string
 */
function filter_html( $html ) {
	$b = isset( $GLOBALS['a2e_hm_buf'] ) ? $GLOBALS['a2e_hm_buf'] : null;
	if ( ! $b || ! is_string( $html ) || false === stripos( $html, '<html' ) ) {
		return $html;
	}
	if ( '' !== $b['mobile'] ) {
		$html = preg_replace_callback(
			'#(<div class="mnav__panel">\s*<nav\b[^>]*>)(.*?)(</nav>)#s',
			function ( $m ) use ( $b ) {
				return $m[1] . $b['mobile'] . $m[3];
			},
			$html,
			1
		);
	}
	if ( null !== $b['cta'] ) {
		$html = preg_replace_callback(
			'#<a class="btn btn--primary btn--sm header-cta"[^>]*>.*?</a>#s',
			function () use ( $b ) {
				return $b['cta'];
			},
			$html,
			1
		);
		$pos = strpos( $html, '<div class="mnav__cta">' );
		if ( false !== $pos ) {
			$tail = preg_replace_callback(
				'#<a class="btn btn--primary"[^>]*>.*?</a>#s',
				function () use ( $b ) {
					return (string) $b['mcta'];
				},
				substr( $html, $pos ),
				1
			);
			$html = substr( $html, 0, $pos ) . $tail;
		}
	}
	return $html;
}

/**
 * The theme opens the phone menu a fixed 72px from the top, so while the announcement bar
 * is showing its first row sits under the header. Start it at the header's real bottom.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! theme_active() ) {
			return;
		}
		echo "<script>(function(){var b=document.querySelector('[data-burger]'),m=document.querySelector('[data-mnav]'),h=document.querySelector('[data-header]');if(!b||!m||!h){return;}function fit(){if(!m.hidden){m.style.top=Math.max(0,Math.round(h.getBoundingClientRect().bottom))+'px';}}b.addEventListener('click',function(){requestAnimationFrame(fit);});window.addEventListener('resize',fit);window.addEventListener('scroll',fit,{passive:true});})();</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	99
);

add_action(
	'plugins_loaded',
	function () {
		Admin::init();
	}
);
