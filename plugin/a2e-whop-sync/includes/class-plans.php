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
			'billing'    => array( __( 'Billing', 'a2e-whop' ), 'select', 'month', '' ),
			'every_days' => array( __( 'Bill every … days (custom billing only)', 'a2e-whop' ), 'number', '30', '' ),
			'price'      => array( __( 'Price', 'a2e-whop' ), 'number', '', __( 'Charged every period (or once for one-time plans).', 'a2e-whop' ) ),
			'currency'   => array( __( 'Currency', 'a2e-whop' ), 'currency', 'usd', '' ),
			'setup_fee'  => array( __( 'One-time setup fee (optional)', 'a2e-whop' ), 'number', '', __( 'Added to the first payment of a recurring plan.', 'a2e-whop' ) ),
			'trial_days' => array( __( 'Free trial days (optional)', 'a2e-whop' ), 'number', '', '' ),
			'desc'       => array( __( 'Short description', 'a2e-whop' ), 'textarea', '', __( 'One or two sentences under the plan name. Also used on Whop.', 'a2e-whop' ) ),
			'features'   => array( __( 'Features (one per line)', 'a2e-whop' ), 'lines', '', '' ),
			'badge'      => array( __( 'Badge (e.g. Most popular)', 'a2e-whop' ), 'text', '', '' ),
			'note'       => array( __( 'Small note under the price (optional)', 'a2e-whop' ), 'text', '', __( 'Leave empty to show the setup fee / trial automatically.', 'a2e-whop' ) ),
			'button'     => array( __( 'Button label', 'a2e-whop' ), 'text', '', '' ),
			'featured'   => array( __( 'Highlight this plan', 'a2e-whop' ), 'checkbox', '', '' ),
			'show'       => array( __( 'Show on the website pricing pages', 'a2e-whop' ), 'checkbox', '1', '' ),
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
				'supports'        => array( 'title', 'page-attributes' ),
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
		if ( '' === $value && 'show' === $key && ! metadata_exists( 'post', $id, '_a2ews_show' ) ) {
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
			$value = self::meta( $post->ID, $key );
			echo '<tr class="a2ews-row a2ews-row--' . esc_attr( $key ) . '"><th><label for="' . esc_attr( $id ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
			switch ( $f[1] ) {
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
		echo '<p class="description">' . esc_html__( 'Publishing (or updating) this plan creates it on your Whop account. Text changes update Whop; a price or billing change creates a new Whop plan so existing subscribers keep their price. Use “Order” in the Plan attributes box to sort plans on the website.', 'a2e-whop' ) . '</p>';
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
			echo '<p class="a2ews-pill">' . esc_html__( 'Not synced yet — publish to create it on Whop.', 'a2e-whop' ) . '</p>';
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
				$raw = isset( $in[ $key ] ) ? $in[ $key ] : '';
				switch ( $f[1] ) {
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
	 * Hide a plan (and its product) on Whop — used for drafts and trashed plans.
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
			if ( get_post_meta( $id, '_a2ews_error', true ) ) {
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
	 * Published plans shown on the website, in display order.
	 *
	 * @return array Theme-format plans.
	 */
	public static function for_site() {
		$posts = get_posts(
			array(
				'post_type'      => CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			)
		);
		$plans = array();
		foreach ( $posts as $p ) {
			$url = (string) get_post_meta( $p->ID, '_a2ews_purchase_url', true );
			if ( '1' !== self::meta( $p->ID, 'show' ) || '' === $url ) {
				continue;
			}
			$d       = self::display( $p->ID );
			$plans[] = array(
				'id'       => 'whop-' . $p->ID,
				'name'     => get_the_title( $p ),
				'badge'    => self::meta( $p->ID, 'badge' ),
				'desc'     => self::meta( $p->ID, 'desc' ),
				'price'    => $d['price'],
				'period'   => $d['period'],
				'note'     => $d['note'],
				'features' => array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', self::meta( $p->ID, 'features' ) ) ) ) ),
				'button'   => self::meta( $p->ID, 'button' ) ? self::meta( $p->ID, 'button' ) : ( 'once' === $d['interval'] ? __( 'Buy now', 'a2e-whop' ) : __( 'Subscribe', 'a2e-whop' ) ),
				'url'      => $url,
				'featured' => '1' === self::meta( $p->ID, 'featured' ),
				'amount'   => $d['amount'],
				'currency' => $d['currency'],
				'interval' => $d['interval'],
				'source'   => 'whop',
			);
		}
		return $plans;
	}
}
