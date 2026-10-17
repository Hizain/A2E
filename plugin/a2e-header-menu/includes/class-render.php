<?php
/**
 * Markup builders. Every panel reproduces the A2E Prime header markup exactly, so the
 * theme's own CSS and JavaScript style and drive it with no changes.
 *
 * @package A2E_Header_Menu
 */

namespace A2E\HeaderMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render helpers.
 */
class Render {

	/**
	 * Icons offered for dropdown cards (all exist in the A2E Prime icon set).
	 *
	 * @return array name => label
	 */
	public static function icons() {
		$names = array( 'arrow-right', 'grid', 'line', 'star', 'users', 'user', 'sparkle', 'shield', 'award', 'trophy', 'badge', 'tag', 'trend', 'chart', 'gauge', 'pie', 'calculator', 'percent', 'dollar', 'coins', 'wallet', 'credit-card', 'receipt', 'cart', 'store', 'store-front', 'box', 'package', 'truck', 'route', 'list', 'clipboard', 'file', 'book', 'calendar', 'clock', 'timer', 'chat', 'mail', 'phone', 'whatsapp', 'headset', 'handshake', 'megaphone', 'rocket', 'bolt', 'target', 'layers', 'globe', 'home', 'building', 'heart', 'gear', 'wand', 'pen', 'eye', 'search', 'link', 'info', 'play', 'quote' );
		$out   = array();
		foreach ( $names as $n ) {
			$out[ $n ] = ucwords( str_replace( '-', ' ', $n ) );
		}
		return $out;
	}

	/**
	 * Theme icon markup (empty string when the theme is not active).
	 *
	 * @param string $name  Icon.
	 * @param string $class Extra class.
	 * @return string
	 */
	public static function icon( $name, $class = '' ) {
		return function_exists( 'a2e_icon' ) ? a2e_icon( $name, $class ) : '';
	}

	/**
	 * Dropdown style of a menu item: auto | services | tools.
	 *
	 * @param int $item_id Menu item id.
	 * @return string
	 */
	public static function mega( $item_id ) {
		$v = (string) get_post_meta( $item_id, '_a2e_mega', true );
		return in_array( $v, array( 'services', 'tools' ), true ) ? $v : 'auto';
	}

	/**
	 * Card text for a sub-item, with {rating}, {reviews} and {since} filled from the theme.
	 *
	 * @param \WP_Post $item Menu item.
	 * @return string
	 */
	public static function desc( $item ) {
		$text = (string) get_post_meta( $item->ID, '_a2e_desc', true );
		if ( '' === $text && ! empty( $item->description ) ) {
			$text = (string) $item->description;
		}
		if ( '' === $text ) {
			return '';
		}
		$opt = function ( $k ) {
			return function_exists( 'a2e_opt' ) ? (string) a2e_opt( $k ) : '';
		};
		return strtr(
			$text,
			array(
				'{rating}'  => $opt( 'rating' ),
				'{reviews}' => $opt( 'reviews' ),
				'{since}'   => $opt( 'since' ),
			)
		);
	}

	/**
	 * Card icon for a sub-item.
	 *
	 * @param int $item_id Menu item id.
	 * @return string
	 */
	public static function item_icon( $item_id ) {
		$v = (string) get_post_meta( $item_id, '_a2e_icon', true );
		return isset( self::icons()[ $v ] ) ? $v : 'arrow-right';
	}

	/**
	 * Link attributes (target / rel / title) for a menu item.
	 *
	 * @param \WP_Post $item Item.
	 * @return string
	 */
	public static function link_attrs( $item ) {
		$a = '';
		if ( ! empty( $item->target ) ) {
			$a .= ' target="' . esc_attr( $item->target ) . '"';
		}
		$rel = trim( (string) $item->xfn );
		if ( '_blank' === $item->target && false === strpos( $rel, 'noopener' ) ) {
			$rel = trim( $rel . ' noopener' );
		}
		if ( $rel ) {
			$a .= ' rel="' . esc_attr( $rel ) . '"';
		}
		if ( ! empty( $item->attr_title ) ) {
			$a .= ' title="' . esc_attr( $item->attr_title ) . '"';
		}
		return $a;
	}

