<?php
/**
 * /amazon-to-ebay-dropshipping-checklist/: interactive checklist.
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Free checklist', 'a2e' ),
		'title'   => __( 'The Amazon to eBay dropshipping <em>checklist we use.</em>', 'a2e' ),
		'intro'   => __( 'Store setup, product research, listings, inventory, orders and weekly account health. Tick items off as you go. Your progress is saved in this browser, so bookmark the page and come back each week.', 'a2e' ),
	)
);
?>
<section class="section section--tight">
	<div class="container"><?php a2e_render_checklist(); ?></div>
</section>
<section class="section section--alt">
	<div class="container container--mid">
		<div class="card news-card" data-reveal>
			<?php a2e_section_head( array( 'eyebrow' => __( 'Stay updated', 'a2e' ), 'title' => __( 'Get checklist & fee <em>updates by email</em>', 'a2e' ), 'intro' => __( 'eBay changes its rules and fees regularly. We will email you when this checklist or our free tools change. No spam.', 'a2e' ) ) ); ?>
			<?php a2e_render_newsletter( __( 'Send me updates', 'a2e' ) ); ?>
		</div>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
