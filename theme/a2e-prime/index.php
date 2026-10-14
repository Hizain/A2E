<?php
/**
 * Fallback template.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Blog', 'a2e' ),
		'title'   => is_archive() ? get_the_archive_title() : __( 'Latest articles', 'a2e' ),
		'class'   => 'phero--center',
	)
);
?>
<section class="section section--tight">
	<div class="container">
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
			<p class="empty-note"><?php esc_html_e( 'Nothing here yet.', 'a2e' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
