<?php
/**
 * Custom post types. Slugs and archive URLs match the live site so no URL changes.
 *
 * /service-guides/{slug}/  /case-studies/  /portfolio/{slug}/  /testimonials/
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register post types.
 */
function a2e_register_post_types() {
	register_post_type(
		'a2e_service',
		array(
			'labels'        => array(
				'name'          => __( 'Service Guides', 'a2e' ),
				'singular_name' => __( 'Service Guide', 'a2e' ),
				'add_new_item'  => __( 'Add New Service Guide', 'a2e' ),
				'edit_item'     => __( 'Edit Service Guide', 'a2e' ),
				'menu_name'     => __( 'Services', 'a2e' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'service-guides', 'with_front' => false ),
			'menu_icon'     => 'dashicons-store',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'a2e_case_study',
		array(
			'labels'        => array(
				'name'          => __( 'Case Studies', 'a2e' ),
				'singular_name' => __( 'Case Study', 'a2e' ),
				'add_new_item'  => __( 'Add New Case Study', 'a2e' ),
			),
			'public'        => true,
			'has_archive'   => 'case-studies',
			'rewrite'       => array( 'slug' => 'case-studies', 'with_front' => false ),
			'menu_icon'     => 'dashicons-chart-line',
			'menu_position' => 22,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'a2e_portfolio',
		array(
			'labels'        => array(
				'name'          => __( 'Portfolio', 'a2e' ),
				'singular_name' => __( 'Portfolio Item', 'a2e' ),
				'add_new_item'  => __( 'Add New Portfolio Item', 'a2e' ),
			),
			'public'        => true,
			'has_archive'   => false, // The /portfolio/ page lists these items.
			'rewrite'       => array( 'slug' => 'portfolio', 'with_front' => false ),
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'a2e_testimonial',
		array(
			'labels'        => array(
				'name'          => __( 'Testimonials', 'a2e' ),
				'singular_name' => __( 'Testimonial', 'a2e' ),
				'add_new_item'  => __( 'Add New Testimonial', 'a2e' ),
			),
			'public'        => true,
			'has_archive'   => 'testimonials',
			'rewrite'       => array( 'slug' => 'testimonials', 'with_front' => false ),
			'menu_icon'     => 'dashicons-star-filled',
			'menu_position' => 24,
			'supports'      => array( 'title', 'editor', 'custom-fields' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'a2e_lead',
		array(
			'labels'          => array(
				'name'          => __( 'Leads', 'a2e' ),
				'singular_name' => __( 'Lead', 'a2e' ),
				'menu_name'     => __( 'Leads', 'a2e' ),
				'all_items'     => __( 'All Leads', 'a2e' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'a2e-settings',
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'a2e_register_post_types' );

/**
 * Individual testimonials have no page of their own: send visitors to the list.
 */
function a2e_redirect_single_testimonial() {
	if ( is_singular( 'a2e_testimonial' ) ) {
		wp_safe_redirect( get_post_type_archive_link( 'a2e_testimonial' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'a2e_redirect_single_testimonial' );

/**
 * Show every testimonial and case study on their archive pages.
 *
 * @param WP_Query $query Query.
 */
function a2e_archive_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'a2e_testimonial' ) ) {
		$query->set( 'posts_per_page', 60 );
	}
	if ( $query->is_post_type_archive( 'a2e_case_study' ) ) {
		$query->set( 'posts_per_page', 24 );
	}
}
add_action( 'pre_get_posts', 'a2e_archive_queries' );

/**
 * Lead list columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function a2e_lead_columns( $columns ) {
	return array(
		'cb'        => $columns['cb'],
		'title'     => __( 'Lead', 'a2e' ),
		'a2e_type'  => __( 'Form', 'a2e' ),
		'a2e_email' => __( 'Email', 'a2e' ),
		'a2e_phone' => __( 'WhatsApp / phone', 'a2e' ),
		'date'      => $columns['date'],
	);
}
add_filter( 'manage_a2e_lead_posts_columns', 'a2e_lead_columns' );

/**
 * Lead column values.
 *
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function a2e_lead_column_values( $column, $post_id ) {
	if ( 'a2e_type' === $column ) {
		echo esc_html( ucfirst( (string) get_post_meta( $post_id, '_a2e_form', true ) ) );
	} elseif ( 'a2e_email' === $column ) {
		$email = (string) get_post_meta( $post_id, '_a2e_email', true );
		echo $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—';
	} elseif ( 'a2e_phone' === $column ) {
		$phone = (string) get_post_meta( $post_id, '_a2e_phone', true );
		echo $phone ? '<a href="' . esc_url( 'https://wa.me/' . preg_replace( '/\D+/', '', $phone ) ) . '" target="_blank" rel="noopener">' . esc_html( $phone ) . '</a>' : '—';
	}
}
add_action( 'manage_a2e_lead_posts_custom_column', 'a2e_lead_column_values', 10, 2 );
