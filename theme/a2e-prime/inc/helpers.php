<?php
/**
 * Small, reusable helpers used across templates.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache-busting version for a theme asset (file modification time).
 *
 * @param string $relative Path relative to the theme root.
 * @return string
 */
function a2e_asset_ver( $relative ) {
	$path = A2E_DIR . '/' . ltrim( $relative, '/' );
	return file_exists( $path ) ? A2E_VERSION . '.' . filemtime( $path ) : A2E_VERSION;
}

/**
 * Permalink for a page path, falling back to a pretty URL when the page does not exist yet.
 *
 * @param string $path Page path such as 'pricing' or 'free-tools/ebay-fee-calculator'.
 * @return string
 */
function a2e_url( $path = '' ) {
	$path = trim( (string) $path, '/' );
	if ( '' === $path ) {
		return home_url( '/' );
	}
	static $cache = array();
	if ( isset( $cache[ $path ] ) ) {
		return $cache[ $path ];
	}
	$page = get_page_by_path( $path );
	if ( $page && 'publish' === $page->post_status ) {
		$cache[ $path ] = get_permalink( $page );
	} else {
		$cache[ $path ] = home_url( user_trailingslashit( $path ) );
	}
	return $cache[ $path ];
}

/**
 * Resolve a settings value that may be a site-relative path ("/pricing/") into a full URL.
 *
 * @param string $value URL or path.
 * @return string
 */
function a2e_resolve_url( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( 0 === strpos( $value, '/' ) && 0 !== strpos( $value, '//' ) ) {
		return home_url( $value );
	}
	return $value;
}

/**
 * Blog index URL.
 *
 * @return string
 */
function a2e_blog_url() {
	$id = (int) get_option( 'page_for_posts' );
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return get_permalink( $id );
	}
	return home_url( '/blog/' );
}

/**
 * Click-to-chat WhatsApp URL.
 *
 * @param string $message Optional prefilled message.
 * @return string
 */
function a2e_wa_link( $message = '' ) {
	$number = preg_replace( '/\D+/', '', (string) a2e_opt( 'whatsapp' ) );
	if ( ! $number ) {
		return a2e_url( 'contact' );
	}
	$url = 'https://wa.me/' . $number;
	if ( $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/**
 * URL of a bundled theme image.
 *
 * @param string $file Path inside assets/images.
 * @return string
 */
function a2e_img( $file ) {
	return A2E_URI . '/assets/images/' . ltrim( $file, '/' );
}

/**
 * Logo markup with separate light and dark artwork.
 *
 * Uses the Customizer logo (light backgrounds) plus the dark-mode logo from
 * A2E → Site Settings, falling back to the bundled A2E artwork.
 *
 * @param string $location header|footer.
 * @return string
 */
function a2e_logo( $location = 'header' ) {
	$name      = get_bloginfo( 'name' );
	$is_header = 'header' === $location;
	$light_id  = absint( get_theme_mod( 'custom_logo' ) );
	$dark_url  = a2e_opt( 'logo_dark' );
	$loading   = $is_header ? ' fetchpriority="high"' : ' loading="lazy"';

	if ( $light_id ) {
		$light = wp_get_attachment_image(
			$light_id,
			'full',
			false,
			array(
				'class'    => 'logo__img logo__img--light',
				'alt'      => $name,
				'loading'  => $is_header ? 'eager' : 'lazy',
				'decoding' => 'async',
			)
		);
	} else {
		$light = sprintf(
			'<img class="logo__img logo__img--light" src="%1$s" srcset="%1$s 1x, %2$s 2x" width="173" height="60" alt="%3$s" decoding="async"%4$s>',
			esc_url( a2e_img( 'brand/a2e-logo-light.webp' ) ),
			esc_url( a2e_img( 'brand/a2e-logo-light@2x.webp' ) ),
			esc_attr( $name ),
			$loading
		);
	}

	if ( $dark_url ) {
		$dark = sprintf( '<img class="logo__img logo__img--dark" src="%1$s" height="60" alt="%2$s" decoding="async" loading="lazy">', esc_url( $dark_url ), esc_attr( $name ) );
	} elseif ( ! $light_id ) {
		$dark = sprintf(
			'<img class="logo__img logo__img--dark" src="%1$s" srcset="%1$s 1x, %2$s 2x" width="183" height="60" alt="%3$s" decoding="async" loading="lazy">',
			esc_url( a2e_img( 'brand/a2e-logo-dark.webp' ) ),
			esc_url( a2e_img( 'brand/a2e-logo-dark@2x.webp' ) ),
			esc_attr( $name )
		);
	} else {
		$dark = '';
	}

	return sprintf(
		'<a class="logo logo--%1$s%2$s" href="%3$s" rel="home" aria-label="%4$s">%5$s%6$s</a>',
		esc_attr( $location ),
		$dark ? ' logo--has-dark' : '',
		esc_url( home_url( '/' ) ),
		/* translators: %s: site name. */
		esc_attr( sprintf( __( '%s — home', 'a2e' ), $name ) ),
		$light,
		$dark
	);
}

/**
 * Whether a dedicated SEO plugin is handling titles, meta and schema.
 *
 * @return bool
 */
function a2e_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || class_exists( 'The_SEO_Framework\Load' );
}

/**
 * Estimated reading time in minutes.
 *
 * @param int|WP_Post|null $post Post.
 * @return int
 */
function a2e_reading_time( $post = null ) {
	$post  = get_post( $post );
	$words = $post ? str_word_count( wp_strip_all_tags( $post->post_content ) ) : 0;
	return max( 1, (int) ceil( $words / 230 ) );
}

/**
 * Split a multi-line setting into trimmed, non-empty lines.
 *
 * @param string|array $text Text.
 * @return array
 */
function a2e_lines( $text ) {
	if ( is_array( $text ) ) {
		return array_values( array_filter( array_map( 'trim', $text ) ) );
	}
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}

/**
 * Request path relative to the WordPress home (no leading/trailing slashes).
 *
 * @return string
 */
function a2e_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $home && 0 === strpos( $path . '/', $home . '/' ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}
	return rawurldecode( $path );
}

