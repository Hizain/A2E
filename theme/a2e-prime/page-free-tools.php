<?php
/**
 * /free-tools/: tool hub with search and category filters.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Free seller tools', 'a2e' ),
		'title'   => sprintf( __( '%d free eBay tools to <em>price, list and grow.</em>', 'a2e' ), count( a2e_tools() ) ),
		'intro'   => __( 'Fee and profit calculators, title and description generators, research scorecards and growth planners. The same tools our team uses every day. Free, no sign-up, and nothing you type leaves your browser.', 'a2e' ),
		'class'   => 'phero--center',
		/* translators: %s: month and year */
		'meta'    => '<span>' . a2e_icon( 'check-circle' ) . esc_html__( '100% free', 'a2e' ) . '</span><span>' . a2e_icon( 'lock' ) . esc_html__( 'No sign-up', 'a2e' ) . '</span><span>' . a2e_icon( 'refresh' ) . esc_html( sprintf( __( 'eBay fee rates checked %s', 'a2e' ), a2e_opt( 'fee_checked' ) ) ) . '</span>',
	)
);
?>
<section class="section section--tight">
	<div class="container">
		<?php a2e_render_tools_grid( 0, true ); ?>
	</div>
</section>

<section class="section section--alt">
	<div class="container">
		<div class="split">
			<div>
				<?php a2e_section_head( array( 'eyebrow' => __( 'Why we built them', 'a2e' ), 'title' => __( 'Built by people who <em>sell on eBay every day</em>', 'a2e' ), 'intro' => __( 'We use these calculations every day when we research products and price listings for clients. We keep the fee rates current, so you can trust the numbers before you list.', 'a2e' ), 'align' => 'left' ) ); ?>
				<div class="btn-row" data-reveal><?php a2e_buttons( array( array( __( 'Or let us do it for you', 'a2e' ), a2e_url( 'pricing' ), 'primary' ) ) ); ?></div>
			</div>
			<div class="deliver-grid deliver-grid--2">
				<?php
				$a2e_why = array(
					array( 'calculator', __( 'Accurate fee maths', 'a2e' ), __( 'Fees on the full sale, including tax and shipping.', 'a2e' ) ),
					array( 'bolt', __( 'Instant results', 'a2e' ), __( 'Everything recalculates as you type.', 'a2e' ) ),
					array( 'lock', __( 'Private', 'a2e' ), __( 'Nothing you enter is sent to our servers.', 'a2e' ) ),
					array( 'refresh', __( 'Kept current', 'a2e' ), __( 'Rates updated when eBay changes them.', 'a2e' ) ),
				);
				foreach ( $a2e_why as $a2e_i => $a2e_w ) {
					printf( '<div class="deliver" data-reveal style="--d:%1$d"><span class="deliver__icon">%2$s</span><h3>%3$s</h3><p>%4$s</p></div>', (int) $a2e_i, a2e_icon( $a2e_w[0] ), esc_html( $a2e_w[1] ), esc_html( $a2e_w[2] ) ); // phpcs:ignore
				}
				?>
			</div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'FAQ', 'a2e' ), 'title' => __( 'About our <em>free tools</em>', 'a2e' ) ) ); ?>
		<?php
		a2e_render_faq(
			array(
				array( __( 'Are these eBay tools really free?', 'a2e' ), __( 'Yes. Every tool is free to use as often as you like, with no sign-up and no email required.', 'a2e' ) ),
				array( __( 'Which eBay fees do the calculators use?', 'a2e' ), sprintf( /* translators: 1: rate 2: month */ __( 'The default eBay US rate for most categories (%1$s%% final value fee plus the per-order fee), with options for other categories, seller level, ads and international sales. Rates were last checked in %2$s.', 'a2e' ), a2e_opt( 'fee_rate' ), a2e_opt( 'fee_checked' ) ) ),
				array( __( 'Do you store what I type?', 'a2e' ), __( 'No. All calculations run in your browser. Nothing you enter is uploaded or saved on our servers.', 'a2e' ) ),
				array( __( 'Can you do the research and pricing for me?', 'a2e' ), __( 'Yes, that is what our plans are for. We research products, price them for profit and list them on your store.', 'a2e' ) ),
			)
		);
		?>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
