<?php
/**
 * /testimonials/: client reviews.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Client reviews', 'a2e' ),
		/* translators: 1: rating 2: count */
		'title'   => sprintf( __( 'Rated %1$s by <em>%2$s sellers.</em>', 'a2e' ), esc_html( a2e_opt( 'rating' ) ), esc_html( a2e_opt( 'reviews' ) ) ),
		'intro'   => __( 'Real feedback from eBay sellers we have worked with. Every review is public on our Fiverr profile.', 'a2e' ),
		'class'   => 'phero--center',
		'actions' => array( array( __( 'Read them on Fiverr', 'a2e' ), a2e_opt( 'fiverr_url' ), 'ghost', 'arrow-up-right' ) ),
	)
);
?>
<section class="section section--tight">
	<div class="container"><?php a2e_render_reviews( 60, 'grid' ); ?></div>
</section>
<?php
a2e_render_cta();
get_footer();
