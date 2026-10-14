<?php
/**
 * /pricing/: plans, comparison table, billing FAQ.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Plans & pricing', 'a2e' ),
		'title'   => __( 'Simple pricing for <em>done-for-you</em> eBay dropshipping.', 'a2e' ),
		'intro'   => __( 'Start with a one-time listing batch or let us run your store every month. Clear scope, fixed price, no long-term contract.', 'a2e' ),
		'class'   => 'phero--center',
		'meta'    => '<span>' . a2e_icon( 'lock' ) . esc_html__( 'Secure checkout', 'a2e' ) . '</span><span>' . a2e_icon( 'repeat' ) . esc_html__( 'Cancel anytime', 'a2e' ) . '</span><span>' . a2e_icon( 'star' ) . esc_html( sprintf( /* translators: 1: rating 2: reviews */ __( '%1$s/5 from %2$s reviews', 'a2e' ), a2e_opt( 'rating' ), a2e_opt( 'reviews' ) ) ) . '</span>',
	)
);
$a2e_plans = a2e_get_plans();
?>
<section class="section section--tight">
	<div class="container"><?php a2e_render_pricing(); ?></div>
</section>

<?php if ( count( $a2e_plans ) >= 2 ) : ?>
<section class="section section--alt">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Compare', 'a2e' ), 'title' => __( 'What is included <em>in each plan</em>', 'a2e' ) ) ); ?>
		<?php
		$a2e_rows = array(
			array( __( 'Profit-checked product research', 'a2e' ), array( 1, 1, 1 ) ),
			array( __( 'SEO titles & complete item specifics', 'a2e' ), array( 1, 1, 1 ) ),
			array( __( 'Listings uploaded to your store', 'a2e' ), array( 1, 1, 1 ) ),
			array( __( 'New listings every month', 'a2e' ), array( 0, '150', '500' ) ),
			array( __( 'Stock & price monitoring', 'a2e' ), array( 0, 1, 1 ) ),
			array( __( 'Orders & buyer messages', 'a2e' ), array( 0, 1, 1 ) ),
			array( __( 'Account health monitoring', 'a2e' ), array( 0, 1, 1 ) ),
			array( __( 'Monthly report', 'a2e' ), array( 0, 1, 1 ) ),
			array( __( 'Monthly strategy call', 'a2e' ), array( 0, 0, 1 ) ),
			array( __( 'Priority WhatsApp support', 'a2e' ), array( 0, 1, 1 ) ),
		);
		$a2e_cols = array_slice( $a2e_plans, 0, 3 );
		?>
		<div class="table-wrap" data-reveal>
			<table class="price-table">
				<thead><tr><th><?php esc_html_e( 'Feature', 'a2e' ); ?></th><?php foreach ( $a2e_cols as $a2e_p ) : ?><th><?php echo esc_html( $a2e_p['name'] ); ?><br><small><?php echo esc_html( $a2e_p['price'] . ' ' . $a2e_p['period'] ); ?></small></th><?php endforeach; ?></tr></thead>
				<tbody>
					<?php foreach ( $a2e_rows as $a2e_row ) : ?>
						<tr><td><?php echo esc_html( $a2e_row[0] ); ?></td>
							<?php foreach ( array_keys( $a2e_cols ) as $a2e_i ) : $a2e_v = isset( $a2e_row[1][ $a2e_i ] ) ? $a2e_row[1][ $a2e_i ] : 0; ?>
								<td><?php echo 1 === $a2e_v ? a2e_icon( 'check', '', __( 'Included', 'a2e' ) ) : ( $a2e_v ? '<strong>' . esc_html( $a2e_v ) . '</strong>' : '<span class="no">' . a2e_icon( 'minus', '', __( 'Not included', 'a2e' ) ) . '</span>' ); // phpcs:ignore ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="pricing__trust"><?php a2e_the_icon( 'info' ); ?><?php esc_html_e( 'Exact scope is confirmed in writing before any work starts.', 'a2e' ); ?></p>
	</div>
</section>
<?php endif; ?>

<section class="section">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'After you subscribe', 'a2e' ), 'title' => __( 'What happens <em>in your first week</em>', 'a2e' ) ) ); ?>
		<ol class="steps">
			<?php
			$a2e_week = array(
				array( 'check-circle', __( 'Day 1: Welcome', 'a2e' ), __( 'You get a welcome message and a short onboarding form for your store, goals and access.', 'a2e' ) ),
				array( 'scan', __( 'Days 1-2: Store review', 'a2e' ), __( 'We review your store, policies and niches and confirm your delivery schedule in writing.', 'a2e' ) ),
				array( 'search', __( 'Days 2-5: Research', 'a2e' ), __( 'Products are researched and profit-checked; you can see the shortlist before anything goes live.', 'a2e' ) ),
				array( 'rocket', __( 'Week 1+: Listings go live', 'a2e' ), __( 'Listings start going live and, on monthly plans, daily management begins.', 'a2e' ) ),
			);
			foreach ( $a2e_week as $a2e_i => $a2e_s ) {
				printf( '<li class="step" data-reveal style="--d:%1$d"><span class="step__num">%2$s</span><span class="step__icon">%3$s</span><h3 class="step__title">%4$s</h3><p class="step__text">%5$s</p></li>', (int) $a2e_i, esc_html( sprintf( '%02d', $a2e_i + 1 ) ), a2e_icon( $a2e_s[0] ), esc_html( $a2e_s[1] ), esc_html( $a2e_s[2] ) ); // phpcs:ignore
			}
			?>
		</ol>
	</div>
</section>

<section class="section section--alt">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Billing questions', 'a2e' ), 'title' => __( 'Straight answers about <em>paying and cancelling</em>', 'a2e' ) ) ); ?>
		<?php
		a2e_render_faq(
			array(
				array( __( 'How do I pay?', 'a2e' ), __( 'Checkout is handled by Whop, a secure payments platform that accepts cards and other local payment methods. Card details never touch this website.', 'a2e' ) ),
				array( __( 'Can I cancel anytime?', 'a2e' ), __( 'Yes. Cancel future renewals from your account whenever you like. Your plan stays active until the end of the period you paid for.', 'a2e' ) ),
				array( __( 'Can I switch plans?', 'a2e' ), __( 'Yes. Upgrade or downgrade at the end of a billing period. Message us and we will move you over.', 'a2e' ) ),
				array( __( 'Do you offer refunds?', 'a2e' ), __( 'Work starts soon after purchase, so payments for work already started are generally non-refundable. If something is not right, tell us and we will always try to fix it first.', 'a2e' ) ),
				array( __( 'Is there a setup fee?', 'a2e' ), __( 'Only where shown on the plan (for example a one-time store audit and setup on Growth). Scale Management has no setup fee.', 'a2e' ) ),
			)
		);
		?>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