/**
 * Per-page overridable text (page hero meta box) with a default.
 *
 * @param string $key     Meta key suffix: eyebrow, title, intro.
 * @param string $default Default.
 * @param int    $post_id Post ID (defaults to the queried object).
 * @return string
 */
function a2e_page_text( $key, $default = '', $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_queried_object_id();
	if ( $post_id ) {
		$value = get_post_meta( $post_id, '_a2e_hero_' . $key, true );
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			return trim( $value );
		}
	}
	return $default;
}

/**
 * Pricing plans for display. The A2E Whop Sync plugin supplies live plans through the
 * `a2e_pricing_plans` filter; otherwise the fallback plans from Site Settings are used.
 *
 * Each plan: id, name, badge, desc, price, period, note, features[], button, url,
 * featured (bool), amount (float|null), currency, interval ('month'|'year'|'once'|''), source.
 *
 * @return array
 */
function a2e_get_plans() {
	static $plans = null;
	if ( null !== $plans ) {
		return $plans;
	}
	$fallback = array();
	for ( $i = 1; $i <= 3; $i++ ) {
		$name = a2e_opt( "plan_{$i}_name" );
		if ( '' === $name ) {
			continue;
		}
		$price  = a2e_opt( "plan_{$i}_price" );
		$period = a2e_opt( "plan_{$i}_period" );
		$url    = a2e_resolve_url( a2e_opt( "plan_{$i}_url" ) );
		$fallback[] = array(
			'id'       => 'plan-' . $i,
			'name'     => $name,
			'badge'    => a2e_opt( "plan_{$i}_badge" ),
			'desc'     => a2e_opt( "plan_{$i}_desc" ),
			'price'    => $price,
			'period'   => $period,
			'note'     => a2e_opt( "plan_{$i}_note" ),
			'features' => a2e_lines( a2e_opt( "plan_{$i}_features" ) ),
			'button'   => a2e_opt( "plan_{$i}_button" ),
			'url'      => $url ? $url : add_query_arg( 'plan', rawurlencode( $name ), a2e_url( 'quote-request' ) ),
			'featured' => (string) $i === (string) a2e_opt( 'plan_featured' ),
			'amount'   => is_numeric( preg_replace( '/[^0-9.]/', '', $price ) ) ? (float) preg_replace( '/[^0-9.]/', '', $price ) : null,
			'currency' => 'USD',
			'interval' => false !== stripos( $period, 'month' ) ? 'month' : ( false !== stripos( $period, 'year' ) ? 'year' : 'once' ),
			'source'   => 'theme',
		);
	}
	$plans = apply_filters( 'a2e_pricing_plans', $fallback );
	if ( empty( $plans ) ) {
		$plans = $fallback;
	}
	return $plans;
}

/**
 * Whether live plans are coming from Whop.
 *
 * @return bool
 */
function a2e_plans_from_whop() {
	$plans = a2e_get_plans();
	return ! empty( $plans ) && isset( $plans[0]['source'] ) && 'whop' === $plans[0]['source'];
}

/**
 * Client area URL (the Whop plugin can point this at the customer portal).
 *
 * @return string
 */
function a2e_account_url() {
	return apply_filters( 'a2e_account_url', a2e_url( 'account' ) );
}

/**
 * Format a number for display with a suffix-aware short scale (12.4K).
 *
 * @param float $n Number.
 * @return string
 */
function a2e_short_number( $n ) {
	if ( $n >= 1000000 ) {
		return round( $n / 1000000, 1 ) . 'M';
	}
	if ( $n >= 1000 ) {
		return round( $n / 1000, 1 ) . 'K';
	}
	return (string) $n;
}

/**
 * Echo an attribute-safe JSON blob for data-* attributes.
 *
 * @param mixed $data Data.
 * @return string
 */
function a2e_json_attr( $data ) {
	return esc_attr( wp_json_encode( $data ) );
}
