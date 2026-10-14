<?php
/**
 * /portfolio/: projects + reviews.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Our work', 'a2e' ),
		'title'   => __( 'Research, listings and stores <em>built to perform.</em>', 'a2e' ),
		'intro'   => __( 'A look at recent product research, listing and account management projects, and what our clients say about them.', 'a2e' ),
		'class'   => 'phero--center',
	)
);
?>
<section class="section section--tight">
	<div class="container"><?php a2e_render_portfolio( 12 ); ?></div>
</section>
<section class="section section--alt">
	<div class="container">
		<?php a2e_render_stats(); ?>
	</div>
</section>
<section class="section">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Client reviews', 'a2e' ), 'title' => __( 'What clients <em>say</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_reviews( 9, 'grid' ); ?>
		<p class="section__more"><a class="link-arrow" href="<?php echo esc_url( a2e_opt( 'fiverr_url' ) ); ?>" target="_blank" rel="noopener"><?php /* translators: %s: count */ printf( esc_html__( 'Read all %s reviews on Fiverr', 'a2e' ), esc_html( a2e_opt( 'reviews' ) ) ); ?> <?php a2e_the_icon( 'arrow-up-right' ); ?></a></p>
	</div>
</section>
<section class="section section--alt">
	<div class="container">
		<div class="sec-row">
			<?php a2e_section_head( array( 'eyebrow' => __( 'Case studies', 'a2e' ), 'title' => __( 'The problem, the work, <em>the result</em>', 'a2e' ), 'align' => 'left' ) ); ?>
			<a class="btn btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'a2e_case_study' ) ); ?>"><span><?php esc_html_e( 'All case studies', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
		</div>
		<?php a2e_render_case_studies( 3 ); ?>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
