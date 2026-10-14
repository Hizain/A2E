<?php
/**
 * Services shop: one wide page with every monthly plan and one-time service, grouped by
 * category. Plans come from the A2E Whop Sync plugin (or the fallback plans in Site Settings).
 *
 * When WooCommerce is installed, the theme takes over its shop page so the old /shop/ link keeps
 * working without product listings or product filters, and old product pages are sent to the
 * matching service in the shop.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page that shows the services shop: WooCommerce's shop page if one was set (even after
 * WooCommerce is turned off), otherwise a page with the slug shop, services-shop or package-shop.
 *
 * @return int Page ID or 0.
 */
function a2e_shop_page_id() {
	static $found = 0;
	if ( $found ) {
		return $found;
	}
	$id = (int) get_option( 'woocommerce_shop_page_id' );
	if ( ! ( $id > 0 && 'page' === get_post_type( $id ) && 'publish' === get_post_status( $id ) ) ) {
		$id = 0;
		foreach ( array( 'shop', 'services-shop', 'package-shop' ) as $slug ) {
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$id = (int) $page->ID;
				break;
			}
		}
	}
	$found = (int) apply_filters( 'a2e_shop_page_id', $id );
	return $found;
}

/**
 * Shop URL.
 *
 * @return string
 */
function a2e_shop_url() {
	$id = a2e_shop_page_id();
	return $id ? get_permalink( $id ) : a2e_url( 'shop' );
}

/**
 * Whether this request shows the services shop.
 *
 * @return bool
 */
function a2e_is_shop_request() {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	$id = a2e_shop_page_id();
	return $id && is_page( $id );
}

/**
 * Use the services shop template for the shop page (and WooCommerce's product archive).
 *
 * @param string $template Template.
 * @return string
 */
function a2e_shop_template( $template ) {
	if ( a2e_is_shop_request() ) {
		$shop = locate_template( 'shop-services.php' );
		if ( $shop ) {
			return $shop;
		}
	}
	return $template;
}
add_filter( 'template_include', 'a2e_shop_template', 99 );

/**
 * Send old WooCommerce product and product-category pages to the services shop
 * (to the matching service when a plan was imported from that product).
 */
