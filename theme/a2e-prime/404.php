<?php
/**
 * 404 page.
 *
 * @package A2E_Prime
 */

get_header();
?>
<section class="phero phero--center">
	<div class="phero__bg" aria-hidden="true"><span class="aurora aurora--a"></span><span class="aurora aurora--b"></span><span class="grid-fade"></span></div>
	<div class="container phero__inner">
		<div class="phero__copy notfound">
			<p class="notfound__code">404</p>
			<h1 class="phero__title"><?php esc_html_e( 'This page sold out.', 'a2e' ); ?> <em><?php esc_html_e( 'The rest of the store is open.', 'a2e' ); ?></em></h1>
			<p class="phero__intro"><?php esc_html_e( 'The link may be old or mistyped. Search below, or jump straight to what most visitors are looking for.', 'a2e' ); ?></p>
			<?php get_search_form(); ?>
			<div class="btn-row">
				<?php
				a2e_buttons(
					array(
						array( __( 'Go to homepage', 'a2e' ), home_url( '/' ), 'primary' ),
						array( __( 'Free tools', 'a2e' ), a2e_url( 'free-tools' ), 'ghost', 'calculator' ),
						array( __( 'Pricing', 'a2e' ), a2e_url( 'pricing' ), 'ghost', 'tag' ),
					)
				);
				?>
			</div>
		</div>
	</div>
</section>
<section class="section section--tight">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Popular right now', 'a2e' ), 'title' => __( 'Free tools sellers use every day', 'a2e' ) ) ); ?>
		<?php a2e_render_tools_grid( 6 ); ?>
	</div>
</section>
<?php
get_footer();