	/**
	 * The designed Services mega panel.
	 *
	 * @param string $id Panel id.
	 * @return string
	 */
	public static function services_panel( $id ) {
		if ( ! function_exists( 'a2e_services' ) || ! function_exists( 'a2e_featured_service_slugs' ) ) {
			return '';
		}
		$services = a2e_services();
		$s        = settings();
		$monthly  = array();
		if ( function_exists( 'a2e_get_plans' ) ) {
			foreach ( (array) a2e_get_plans() as $p ) {
				if ( isset( $p['interval'] ) && 'month' === $p['interval'] ) {
					$monthly[] = $p;
				}
			}
		}
		$btn_label = '' !== $s['aside_label'] ? $s['aside_label'] : __( 'See plans', 'a2e' );
		$btn_url   = '' !== $s['aside_url'] ? $s['aside_url'] : ( function_exists( 'a2e_url' ) ? a2e_url( 'pricing' ) : home_url( '/pricing/' ) );

		$h  = '<div class="mega" id="' . esc_attr( $id ) . '"><div class="mega__inner"><div class="mega__grid mega__grid--2">';
		foreach ( a2e_featured_service_slugs() as $slug ) {
			if ( ! isset( $services[ $slug ] ) ) {
				continue;
			}
			$sv = $services[ $slug ];
			$h .= '<a class="mega__link" href="' . esc_url( a2e_service_url( $slug ) ) . '"><span class="mega__icon">' . self::icon( $sv['icon'] ) . '</span><span><strong>' . esc_html( $sv['nav'] ) . '</strong><small>' . esc_html( $sv['short'] ) . '</small></span></a>';
		}
		$h .= '</div>';
		if ( '1' === $s['aside_show'] ) {
			$h .= '<aside class="mega__aside"><p class="mega__kicker">' . esc_html__( 'Done-for-you', 'a2e' ) . '</p><p class="mega__title">' . esc_html__( 'Let us run your eBay store every day', 'a2e' ) . '</p>';
			if ( $monthly ) {
				/* translators: 1: price 2: period */
				$h .= '<p class="mega__price">' . sprintf( esc_html__( 'Plans from %1$s%2$s', 'a2e' ), '<strong>' . esc_html( $monthly[0]['price'] ) . '</strong>', esc_html( $monthly[0]['period'] ) ) . '</p>';
			}
			$h .= '<a class="btn btn--primary btn--sm" href="' . esc_url( $btn_url ) . '"><span>' . esc_html( $btn_label ) . '</span>' . self::icon( 'arrow-right', 'btn__icon' ) . '</a>';
			$h .= '<a class="mega__all" href="' . esc_url( a2e_url( 'amazon-to-ebay-dropshipping-service' ) ) . '">' . esc_html__( 'All services', 'a2e' ) . ' ' . self::icon( 'arrow-right' ) . '</a></aside>';
		}
		$h .= '</div></div>';
		return $h;
	}

	/**
	 * The designed Free Tools mega panel: every tool, grouped by category.
	 *
	 * @param string $id Panel id.
	 * @return string
	 */
	public static function tools_panel( $id ) {
		if ( ! function_exists( 'a2e_tools' ) || ! function_exists( 'a2e_tool_categories' ) ) {
			return '';
		}
		$tools = a2e_tools();
		$h     = '<div class="mega mega--tools" id="' . esc_attr( $id ) . '"><div class="mega__inner"><div class="mega__cols">';
		foreach ( a2e_tool_categories() as $cat => $label ) {
			$h .= '<div class="mega__col"><p class="mega__kicker">' . esc_html( $label ) . '</p><ul>';
			foreach ( $tools as $tool ) {
				if ( isset( $tool['cat'] ) && $tool['cat'] === $cat ) {
					$h .= '<li><a href="' . esc_url( a2e_tool_url( $tool['slug'] ) ) . '">' . self::icon( $tool['icon'] ) . esc_html( $tool['name'] ) . '</a></li>';
				}
			}
			$h .= '</ul></div>';
		}
		$h .= '</div><div class="mega__foot"><span>' . self::icon( 'sparkle' ) . esc_html__( 'Free forever · no sign-up · fee rates kept up to date', 'a2e' ) . '</span><a href="' . esc_url( a2e_url( 'free-tools' ) ) . '">' . esc_html__( 'All free tools', 'a2e' ) . ' ' . self::icon( 'arrow-right' ) . '</a></div></div></div>';
		return $h;
	}

