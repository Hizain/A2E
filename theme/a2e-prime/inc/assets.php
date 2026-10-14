<?php
/**
 * Front-end and admin assets.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue front-end CSS and JS.
 */
function a2e_enqueue_assets() {
	wp_enqueue_style( 'a2e-main', A2E_URI . '/assets/css/main.css', array(), a2e_asset_ver( 'assets/css/main.css' ) );

	wp_enqueue_script( 'a2e-app', A2E_URI . '/assets/js/app.js', array(), a2e_asset_ver( 'assets/js/app.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'a2e-app',
		'a2eApp',
		array(
			'plans' => array_map(
				function ( $p ) {
					return array(
						'name'   => $p['name'],
						'price'  => $p['price'],
						'period' => $p['period'],
						'url'    => $p['url'],
					);
				},
				a2e_get_plans()
			),
			'quoteUrl' => a2e_url( 'quote-request' ),
			'fees'     => a2e_fee_settings(),
		)
	);

	if ( a2e_current_tool() || is_page( 'free-tools' ) || a2e_page_has_tool_shortcode() ) {
		wp_enqueue_script( 'a2e-tools', A2E_URI . '/assets/js/tools.js', array(), a2e_asset_ver( 'assets/js/tools.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script( 'a2e-tools', 'a2eFees', a2e_fee_settings() );
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// Pages built entirely from theme components do not need the block library CSS.
	if ( is_front_page() || a2e_current_tool() || a2e_is_designed_page() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'a2e_enqueue_assets', 20 );

/**
 * Whether the current singular content uses a tool shortcode.
 *
 * @return bool
 */
function a2e_page_has_tool_shortcode() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_queried_object();
	return $post instanceof WP_Post && ( has_shortcode( $post->post_content, 'a2e_tool' ) || has_shortcode( $post->post_content, 'a2e_tools_hub' ) );
}

/**
 * Theme bootstrap in <head>: set the colour mode before first paint (no flash), preload fonts.
 */
function a2e_head_bootstrap() {
	?>
	<script>(function(){try{var t=localStorage.getItem('a2e-theme');}catch(e){}var d=document.documentElement;if(t!=='light'&&t!=='dark'){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}d.setAttribute('data-theme',t);d.classList.add('js');})();</script>
	<meta name="color-scheme" content="light dark">
	<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#070a1a" media="(prefers-color-scheme: dark)">
	<link rel="preload" href="<?php echo esc_url( A2E_URI . '/assets/fonts/geist.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="<?php echo esc_url( A2E_URI . '/assets/fonts/instrument-serif-italic.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<style id="a2e-fonts">
		@font-face{font-family:"Geist";src:url("<?php echo esc_url( A2E_URI . '/assets/fonts/geist.woff2' ); ?>") format("woff2");font-weight:100 900;font-style:normal;font-display:swap}
		@font-face{font-family:"Geist Mono";src:url("<?php echo esc_url( A2E_URI . '/assets/fonts/geist-mono.woff2' ); ?>") format("woff2");font-weight:100 900;font-style:normal;font-display:swap}
		@font-face{font-family:"Instrument Serif";src:url("<?php echo esc_url( A2E_URI . '/assets/fonts/instrument-serif-italic.woff2' ); ?>") format("woff2");font-weight:400;font-style:italic;font-display:swap}
	</style>
	<?php
}
add_action( 'wp_head', 'a2e_head_bootstrap', 1 );

/**
 * Favicons and app icons (only when no Site Icon is set in the Customizer).
 */
function a2e_favicons() {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( a2e_img( 'brand/favicon.ico' ) ) );
	printf( '<link rel="icon" type="image/png" sizes="32x32" href="%s">' . "\n", esc_url( a2e_img( 'brand/a2e-icon-32.png' ) ) );
	printf( '<link rel="icon" type="image/png" sizes="192x192" href="%s">' . "\n", esc_url( a2e_img( 'brand/a2e-icon-192.png' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( a2e_img( 'brand/a2e-icon-180.png' ) ) );
}
add_action( 'wp_head', 'a2e_favicons', 2 );
add_action( 'admin_head', 'a2e_favicons' );

/**
 * Google Analytics 4 (only when an ID is set).
 */
function a2e_ga4() {
	$id = preg_replace( '/[^A-Z0-9-]/', '', strtoupper( a2e_opt( 'ga4_id' ) ) );
	if ( ! $id || is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}
	?>
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $id ); ?>"></script>
	<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo esc_js( $id ); ?>');</script>
	<?php
}
add_action( 'wp_head', 'a2e_ga4', 50 );

/**
 * Admin assets for the settings screens and meta boxes.
 *
 * @param string $hook Hook suffix.
 */
function a2e_admin_assets( $hook ) {
	wp_enqueue_style( 'a2e-admin', A2E_URI . '/assets/css/admin.css', array(), a2e_asset_ver( 'assets/css/admin.css' ) );
	if ( false !== strpos( $hook, 'a2e' ) ) {
		wp_enqueue_script( 'a2e-admin', A2E_URI . '/assets/js/admin.js', array( 'jquery' ), a2e_asset_ver( 'assets/js/admin.js' ), true );
	}
}
add_action( 'admin_enqueue_scripts', 'a2e_admin_assets' );

/**
 * eBay fee settings passed to the calculators.
 *
 * @return array
 */
function a2e_fee_settings() {
	$num = function ( $key ) {
		return (float) str_replace( array( ',', '$', '%' ), '', a2e_opt( $key ) );
	};
	return array(
		'categories'    => array(
			array( 'id' => 'default', 'label' => __( 'Most categories', 'a2e' ), 'rate' => $num( 'fee_rate' ) ),
			array( 'id' => 'media', 'label' => __( 'Books, movies & music', 'a2e' ), 'rate' => $num( 'fee_rate_media' ) ),
			array( 'id' => 'guitars', 'label' => __( 'Guitars & basses', 'a2e' ), 'rate' => $num( 'fee_rate_guitars' ) ),
			array( 'id' => 'sneakers', 'label' => __( 'Athletic shoes ($150+)', 'a2e' ), 'rate' => $num( 'fee_rate_sneakers' ), 'noOrderFeeFrom' => 150 ),
			array( 'id' => 'custom', 'label' => __( 'Custom rate…', 'a2e' ), 'rate' => $num( 'fee_rate' ) ),
		),
		'threshold'     => $num( 'fee_threshold' ),
		'rateAbove'     => $num( 'fee_rate_above' ),
		'orderSmall'    => $num( 'fee_order_small' ),
		'orderLarge'    => $num( 'fee_order_large' ),
		'orderLimit'    => $num( 'fee_order_limit' ),
		'international' => $num( 'fee_international' ),
		'belowStandard' => $num( 'fee_below' ),
		'topRated'      => $num( 'fee_top_rated' ),
		'checked'       => a2e_opt( 'fee_checked' ),
		'currency'      => '$',
	);
}
