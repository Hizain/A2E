<?php
/**
 * Services shop (/shop/ or the WooCommerce shop page): every monthly plan and one-time
 * service, grouped by category, with Whop checkout. Loaded by a2e_shop_template().
 *
 * @package A2E_Prime
 */

get_header();

$a2e_shop_id  = a2e_shop_page_id();
$a2e_catalog  = a2e_shop_catalog();
$a2e_sections = a2e_shop_sections();
$a2e_counts   = array(
	'all'          => count( $a2e_catalog['plans'] ),
	'subscription' => 0,
	'one-time'     => 0,
);
foreach ( $a2e_catalog['plans'] as $a2e_p ) {
	if ( isset( $a2e_counts[ $a2e_p['kind'] ] ) ) {
		$a2e_counts[ $a2e_p['kind'] ]++;
	}
}
$a2e_title = a2e_page_text( 'title', '', $a2e_shop_id );
$a2e_intro = a2e_page_text( 'intro', '', $a2e_shop_id );

$a2e_meta  = '<ul class="shop-trust">';
$a2e_meta .= '<li>' . a2e_icon( 'lock' ) . esc_html__( 'Secure checkout by Whop', 'a2e' ) . '</li>';
$a2e_meta .= '<li>' . a2e_icon( 'refresh' ) . esc_html__( 'Cancel monthly plans anytime', 'a2e' ) . '</li>';
$a2e_meta .= '<li>' . a2e_icon( 'shield' ) . esc_html__( 'Your eBay account stays yours', 'a2e' ) . '</li>';
/* translators: 1: rating 2: number of reviews */
$a2e_meta .= '<li>' . a2e_stars( (float) a2e_opt( 'rating' ) ) . sprintf( esc_html__( '%1$s from %2$s reviews', 'a2e' ), '<strong>' . esc_html( a2e_opt( 'rating' ) ) . '</strong>', esc_html( a2e_opt( 'reviews' ) ) ) . '</li>';
$a2e_meta .= '</ul>';

a2e_page_hero(
	array(
		'eyebrow' => a2e_page_text( 'eyebrow', a2e_opt( 'shop_eyebrow' ), $a2e_shop_id ),
		'title'   => a2e_format_headline( $a2e_title ? $a2e_title : a2e_opt( 'shop_title' ) ),
		'intro'   => esc_html( $a2e_intro ? $a2e_intro : a2e_opt( 'shop_intro' ) ),
		'class'   => 'phero--center phero--shop',
		'meta'    => $a2e_meta,
	)
);
?>