function a2e_shop_redirect_products() {
	if ( '1' !== a2e_opt( 'shop_redirect_products' ) || is_preview() ) {
		return;
	}
	$is_product = function_exists( 'is_product' ) && is_product();
	$is_tax     = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
	if ( ! $is_product && ! $is_tax ) {
		return;
	}
	$url = a2e_shop_url();
	if ( $is_product && post_type_exists( 'a2ews_plan' ) ) {
		$plans = get_posts(
			array(
				'post_type'      => 'a2ews_plan',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_a2ews_wc_product', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => get_queried_object_id(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		if ( $plans ) {
			$url .= '#plan-' . $plans[0]->post_name;
		}
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'a2e_shop_redirect_products', 2 );

/**
 * After WooCommerce is switched off, old /product/... and /product-category/... links 404 and
 * its cart, checkout and account pages are left behind. Send them to the shop (to the matching
 * imported service when there is one) and to the client area.
 */
function a2e_shop_redirect_old_products() {
	if ( class_exists( 'WooCommerce' ) || ! a2e_shop_page_id() ) {
		return;
	}
	// WooCommerce's own cart, checkout and account pages are left with broken shortcodes.
	if ( is_page() ) {
		$id = get_queried_object_id();
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $key ) {
			if ( $id && (int) get_option( 'woocommerce_' . $key . '_page_id' ) === $id ) {
				$to = 'myaccount' === $key ? a2e_account_url() : a2e_shop_url();
				if ( untrailingslashit( $to ) !== untrailingslashit( get_permalink( $id ) ) ) {
					wp_safe_redirect( $to, 301 );
					exit;
				}
			}
		}
		return;
	}
	if ( ! is_404() ) {
		return;
	}
	$path = a2e_request_path();
	if ( ! preg_match( '#^(product|product-category|product-tag)/([^/]+)#', $path, $m ) ) {
		return;
	}
	$url = a2e_shop_url();
	if ( 'product' === $m[1] && post_type_exists( 'a2ews_plan' ) ) {
		$product = get_page_by_path( sanitize_title( $m[2] ), OBJECT, 'product' );
		if ( $product ) {
			$plans = get_posts(
				array(
					'post_type'      => 'a2ews_plan',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_key'       => '_a2ews_wc_product', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $product->ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			if ( $plans ) {
				$url .= '#plan-' . $plans[0]->post_name;
			}
		}
	}
	wp_safe_redirect( $url, 301 );
	exit;
}
add_action( 'template_redirect', 'a2e_shop_redirect_old_products', 1 );

/**
 * When WooCommerce is switched off, its saved permalink rules can keep pointing /shop/ at the
 * old product archive, which sends visitors to the homepage. Refresh the rules once.
 */
function a2e_shop_refresh_stale_rules() {
	if ( post_type_exists( 'product' ) || get_transient( 'a2e_rules_checked' ) ) {
		return;
	}
	set_transient( 'a2e_rules_checked', 1, DAY_IN_SECONDS );
	$rules = get_option( 'rewrite_rules' );
	if ( is_array( $rules ) && false !== strpos( implode( ' ', $rules ), 'post_type=product' ) ) {
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'a2e_shop_refresh_stale_rules', 99 );

/**
 * Check again after any plugin is switched off.
 */
function a2e_shop_rules_recheck() {
	delete_transient( 'a2e_rules_checked' );
}
add_action( 'deactivated_plugin', 'a2e_shop_rules_recheck' );

/**
 * WooCommerce pages that still need WooCommerce's own styles.
 *
 * @return bool
 */
function a2e_woo_needs_styles() {
	return ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_account_page' ) && is_account_page() );
}

/**
 * Keep WooCommerce's stylesheets off the designed pages. Its image rule
 * (".woocommerce-page img { height: auto }") otherwise blows the logo up to full size.
 *
 * @param array $styles Styles.
 * @return array
 */
function a2e_woo_styles( $styles ) {
	return a2e_woo_needs_styles() ? $styles : array();
}
add_filter( 'woocommerce_enqueue_styles', 'a2e_woo_styles', 99 );

/**
 * Drop WooCommerce block styles outside cart, checkout and account.
 */
function a2e_woo_dequeue() {
	if ( ! class_exists( 'WooCommerce' ) || a2e_woo_needs_styles() ) {
		return;
	}
	foreach ( array( 'wc-blocks-style', 'wc-blocks-vendors-style', 'woocommerce-inline', 'wc-block-style', 'woocommerce-blocktheme' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'a2e_woo_dequeue', 100 );

/**
 * Shop body classes (without WooCommerce's, so no product styling leaks in).
 *
 * @param array $classes Classes.
 * @return array
 */
function a2e_shop_body_class( $classes ) {
	if ( a2e_is_shop_request() ) {
		$classes   = array_values( array_diff( $classes, array( 'woocommerce', 'woocommerce-page', 'woocommerce-shop', 'post-type-archive', 'post-type-archive-product', 'archive' ) ) );
		$classes[] = 'a2e-shop-page';
	}
	return $classes;
}
add_filter( 'body_class', 'a2e_shop_body_class', 99 );

/**
 * Everything the shop shows. The Whop plugin fills it through the `a2e_shop_catalog` filter;
 * otherwise the pricing plans (Site Settings fallback) are used.
 *
 * @return array groups[], plans[], source
 */
function a2e_shop_catalog() {
	static $catalog = null;
	if ( null !== $catalog ) {
		return $catalog;
	}
	$fallback = array(
		'groups' => array(),
		'plans'  => a2e_get_plans(),
		'source' => a2e_plans_from_whop() ? 'whop' : 'theme',
	);
	$catalog  = apply_filters( 'a2e_shop_catalog', $fallback );
	if ( ! is_array( $catalog ) || empty( $catalog['plans'] ) ) {
		$catalog = $fallback;
	}
	$defaults = array(
		'id'         => '',
		'slug'       => '',
		'name'       => '',
		'badge'      => '',
		'desc'       => '',
		'details'    => '',
		'icon'       => '',
		'best_for'   => '',
		'delivery'   => '',
		'price'      => '',
		'compare_at' => '',
		'period'     => '',
		'note'       => '',
		'features'   => array(),
		'button'     => '',
		'url'        => '',
		'checkout'   => false,
		'learn_more' => '',
		'featured'   => false,
		'kind'       => '',
		'group'      => '',
		'group_name' => '',
		'interval'   => '',
		'amount'     => null,
		'currency'   => 'USD',
		'source'     => 'theme',
	);
	$plans    = array();
	$slugs    = array();
	foreach ( (array) $catalog['plans'] as $plan ) {
		$plan = wp_parse_args( (array) $plan, $defaults );
		if ( '' === $plan['kind'] ) {
			$plan['kind'] = 'once' === $plan['interval'] ? 'one-time' : ( 'quote' === $plan['interval'] ? 'quote' : 'subscription' );
		}
		if ( '' === $plan['icon'] ) {
			$plan['icon'] = 'one-time' === $plan['kind'] ? 'package' : ( 'quote' === $plan['kind'] ? 'handshake' : 'store' );
		}
		$slug = sanitize_title( $plan['slug'] ? $plan['slug'] : $plan['name'] );
		while ( isset( $slugs[ $slug ] ) ) {
			$slug .= '-2';
		}
		$slugs[ $slug ] = true;
		$plan['slug']   = $slug;
		if ( '' === $plan['button'] ) {
			$plan['button'] = 'quote' === $plan['kind'] ? __( 'Get a quote', 'a2e' ) : ( 'one-time' === $plan['kind'] ? __( 'Buy now', 'a2e' ) : __( 'Subscribe', 'a2e' ) );
		}
		$plans[] = $plan;
	}
	$catalog = array(
		'groups' => isset( $catalog['groups'] ) && is_array( $catalog['groups'] ) ? $catalog['groups'] : array(),
		'plans'  => $plans,
		'source' => isset( $catalog['source'] ) ? $catalog['source'] : 'theme',
	);
	return $catalog;
}

/**
 * Shop sections: categories in order, each with its plans. Plans without a category are
 * grouped into "Monthly plans" and "One-time services".
 *
 * @return array slug, name, desc, icon, plans[]
 */
function a2e_shop_sections() {
	$catalog  = a2e_shop_catalog();
	$sections = array();
	foreach ( $catalog['groups'] as $group ) {
		$group = wp_parse_args( (array) $group, array( 'slug' => '', 'name' => '', 'desc' => '', 'icon' => 'package' ) );
		if ( '' !== $group['slug'] ) {
			$sections[ $group['slug'] ] = $group + array( 'plans' => array() );
		}
	}
	$auto = array(
		'_monthly' => array(
			'slug'  => 'monthly-plans',
			'name'  => __( 'Monthly plans', 'a2e' ),
			'desc'  => __( 'Ongoing work on your store every month. No long-term contract, cancel anytime.', 'a2e' ),
			'icon'  => 'calendar',
			'plans' => array(),
		),
		'_once'    => array(
			'slug'  => 'one-time-services',
			'name'  => __( 'One-time services', 'a2e' ),
			'desc'  => __( 'Pay once for a clearly scoped job, delivered with a report.', 'a2e' ),
			'icon'  => 'package',
			'plans' => array(),
		),
	);
	foreach ( $catalog['plans'] as $plan ) {
		if ( '' !== $plan['group'] && isset( $sections[ $plan['group'] ] ) ) {
			$sections[ $plan['group'] ]['plans'][] = $plan;
		} else {
			$auto[ 'one-time' === $plan['kind'] ? '_once' : '_monthly' ]['plans'][] = $plan;
		}
	}
	$out = array();
	foreach ( array_merge( $sections, $auto ) as $section ) {
		if ( ! empty( $section['plans'] ) ) {
			$out[] = $section;
		}
	}
	return $out;
}

/**
 * Plain-language label for a plan's billing.
 *
 * @param array $plan Plan.
 * @return string
 */
function a2e_shop_kind_label( $plan ) {
	if ( 'quote' === $plan['kind'] ) {
		return __( 'Custom quote', 'a2e' );
	}
	if ( 'one-time' === $plan['kind'] ) {
		return __( 'One-time service', 'a2e' );
	}
	return 'year' === $plan['interval'] ? __( 'Yearly plan', 'a2e' ) : __( 'Monthly plan', 'a2e' );
}

/**
 * Period shown after the price ("/month", "one-time").
 *
 * @param array $plan Plan.
 * @return string
 */
function a2e_shop_period( $plan ) {
	return 'quote' === $plan['kind'] ? '' : (string) $plan['period'];
}

/**
 * One service card.
 *
 * @param array $plan    Plan.
 * @param array $section Section.
 */
function a2e_render_service_card( $plan, $section ) {
	$features = array_values( array_filter( (array) $plan['features'] ) );
	$shown    = array_slice( $features, 0, 4 );
	$extra    = count( $features ) - count( $shown );
	$kind     = sanitize_html_class( $plan['kind'] );
	$rel      = $plan['checkout'] ? ' rel="nofollow"' : '';
	?>
	<article class="sv-card sv-card--<?php echo esc_attr( $kind ); ?><?php echo $plan['featured'] ? ' is-featured' : ''; ?>" id="plan-<?php echo esc_attr( $plan['slug'] ); ?>" data-kind="<?php echo esc_attr( $kind ); ?>" data-reveal>
		<?php if ( $plan['badge'] ) : ?><span class="sv-card__badge"><?php a2e_the_icon( $plan['featured'] ? 'star' : 'sparkle' ); ?><?php echo esc_html( $plan['badge'] ); ?></span><?php endif; ?>
		<div class="sv-card__top">
			<span class="sv-card__icon"><?php a2e_the_icon( $plan['icon'] ); ?></span>
			<span class="sv-card__kind"><?php echo esc_html( a2e_shop_kind_label( $plan ) ); ?></span>
		</div>
		<h3 class="sv-card__title"><?php echo esc_html( $plan['name'] ); ?></h3>
		<?php if ( $plan['desc'] ) : ?><p class="sv-card__desc"><?php echo esc_html( $plan['desc'] ); ?></p><?php endif; ?>
		<?php if ( $plan['best_for'] ) : ?><p class="sv-card__best"><?php a2e_the_icon( 'target' ); ?><span><strong><?php esc_html_e( 'Best for:', 'a2e' ); ?></strong> <?php echo esc_html( $plan['best_for'] ); ?></span></p><?php endif; ?>
		<?php if ( $shown ) : ?>
			<ul class="sv-card__list">
				<?php foreach ( $shown as $feature ) : ?><li><?php a2e_the_icon( 'check' ); ?><span><?php echo esc_html( $feature ); ?></span></li><?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $extra > 0 ) : ?><p class="sv-card__more"><?php /* translators: %d: number of extra features */ printf( esc_html( _n( '+ %d more included', '+ %d more included', $extra, 'a2e' ) ), (int) $extra ); ?></p><?php endif; ?>
		<div class="sv-card__foot">
			<?php if ( $plan['delivery'] ) : ?><p class="sv-card__eta"><?php a2e_the_icon( 'clock' ); ?><?php echo esc_html( $plan['delivery'] ); ?></p><?php endif; ?>
			<div class="sv-card__price">
				<?php if ( $plan['compare_at'] ) : ?><del><?php echo esc_html( $plan['compare_at'] ); ?></del><?php endif; ?>
				<strong><?php echo esc_html( $plan['price'] ); ?></strong>
				<?php if ( a2e_shop_period( $plan ) ) : ?><span><?php echo esc_html( a2e_shop_period( $plan ) ); ?></span><?php endif; ?>
			</div>
			<?php if ( $plan['note'] ) : ?><p class="sv-card__note"><?php echo esc_html( $plan['note'] ); ?></p><?php endif; ?>
			<div class="sv-card__actions">
				<a class="btn <?php echo $plan['featured'] ? 'btn--primary' : 'btn--dark'; ?> btn--block" href="<?php echo esc_url( $plan['url'] ); ?>"<?php echo $rel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span><?php echo esc_html( $plan['button'] ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
				<button type="button" class="btn btn--ghost sv-card__details-btn" data-shop-details aria-haspopup="dialog"><span><?php esc_html_e( 'Details', 'a2e' ); ?></span></button>
			</div>
		</div>
		<div class="sv-card__details" data-details hidden>
			<div class="sv-detail">
				<div class="sv-detail__head">
					<span class="sv-card__icon"><?php a2e_the_icon( $plan['icon'] ); ?></span>
					<div>
						<p class="sv-detail__kind"><?php echo esc_html( a2e_shop_kind_label( $plan ) ); ?><?php echo $section['name'] ? ' · ' . esc_html( $section['name'] ) : ''; ?></p>
						<p class="sv-detail__title"><?php echo esc_html( $plan['name'] ); ?></p>
					</div>
					<div class="sv-detail__price">
						<?php if ( $plan['compare_at'] ) : ?><del><?php echo esc_html( $plan['compare_at'] ); ?></del><?php endif; ?>
						<strong><?php echo esc_html( $plan['price'] ); ?></strong><span><?php echo esc_html( a2e_shop_period( $plan ) ); ?></span>
					</div>
				</div>
				<div class="sv-detail__grid">
					<div class="sv-detail__main prose">
						<?php
						if ( $plan['details'] ) {
							echo wp_kses_post( $plan['details'] );
						} elseif ( $plan['desc'] ) {
							echo '<p>' . esc_html( $plan['desc'] ) . '</p>';
						}
						?>
						<?php if ( $plan['learn_more'] ) : ?><p><a href="<?php echo esc_url( $plan['learn_more'] ); ?>"><?php esc_html_e( 'Learn more about this service', 'a2e' ); ?></a></p><?php endif; ?>
					</div>
					<aside class="sv-detail__aside">
						<?php if ( $features ) : ?>
							<p class="sv-detail__label"><?php esc_html_e( 'What is included', 'a2e' ); ?></p>
							<ul class="sv-card__list">
								<?php foreach ( $features as $feature ) : ?><li><?php a2e_the_icon( 'check' ); ?><span><?php echo esc_html( $feature ); ?></span></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<dl class="sv-detail__facts">
							<?php if ( $plan['best_for'] ) : ?><div><dt><?php esc_html_e( 'Best for', 'a2e' ); ?></dt><dd><?php echo esc_html( $plan['best_for'] ); ?></dd></div><?php endif; ?>
							<?php if ( $plan['delivery'] ) : ?><div><dt><?php esc_html_e( 'Delivery', 'a2e' ); ?></dt><dd><?php echo esc_html( $plan['delivery'] ); ?></dd></div><?php endif; ?>
							<?php if ( $plan['note'] ) : ?><div><dt><?php esc_html_e( 'Billing', 'a2e' ); ?></dt><dd><?php echo esc_html( $plan['note'] ); ?></dd></div><?php endif; ?>
						</dl>
					</aside>
				</div>
				<div class="sv-detail__actions">
					<?php
					a2e_buttons(
						array(
							array( $plan['button'], $plan['url'], 'primary' ),
							/* translators: %s: service name */
							array( __( 'Ask a question', 'a2e' ), a2e_wa_link( sprintf( __( 'Hi A2E! I have a question about %s.', 'a2e' ), $plan['name'] ) ), 'wa' ),
						)
					);
					?>
				</div>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Structured data for the shop: an OfferCatalog of every priced service.
 */
function a2e_shop_schema() {
	$offers = array();
	foreach ( a2e_shop_catalog()['plans'] as $plan ) {
		if ( null === $plan['amount'] || 'quote' === $plan['kind'] ) {
			continue;
		}
		$offer = array(
			'@type'         => 'Offer',
			'name'          => $plan['name'],
			'description'   => $plan['desc'],
			'price'         => (string) $plan['amount'],
			'priceCurrency' => strtoupper( (string) $plan['currency'] ),
			'url'           => a2e_shop_url() . '#plan-' . $plan['slug'],
			'availability'  => 'https://schema.org/InStock',
			'itemOffered'   => array(
				'@type'    => 'Service',
				'name'     => $plan['name'],
				'provider' => array( '@id' => home_url( '/' ) . '#organization' ),
			),
		);
		if ( 'subscription' === $plan['kind'] ) {
			$offer['priceSpecification'] = array(
				'@type'           => 'UnitPriceSpecification',
				'price'           => (string) $plan['amount'],
				'priceCurrency'   => strtoupper( (string) $plan['currency'] ),
				'unitText'        => 'year' === $plan['interval'] ? 'YEAR' : 'MONTH',
				'referenceQuantity' => array( '@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'year' === $plan['interval'] ? 'ANN' : 'MON' ),
			);
		}
		if ( $plan['group_name'] ) {
			$offer['category'] = $plan['group_name'];
		}
		$offers[] = $offer;
	}
	if ( ! $offers ) {
		return;
	}
	$data = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'OfferCatalog',
		'name'            => __( 'Amazon to eBay dropshipping services', 'a2e' ),
		'url'             => a2e_shop_url(),
		'itemListElement' => $offers,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
