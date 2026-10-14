<?php
/**
 * Default page template (legal pages and any page you create).
 *
 * @package A2E_Prime
 */

get_header();
while ( have_posts() ) :
	the_post();
	$a2e_updated = get_the_modified_date();
	a2e_page_hero(
		array(
			'title' => esc_html( get_the_title() ),
			'intro' => has_excerpt() ? esc_html( get_the_excerpt() ) : '',
			/* translators: %s: date */
			'meta'  => in_array( get_post_field( 'post_name' ), array( 'privacy-policy', 'terms-of-service' ), true ) ? '<span>' . a2e_icon( 'calendar' ) . esc_html( sprintf( __( 'Last updated %s', 'a2e' ), $a2e_updated ) ) . '</span>' : '',
		)
	);
	?>
	<section class="page-body">
		<div class="container container--narrow">
			<div class="prose"><?php the_content(); ?></div>
			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="pagination"><div class="nav-links">',
					'after'  => '</div></nav>',
				)
			);
			?>
		</div>
	</section>
	<?php
endwhile;
a2e_render_cta();
get_footer();