<div class="shop" data-shop>
	<div class="shop-bar" data-shop-bar>
		<div class="container container--wide shop-bar__inner">
			<div class="shop-seg" role="group" aria-label="<?php esc_attr_e( 'Show services', 'a2e' ); ?>">
				<button type="button" class="shop-seg__btn is-active" data-shop-kind="all" aria-pressed="true"><span class="shop-seg__full"><?php esc_html_e( 'All services', 'a2e' ); ?></span><span class="shop-seg__short"><?php esc_html_e( 'All', 'a2e' ); ?></span><b><?php echo (int) $a2e_counts['all']; ?></b></button>
				<?php if ( $a2e_counts['subscription'] ) : ?>
					<button type="button" class="shop-seg__btn" data-shop-kind="subscription" aria-pressed="false"><span class="shop-seg__full"><?php esc_html_e( 'Monthly plans', 'a2e' ); ?></span><span class="shop-seg__short"><?php esc_html_e( 'Monthly', 'a2e' ); ?></span><b><?php echo (int) $a2e_counts['subscription']; ?></b></button>
				<?php endif; ?>
				<?php if ( $a2e_counts['one-time'] ) : ?>
					<button type="button" class="shop-seg__btn" data-shop-kind="one-time" aria-pressed="false"><span class="shop-seg__full"><?php esc_html_e( 'One-time services', 'a2e' ); ?></span><span class="shop-seg__short"><?php esc_html_e( 'One-time', 'a2e' ); ?></span><b><?php echo (int) $a2e_counts['one-time']; ?></b></button>
				<?php endif; ?>
			</div>
			<?php if ( count( $a2e_sections ) > 1 ) : ?>
				<nav class="shop-bar__jump" aria-label="<?php esc_attr_e( 'Service categories', 'a2e' ); ?>">
					<?php foreach ( $a2e_sections as $a2e_s ) : ?>
						<a class="shop-jump" href="#cat-<?php echo esc_attr( $a2e_s['slug'] ); ?>" data-shop-jump><?php a2e_the_icon( $a2e_s['icon'] ); ?><?php echo esc_html( $a2e_s['name'] ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( 'whop' !== $a2e_catalog['source'] && current_user_can( 'edit_posts' ) ) : ?>
		<div class="container container--wide">
			<p class="shop-admin-note"><?php a2e_the_icon( 'info' ); ?><span><?php esc_html_e( 'Only you can see this: the shop is showing the fallback plans from A2E → Site Settings. Add your services under Whop → Plans (with the A2E Whop Sync plugin) and they appear here, grouped by category.', 'a2e' ); ?></span></p>
		</div>
	<?php endif; ?>

	<?php foreach ( $a2e_sections as $a2e_s ) : ?>
		<section class="shop-sec" id="cat-<?php echo esc_attr( $a2e_s['slug'] ); ?>" data-shop-sec>
			<div class="container container--wide">
				<header class="shop-sec__head" data-reveal>
					<span class="shop-sec__icon"><?php a2e_the_icon( $a2e_s['icon'] ); ?></span>
					<div class="shop-sec__copy">
						<h2 class="shop-sec__title"><?php echo esc_html( $a2e_s['name'] ); ?></h2>
						<?php if ( $a2e_s['desc'] ) : ?><p class="shop-sec__desc"><?php echo esc_html( $a2e_s['desc'] ); ?></p><?php endif; ?>
					</div>
					<span class="shop-sec__count" data-shop-count><?php /* translators: %d: number of services */ printf( esc_html( _n( '%d service', '%d services', count( $a2e_s['plans'] ), 'a2e' ) ), count( $a2e_s['plans'] ) ); ?></span>
				</header>
				<div class="shop-grid shop-grid--<?php echo (int) min( 4, count( $a2e_s['plans'] ) ); ?>">
					<?php
					foreach ( $a2e_s['plans'] as $a2e_plan ) {
						a2e_render_service_card( $a2e_plan, $a2e_s );
					}
					?>
				</div>
			</div>
		</section>
	<?php endforeach; ?>

	<div class="container container--wide">
		<p class="shop-empty" data-shop-empty hidden><?php esc_html_e( 'Nothing here yet. Try “All services”, or ask us for a custom plan.', 'a2e' ); ?></p>

		<div class="shop-help" data-reveal>
			<div class="shop-help__copy">
				<p class="eyebrow"><span class="eyebrow__dot" aria-hidden="true"></span><?php esc_html_e( 'Not sure what to pick?', 'a2e' ); ?></p>
				<h2 class="shop-help__title"><?php echo a2e_format_headline( a2e_opt( 'shop_help_title' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<p class="shop-help__text"><?php echo esc_html( a2e_opt( 'shop_help_text' ) ); ?></p>
			</div>
			<div class="btn-row">
				<?php
				a2e_buttons(
					array(
						array( __( 'Get a free store audit', 'a2e' ), a2e_url( 'quote-request' ), 'primary' ),
						array( __( 'Ask on WhatsApp', 'a2e' ), a2e_wa_link( 'Hi A2E! Which of your services fits my eBay store?' ), 'wa' ),
					)
				);
				?>
			</div>
		</div>
	</div>
</div>

<section class="section">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'How ordering works', 'a2e' ), 'title' => __( 'From checkout to results in <em>four simple steps</em>', 'a2e' ) ) ); ?>
		<ol class="steps">
			<?php
			$a2e_steps = array(
				array( 'tag', __( 'Pick a service', 'a2e' ), __( 'Choose a monthly plan, a one-time service or both. Every card shows the price, what is included and how long it takes.', 'a2e' ) ),
				array( 'lock', __( 'Pay securely', 'a2e' ), __( 'Checkout runs on Whop. Your card details never touch this website and you get a receipt by email.', 'a2e' ) ),
				array( 'chat', __( 'Quick onboarding', 'a2e' ), __( 'A real person contacts you within one business day to confirm your store details, niches and access.', 'a2e' ) ),
				array( 'chart', __( 'Work and reports', 'a2e' ), __( 'We deliver on the schedule shown and send a clear report. Monthly plans can be cancelled anytime.', 'a2e' ) ),
			);
			foreach ( $a2e_steps as $a2e_i => $a2e_step ) {
				printf(
					'<li class="step" data-reveal style="--d:%1$d"><span class="step__num">%2$s</span><span class="step__icon">%3$s</span><h3 class="step__title">%4$s</h3><p class="step__text">%5$s</p></li>',
					(int) $a2e_i,
					esc_html( sprintf( '%02d', $a2e_i + 1 ) ),
					a2e_icon( $a2e_step[0] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $a2e_step[1] ),
					esc_html( $a2e_step[2] )
				);
			}
			?>
		</ol>
	</div>
</section>

<section class="section section--alt">
	<div class="container container--narrow">
		<?php a2e_section_head( array( 'eyebrow' => __( 'FAQ', 'a2e' ), 'title' => __( 'Buying from A2E, <em>answered</em>', 'a2e' ) ) ); ?>
		<?php
		a2e_render_faq(
			array(
				array( __( 'How does checkout work?', 'a2e' ), __( 'Press the button on any service to open a secure Whop checkout. After you pay you get a receipt by email and we contact you within one business day to start onboarding.', 'a2e' ) ),
				array( __( 'Can I cancel a monthly plan?', 'a2e' ), __( 'Yes. There is no long-term contract: cancel future renewals from your account at any time. One-time services are paid once and never renew.', 'a2e' ) ),
				array( __( 'When does the work start?', 'a2e' ), __( 'As soon as onboarding is complete. Each service shows its delivery or start time, and we confirm your schedule in writing.', 'a2e' ) ),
				array( __( 'Can I buy more than one service?', 'a2e' ), __( 'Of course. Many sellers combine a store setup or a listing package with a monthly plan. If you need a bigger or different mix, ask for a custom plan.', 'a2e' ) ),
				array( __( 'Do you need my eBay password?', 'a2e' ), __( 'No. We work with the access you choose to give, ideally eBay’s own team permissions, and you can remove it whenever you like. Your account, listings and profits stay yours.', 'a2e' ) ),
				array( __( 'Do you offer refunds?', 'a2e' ), __( 'Work starts soon after purchase, so payments for work already started are generally non-refundable. If something is not right, tell us and we will always try to fix it first.', 'a2e' ) ),
			)
		);
		?>
	</div>
</section>

<?php a2e_render_cta(); ?>

<dialog class="shop-dialog" data-shop-dialog>
	<div class="shop-dialog__panel">
		<button type="button" class="icon-btn shop-dialog__close" data-shop-close aria-label="<?php esc_attr_e( 'Close', 'a2e' ); ?>"><?php a2e_the_icon( 'close' ); ?></button>
		<div class="shop-dialog__body" data-shop-dialog-body></div>
	</div>
</dialog>

<?php
a2e_shop_schema();
get_footer();
