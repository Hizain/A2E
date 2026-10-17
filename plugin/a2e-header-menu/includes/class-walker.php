<?php
/**
 * Renders the header menu (Appearance > Menus) in the A2E Prime mega-menu markup.
 *
 * Top-level items become one of:
 *  - a plain link (Pricing, Shop, Blog...)
 *  - the designed Services mega panel ("Dropdown style: Services panel")
 *  - the designed Free Tools mega panel with the live tool count ("Free Tools panel")
 *  - a card dropdown of its sub-items, each with an icon and short text ("Auto")
 *
 * @package A2E_Header_Menu
 */

namespace A2E\HeaderMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mega walker.
 */
class Mega_Walker extends \Walker_Nav_Menu {

	/**
	 * Ids already used for panels on this page.
	 *
	 * @var array
	 */
	private $ids = array();

	/**
	 * The top-level item whose card dropdown is being written.
	 *
	 * @var int
	 */
	private $parent = 0;

	/**
	 * A unique panel id (keeps the theme's own ids for the first Services / Tools panel).
	 *
	 * @param string $base Base id.
	 * @return string
	 */
	private function panel_id( $base ) {
		$id = $base;
		$n  = 2;
		while ( isset( $this->ids[ $id ] ) ) {
			$id = $base . '-' . $n++;
		}
		$this->ids[ $id ] = true;
		return $id;
	}

	/**
	 * Services / Free Tools panels draw their own content, so any sub-items are skipped.
	 *
	 * @param object $element           Item.
	 * @param array  $children_elements Children by parent id.
	 * @param int    $max_depth         Max depth.
	 * @param int    $depth             Depth.
	 * @param array  $args              Args.
	 * @param string $output            Output.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		if ( 0 === (int) $depth && 'auto' !== Render::mega( $element->ID ) ) {
			unset( $children_elements[ $element->ID ] );
		}
		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Is the item (or something under it) the current page?
	 *
	 * @param object $item Item.
	 * @param bool   $tree Include ancestors.
	 * @return bool
	 */
	private function is_current( $item, $tree = false ) {
		$c    = (array) $item->classes;
		$keys = $tree ? array( 'current-menu-item', 'current_page_item', 'current-menu-ancestor', 'current-menu-parent', 'current_page_parent', 'current_page_ancestor' ) : array( 'current-menu-item', 'current_page_item' );
		return (bool) array_intersect( $keys, $c );
	}

	/**
	 * The user's own CSS classes from the menu screen.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	private function user_classes( $item ) {
		$out = array();
		foreach ( (array) $item->classes as $c ) {
			if ( $c && 0 !== strpos( $c, 'current' ) && 0 !== strpos( $c, 'menu-item' ) && 0 !== strpos( $c, 'page_item' ) && 0 !== strpos( $c, 'page-item' ) ) {
				$out[] = sanitize_html_class( $c );
			}
		}
		return $out ? ' ' . implode( ' ', array_filter( $out ) ) : '';
	}

	/**
	 * Item title, filtered like core does.
	 *
	 * @param object $item  Item.
	 * @param object $args  Args.
	 * @param int    $depth Depth.
	 * @return string
	 */
	private function title( $item, $args, $depth ) {
		$title = apply_filters( 'the_title', $item->title, $item->ID );
		return apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );
	}

	/**
	 * Open a card dropdown.
	 *
	 * @param string $output Output.
	 * @param int    $depth  Depth.
	 * @param object $args   Args.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === (int) $depth ) {
			$output .= '<div class="mega mega--small" id="' . esc_attr( $this->panel_id( 'mega-item-' . $this->parent ) ) . '"><div class="mega__inner"><div class="mega__grid">';
		}
	}

	/**
	 * Close a card dropdown.
	 *
	 * @param string $output Output.
	 * @param int    $depth  Depth.
	 * @param object $args   Args.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		if ( 0 === (int) $depth ) {
			$output .= '</div></div></div>';
		}
	}

	/**
	 * Start an item.
	 *
	 * @param string $output            Output.
	 * @param object $data_object       Item.
	 * @param int    $depth             Depth.
	 * @param object $args              Args.
	 * @param int    $current_object_id Current id.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item  = $data_object;
		$title = $this->title( $item, $args, $depth );

		if ( (int) $depth > 0 ) {
			$desc    = Render::desc( $item );
			$output .= '<a class="mega__link' . ( $this->is_current( $item ) ? ' is-current' : '' ) . '" href="' . esc_url( $item->url ) . '"' . Render::link_attrs( $item ) . '>'
				. '<span class="mega__icon">' . Render::icon( Render::item_icon( $item->ID ) ) . '</span>'
				. '<span><strong>' . $title . '</strong>' . ( '' !== $desc ? '<small>' . esc_html( $desc ) . '</small>' : '' ) . '</span></a>';
			return;
		}

		$mega = Render::mega( $item->ID );
		$chev = Render::icon( 'chevron-down', 'nav__chev' );
		$cls  = $this->user_classes( $item );

		if ( 'services' === $mega ) {
			$id      = $this->panel_id( 'mega-services' );
			$current = $this->is_current( $item, true ) || is_singular( 'a2e_service' );
			$output .= '<li class="nav__item has-mega' . $cls . '" data-mega><button type="button" class="nav__link' . ( $current ? ' is-current' : '' ) . '" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '">' . $title . $chev . '</button>' . Render::services_panel( $id );
			return;
		}

		if ( 'tools' === $mega ) {
			$id      = $this->panel_id( 'mega-tools' );
			$current = $this->is_current( $item, true ) || ( function_exists( 'a2e_current_tool' ) && a2e_current_tool() );
			$count   = Render::tool_count();
			$output .= '<li class="nav__item has-mega' . $cls . '" data-mega><button type="button" class="nav__link' . ( $current ? ' is-current' : '' ) . '" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '">' . $title . ( $count ? '<span class="nav__badge">' . (int) $count . '</span>' : '' ) . $chev . '</button>' . Render::tools_panel( $id );
			return;
		}

		if ( $this->has_children ) {
			$this->parent = (int) $item->ID;
			$output      .= '<li class="nav__item has-mega has-mega--small' . $cls . '" data-mega><button type="button" class="nav__link' . ( $this->is_current( $item, true ) ? ' is-current' : '' ) . '" aria-expanded="false" aria-controls="' . esc_attr( 'mega-item-' . $item->ID ) . '">' . $title . $chev . '</button>';
			return;
		}

		$output .= '<li class="nav__item' . $cls . '"><a class="nav__link' . ( $this->is_current( $item ) ? ' is-current' : '' ) . '" href="' . esc_url( $item->url ) . '"' . Render::link_attrs( $item ) . '>' . $title . '</a>';
	}

	/**
	 * End an item.
	 *
	 * @param string $output      Output.
	 * @param object $data_object Item.
	 * @param int    $depth       Depth.
	 * @param object $args        Args.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		if ( 0 === (int) $depth ) {
			$output .= '</li>';
		}
	}
}
