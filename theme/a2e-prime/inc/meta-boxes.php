<?php
/**
 * Editor meta boxes: page header + SEO for every content type, and details for
 * testimonials, portfolio items and case studies.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions per box.
 *
 * @return array
 */
function a2e_meta_box_fields() {
	return array(
		'a2e_header'      => array(
			'title'   => __( 'A2E page header & SEO', 'a2e' ),
			'screens' => array( 'page', 'post', 'a2e_service', 'a2e_case_study', 'a2e_portfolio' ),
			'context' => 'normal',
			'fields'  => array(
				'_a2e_hero_eyebrow' => array( __( 'Small label above the title', 'a2e' ), 'text', '' ),
				'_a2e_hero_title'   => array( __( 'Page headline (leave empty to use the built-in headline / post title)', 'a2e' ), 'text', '' ),
				'_a2e_hero_intro'   => array( __( 'Intro paragraph under the headline', 'a2e' ), 'textarea', '' ),
				'_a2e_seo_title'    => array( __( 'SEO title (browser tab & Google, ~60 characters)', 'a2e' ), 'text', '' ),
				'_a2e_seo_desc'     => array( __( 'Meta description (~155 characters)', 'a2e' ), 'textarea', '' ),
				'_a2e_noindex'      => array( __( 'Hide this page from search engines (noindex)', 'a2e' ), 'checkbox', '' ),
				'_a2e_show_content' => array( __( 'Designed pages only: also show the editor content below the layout', 'a2e' ), 'checkbox', '' ),
			),
		),
		'a2e_testimonial' => array(
			'title'   => __( 'Review details', 'a2e' ),
			'screens' => array( 'a2e_testimonial' ),
			'context' => 'side',
			'fields'  => array(
				'_a2e_rating'  => array( __( 'Rating (1-5)', 'a2e' ), 'text', '5' ),
				'_a2e_country' => array( __( 'Country', 'a2e' ), 'text', '' ),
				'_a2e_source'  => array( __( 'Service', 'a2e' ), 'text', '' ),
			),
		),
		'a2e_portfolio'   => array(
			'title'   => __( 'Project details', 'a2e' ),
			'screens' => array( 'a2e_portfolio' ),
			'context' => 'side',
			'fields'  => array(
				'_a2e_client_name' => array( __( 'Client', 'a2e' ), 'text', '' ),
				'_a2e_result'      => array( __( 'Result (one line)', 'a2e' ), 'text', '' ),
				'_a2e_service'     => array( __( 'Service', 'a2e' ), 'text', '' ),
			),
		),
		'a2e_case'        => array(
			'title'   => __( 'Case study numbers', 'a2e' ),
			'screens' => array( 'a2e_case_study' ),
			'context' => 'side',
			'fields'  => array(
				'_a2e_case_stats' => array( __( 'Key results, one per line as “value | label”, e.g. “+42% | impressions in 60 days”', 'a2e' ), 'textarea', '' ),
				'_a2e_case_tag'   => array( __( 'Service tag', 'a2e' ), 'text', '' ),
			),
		),
	);
}

/**
 * Register boxes.
 */
function a2e_add_meta_boxes() {
	foreach ( a2e_meta_box_fields() as $id => $box ) {
		add_meta_box( $id, $box['title'], 'a2e_render_meta_box', $box['screens'], $box['context'], 'default', array( 'box' => $id ) );
	}
}
add_action( 'add_meta_boxes', 'a2e_add_meta_boxes' );

/**
 * Render a box.
 *
 * @param WP_Post $post Post.
 * @param array   $args Callback args.
 */
function a2e_render_meta_box( $post, $args ) {
	$boxes = a2e_meta_box_fields();
	$box   = $boxes[ $args['args']['box'] ];
	wp_nonce_field( 'a2e_meta_' . $args['args']['box'], 'a2e_meta_nonce_' . $args['args']['box'] );
	if ( 'a2e_header' === $args['args']['box'] && ( a2e_is_designed_page( $post ) || 'a2e_service' === $post->post_type ) ) {
		echo '<p class="a2e-meta__note"><strong>' . esc_html__( 'This page uses an A2E designed layout.', 'a2e' ) . '</strong> ' . esc_html__( 'Its sections come from the theme (texts editable under A2E → Site Settings). Use the fields below to change the headline and SEO. Tick “also show the editor content” to add your own blocks under the layout.', 'a2e' ) . '</p>';
	}
	echo '<div class="a2e-meta">';
	foreach ( $box['fields'] as $key => $field ) {
		list( $label, $type ) = $field;
		$value = get_post_meta( $post->ID, $key, true );
		if ( '' === $value && isset( $field[2] ) && 'checkbox' !== $type ) {
			$value = $field[2];
		}
		echo '<p class="a2e-meta__field">';
		if ( 'checkbox' === $type ) {
			printf( '<label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>', esc_attr( $key ), checked( '1', $value, false ), esc_html( $label ) );
		} elseif ( 'textarea' === $type ) {
			printf( '<label for="%1$s">%2$s</label><textarea class="widefat" rows="3" id="%1$s" name="%1$s">%3$s</textarea>', esc_attr( $key ), esc_html( $label ), esc_textarea( $value ) );
		} else {
			printf( '<label for="%1$s">%2$s</label><input class="widefat" type="text" id="%1$s" name="%1$s" value="%3$s">', esc_attr( $key ), esc_html( $label ), esc_attr( $value ) );
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Save boxes.
 *
 * @param int $post_id Post ID.
 */
function a2e_save_meta_boxes( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( a2e_meta_box_fields() as $id => $box ) {
		$nonce = isset( $_POST[ 'a2e_meta_nonce_' . $id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'a2e_meta_nonce_' . $id ] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'a2e_meta_' . $id ) ) {
			continue;
		}
		foreach ( $box['fields'] as $key => $field ) {
			$type = $field[1];
			if ( 'checkbox' === $type ) {
				if ( ! empty( $_POST[ $key ] ) ) {
					update_post_meta( $post_id, $key, '1' );
				} else {
					delete_post_meta( $post_id, $key );
				}
				continue;
			}
			$raw   = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = 'textarea' === $type ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
}
add_action( 'save_post', 'a2e_save_meta_boxes' );