	/**
	 * Number of tools (for the Free Tools badge).
	 *
	 * @return int
	 */
	public static function tool_count() {
		return function_exists( 'a2e_tools' ) ? count( a2e_tools() ) : 0;
	}

	/**
	 * The menu assigned to the header, as a flat list of items.
	 *
	 * @return array
	 */
	public static function header_items() {
		$locations = get_nav_menu_locations();
		$menu_id   = isset( $locations[ LOCATION ] ) ? (int) $locations[ LOCATION ] : 0;
		if ( ! $menu_id ) {
			return array();
		}
		$items = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * The mobile drawer navigation, built from the same menu as the desktop header.
	 *
	 * @return string Inner HTML of the drawer's <nav>, or '' when there is no menu.
	 */
	public static function mobile_nav() {
		$items = self::header_items();
		if ( ! $items ) {
			return '';
		}
		$kids = array();
		foreach ( $items as $it ) {
			$kids[ (int) $it->menu_item_parent ][] = $it;
		}
		$top    = isset( $kids[0] ) ? $kids[0] : array();
		$groups = '';
		$links  = '';
		$first  = true;
		$chev   = self::icon( 'chevron-down' );

		foreach ( $top as $it ) {
			$title = wp_strip_all_tags( apply_filters( 'the_title', $it->title, $it->ID ) );
			$mega  = self::mega( $it->ID );
			$list  = '';
			if ( 'services' === $mega && function_exists( 'a2e_services' ) ) {
				$services = a2e_services();
				foreach ( a2e_featured_service_slugs() as $slug ) {
					if ( isset( $services[ $slug ] ) ) {
						$list .= '<li><a href="' . esc_url( a2e_service_url( $slug ) ) . '">' . self::icon( $services[ $slug ]['icon'] ) . esc_html( $services[ $slug ]['nav'] ) . '</a></li>';
					}
				}
				$list .= '<li><a href="' . esc_url( a2e_url( 'amazon-to-ebay-dropshipping-service' ) ) . '">' . self::icon( 'arrow-right' ) . esc_html__( 'All services', 'a2e' ) . '</a></li>';
			} elseif ( 'tools' === $mega && function_exists( 'a2e_tools' ) ) {
				foreach ( a2e_tools() as $tool ) {
					$list .= '<li><a href="' . esc_url( a2e_tool_url( $tool['slug'] ) ) . '">' . self::icon( $tool['icon'] ) . esc_html( $tool['name'] ) . '</a></li>';
				}
			} elseif ( ! empty( $kids[ (int) $it->ID ] ) ) {
				foreach ( $kids[ (int) $it->ID ] as $child ) {
					$list .= '<li><a href="' . esc_url( $child->url ) . '"' . self::link_attrs( $child ) . '>' . self::icon( self::item_icon( $child->ID ) ) . esc_html( wp_strip_all_tags( apply_filters( 'the_title', $child->title, $child->ID ) ) ) . '</a></li>';
				}
			}

			if ( '' !== $list ) {
				$groups .= '<details class="mnav__group"' . ( $first ? ' open' : '' ) . '><summary>' . esc_html( $title ) . $chev . '</summary><ul>' . $list . '</ul></details>';
				$first   = false;
			} else {
				$links .= '<li><a href="' . esc_url( $it->url ) . '"' . self::link_attrs( $it ) . '>' . esc_html( $title ) . '</a></li>';
			}
		}

		if ( '1' === settings()['client_area'] && function_exists( 'a2e_account_url' ) ) {
			$links .= '<li><a href="' . esc_url( a2e_account_url() ) . '">' . esc_html__( 'Client area', 'a2e' ) . '</a></li>';
		}
		return $groups . ( '' !== $links ? '<ul class="mnav__links">' . $links . '</ul>' : '' );
	}
}
