<?php
/**
 * Subscription plans managed in WordPress and synced to Whop.
 *
 * Each WordPress plan maps to one Whop product with one live plan (variant). Text changes
 * update Whop in place. A price/billing change creates a new Whop plan and hides the old one,
 * so existing subscribers keep the price they signed up for. Trashing a plan hides it on Whop.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plans.
 */
class Plans {

	/**
	 * Meta fields: key => [ label, type, default, help ].
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'_h_price'   => array( __( 'Price and billing', 'a2e-whop' ), 'heading', '', '' ),
			'billing'    => array( __( 'Billing', 'a2e-whop' ), 'select', 'month', '' ),
			'every_days' => array( __( 'Bill every … days (custom billing only)', 'a2e-whop' ), 'number', '30', '' ),
			'price'      => array( __( 'Price', 'a2e-whop' ), 'number', '', __( 'Charged every period (or once for one-time plans).', 'a2e-whop' ) ),
			'currency'   => array( __( 'Currency', 'a2e-whop' ), 'currency', 'usd', '' ),
			'setup_fee'  => array( __( 'One-time setup fee (optional)', 'a2e-whop' ), 'number', '', __( 'Added to the first payment of a recurring plan.', 'a2e-whop' ) ),
			'trial_days' => array( __( 'Free trial days (optional)', 'a2e-whop' ), 'number', '', '' ),
			'compare_at' => array( __( 'Regular price (optional)', 'a2e-whop' ), 'number', '', __( 'Shown crossed out next to the price, for example during a launch offer. Website only, not sent to Whop.', 'a2e-whop' ) ),
			'_h_card'    => array( __( 'Card in the services shop', 'a2e-whop' ), 'heading', '', __( 'How this plan looks on the website. Write the full description in the editor above: it opens in the plan’s “Details” window.', 'a2e-whop' ) ),
			'desc'       => array( __( 'Short description', 'a2e-whop' ), 'textarea', '', __( 'One or two sentences under the plan name. Also used on Whop.', 'a2e-whop' ) ),
			'icon'       => array( __( 'Icon', 'a2e-whop' ), 'icon', 'package', '' ),
			'best_for'   => array( __( 'Best for (optional)', 'a2e-whop' ), 'text', '', __( 'Who should buy this, e.g. “New stores that need their first 100 products”.', 'a2e-whop' ) ),
			'delivery'   => array( __( 'Delivery or start time (optional)', 'a2e-whop' ), 'text', '', __( 'e.g. “Delivered in 5 to 7 days” or “Starts within 48 hours”.', 'a2e-whop' ) ),
			'features'   => array( __( 'Features (one per line)', 'a2e-whop' ), 'lines', '', __( 'The first four show on the card; all of them show in the Details window.', 'a2e-whop' ) ),
			'badge'      => array( __( 'Badge (e.g. Most popular)', 'a2e-whop' ), 'text', '', '' ),
			'note'       => array( __( 'Small note under the price (optional)', 'a2e-whop' ), 'text', '', __( 'Leave empty to show the setup fee / trial automatically.', 'a2e-whop' ) ),
			'button'     => array( __( 'Button label', 'a2e-whop' ), 'text', '', '' ),
			'link'       => array( __( 'Button link instead of Whop checkout (optional)', 'a2e-whop' ), 'url', '', __( 'For quote-only services: leave the price empty and link to your quote form or WhatsApp. A plan with a link and no price is shown on the website but not created on Whop.', 'a2e-whop' ) ),
			'learn_more' => array( __( '“Learn more” link (optional)', 'a2e-whop' ), 'url', '', __( 'A page that explains this service in detail, such as its service guide.', 'a2e-whop' ) ),
			'_h_show'    => array( __( 'Where it appears', 'a2e-whop' ), 'heading', '', '' ),
			'show'       => array( __( 'Show in the services shop', 'a2e-whop' ), 'checkbox', '1', '' ),
			'on_pricing' => array( __( 'Also show on the homepage and pricing page', 'a2e-whop' ), 'checkbox', '1', __( 'Keep 3 or 4 main plans here so the pricing table stays easy to compare. Add-ons and one-off services can live in the shop only.', 'a2e-whop' ) ),
			'featured'   => array( __( 'Highlight this plan', 'a2e-whop' ), 'checkbox', '', '' ),
		);
	}

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . CPT, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'wp_trash_post', array( __CLASS__, 'on_trash' ) );
		add_filter( 'manage_' . CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_action( 'admin_post_a2ews_resync', array( __CLASS__, 'resync_action' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * Register the post type (admin only).
	 */
	public static function register() {
		register_post_type(
			CPT,
			array(
				'labels'          => array(
					'name'          => __( 'Plans', 'a2e-whop' ),
					'singular_name' => __( 'Plan', 'a2e-whop' ),
					'add_new'       => __( 'Add New Plan', 'a2e-whop' ),
					'add_new_item'  => __( 'Add New Subscription Plan', 'a2e-whop' ),
					'edit_item'     => __( 'Edit Plan', 'a2e-whop' ),
					'all_items'     => __( 'Plans', 'a2e-whop' ),
					'menu_name'     => __( 'Plans', 'a2e-whop' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'a2e-whop',
				'supports'        => array( 'title', 'editor', 'page-attributes' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'hierarchical'    => false,
			)
		);
	}

	/**
	 * Title placeholder.
	 *
	 * @param string   $text Text.
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		return CPT === $post->post_type ? __( 'Plan name, e.g. Growth Management', 'a2e-whop' ) : $text;
	}

	/**
	 * Meta value with default.
	 *
	 * @param int    $id  Post ID.
	 * @param string $key Field key.
	 * @return string
	 */
	public static function meta( $id, $key ) {
		$fields = self::fields();
		$value  = get_post_meta( $id, '_a2ews_' . $key, true );
		if ( '' === $value && isset( $fields[ $key ] ) && 'checkbox' !== $fields[ $key ][1] ) {
			return (string) $fields[ $key ][2];
		}
		if ( '' === $value && isset( $fields[ $key ] ) && 'checkbox' === $fields[ $key ][1] && '1' === $fields[ $key ][2] && ! metadata_exists( 'post', $id, '_a2ews_' . $key ) ) {
			return '1';
		}
		return (string) $value;
	}

	/**
	 * Meta boxes.
	 */
	public static function meta_boxes() {
		add_meta_box( 'a2ews_plan', __( 'Plan details', 'a2e-whop' ), array( __CLASS__, 'render_box' ), CPT, 'normal', 'high' );
		add_meta_box( 'a2ews_sync', __( 'Whop sync', 'a2e-whop' ), array( __CLASS__, 'render_sync_box' ), CPT, 'side', 'high' );
	}

	/**
	 * Details box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_box( $post ) {
		wp_nonce_field( 'a2ews_plan', 'a2ews_plan_nonce' );
		echo '<table class="form-table a2ews-form" role="presentation">';
		foreach ( self::fields() as $key => $f ) {
			$name  = 'a2ews[' . $key . ']';
			$id    = 'a2ews-' . $key;
			if ( 'heading' === $f[1] ) {
				echo '<tr class="a2ews-row a2ews-row--heading"><th colspan="2"><h3>' . esc_html( $f[0] ) . '</h3>' . ( $f[3] ? '<p class="description">' . esc_html( $f[3] ) . '</p>' : '' ) . '</th></tr>';
				continue;
			}
			$value = self::meta( $post->ID, $key );
			echo '<tr class="a2ews-row a2ews-row--' . esc_attr( $key ) . '"><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
			switch ( $f[1] ) {
				case 'icon':
					echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( Catalog::icon_choices() as $k => $l ) {
						echo '<option value="' . esc_attr( $k ) . '"' . selected( $value, $k, false ) . '>' . esc_html( $l ) . '</option>';
					}
					echo '</select>';
					break;
				case 'url':
					echo '<input type="text" class="regular-text code" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="https:// or /quote-request/">';
					break;
				case 'select':
					$opts = array(
						'month'  => __( 'Monthly (every 30 days)', 'a2e-whop' ),
						'year'   => __( 'Yearly (every 365 days)', 'a2e-whop' ),
						'custom' => __( 'Custom period', 'a2e-whop' ),
						'once'   => __( 'One-time payment', 'a2e-whop' ),
					);
					echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( $opts as $k => $l ) {
						echo '<option value="' . esc_attr( $k ) . '"' . selected( $value, $k, false ) . '>' . esc_html( $l ) . '</option>';
					}
					echo '</select>';
					break;
				case 'currency':
					echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
					foreach ( array( 'usd', 'eur', 'gbp', 'cad', 'aud', 'aed', 'sgd', 'inr', 'pkr' ) as $c ) {
						echo '<option value="' . esc_attr( $c ) . '"' . selected( $value, $c, false ) . '>' . esc_html( strtoupper( $c ) ) . '</option>';
					}
					echo '</select>';
					break;
				case 'number':
					echo '<input type="number" min="0" step="0.01" class="small-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
					break;
				case 'textarea':
				case 'lines':
					echo '<textarea class="large-text" rows="' . ( 'lines' === $f[1] ? 6 : 3 ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
					break;
				case 'checkbox':
					echo '<label><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', $value, false ) . '> ' . esc_html__( 'Yes', 'a2e-whop' ) . '</label>';
					break;
				default:
					echo '<input type="text" class="regular-text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
			}
			if ( $f[3] ) {
				echo '<p class="description">' . esc_html( $f[3] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
		echo '<p class="description">' . esc_html__( 'Publishing (or updating) this plan creates it on your Whop account. Text changes update Whop; a price or billing change creates a new Whop plan so existing subscribers keep their price. Pick a category in the “Service categories” box and use “Order” in the Plan attributes box to sort plans on the website.', 'a2e-whop' ) . '</p>';
	}

	/**
	 * Sync status box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_sync_box( $post ) {
		$plan_id  = get_post_meta( $post->ID, '_a2ews_plan_id', true );
		$prod_id  = get_post_meta( $post->ID, '_a2ews_product_id', true );
		$url      = get_post_meta( $post->ID, '_a2ews_purchase_url', true );
		$synced   = (int) get_post_meta( $post->ID, '_a2ews_synced_at', true );
		$error    = get_post_meta( $post->ID, '_a2ews_error', true );
		if ( self::is_quote_only( $post->ID ) ) {
			echo '<p class="a2ews-pill">' . esc_html__( 'Website only', 'a2e-whop' ) . '</p><p>' . esc_html__( 'This service has no price and a custom button link, so it is shown in the shop but not created on Whop. Add a price to sell it through Whop.', 'a2e-whop' ) . '</p>';
			return;
		}
		if ( ! is_connected() ) {
			echo '<p class="a2ews-pill a2ews-pill--warn">' . esc_html__( 'Whop is not connected', 'a2e-whop' ) . '</p><p><a class="button" href="' . esc_url( settings_url() ) . '">' . esc_html__( 'Connect Whop', 'a2e-whop' ) . '</a></p>';
			return;
		}
		if ( $error ) {
			echo '<p class="a2ews-pill a2ews-pill--err">' . esc_html__( 'Last sync failed', 'a2e-whop' ) . '</p><p class="a2ews-error">' . esc_html( $error ) . '</p>';
		} elseif ( $plan_id ) {
			/* translators: %s: time ago */
			echo '<p class="a2ews-pill a2ews-pill--ok">' . esc_html__( 'Synced to Whop', 'a2e-whop' ) . '</p><p>' . esc_html( sprintf( __( '%s ago', 'a2e-whop' ), human_time_diff( $synced ) ) ) . '</p>';
		} else {
			echo '<p class="a2ews-pill">' . esc_html__( 'Not synced yet. Publish to create it on Whop.', 'a2e-whop' ) . '</p>';
		}
		if ( $prod_id ) {
			echo '<p><small>' . esc_html__( 'Product', 'a2e-whop' ) . ': <code>' . esc_html( $prod_id ) . '</code></small><br><small>' . esc_html__( 'Plan', 'a2e-whop' ) . ': <code>' . esc_html( $plan_id ) . '</code></small></p>';
		}
		if ( $url ) {
			echo '<p><a class="button" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open checkout ↗', 'a2e-whop' ) . '</a></p>';
			echo '<p><input type="text" readonly class="widefat" value="' . esc_attr( $url ) . '" onclick="this.select()"></p>';
		}
		if ( 'publish' === $post->post_status ) {
			echo '<p><a class="button button-secondary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a2ews_resync&post=' . $post->ID ), 'a2ews_resync_' . $post->ID ) ) . '">' . esc_html__( 'Re-sync now', 'a2e-whop' ) . '</a></p>';
		}
	}

	/**
	 * Save meta, then sync.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public static function save( $post_id, $post ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		$nonce = isset( $_POST['a2ews_plan_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['a2ews_plan_nonce'] ) ) : '';
		if ( $nonce && wp_verify_nonce( $nonce, 'a2ews_plan' ) && current_user_can( 'edit_post', $post_id ) ) {
			$in = isset( $_POST['a2ews'] ) && is_array( $_POST['a2ews'] ) ? wp_unslash( $_POST['a2ews'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			foreach ( self::fields() as $key => $f ) {
				if ( 'heading' === $f[1] ) {
					continue;
				}
				$raw = isset( $in[ $key ] ) ? $in[ $key ] : '';
				switch ( $f[1] ) {
					case 'icon':
						$value = array_key_exists( (string) $raw, Catalog::icon_choices() ) ? (string) $raw : 'package';
						break;
					case 'url':
						$raw   = trim( (string) $raw );
						$value = ( 0 === strpos( $raw, '/' ) && 0 !== strpos( $raw, '//' ) ) ? sanitize_text_field( $raw ) : esc_url_raw( $raw );
						break;
					case 'checkbox':
						$value = empty( $raw ) ? '0' : '1';
						break;
					case 'number':
						$value = '' === trim( (string) $raw ) ? '' : (string) max( 0, round( (float) $raw, 2 ) );
						break;
					case 'textarea':
					case 'lines':
						$value = sanitize_textarea_field( $raw );
						break;
					case 'select':
						$value = in_array( $raw, array( 'month', 'year', 'custom', 'once' ), true ) ? $raw : 'month';
						break;
					case 'currency':
						$value = preg_match( '/^[a-z]{3}$/', (string) $raw ) ? $raw : 'usd';
						break;
					default:
						$value = sanitize_text_field( $raw );
				}
				update_post_meta( $post_id, '_a2ews_' . $key, $value );
			}
		}
		if ( 'publish' === $post->post_status ) {
			self::sync( $post_id );
		} elseif ( in_array( $post->post_status, array( 'draft', 'pending', 'private' ), true ) ) {
			self::hide_on_whop( $post_id );
		}
	}

	/**
	 * Billing details for Whop.
	 *
	 * @param int $id Post ID.
	 * @return array plan_type, billing_period, initial_price, renewal_price, trial, currency
	 */
	public static function billing( $id ) {
		$billing = self::meta( $id, 'billing' );
		$price   = (float) self::meta( $id, 'price' );
		$setup   = (float) self::meta( $id, 'setup_fee' );
		$days    = 'year' === $billing ? 365 : ( 'custom' === $billing ? max( 1, (int) self::meta( $id, 'every_days' ) ) : 30 );
		if ( 'once' === $billing ) {
			return array(
				'plan_type'     => 'one_time',
				'initial_price' => $price,
				'currency'      => self::meta( $id, 'currency' ),
			);
		}
		$out = array(
			'plan_type'      => 'renewal',
			'billing_period' => $days,
			'initial_price'  => $setup,
			'renewal_price'  => $price,
			'currency'       => self::meta( $id, 'currency' ),
		);
		$trial = (int) self::meta( $id, 'trial_days' );
		if ( $trial > 0 ) {
			$out['trial_period_days'] = $trial;
		}
		return $out;
	}

	/**
	 * Create or update the plan on Whop.
	 *
	 * @param int $id Post ID.
	 * @return true|\WP_Error
	 */
	public static function sync( $id ) {
		if ( self::is_quote_only( $id ) ) {
			delete_post_meta( $id, '_a2ews_error' );
			return true;
		}
		if ( ! is_connected() ) {
			return new \WP_Error( 'a2ews_not_connected', __( 'Whop is not connected.', 'a2e-whop' ) );
		}
		$title = wp_strip_all_tags( get_the_title( $id ) );
		$desc  = self::meta( $id, 'desc' );
		if ( (float) self::meta( $id, 'price' ) <= 0 ) {
			return self::fail( $id, __( 'Add a price above 0 before publishing.', 'a2e-whop' ) );
		}

		// 1. Product.
		$product_id = (string) get_post_meta( $id, '_a2ews_product_id', true );
		$product    = array(
			'title'       => mb_substr( $title, 0, 80 ),
			'description' => mb_substr( $desc, 0, 1500 ),
			'visibility'  => 'visible',
		);
		$redirect   = setting( 'redirect_url' ) ? setting( 'redirect_url' ) : add_query_arg( 'purchase', 'success', home_url( '/account/' ) );
		if ( $product_id ) {
			$res = Api::update_product( $product_id, $product );
		} else {
			$product['redirect_purchase_url'] = $redirect;
			$product['metadata']              = array( 'source' => 'a2e-whop-sync', 'wp_plan_id' => (string) $id );
			$res                              = Api::create_product( $product );
			if ( ! is_wp_error( $res ) ) {
				$product_id = (string) pick( $res, array( 'id' ) );
				update_post_meta( $id, '_a2ews_product_id', $product_id );
			}
		}
		if ( is_wp_error( $res ) ) {
			return self::fail( $id, $res->get_error_message() );
		}

		// 2. Plan (variant). New pricing → new plan; old plan hidden so existing subscribers keep their price.
		$billing   = self::billing( $id );
		$signature = md5( wp_json_encode( $billing ) );
		$plan_id   = (string) get_post_meta( $id, '_a2ews_plan_id', true );
		$text      = array(
			'title'       => mb_substr( $title, 0, 30 ),
			'description' => mb_substr( $desc, 0, 1000 ),
			'visibility'  => 'visible',
		);
		if ( $plan_id && get_post_meta( $id, '_a2ews_signature', true ) === $signature ) {
			$res = Api::update_plan( $plan_id, $text );
			if ( is_wp_error( $res ) ) {
				return self::fail( $id, $res->get_error_message() );
			}
		} else {
			$body = array_merge(
				$text,
				$billing,
				array(
					'product_id'      => $product_id,
					'release_method'  => 'buy_now',
					'unlimited_stock' => true,
					'metadata'        => array( 'source' => 'a2e-whop-sync', 'wp_plan_id' => (string) $id ),
				)
			);
			$res  = Api::create_plan( $body );
			if ( is_wp_error( $res ) ) {
				return self::fail( $id, $res->get_error_message() );
			}
			if ( $plan_id ) {
				Api::update_plan( $plan_id, array( 'visibility' => 'hidden' ) );
				$old   = (array) get_post_meta( $id, '_a2ews_old_plan_ids', true );
				$old[] = $plan_id;
				update_post_meta( $id, '_a2ews_old_plan_ids', array_values( array_unique( array_filter( $old ) ) ) );
			}
			$plan_id = (string) pick( $res, array( 'id' ) );
			update_post_meta( $id, '_a2ews_plan_id', $plan_id );
			update_post_meta( $id, '_a2ews_signature', $signature );
		}
		$purchase = (string) pick( is_array( $res ) ? $res : array(), array( 'purchase_url', 'direct_link' ) );
		if ( '' === $purchase && $plan_id && '' === (string) get_post_meta( $id, '_a2ews_purchase_url', true ) ) {
			$purchase = 'https://whop.com/checkout/' . rawurlencode( $plan_id ) . '/';
		}
		if ( $purchase ) {
			update_post_meta( $id, '_a2ews_purchase_url', esc_url_raw( $purchase ) );
		}
		update_post_meta( $id, '_a2ews_synced_at', time() );
		delete_post_meta( $id, '_a2ews_error' );
		log( 'sync', sprintf( 'Synced “%s” to Whop (%s).', $title, $plan_id ) );
		return true;
	}

	/**
	 * Record a sync failure.
	 *
	 * @param int    $id      Post ID.
	 * @param string $message Message.
	 * @return \WP_Error
	 */
	protected static function fail( $id, $message ) {
		update_post_meta( $id, '_a2ews_error', $message );
		set_transient( 'a2ews_notice_' . get_current_user_id(), array( 'error', $message ), 60 );
		log( 'sync', sprintf( 'Sync failed for “%s”: %s', get_the_title( $id ), $message ), false );
		return new \WP_Error( 'a2ews_sync', $message );
	}

	/**
	 * Hide a plan (and its product) on Whop: used for drafts and trashed plans.
	 *
	 * @param int $id Post ID.
	 */
	public static function hide_on_whop( $id ) {
		if ( ! is_connected() ) {
			return;
		}
		$plan_id    = (string) get_post_meta( $id, '_a2ews_plan_id', true );
		$product_id = (string) get_post_meta( $id, '_a2ews_product_id', true );
		if ( $plan_id ) {
			Api::update_plan( $plan_id, array( 'visibility' => 'hidden' ) );
		}
		if ( $product_id ) {
			Api::update_product( $product_id, array( 'visibility' => 'hidden' ) );
		}
		if ( $plan_id || $product_id ) {
			log( 'sync', sprintf( 'Hid “%s” on Whop (existing subscribers are not affected).', get_the_title( $id ) ) );
		}
	}

	/**
	 * Trash handler.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function on_trash( $post_id ) {
		if ( CPT === get_post_type( $post_id ) ) {
			self::hide_on_whop( $post_id );
		}
	}

	/**
	 * Manual re-sync.
	 */
	public static function resync_action() {
		$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'a2ews_resync_' . $id );
		if ( ! current_user_can( 'edit_post', $id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-whop' ) );
		}
		$res = self::sync( $id );
		if ( true === $res ) {
			set_transient( 'a2ews_notice_' . get_current_user_id(), array( 'success', __( 'Plan synced to Whop.', 'a2e-whop' ) ), 60 );
		}
		wp_safe_redirect( get_edit_post_link( $id, 'raw' ) );
		exit;
	}

	/**
	 * Show the last sync notice.
	 */
	public static function notices() {
		$key    = 'a2ews_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! $notice ) {
			return;
		}
		delete_transient( $key );
		printf( '<div class="notice notice-%1$s is-dismissible"><p><strong>Whop:</strong> %2$s</p></div>', esc_attr( 'error' === $notice[0] ? 'error' : 'success' ), esc_html( $notice[1] ) );
	}

	/**
	 * List columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		return array(
			'cb'           => $cols['cb'],
			'title'        => __( 'Plan', 'a2e-whop' ),
			'a2ews_price'  => __( 'Price', 'a2e-whop' ),
			'taxonomy-' . Catalog::TAX => __( 'Category', 'a2e-whop' ),
			'a2ews_subs'   => __( 'Active subscribers', 'a2e-whop' ),
			'a2ews_status' => __( 'Whop', 'a2e-whop' ),
			'date'         => $cols['date'],
		);
	}

	/**
	 * Column values.
	 *
	 * @param string $col Column.
	 * @param int    $id  Post ID.
	 */
	public static function column( $col, $id ) {
		if ( 'a2ews_price' === $col ) {
			$d = self::display( $id );
			echo esc_html( $d['price'] . ' ' . $d['period'] );
		} elseif ( 'a2ews_subs' === $col ) {
			global $wpdb;
			$n = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Members::table() . " WHERE wp_plan_id = %d AND status IN ('active','trialing','past_due','canceling')", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
			echo (int) $n;
		} elseif ( 'a2ews_status' === $col ) {
			if ( self::is_quote_only( $id ) ) {
				echo '<span class="a2ews-pill">' . esc_html__( 'Website only', 'a2e-whop' ) . '</span>';
			} elseif ( get_post_meta( $id, '_a2ews_error', true ) ) {
				echo '<span class="a2ews-pill a2ews-pill--err" title="' . esc_attr( get_post_meta( $id, '_a2ews_error', true ) ) . '">' . esc_html__( 'Error', 'a2e-whop' ) . '</span>';
			} elseif ( get_post_meta( $id, '_a2ews_plan_id', true ) ) {
				echo '<span class="a2ews-pill a2ews-pill--ok">' . esc_html__( 'Synced', 'a2e-whop' ) . '</span>';
			} else {
				echo '<span class="a2ews-pill">' . esc_html__( 'Not synced', 'a2e-whop' ) . '</span>';
			}
		}
	}

	/**
	 * Display data for a plan.
	 *
	 * @param int $id Post ID.
	 * @return array price, period, note, amount, interval
	 */
	public static function display( $id ) {
		$billing  = self::meta( $id, 'billing' );
		$currency = self::meta( $id, 'currency' );
		$amount   = (float) self::meta( $id, 'price' );
		$days     = (int) self::meta( $id, 'every_days' );
		$periods  = array(
			'month'  => __( '/month', 'a2e-whop' ),
			'year'   => __( '/year', 'a2e-whop' ),
			/* translators: %d: days */
			'custom' => sprintf( _n( 'every %d day', 'every %d days', max( 1, $days ), 'a2e-whop' ), max( 1, $days ) ),
			'once'   => __( 'one-time', 'a2e-whop' ),
		);
		$note = self::meta( $id, 'note' );
		if ( self::is_quote_only( $id ) ) {
			return array(
				'price'    => __( 'Custom quote', 'a2e-whop' ),
				'period'   => '',
				'note'     => '' !== $note ? $note : __( 'Priced for your store', 'a2e-whop' ),
				'amount'   => null,
				'interval' => 'quote',
				'currency' => strtoupper( $currency ),
			);
		}
		if ( '' === $note ) {
			$parts = array();
			if ( (int) self::meta( $id, 'trial_days' ) > 0 && 'once' !== $billing ) {
				/* translators: %d: days */
				$parts[] = sprintf( __( '%d-day free trial', 'a2e-whop' ), (int) self::meta( $id, 'trial_days' ) );
			}
			if ( (float) self::meta( $id, 'setup_fee' ) > 0 && 'once' !== $billing ) {
				/* translators: %s: amount */
				$parts[] = sprintf( __( '+ %s one-time setup', 'a2e-whop' ), format_price( (float) self::meta( $id, 'setup_fee' ), $currency ) );
			}
			if ( 'once' === $billing ) {
				$parts[] = __( 'Pay once. No subscription.', 'a2e-whop' );
			} elseif ( ! $parts ) {
				$parts[] = __( 'Cancel anytime', 'a2e-whop' );
			}
			$note = implode( ' · ', $parts );
		}
		return array(
			'price'    => format_price( $amount, $currency ),
			'period'   => $periods[ $billing ] ?? '',
			'note'     => $note,
			'amount'   => $amount,
			'interval' => 'once' === $billing ? 'once' : ( 'year' === $billing ? 'year' : 'month' ),
			'currency' => strtoupper( $currency ),
		);
	}

	/**
	 * Monthly value of a WordPress plan (for the MRR estimate).
	 *
	 * @param int $id Post ID.
	 * @return float
	 */
	public static function monthly_value( $id ) {
		if ( ! $id || CPT !== get_post_type( $id ) ) {
			return 0.0;
		}
		$billing = self::meta( $id, 'billing' );
		$price   = (float) self::meta( $id, 'price' );
		if ( 'once' === $billing ) {
			return 0.0;
		}
		$days = 'year' === $billing ? 365 : ( 'custom' === $billing ? max( 1, (int) self::meta( $id, 'every_days' ) ) : 30 );
		return $price * 30 / $days;
	}

	/**
	 * Find the WordPress plan for a Whop plan ID (current or previous).
	 *
	 * @param string $whop_plan_id Whop plan ID.
	 * @return int
	 */
	public static function find_by_whop_plan( $whop_plan_id ) {
		static $cache = array();
		if ( isset( $cache[ $whop_plan_id ] ) ) {
			return $cache[ $whop_plan_id ];
		}
		$ids = get_posts(
			array(
				'post_type'      => CPT,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_a2ews_plan_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $whop_plan_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( ! $ids ) {
			foreach ( get_posts( array( 'post_type' => CPT, 'post_status' => 'any', 'posts_per_page' => 100, 'fields' => 'ids' ) ) as $pid ) {
				if ( in_array( $whop_plan_id, (array) get_post_meta( $pid, '_a2ews_old_plan_ids', true ), true ) ) {
					$ids = array( $pid );
					break;
				}
			}
		}
		$cache[ $whop_plan_id ] = $ids ? (int) $ids[0] : 0;
		return $cache[ $whop_plan_id ];
	}

	/**
	 * Quote-only service: no price, but a custom button link. Shown on the website, never sent to Whop.
	 *
	 * @param int $id Post ID.
	 * @return bool
	 */
	public static function is_quote_only( $id ) {
		return (float) self::meta( $id, 'price' ) <= 0 && '' !== self::meta( $id, 'link' );
	}

	/**
	 * Published plans for the website, in display order (menu order, then date).
	 *
	 * @param string $where 'shop' (everything shown in the shop) or 'pricing' (homepage and pricing page).
	 * @return array Theme-format plans.
	 */
	public static function published( $where = 'shop' ) {
		$posts = get_posts(
			array(
				'post_type'      => CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			)
		);
		$plans = array();
		foreach ( $posts as $p ) {
			if ( '1' !== self::meta( $p->ID, 'show' ) || ( 'pricing' === $where && '1' !== self::meta( $p->ID, 'on_pricing' ) ) ) {
				continue;
			}
			$plan = self::to_array( $p );
			if ( '' !== $plan['url'] ) {
				$plans[] = $plan;
			}
		}
		return $plans;
	}

	/**
	 * Plans for the homepage and pricing page (kept for the theme's pricing table).
	 *
	 * @return array
	 */
	public static function for_site() {
		return self::published( 'pricing' );
	}

	/**
	 * One plan in the format the A2E theme uses.
	 *
	 * @param \WP_Post $p Plan post.
	 * @return array
	 */
	public static function to_array( $p ) {
		$d        = self::display( $p->ID );
		$link     = self::meta( $p->ID, 'link' );
		$checkout = (string) get_post_meta( $p->ID, '_a2ews_purchase_url', true );
		$url      = '' !== $link ? ( 0 === strpos( $link, '/' ) && 0 !== strpos( $link, '//' ) ? home_url( $link ) : $link ) : $checkout;
		$compare  = (float) self::meta( $p->ID, 'compare_at' );
		$terms    = get_the_terms( $p, Catalog::TAX );
		$term     = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
		$more     = self::meta( $p->ID, 'learn_more' );
		$quote    = self::is_quote_only( $p->ID );
		$button   = self::meta( $p->ID, 'button' );
		if ( '' === $button ) {
			$button = $quote ? __( 'Get a quote', 'a2e-whop' ) : ( 'once' === $d['interval'] ? __( 'Buy now', 'a2e-whop' ) : __( 'Subscribe', 'a2e-whop' ) );
		}
		$content = trim( (string) $p->post_content );
		return array(
			'id'         => 'whop-' . $p->ID,
			'post_id'    => $p->ID,
			'slug'       => $p->post_name ? $p->post_name : sanitize_title( $p->post_title ),
			'name'       => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
			'badge'      => self::meta( $p->ID, 'badge' ),
			'desc'       => self::meta( $p->ID, 'desc' ),
			'details'    => '' !== $content ? wpautop( wp_kses_post( strip_shortcodes( $content ) ) ) : '',
			'icon'       => self::meta( $p->ID, 'icon' ),
			'best_for'   => self::meta( $p->ID, 'best_for' ),
			'delivery'   => self::meta( $p->ID, 'delivery' ),
			'price'      => $d['price'],
			'compare_at' => ( $compare > 0 && ! $quote && $compare > (float) $d['amount'] ) ? format_price( $compare, self::meta( $p->ID, 'currency' ) ) : '',
			'period'     => $d['period'],
			'note'       => $d['note'],
			'features'   => array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', self::meta( $p->ID, 'features' ) ) ) ) ),
			'button'     => $button,
			'url'        => $url ? esc_url_raw( $url ) : '',
			'checkout'   => '' === $link && '' !== $checkout,
			'learn_more' => '' !== $more ? ( 0 === strpos( $more, '/' ) && 0 !== strpos( $more, '//' ) ? home_url( $more ) : esc_url_raw( $more ) ) : '',
			'featured'   => '1' === self::meta( $p->ID, 'featured' ),
			'on_pricing' => '1' === self::meta( $p->ID, 'on_pricing' ),
			'amount'     => $d['amount'],
			'currency'   => $d['currency'],
			'interval'   => $d['interval'],
			'kind'       => $quote ? 'quote' : ( 'once' === $d['interval'] ? 'one-time' : 'subscription' ),
			'group'      => $term ? $term->slug : '',
			'group_name' => $term ? $term->name : '',
			'source'     => 'whop',
		);
	}
}
