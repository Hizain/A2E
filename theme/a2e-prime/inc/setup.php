<?php
/**
 * Theme supports, menus, image sizes and front-end clean-up.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup.
 */
function a2e_setup() {
	load_theme_textdomain( 'a2e', A2E_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 360,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Header menu (optional — replaces the built-in mega menu)', 'a2e' ),
			'footer'  => __( 'Footer — extra links', 'a2e' ),
		)
	);

	add_image_size( 'a2e-card', 800, 480, true );
	add_image_size( 'a2e-hero', 1600, 840, true );

	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'a2e_setup' );

/**
 * Remove head clutter that does nothing for visitors or SEO.
 */
function a2e_clean_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', 'a2e_clean_head' );

/**
 * Body classes.
 *
 * @param array $classes Classes.
 * @return array
 */
function a2e_body_class( $classes ) {
	if ( a2e_current_tool() ) {
		$classes[] = 'is-tool';
	}
	if ( a2e_opt( 'announcement' ) ) {
		$classes[] = 'has-announcement';
	}
	return $classes;
}
add_filter( 'body_class', 'a2e_body_class' );

/**
 * Excerpt length and "more" text.
 */
add_filter(
	'excerpt_length',
	function () {
		return 26;
	}
);
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/**
 * Sensible default for the blog: 9 posts per page fits the 3-column grid.
 *
 * @param WP_Query $query Query.
 */
function a2e_posts_per_page( $query ) {
	if ( ! is_admin() && $query->is_main_query() && ( $query->is_home() || $query->is_category() || $query->is_tag() ) && 10 === (int) get_option( 'posts_per_page' ) ) {
		$query->set( 'posts_per_page', 9 );
	}
}
add_action( 'pre_get_posts', 'a2e_posts_per_page' );

/**
 * Add id attributes to H2/H3 in posts so the table of contents can link to them.
 *
 * @param string $content Content.
 * @return string
 */
function a2e_heading_ids( $content ) {
	if ( ! is_singular( array( 'post', 'a2e_case_study' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return a2e_heading_ids_raw( $content );
}
add_filter( 'the_content', 'a2e_heading_ids', 12 );

/**
 * Table of contents built from the post's H2/H3 headings.
 *
 * @param WP_Post $post Post.
 * @return array list of [ level, id, text ]
 */
function a2e_toc( $post ) {
	$items = array();
	if ( ! $post ) {
		return $items;
	}
	$html = a2e_heading_ids_raw( $post->post_content );
	if ( preg_match_all( '#<h([23])[^>]*id="([^"]+)"[^>]*>(.*?)</h\1>#is', $html, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $h ) {
			$items[] = array( (int) $h[1], $h[2], wp_strip_all_tags( $h[3] ) );
		}
	}
	return $items;
}

/**
 * Same slugging as a2e_heading_ids() but usable outside the loop.
 *
 * @param string $content Raw content.
 * @return string
 */
function a2e_heading_ids_raw( $content ) {
	$used = array();
	return preg_replace_callback(
		'#<h([23])([^>]*)>(.*?)</h\1>#is',
		function ( $m ) use ( &$used ) {
			if ( preg_match( '/ id="([^"]+)"/', $m[2] ) ) {
				return $m[0];
			}
			$slug = sanitize_title( wp_strip_all_tags( $m[3] ) );
			$slug = $slug ? $slug : 'section';
			$base = $slug;
			$n    = 2;
			while ( isset( $used[ $slug ] ) ) {
				$slug = $base . '-' . $n++;
			}
			$used[ $slug ] = true;
			return '<h' . $m[1] . $m[2] . ' id="' . esc_attr( $slug ) . '">' . $m[3] . '</h' . $m[1] . '>';
		},
		(string) $content
	);
}
