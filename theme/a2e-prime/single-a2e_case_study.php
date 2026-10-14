<?php
/**
 * Single case study.
 *
 * @package A2E_Prime
 */

get_header();
while ( have_posts() ) :
	the_post();
	$a2e_stats = array();
	foreach ( a2e_lines( (string) get_post_meta( get_the_ID(), '_a2e_case_stats', true ) ) as $a2e_line ) {
		$a2e_p = array_map( 'trim', explode( '|', $a2e_line, 2 ) );
		if ( 2 === count( $a2e_p ) ) {
			$a2e_stats[] = $a2e_p;
		}
	}
	$a2e_tag = (string) get_post_meta( get_the_ID(), '_a2e_case_tag', true );
	a2e_page_hero(
		array(
			'eyebrow' => $a2e_tag ? $a2e_tag : __( 'Case study', 'a2e' ),
			'title'   => esc_html( get_the_title() ),
			'intro'   => has_excerpt() ? esc_html( get_the_excerpt() ) : '',
		)
	);
	?>
	<section class="page-body">
		<div class="container container--narrow">
			<?php if ( $a2e_stats ) : ?>
				<div class="stats" style="margin-bottom:40px">
					<?php foreach ( array_slice( $a2e_stats, 0, 4 ) as $a2e_s ) : ?>
						<div class="stat"><strong class="stat__num"><?php echo esc_html( $a2e_s[0] ); ?></strong><span class="stat__label"><?php echo esc_html( $a2e_s[1] ); ?></span></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( has_post_thumbnail() ) : ?><figure class="prose"><?php the_post_thumbnail( 'a2e-hero' ); ?></figure><?php endif; ?>
			<div class="prose"><?php the_content(); ?></div>
			<?php echo a2e_inline_cta( __( 'Want results like this?', 'a2e' ), __( 'Get a free audit of your store and a fixed-price plan.', 'a2e' ), __( 'Get my free audit', 'a2e' ), a2e_url( 'quote-request' ) ); // phpcs:ignore ?>
		</div>
	</section>
	<section class="section section--alt">
		<div class="container">
			<div class="sec-row">
				<?php a2e_section_head( array( 'eyebrow' => __( 'More case studies', 'a2e' ), 'title' => __( 'Keep <em>reading</em>', 'a2e' ), 'align' => 'left' ) ); ?>
				<a class="btn btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'a2e_case_study' ) ); ?>"><span><?php esc_html_e( 'All case studies', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
			</div>
			<?php
			$a2e_more = new WP_Query( array( 'post_type' => 'a2e_case_study', 'posts_per_page' => 3, 'post__not_in' => array( get_the_ID() ), 'no_found_rows' => true ) );
			if ( $a2e_more->have_posts() ) {
				echo '<div class="case-grid">';
				$a2e_i = 0;
				while ( $a2e_more->have_posts() ) {
					$a2e_more->the_post();
					get_template_part( 'template-parts/card-case', null, array( 'i' => $a2e_i++ ) );
				}
				echo '</div>';
				wp_reset_postdata();
			}
			?>
		</div>
	</section>
	<?php
endwhile;
a2e_render_cta();
get_footer();
