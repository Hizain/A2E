<?php
/**
 * /account/ — client area. With the A2E Whop Sync plugin active, the "Manage subscription"
 * link goes to the customer's Whop billing portal.
 *
 * @package A2E_Prime
 */

get_header();
$a2e_portal = apply_filters( 'a2e_billing_portal_url', 'https://whop.com/@me/settings/memberships/' );
$a2e_status = isset( $_GET['purchase'] ) ? sanitize_key( wp_unslash( $_GET['purchase'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
a2e_page_hero(
	array(
		'eyebrow' => __( 'Client area', 'a2e' ),
		'title'   => 'success' === $a2e_status ? __( 'Welcome aboard — <em>you are all set.</em>', 'a2e' ) : __( 'Your subscription, <em>billing and support.</em>', 'a2e' ),
		'intro'   => 'success' === $a2e_status ? __( 'Thank you for subscribing. Check your email for your Whop receipt and our onboarding message — we will be in touch within one business day to confirm your store details and delivery schedule.', 'a2e' ) : __( 'Manage your plan and payment details through Whop, our secure billing partner, or reach the team directly.', 'a2e' ),
		'class'   => 'phero--center',
	)
);
?>
<section class="section section--tight">
	<div class="container">
		<div class="account-grid">
			<a class="contact-card" href="<?php echo esc_url( $a2e_portal ); ?>" target="_blank" rel="noopener">
				<span class="contact-card__icon"><?php a2e_the_icon( 'credit-card' ); ?></span>
				<span><strong><?php esc_html_e( 'Manage subscription', 'a2e' ); ?></strong><small><?php esc_html_e( 'Update card, switch or cancel renewals', 'a2e' ); ?></small><em><?php esc_html_e( 'Opens your Whop account', 'a2e' ); ?></em></span>
				<?php a2e_the_icon( 'arrow-up-right', 'contact-card__go' ); ?>
			</a>
			<a class="contact-card contact-card--wa" href="<?php echo esc_url( a2e_wa_link( 'Hi A2E, I am a client and have a question about my store.' ) ); ?>" target="_blank" rel="noopener">
				<span class="contact-card__icon"><?php a2e_the_icon( 'whatsapp' ); ?></span>
				<span><strong><?php esc_html_e( 'Client support', 'a2e' ); ?></strong><small><?php echo esc_html( a2e_opt( 'whatsapp' ) ); ?></small><em><?php esc_html_e( 'Fastest way to reach your team', 'a2e' ); ?></em></span>
				<?php a2e_the_icon( 'arrow-up-right', 'contact-card__go' ); ?>
			</a>
			<a class="contact-card" href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>">
				<span class="contact-card__icon"><?php a2e_the_icon( 'layers' ); ?></span>
				<span><strong><?php esc_html_e( 'Plans', 'a2e' ); ?></strong><small><?php esc_html_e( 'Compare or upgrade', 'a2e' ); ?></small><em><?php esc_html_e( 'See what each plan includes', 'a2e' ); ?></em></span>
				<?php a2e_the_icon( 'arrow-right', 'contact-card__go' ); ?>
			</a>
		</div>
	</div>
</section>
<section class="section section--alt">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Help', 'a2e' ), 'title' => __( 'Account & billing <em>questions</em>', 'a2e' ) ) ); ?>
		<?php
		a2e_render_faq(
			array(
				array( __( 'Where do I see my invoices?', 'a2e' ), __( 'All receipts and invoices are in your Whop account under Memberships. You also receive an email receipt for every payment.', 'a2e' ) ),
				array( __( 'How do I cancel?', 'a2e' ), __( 'Open “Manage subscription”, choose your A2E plan and cancel future renewals. Your plan stays active until the end of the paid period.', 'a2e' ) ),
				array( __( 'How do I give you access to my store?', 'a2e' ), __( 'We send step-by-step instructions during onboarding. Where possible we use eBay’s own team permissions so you never share your password.', 'a2e' ) ),
			)
		);
		?>
	</div>
</section>
<?php
a2e_designed_extra_content();
get_footer();
