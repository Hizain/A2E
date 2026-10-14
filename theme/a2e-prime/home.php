<?php
/**
 * Blog index (/blog/): featured latest post, category chips, search, grid.
 *
 * @package A2E_Prime
 */

get_header();
$a2e_paged = max( 1, (int) get_query_var( 'paged' ) );
a2e_page_hero(
	array(
		'eyebrow' => __( 'The A2E blog', 'a2e' ),
		'title'   => __( 'Amazon to eBay dropshipping, <em>explained properly.</em>', 'a2e' ),
		'intro'   => __( 'Practical guides on product research, listing SEO, fees and pricing, account health and scaling — written by people who run eBay stores every day.', 'a2e' ),
		'class'   => 'phero--center',
	)
);
?>
<section class="section section--tight">
	<div class="container">
		<div class="blog-bar">
			<div class="chips">
				<a class="chip is-active" href="<?php echo esc_url( a2e_blog_url() ); ?>"><?php esc_html_e( 'All', 'a2e' ); ?></a>
				<?php
				foreach ( get_categories( array( 'hide_empty' => true, 'number' => 8, 'orderby' => 'count', 'order' => 'DESC' ) ) as $a2e_cat ) {
					if ( 'uncategorized' === $a2e_cat->slug ) {
						continue;
					}
					echo '<a class="chip" href="' . esc_url( get_category_link( $a2e_cat ) ) . '">' . esc_html( $a2e_cat->name ) . '</a>';
				}
				?>
			</div>
			<?php get_search_form(); ?>
		</div>

		<?php if ( have_posts() ) : ?>
			<?php
			$a2e_first = true;
			echo '<div class="post-grid">';
			while ( have_posts() ) :
				the_post();
				if ( $a2e_first && 1 === $a2e_paged ) {
					$a2e_first = false;
					echo '</div>';
					?>
					<article <?php post_class( 'post-card post-card--featured' ); ?> data-reveal>
						<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'a2e-hero', array( 'alt' => '' ) ); else : ?><span class="post-card__art"><span class="post-card__art-icon"><?php a2e_the_icon( 'book' ); ?></span></span><?php endif; ?>
						</a>
						<div class="post-card__body">
							<div class="post-card__meta"><span class="tag"><?php esc_html_e( 'Latest', 'a2e' ); ?></span><span><?php /* translators: %d: minutes */ printf( esc_html__( '%d min read', 'a2e' ), (int) a2e_reading_time() ); ?></span></div>
							<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 34 ) ); ?></p>
							<a class="link-arrow" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read the guide', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></a>
						</div>
					</article>
					<?php
					echo '<div class="post-grid">';
					continue;
				}
				get_template_part( 'template-parts/card-post' );
			endwhile;
			echo '</div>';
			?>
			<div class="pagination"><?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→' ) ); ?></div>
		<?php else : ?>
			<p class="empty-note"><?php esc_html_e( 'The first guides are on their way. In the meantime, try our free tools.', 'a2e' ); ?> <a href="<?php echo esc_url( a2e_url( 'free-tools' ) ); ?>"><?php esc_html_e( 'Open the tools →', 'a2e' ); ?></a></p>
		<?php endif; ?>
	</div>
</section>
<?php
a2e_render_cta();
get_footer();
