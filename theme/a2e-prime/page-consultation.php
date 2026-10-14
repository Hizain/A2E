<?php
/**
 * /consultation/
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Consultation', 'a2e' ),
		'title'   => __( 'A clear, prioritised plan <em>before you spend.</em>', 'a2e' ),
		'intro'   => __( 'Not sure what your store needs first? We review your store with you and map the next steps — product research direction, listing SEO, account health and which plan (if any) makes sense.', 'a2e' ),
	)
);
?>
<section class="section section--tight" id="consultation">
	<div class="container">
		<div class="form-layout">
			<div class="form-card">
				<h2 class="h3"><?php esc_html_e( 'Request your consultation', 'a2e' ); ?></h2>
				<?php a2e_render_form( 'consultation' ); ?>
			</div>
			<aside class="form-side">
				<div class="aside-card">
					<p class="aside-card__title"><?php esc_html_e( 'What we can cover', 'a2e' ); ?></p>
					<ul class="check-list">
						<?php foreach ( array( __( 'A review of your store, listings and results', 'a2e' ), __( 'Product research direction and profit checks', 'a2e' ), __( 'eBay listing SEO: titles, item specifics, descriptions', 'a2e' ), __( 'Stock, pricing and order routines', 'a2e' ), __( 'Account health and how to protect it', 'a2e' ), __( 'Which service or plan makes sense — if any', 'a2e' ) ) as $a2e_item ) : ?>
							<li><?php a2e_the_icon( 'check' ); ?><?php echo esc_html( $a2e_item ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="aside-card">
					<p class="aside-card__title"><?php esc_html_e( 'Who it is for', 'a2e' ); ?></p>
					<p><?php esc_html_e( 'Sellers with several problems at once, sellers choosing between plans, or anyone who wants an experienced second opinion before investing more time and money.', 'a2e' ); ?></p>
				</div>
			</aside>
		</div>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
