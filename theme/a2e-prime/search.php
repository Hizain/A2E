<?php
/**
 * Search results.
 *
 * @package A2E_Prime
 */

get_header();
/* translators: %s: search query */
a2e_page_hero(
	array(
		'eyebrow' => __( 'Search', 'a2e' ),
		'title'   => esc_html( sprintf( __( 'Results for “%s”', 'a2e' ), get_search_query() ) ),
		'class'   => 'phero--center',
		'crumbs'  => false,
	)
);
?>
<section class="section section--tight">
	<div class="container">
		<div class="blog-bar"><span></span><?php get_search_form(); ?></div>
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/card-post' );
				}
				?>
			</div>
			<div class="pagination"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→' ) ); ?></div>
		<?php else : ?>
			<p class="empty-note"><?php esc_html_e( 'Nothing matched that search. Try a different word, or browse our free tools.', 'a2e' ); ?></p>
			<?php a2e_render_tools_grid( 6 ); ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
