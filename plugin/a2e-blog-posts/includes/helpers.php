<?php
/**
 * Settings and small helpers.
 *
 * @package A2E_Blog_Posts
 */

namespace A2E\BlogPosts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the A2E Prime theme running?
 *
 * @return bool
 */
function theme_active() {
	return function_exists( 'a2e_opt' ) && function_exists( 'a2e_section_head' );
}

/**
 * Where a section can be shown (besides specific pages).
 *
 * @return array key => label
 */
function where_options() {
	return array(
		'front'      => __( 'Homepage', 'a2e-blog-posts' ),
		'all_pages'  => __( 'Every page', 'a2e-blog-posts' ),
		'services'   => __( 'Every service guide', 'a2e-blog-posts' ),
		'posts'      => __( 'Every blog post (the post itself is left out)', 'a2e-blog-posts' ),
		'categories' => __( 'Category pages', 'a2e-blog-posts' ),
		'blog'       => __( 'The blog page', 'a2e-blog-posts' ),
	);
}

/**
 * Where on the page a section goes.
 *
 * @return array key => label
 */
function position_options() {
	return array(
		'before_cta' => __( 'Near the bottom, just before the final "free store audit" call to action (recommended)', 'a2e-blog-posts' ),
		'footer'     => __( 'At the very bottom, just above the footer', 'a2e-blog-posts' ),
		'before_faq' => __( 'Before the FAQ section (falls back to near the bottom on pages without one)', 'a2e-blog-posts' ),
	);
}

/**
 * Post order choices.
 *
 * @return array key => label
 */
function order_options() {
	return array(
		'newest'  => __( 'Newest first', 'a2e-blog-posts' ),
		'popular' => __( 'Most commented first', 'a2e-blog-posts' ),
		'random'  => __( 'Random (changes on every visit)', 'a2e-blog-posts' ),
		'oldest'  => __( 'Oldest first', 'a2e-blog-posts' ),
	);
}

/**
 * A new section with sensible defaults.
 *
 * @param int $id Section id.
 * @return array
 */
function section_defaults( $id = 1 ) {
	return array(
		'id'        => (int) $id,
		'enabled'   => '1',
		'name'      => __( 'Latest guides', 'a2e-blog-posts' ),
		'where'     => array(),
		'pages'     => array(),
		'position'  => 'before_cta',
		'cats'      => array(),
		'match_cat' => '1',
		'count'     => 3,
		'order'     => 'newest',
		'columns'   => 3,
		'eyebrow'   => __( 'From the blog', 'a2e-blog-posts' ),
		'title'     => __( 'Guides for sellers who *play the long game.*', 'a2e-blog-posts' ),
		'intro'     => '',
		'button'    => __( 'Read all guides', 'a2e-blog-posts' ),
		'link'      => '',
		'style'     => 'alt',
	);
}

/**
 * Settings for a fresh install: the homepage section, switched on.
 *
 * @return array
 */
function default_settings() {
	$home          = section_defaults( 1 );
	$home['name']  = __( 'Homepage: latest guides', 'a2e-blog-posts' );
	$home['where'] = array( 'front' );
	$home['intro'] = __( 'Practical guides on product research, listing SEO, fees and account health, written by people who run eBay stores every day.', 'a2e-blog-posts' );
	return array(
		'fix_chips'        => '1',
		'hide_theme_block' => '1',
		'sections'         => array( 1 => $home ),
	);
}

/**
 * All settings (sections normalised).
 *
 * @return array
 */
function settings() {
	$saved = get_option( OPTION, false );
	if ( false === $saved ) {
		$saved = default_settings();
	}
	$saved = wp_parse_args(
		is_array( $saved ) ? $saved : array(),
		array( 'fix_chips' => '1', 'hide_theme_block' => '1', 'sections' => array() )
	);
	$sections = array();
	foreach ( (array) $saved['sections'] as $section ) {
		if ( ! is_array( $section ) || empty( $section['id'] ) ) {
			continue;
		}
		$section                     = wp_parse_args( $section, section_defaults( (int) $section['id'] ) );
		$sections[ (int) $section['id'] ] = $section;
	}
	$saved['sections'] = $sections;
	return $saved;
}

/**
 * Save settings.
 *
 * @param array $values Settings.
 */
function update_settings( $values ) {
	update_option( OPTION, $values, false );
}

/**
 * Is any switched-on section placed on the homepage?
 *
 * @return bool
 */
function homepage_section_on() {
	foreach ( settings()['sections'] as $section ) {
		if ( '1' === $section['enabled'] && in_array( 'front', (array) $section['where'], true ) ) {
			return true;
		}
	}
	$front = (int) get_option( 'page_on_front' );
	if ( $front ) {
		foreach ( settings()['sections'] as $section ) {
			if ( '1' === $section['enabled'] && in_array( $front, array_map( 'intval', (array) $section['pages'] ), true ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * The blog page URL.
 *
 * @return string
 */
function blog_url() {
	if ( function_exists( 'a2e_blog_url' ) ) {
		return a2e_blog_url();
	}
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/' );
}
