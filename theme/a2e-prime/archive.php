<?php
/**
 * Category, tag, author and date archives.
 *
 * @package A2E_Prime
 */

get_header();
$a2e_desc = get_the_archive_description();
a2e_page_hero(
	array(
		'eyebrow' => is_category() ? __( 'Category', 'a2e' ) : ( is_tag() ? __( 'Topic', 'a2e' ) : __( 'Archive', 'a2e' ) ),
		'title'   => esc_html( wp_strip_all_tags( get_the_archive_title() ) ),
		'intro'   => $a2e_desc ? wp_strip_all_tags( $a2e_desc ) : '',
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
			<p class="empty-note"><?php esc_html_e( 'No posts here yet.', 'a2e' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
a2e_render_cta();
get_footer();
