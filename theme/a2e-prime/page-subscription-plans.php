<?php
/**
 * /subscription-plans/ — monthly plans and how subscriptions work.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Monthly management', 'a2e' ),
		'title'   => __( 'Your eBay store, <em>growing every month.</em>', 'a2e' ),
		'intro'   => __( 'Stores grow from steady work: new researched products every month, better listings, fast stock and price checks and quick answers to buyers. Our monthly plans give you that rhythm — without hiring and training a team.', 'a2e' ),
		'class'   => 'phero--center',
		'actions' => array( array( __( 'Compare all plans', 'a2e' ), a2e_url( 'pricing' ), 'ghost', 'arrow-right' ) ),
	)
);
?>
<section class="section section--tight">
	<div class="container"><?php a2e_render_pricing( array( 'only' => 'month' ) ); ?></div>
</section>

<section class="section section--alt">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'How it works', 'a2e' ), 'title' => __( 'A subscription that <em>does the work</em>', 'a2e' ) ) ); ?>
		<div class="deliver-grid">
			<?php
			$a2e_items = array(
				array( 'credit-card', __( 'Choose a plan', 'a2e' ), __( 'Check out securely through Whop. Your plan starts immediately.', 'a2e' ) ),
				array( 'clipboard', __( 'Quick onboarding', 'a2e' ), __( 'We confirm your store details, goals and access, then share your delivery schedule.', 'a2e' ) ),
				array( 'repeat', __( 'Work every month', 'a2e' ), __( 'We research, list and manage according to your plan — no reminders needed.', 'a2e' ) ),
				array( 'chart', __( 'Clear reporting', 'a2e' ), __( 'A monthly summary of what was done, what sold and what is next.', 'a2e' ) ),
				array( 'gear', __( 'Flexible', 'a2e' ), __( 'Upgrade, downgrade or cancel future renewals from your account.', 'a2e' ) ),
				array( 'shield', __( 'Your account stays yours', 'a2e' ), __( 'You keep full ownership and control of your store, listings and customers.', 'a2e' ) ),
			);
			foreach ( $a2e_items as $a2e_i => $a2e_it ) {
				printf( '<div class="deliver" data-reveal style="--d:%1$d"><span class="deliver__icon">%2$s</span><h3>%3$s</h3><p>%4$s</p></div>', (int) ( $a2e_i % 3 ), a2e_icon( $a2e_it[0] ), esc_html( $a2e_it[1] ), esc_html( $a2e_it[2] ) ); // phpcs:ignore
			}
			?>
		</div>
	</div>
</section>

<section class="section">
	<div class="container">
		<div class="fit">
			<div class="fit__card" data-reveal>
				<h3><?php esc_html_e( 'Monthly plans are for you if…', 'a2e' ); ?></h3>
				<ul class="check-list">
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'You already have an eBay store and want steady growth', 'a2e' ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'You do not have time for daily store work', 'a2e' ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'You tried doing it alone and want an experienced team', 'a2e' ); ?></li>
				</ul>
			</div>
			<div class="fit__card" data-reveal style="--d:1">
				<h3><?php esc_html_e( 'Start with a one-time batch if…', 'a2e' ); ?></h3>
				<ul class="check-list">
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'You want to test our research quality first', 'a2e' ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Your store is brand new with small selling limits', 'a2e' ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'You prefer to manage day-to-day work yourself', 'a2e' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
</section>
<section class="section section--alt">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'FAQ', 'a2e' ), 'title' => __( 'Questions about <em>subscriptions</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_faq( array_slice( a2e_site_faq(), 2, 5 ) ); ?>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
