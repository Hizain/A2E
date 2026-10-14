<?php
/**
 * Single portfolio item.
 *
 * @package A2E_Prime
 */

get_header();
while ( have_posts() ) :
	the_post();
	$a2e_result = (string) get_post_meta( get_the_ID(), '_a2e_result', true );
	$a2e_client = (string) get_post_meta( get_the_ID(), '_a2e_client_name', true );
	$a2e_svc    = (string) get_post_meta( get_the_ID(), '_a2e_service', true );
	$a2e_meta   = '';
	if ( $a2e_client ) {
		/* translators: %s: client */
		$a2e_meta .= '<span>' . a2e_icon( 'user' ) . esc_html( sprintf( __( 'Client: %s', 'a2e' ), $a2e_client ) ) . '</span>';
	}
	if ( $a2e_result ) {
		$a2e_meta .= '<span>' . a2e_icon( 'trend' ) . esc_html( $a2e_result ) . '</span>';
	}
	a2e_page_hero(
		array(
			'eyebrow' => $a2e_svc ? $a2e_svc : __( 'Portfolio', 'a2e' ),
			'title'   => esc_html( get_the_title() ),
			'intro'   => has_excerpt() ? esc_html( get_the_excerpt() ) : '',
			'meta'    => $a2e_meta,
		)
	);
	?>
	<section class="page-body">
		<div class="container container--narrow">
			<?php if ( has_post_thumbnail() ) : ?><figure class="prose"><?php the_post_thumbnail( 'a2e-hero' ); ?></figure><?php endif; ?>
			<div class="prose"><?php the_content(); ?></div>
			<?php echo a2e_inline_cta( __( 'Want this for your store?', 'a2e' ), __( 'Tell us where your store is today and get a fixed-price plan.', 'a2e' ), __( 'Get my free audit', 'a2e' ), a2e_url( 'quote-request' ) ); // phpcs:ignore ?>
		</div>
	</section>
	<section class="section section--alt">
		<div class="container">
			<div class="sec-row">
				<?php a2e_section_head( array( 'eyebrow' => __( 'More work', 'a2e' ), 'title' => __( 'Recent <em>projects</em>', 'a2e' ), 'align' => 'left' ) ); ?>
				<a class="btn btn--ghost" href="<?php echo esc_url( a2e_url( 'portfolio' ) ); ?>"><span><?php esc_html_e( 'Full portfolio', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
			</div>
			<?php a2e_render_portfolio( 3 ); ?>
		</div>
	</section>
	<?php
endwhile;
a2e_render_cta();
get_footer();
