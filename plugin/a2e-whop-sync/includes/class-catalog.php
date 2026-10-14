<?php
/**
 * Services shop: plan categories, the catalog feed for the A2E theme's shop page,
 * and a one-click import of existing WooCommerce products as draft plans.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catalog.
 */
class Catalog {

	const TAX = 'a2ews_group';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_filter( 'parent_file', array( __CLASS__, 'parent_file' ) );
		add_filter( 'submenu_file', array( __CLASS__, 'submenu_file' ) );
		add_action( self::TAX . '_add_form_fields', array( __CLASS__, 'term_fields_add' ) );
		add_action( self::TAX . '_edit_form_fields', array( __CLASS__, 'term_fields_edit' ) );
		add_action( 'created_' . self::TAX, array( __CLASS__, 'term_save' ) );
		add_action( 'edited_' . self::TAX, array( __CLASS__, 'term_save' ) );
		add_filter( 'manage_edit-' . self::TAX . '_columns', array( __CLASS__, 'term_columns' ) );
		add_filter( 'manage_' . self::TAX . '_custom_column', array( __CLASS__, 'term_column' ), 10, 3 );
		add_filter( 'a2e_shop_catalog', array( __CLASS__, 'theme_catalog' ), 20 );
		add_action( 'admin_notices', array( __CLASS__, 'woo_notice' ) );
		add_action( 'admin_post_a2ews_import_woo', array( __CLASS__, 'import_woo' ) );
	}

	/**
	 * Register the category taxonomy (admin only, no public URLs).
	 */
	public static function register() {
		register_taxonomy(
			self::TAX,
			CPT,
			array(
				'labels'            => array(
					'name'          => __( 'Service categories', 'a2e-whop' ),
					'singular_name' => __( 'Service category', 'a2e-whop' ),
					'menu_name'     => __( 'Categories', 'a2e-whop' ),
					'add_new_item'  => __( 'Add New Category', 'a2e-whop' ),
					'edit_item'     => __( 'Edit Category', 'a2e-whop' ),
					'search_items'  => __( 'Search categories', 'a2e-whop' ),
					'not_found'     => __( 'No categories yet.', 'a2e-whop' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => false,
				'hierarchical'      => true,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);
	}

	/**
	 * Icons the A2E theme can draw (filterable for other themes).
	 *
	 * @return array slug => label
	 */
	public static function icon_choices() {
		return apply_filters(
			'a2ews_icon_choices',
			array(
				'package'   => __( 'Package', 'a2e-whop' ),
				'list'      => __( 'Listings', 'a2e-whop' ),
				'search'    => __( 'Product research', 'a2e-whop' ),
				'pen'       => __( 'Writing / SEO', 'a2e-whop' ),
				'store'     => __( 'Store', 'a2e-whop' ),
				'gear'      => __( 'Setup', 'a2e-whop' ),
				'scan'      => __( 'Audit', 'a2e-whop' ),
				'chart'     => __( 'Growth', 'a2e-whop' ),
				'trend'     => __( 'Trend', 'a2e-whop' ),
				'refresh'   => __( 'Monitoring / sync', 'a2e-whop' ),
				'truck'     => __( 'Orders / fulfilment', 'a2e-whop' ),
				'bot'       => __( 'Automation', 'a2e-whop' ),
				'users'     => __( 'Team / VA', 'a2e-whop' ),
				'headset'   => __( 'Support', 'a2e-whop' ),
				'chat'      => __( 'Buyer messages', 'a2e-whop' ),
				'shield'    => __( 'Account health', 'a2e-whop' ),
				'handshake' => __( 'Consultation', 'a2e-whop' ),
				'rocket'    => __( 'Launch', 'a2e-whop' ),
				'target'    => __( 'Target', 'a2e-whop' ),
				'tag'       => __( 'Pricing', 'a2e-whop' ),
				'layers'    => __( 'Bundle', 'a2e-whop' ),
				'calendar'  => __( 'Monthly', 'a2e-whop' ),
				'sparkle'   => __( 'Premium', 'a2e-whop' ),
				'wand'      => __( 'Done for you', 'a2e-whop' ),
				'gauge'     => __( 'Performance', 'a2e-whop' ),
				'award'     => __( 'Best value', 'a2e-whop' ),
				'box'       => __( 'Box', 'a2e-whop' ),
				'clipboard' => __( 'Checklist', 'a2e-whop' ),
			)
		);
	}

	/**
	 * "Categories" under the Whop menu.
	 */
	public static function menu() {
		add_submenu_page( 'a2e-whop', __( 'Service categories', 'a2e-whop' ), __( 'Categories', 'a2e-whop' ), 'manage_options', 'edit-tags.php?taxonomy=' . self::TAX . '&post_type=' . CPT );
	}

	/**
	 * Keep the Whop menu open on the categories screen.
	 *
	 * @param string $file Parent file.
	 * @return string
	 */
	public static function parent_file( $file ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return ( $screen && self::TAX === $screen->taxonomy ) ? 'a2e-whop' : $file;
	}

	/**
	 * Highlight the Categories item.
	 *
	 * @param string|null $file Submenu file.
	 * @return string|null
	 */
	public static function submenu_file( $file ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return ( $screen && self::TAX === $screen->taxonomy ) ? 'edit-tags.php?taxonomy=' . self::TAX . '&post_type=' . CPT : $file;
	}

	/**
	 * Icon select markup.
	 *
	 * @param string $value Selected.
	 * @return string
	 */
	protected static function icon_select( $value ) {
		$out = '<select name="a2ews_icon" id="a2ews_icon">';
		foreach ( self::icon_choices() as $k => $l ) {
			$out .= '<option value="' . esc_attr( $k ) . '"' . selected( $value, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		return $out . '</select>';
	}

	/**
	 * Fields on "Add category".
	 */
	public static function term_fields_add() {
		wp_nonce_field( 'a2ews_term', 'a2ews_term_nonce' );
		echo '<div class="form-field"><label for="a2ews_icon">' . esc_html__( 'Icon', 'a2e-whop' ) . '</label>' . self::icon_select( 'package' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="form-field"><label for="a2ews_order">' . esc_html__( 'Order', 'a2e-whop' ) . '</label><input type="number" name="a2ews_order" id="a2ews_order" value="0" style="width:90px"><p>' . esc_html__( 'Lower numbers show first in the shop.', 'a2e-whop' ) . '</p></div>';
	}

	/**
	 * Fields on "Edit category".
	 *
	 * @param \WP_Term $term Term.
	 */
	public static function term_fields_edit( $term ) {
		wp_nonce_field( 'a2ews_term', 'a2ews_term_nonce' );
		$icon = get_term_meta( $term->term_id, 'a2ews_icon', true );
		echo '<tr class="form-field"><th scope="row"><label for="a2ews_icon">' . esc_html__( 'Icon', 'a2e-whop' ) . '</label></th><td>' . self::icon_select( $icon ? $icon : 'package' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<tr class="form-field"><th scope="row"><label for="a2ews_order">' . esc_html__( 'Order', 'a2e-whop' ) . '</label></th><td><input type="number" name="a2ews_order" id="a2ews_order" value="' . esc_attr( (int) get_term_meta( $term->term_id, 'a2ews_order', true ) ) . '" style="width:90px"><p class="description">' . esc_html__( 'Lower numbers show first in the shop.', 'a2e-whop' ) . '</p></td></tr>';
	}

	/**
	 * Save category fields.
	 *
	 * @param int $term_id Term ID.
	 */
	public static function term_save( $term_id ) {
		$nonce = isset( $_POST['a2ews_term_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['a2ews_term_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'a2ews_term' ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		$icon = isset( $_POST['a2ews_icon'] ) ? sanitize_key( wp_unslash( $_POST['a2ews_icon'] ) ) : 'package';
		update_term_meta( $term_id, 'a2ews_icon', array_key_exists( $icon, self::icon_choices() ) ? $icon : 'package' );
		update_term_meta( $term_id, 'a2ews_order', isset( $_POST['a2ews_order'] ) ? (int) $_POST['a2ews_order'] : 0 );
	}

	/**
	 * Category list columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function term_columns( $cols ) {
		$cols['a2ews_order'] = __( 'Order', 'a2e-whop' );
		unset( $cols['slug'] );
		return $cols;
	}

	/**
	 * Category list column value.
	 *
	 * @param string $out    Output.
	 * @param string $column Column.
	 * @param int    $term   Term ID.
	 * @return string
	 */
	public static function term_column( $out, $column, $term ) {
		return 'a2ews_order' === $column ? (string) (int) get_term_meta( $term, 'a2ews_order', true ) : $out;
	}

	/**
	 * Categories in display order (order meta, then name).
	 *
	 * @return array slug, name, desc, icon, order
	 */
	public static function groups() {
		$terms = get_terms( array( 'taxonomy' => self::TAX, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $t ) {
			$icon  = get_term_meta( $t->term_id, 'a2ews_icon', true );
			$out[] = array(
				'slug'  => $t->slug,
				'name'  => $t->name,
				'desc'  => $t->description,
				'icon'  => $icon ? $icon : 'package',
				'order' => (int) get_term_meta( $t->term_id, 'a2ews_order', true ),
			);
		}
		usort(
			$out,
			function ( $a, $b ) {
				return $a['order'] === $b['order'] ? strcasecmp( $a['name'], $b['name'] ) : $a['order'] - $b['order'];
			}
		);
		return $out;
	}

	/**
	 * Feed the theme's services shop with every plan shown on the website.
	 *
	 * @param array $fallback Theme catalog.
	 * @return array groups, plans, source
	 */
	public static function theme_catalog( $fallback ) {
		$plans = Plans::published( 'shop' );
		if ( ! $plans ) {
			return $fallback;
		}
		return array(
			'groups' => self::groups(),
			'plans'  => $plans,
			'source' => 'whop',
		);
	}

	/**
	 * WooCommerce products that have not been imported yet.
	 *
	 * @return array WC_Product[]
	 */
	protected static function woo_products_to_import() {
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
			return array();
		}
		$done = array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => CPT,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'meta_key'       => '_a2ews_wc_product', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				)
			)
		);
		$map = array();
		foreach ( $done as $pid ) {
			$map[ (int) get_post_meta( $pid, '_a2ews_wc_product', true ) ] = true;
		}
		$out = array();
		foreach ( (array) wc_get_products( array( 'status' => 'publish', 'limit' => 200 ) ) as $product ) {
			if ( is_object( $product ) && ! isset( $map[ (int) $product->get_id() ] ) ) {
				$out[] = $product;
			}
		}
		return $out;
	}

	/**
	 * Offer the import on the Plans and Connect screens while WooCommerce is active.
	 */
	public static function woo_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! current_user_can( 'manage_options' ) || ! ( ( 'edit' === $screen->base && CPT === $screen->post_type ) || 'toplevel_page_a2e-whop' === $screen->id ) ) {
			return;
		}
		$todo = self::woo_products_to_import();
		if ( ! $todo ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=a2ews_import_woo' ), 'a2ews_import_woo' );
		echo '<div class="notice notice-info a2ews-woo"><p><strong>' . esc_html__( 'Move your WooCommerce packages to Whop', 'a2e-whop' ) . '</strong></p><p>';
		/* translators: %d: number of products */
		echo esc_html( sprintf( _n( 'You have %d WooCommerce product. Import it as a draft plan with its price, description, features and category, then check it and press Publish to create it on Whop.', 'You have %d WooCommerce products. Import them as draft plans with their prices, descriptions, features and categories, then check each one and press Publish to create it on Whop.', count( $todo ), 'a2e-whop' ), count( $todo ) ) );
		echo '</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">';
		/* translators: %d: number of products */
		echo esc_html( sprintf( _n( 'Import %d product as a draft', 'Import %d products as drafts', count( $todo ), 'a2e-whop' ), count( $todo ) ) );
		echo '</a> <span class="description">' . esc_html__( 'Nothing is changed in WooCommerce or on Whop.', 'a2e-whop' ) . '</span></p></div>';
	}

	/**
	 * Import WooCommerce products as draft plans.
	 */
	public static function import_woo() {
		check_admin_referer( 'a2ews_import_woo' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-whop' ) );
		}
		$count = 0;
		foreach ( self::woo_products_to_import() as $product ) {
			if ( self::import_product( $product ) ) {
				$count++;
			}
		}
		/* translators: %d: number of plans */
		$msg = sprintf( _n( 'Imported %d product as a draft plan. Open it, check the details and press Publish to create it on Whop.', 'Imported %d products as draft plans. Open each one, check the details and press Publish to create it on Whop.', $count, 'a2e-whop' ), $count );
		set_transient( 'a2ews_notice_' . get_current_user_id(), array( 'success', $msg ), 60 );
		log( 'import', sprintf( 'Imported %d WooCommerce products as draft plans.', $count ) );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . CPT . '&post_status=draft' ) );
		exit;
	}

	/**
	 * Create one draft plan from a WooCommerce product.
	 *
	 * @param object $product WC_Product.
	 * @return int Plan ID (0 on failure).
	 */
	public static function import_product( $product ) {
		$name        = wp_strip_all_tags( (string) $product->get_name() );
		$description = (string) $product->get_description();
		$short       = trim( wp_strip_all_tags( (string) $product->get_short_description() ) );
		$regular     = (float) $product->get_regular_price();
		$sale        = (float) $product->get_sale_price();
		$price       = (float) $product->get_price();
		$price       = $price > 0 ? $price : ( $sale > 0 ? $sale : $regular );
		$type        = (string) $product->get_type();
		$billing     = 'once';
		$every       = '';
		$setup       = '';
		$trial       = '';

		// WooCommerce Subscriptions products keep their billing period.
		if ( false !== strpos( $type, 'subscription' ) ) {
			$period   = (string) $product->get_meta( '_subscription_period' );
			$interval = max( 1, (int) $product->get_meta( '_subscription_period_interval' ) );
			if ( 'year' === $period && 1 === $interval ) {
				$billing = 'year';
			} elseif ( 'month' === $period && 1 === $interval ) {
				$billing = 'month';
			} else {
				$days    = array( 'day' => 1, 'week' => 7, 'month' => 30, 'year' => 365 );
				$billing = 'custom';
				$every   = (string) ( ( isset( $days[ $period ] ) ? $days[ $period ] : 30 ) * $interval );
			}
			$setup = (string) (float) $product->get_meta( '_subscription_sign_up_fee' );
			if ( 'day' === (string) $product->get_meta( '_subscription_trial_period' ) ) {
				$trial = (string) (int) $product->get_meta( '_subscription_trial_length' );
			}
		}

		// Features from the description's bullet points.
		$features = array();
		if ( preg_match_all( '#<li[^>]*>(.*?)</li>#is', $description, $m ) ) {
			foreach ( $m[1] as $li ) {
				$text = trim( html_entity_decode( wp_strip_all_tags( $li ), ENT_QUOTES, 'UTF-8' ) );
				if ( '' !== $text ) {
					$features[] = $text;
				}
			}
		}
		if ( '' === $short ) {
			$short = wp_trim_words( wp_strip_all_tags( $description ), 28, '…' );
		}

		$plan_id = wp_insert_post(
			array(
				'post_type'    => CPT,
				'post_status'  => 'draft',
				'post_title'   => $name,
				'post_content' => wp_kses_post( $description ),
				'menu_order'   => method_exists( $product, 'get_menu_order' ) ? (int) $product->get_menu_order() : 0,
			),
			true
		);
		if ( is_wp_error( $plan_id ) || ! $plan_id ) {
			return 0;
		}
		$meta = array(
			'billing'    => $billing,
			'every_days' => $every,
			'price'      => $price > 0 ? (string) round( $price, 2 ) : '',
			'currency'   => strtolower( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'usd' ),
			'setup_fee'  => (float) $setup > 0 ? $setup : '',
			'trial_days' => (int) $trial > 0 ? $trial : '',
			'compare_at' => ( $sale > 0 && $regular > $sale ) ? (string) round( $regular, 2 ) : '',
			'desc'       => $short,
			'icon'       => self::guess_icon( $name ),
			'features'   => implode( "\n", array_slice( $features, 0, 12 ) ),
			'show'       => '1',
			'on_pricing' => '0',
			'featured'   => '0',
		);
		if ( ! array_key_exists( $meta['currency'], array_flip( array( 'usd', 'eur', 'gbp', 'cad', 'aud', 'aed', 'sgd', 'inr', 'pkr' ) ) ) ) {
			$meta['currency'] = 'usd';
		}
		foreach ( $meta as $key => $value ) {
			update_post_meta( $plan_id, '_a2ews_' . $key, $value );
		}
		update_post_meta( $plan_id, '_a2ews_wc_product', (int) $product->get_id() );

		// Category from the first product category.
		$cats = method_exists( $product, 'get_category_ids' ) ? (array) $product->get_category_ids() : array();
		foreach ( $cats as $cat_id ) {
			$cat = get_term( (int) $cat_id, 'product_cat' );
			if ( ! $cat || is_wp_error( $cat ) || 'uncategorized' === $cat->slug ) {
				continue;
			}
			$term = term_exists( $cat->name, self::TAX );
			if ( ! $term ) {
				$term = wp_insert_term( $cat->name, self::TAX, array( 'description' => wp_strip_all_tags( $cat->description ) ) );
				if ( ! is_wp_error( $term ) ) {
					update_term_meta( (int) $term['term_id'], 'a2ews_icon', self::guess_icon( $cat->name ) );
				}
			}
			if ( $term && ! is_wp_error( $term ) ) {
				wp_set_object_terms( $plan_id, array( (int) $term['term_id'] ), self::TAX );
			}
			break;
		}
		return (int) $plan_id;
	}

	/**
	 * Pick an icon from a product or category name.
	 *
	 * @param string $name Name.
	 * @return string
	 */
	public static function guess_icon( $name ) {
		$name = strtolower( $name );
		$map  = array(
			'listing'    => 'list',
			'research'   => 'search',
			'seo'        => 'pen',
			'title'      => 'pen',
			'setup'      => 'gear',
			'set up'     => 'gear',
			'audit'      => 'scan',
			'consult'    => 'handshake',
			'manag'      => 'store',
			'va '        => 'users',
			'assistant'  => 'users',
			'automat'    => 'bot',
			'inventory'  => 'refresh',
			'order'      => 'truck',
			'fulfil'     => 'truck',
			'account'    => 'shield',
		);
		foreach ( $map as $needle => $icon ) {
			if ( false !== strpos( $name, $needle ) ) {
				return $icon;
			}
		}
		return 'package';
	}
}
