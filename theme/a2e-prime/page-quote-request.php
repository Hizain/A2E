<?php
/**
 * /quote-request/: free store audit (multi-step form).
 *
 * @package A2E_Prime
 */

get_header();
a2e_page_hero(
	array(
		'eyebrow' => __( 'Free store audit', 'a2e' ),
		'title'   => __( 'Get a free audit and <em>a fixed-price plan.</em>', 'a2e' ),
		'intro'   => __( 'Answer four quick questions. A real person reviews your store and replies with a clear recommendation and price. No pressure, no jargon.', 'a2e' ),
		'meta'    => '<span>' . a2e_icon( 'clock' ) . esc_html__( 'Takes 2 minutes', 'a2e' ) . '</span><span>' . a2e_icon( 'check-circle' ) . esc_html__( 'No obligation', 'a2e' ) . '</span><span>' . a2e_icon( 'chat' ) . esc_html__( 'Reply within one business day', 'a2e' ) . '</span>',
	)
);
?>
<section class="section section--tight" id="quote">
	<div class="container">
		<div class="form-layout">
			<div class="form-card"><?php a2e_render_form( 'quote' ); ?></div>
			<aside class="form-side">
				<div class="aside-card">
					<p class="aside-card__title"><?php esc_html_e( 'What you get back', 'a2e' ); ?></p>
					<ul class="side-list">
						<li><span class="icon-wrap"><?php a2e_the_icon( 'scan' ); ?></span><span><strong><?php esc_html_e( 'A store review', 'a2e' ); ?></strong><span><?php esc_html_e( 'What is working, what is not and why.', 'a2e' ); ?></span></span></li>
						<li><span class="icon-wrap"><?php a2e_the_icon( 'target' ); ?></span><span><strong><?php esc_html_e( 'A clear plan', 'a2e' ); ?></strong><span><?php esc_html_e( 'The next steps that matter most for your store.', 'a2e' ); ?></span></span></li>
						<li><span class="icon-wrap"><?php a2e_the_icon( 'tag' ); ?></span><span><strong><?php esc_html_e( 'A fixed price', 'a2e' ); ?></strong><span><?php esc_html_e( 'Exactly what it costs before any work starts.', 'a2e' ); ?></span></span></li>
					</ul>
				</div>
				<?php a2e_trust_row(); ?>
				<a class="contact-card contact-card--wa" href="<?php echo esc_url( a2e_wa_link( 'Hi A2E! I would like a free audit of my eBay store.' ) ); ?>" target="_blank" rel="noopener">
					<span class="contact-card__icon"><?php a2e_the_icon( 'whatsapp' ); ?></span>
					<span><strong><?php esc_html_e( 'Prefer to chat?', 'a2e' ); ?></strong><small><?php esc_html_e( 'Message us on WhatsApp', 'a2e' ); ?></small></span>
					<?php a2e_the_icon( 'arrow-up-right', 'contact-card__go' ); ?>
				</a>
			</aside>
		</div>
	</div>
</section>
<?php
a2e_designed_extra_content();
get_footer();
