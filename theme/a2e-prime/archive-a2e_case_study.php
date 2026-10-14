<?php
/**
 * /case-studies/ (the old /case-studies-overview/ page redirects here).
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Case studies', 'a2e' ),
		'title'   => __( 'How we fix eBay stores — <em>step by step.</em>', 'a2e' ),
		'intro'   => __( 'Store foundations, research systems and listing clean-ups: the problem, the work and what changed.', 'a2e' ),
		'class'   => 'phero--center',
	)
);
?>
<section class="section section--tight">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="case-grid">
				<?php
				$a2e_i = 0;
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/card-case', null, array( 'i' => $a2e_i++ ) );
				}
				?>
			</div>
		<?php else : ?>
			<p class="empty-note"><?php esc_html_e( 'Case studies are coming soon.', 'a2e' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<section class="section section--alt">
	<div class="container"><?php a2e_render_stats(); ?></div>
</section>
<?php
a2e_render_cta();
get_footer();
