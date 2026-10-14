<?php
/**
 * /about/ — story, values, founder (E-E-A-T).
 *
 * @package A2E_Prime
 */

get_header();
$a2e_since = (int) a2e_opt( 'since' );
a2e_page_hero(
	array(
		'eyebrow' => __( 'About A2E', 'a2e' ),
		/* translators: %s: year */
		'title'   => sprintf( __( 'Helping eBay sellers grow <em>since %s.</em>', 'a2e' ), esc_html( a2e_opt( 'since' ) ) ),
		'intro'   => __( 'A2E Dropshipping is a small, focused team that researches, lists and manages eBay dropshipping stores. We built it on one idea: careful, honest work beats shortcuts — every single day.', 'a2e' ),
		'class'   => 'phero--center',
	)
);
?>
<section class="section section--tight">
	<div class="container"><?php a2e_render_founder(); ?></div>
</section>
<section class="section section--alt">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'What we believe', 'a2e' ), 'title' => __( 'How we work <em>with every store</em>', 'a2e' ) ) ); ?>
		<div class="values">
			<?php
			$a2e_values = array(
				array( 'dollar', __( 'Profit before volume', 'a2e' ), __( 'A product only makes the list if it makes money after every eBay fee. Hundreds of listings that do not sell help no one.', 'a2e' ) ),
				array( 'shield', __( 'Account health first', 'a2e' ), __( 'We work within eBay’s rules and protect your metrics, because a suspended account undoes every other win.', 'a2e' ) ),
				array( 'chat', __( 'Plain-English honesty', 'a2e' ), __( 'If something will not work for your store, we say so — before you spend a cent.', 'a2e' ) ),
				array( 'repeat', __( 'Consistency wins', 'a2e' ), __( 'Stores grow from steady work every week, not one big push. That rhythm is what our plans deliver.', 'a2e' ) ),
				array( 'users', __( 'One team, one plan', 'a2e' ), __( 'Research, listings and management handled by people who talk to each other.', 'a2e' ) ),
				array( 'lock', __( 'Your store stays yours', 'a2e' ), __( 'Your account, listings, customers and profits always belong to you.', 'a2e' ) ),
			);
			foreach ( $a2e_values as $a2e_i => $a2e_v ) {
				printf( '<div class="deliver" data-reveal style="--d:%1$d"><span class="deliver__icon">%2$s</span><h3>%3$s</h3><p>%4$s</p></div>', (int) ( $a2e_i % 3 ), a2e_icon( $a2e_v[0] ), esc_html( $a2e_v[1] ), esc_html( $a2e_v[2] ) ); // phpcs:ignore
			}
			?>
		</div>
	</div>
</section>
<section class="section">
	<div class="container">
		<div class="split">
			<div>
				<?php a2e_section_head( array( 'eyebrow' => __( 'Our story', 'a2e' ), 'title' => __( 'From one seller <em>to hundreds of stores</em>', 'a2e' ), 'align' => 'left' ) ); ?>
				<ol class="timeline" data-reveal>
					<li><strong><?php echo esc_html( $a2e_since ); ?></strong><p><?php esc_html_e( 'Started researching and listing products for eBay sellers, one store at a time.', 'a2e' ); ?></p></li>
					<li><strong><?php echo esc_html( $a2e_since + 2 ); ?></strong><p><?php esc_html_e( 'Built a repeatable research system: sold data, sell-through, VeRO checks and profit after fees.', 'a2e' ); ?></p></li>
					<li><strong><?php echo esc_html( $a2e_since + 4 ); ?></strong><p><?php esc_html_e( 'Grew into a team covering listings, inventory, orders and daily account management.', 'a2e' ); ?></p></li>
					<li><strong><?php echo esc_html( gmdate( 'Y' ) ); ?></strong><p><?php /* translators: %s: reviews count */ printf( esc_html__( '%s five-star reviews, free tools used by sellers worldwide, and simple monthly plans.', 'a2e' ), esc_html( a2e_opt( 'reviews' ) ) ); ?></p></li>
				</ol>
			</div>
			<div><?php a2e_render_stats( 'stats--stack' ); ?></div>
		</div>
	</div>
</section>
<section class="section section--alt">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Client reviews', 'a2e' ), 'title' => __( 'In our clients’ <em>words</em>', 'a2e' ) ) ); ?>
	</div>
	<?php a2e_render_reviews( 12, 'wall' ); ?>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
