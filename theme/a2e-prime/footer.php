<?php
/**
 * Site footer.
 *
 * @package A2E_Prime
 */

$a2e_services = a2e_services();
$a2e_tools    = a2e_tools();
$a2e_social   = array(
	'fiverr'    => a2e_opt( 'fiverr_url' ),
	'linkedin'  => a2e_opt( 'social_linkedin' ),
	'facebook'  => a2e_opt( 'social_facebook' ),
	'instagram' => a2e_opt( 'social_instagram' ),
	'youtube'   => a2e_opt( 'social_youtube' ),
	'x'         => a2e_opt( 'social_x' ),
);
?>
</main>

<footer class="site-footer">
	<div class="site-footer__glow" aria-hidden="true"></div>
	<div class="container">
		<div class="footer-top">
			<div class="footer-brand">
				<?php echo a2e_logo( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<p class="footer-brand__text"><?php esc_html_e( 'Done-for-you Amazon to eBay dropshipping. We research products that sell, write listings that rank and run your store every day, so you can focus on growing.', 'a2e' ); ?></p>
				<?php a2e_trust_row( 'trust--footer' ); ?>
			</div>
			<div class="footer-news">
				<p class="footer-news__title"><?php esc_html_e( 'Get new eBay guides & tool updates', 'a2e' ); ?></p>
				<p class="footer-news__text"><?php esc_html_e( 'One useful email when eBay changes fees or rules, or when we publish a new free tool.', 'a2e' ); ?></p>
				<?php a2e_render_newsletter( __( 'Subscribe', 'a2e' ), 'news-form--footer' ); ?>
			</div>
		</div>

		<div class="footer-cols">
			<div class="footer-col">
				<p class="footer-col__title"><?php esc_html_e( 'Services', 'a2e' ); ?></p>
				<ul>
					<?php foreach ( a2e_featured_service_slugs() as $slug ) : ?>
						<li><a href="<?php echo esc_url( a2e_service_url( $slug ) ); ?>"><?php echo esc_html( $a2e_services[ $slug ]['nav'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="footer-col">
				<p class="footer-col__title"><?php esc_html_e( 'Free tools', 'a2e' ); ?></p>
				<ul>
					<?php foreach ( array_slice( $a2e_tools, 0, 7 ) as $tool ) : ?>
						<li><a href="<?php echo esc_url( a2e_tool_url( $tool['slug'] ) ); ?>"><?php echo esc_html( $tool['name'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a class="footer-col__more" href="<?php echo esc_url( a2e_url( 'free-tools' ) ); ?>"><?php printf( esc_html__( 'All %d tools →', 'a2e' ), count( a2e_tools() ) ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col">
				<p class="footer-col__title"><?php esc_html_e( 'Company', 'a2e' ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( a2e_url( 'about' ) ); ?>"><?php esc_html_e( 'About', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_url( 'portfolio' ) ); ?>"><?php esc_html_e( 'Portfolio', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( get_post_type_archive_link( 'a2e_case_study' ) ); ?>"><?php esc_html_e( 'Case studies', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( get_post_type_archive_link( 'a2e_testimonial' ) ); ?>"><?php esc_html_e( 'Reviews', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_blog_url() ); ?>"><?php esc_html_e( 'Blog', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'a2e' ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col">
				<p class="footer-col__title"><?php esc_html_e( 'Get started', 'a2e' ); ?></p>
				<ul>
					<li><a href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Pricing', 'a2e' ); ?></a></li>
					<?php if ( a2e_shop_page_id() ) : ?><li><a href="<?php echo esc_url( a2e_shop_url() ); ?>"><?php esc_html_e( 'Services shop', 'a2e' ); ?></a></li><?php endif; ?>
					<li><a href="<?php echo esc_url( a2e_url( 'subscription-plans' ) ); ?>"><?php esc_html_e( 'Monthly plans', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_url( 'quote-request' ) ); ?>"><?php esc_html_e( 'Free store audit', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_url( 'consultation' ) ); ?>"><?php esc_html_e( 'Book a consultation', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_url( 'amazon-to-ebay-dropshipping-checklist' ) ); ?>"><?php esc_html_e( 'Free checklist', 'a2e' ); ?></a></li>
					<li><a href="<?php echo esc_url( a2e_account_url() ); ?>"><?php esc_html_e( 'Client area', 'a2e' ); ?></a></li>
				</ul>
			</div>
			<div class="footer-col footer-col--contact">
				<p class="footer-col__title"><?php esc_html_e( 'Talk to us', 'a2e' ); ?></p>
				<a class="footer-wa" href="<?php echo esc_url( a2e_wa_link( 'Hi A2E! I have a question about my eBay store.' ) ); ?>" target="_blank" rel="noopener"><?php a2e_the_icon( 'whatsapp' ); ?><span><strong><?php esc_html_e( 'WhatsApp', 'a2e' ); ?></strong><small><?php echo esc_html( a2e_opt( 'whatsapp' ) ); ?></small></span></a>
				<?php if ( a2e_opt( 'email' ) ) : ?>
					<a class="footer-mail" href="mailto:<?php echo esc_attr( a2e_opt( 'email' ) ); ?>"><?php a2e_the_icon( 'mail' ); ?><?php echo esc_html( a2e_opt( 'email' ) ); ?></a>
				<?php endif; ?>
				<div class="social">
					<?php foreach ( $a2e_social as $network => $url ) : if ( ! $url ) { continue; } ?>
						<a class="social__<?php echo esc_attr( $network ); ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( ucfirst( $network ) ); ?>"><?php a2e_the_icon( $network ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="footer-bottom">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'a2e' ); ?></p>
			<ul class="footer-legal">
				<li><a href="<?php echo esc_url( a2e_url( 'privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( a2e_url( 'terms-of-service' ) ); ?>"><?php esc_html_e( 'Terms', 'a2e' ); ?></a></li>
				<?php
				if ( '1' === a2e_opt( 'use_wp_menus' ) && has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'items_wrap'     => '%3$s',
							'depth'          => 1,
						)
					);
				}
				?>
				<li><button type="button" class="link-btn" data-theme-toggle><?php a2e_the_icon( 'sun', 'theme-toggle__sun' ); ?><?php a2e_the_icon( 'moon', 'theme-toggle__moon' ); ?><span><?php esc_html_e( 'Theme', 'a2e' ); ?></span></button></li>
			</ul>
		</div>
		<p class="footer-disclaimer"><?php esc_html_e( 'Amazon, eBay, Walmart, AliExpress, The Home Depot, Target and all other brand names shown are trademarks of their respective owners. A2E Dropshipping is an independent service provider and is not affiliated with, sponsored or endorsed by any of them. Results shown in examples and calculators are illustrations, not guarantees.', 'a2e' ); ?></p>
	</div>
</footer>

<a class="wa-float" href="<?php echo esc_url( a2e_wa_link( 'Hi A2E! I have a question about my eBay store.' ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Chat with us on WhatsApp', 'a2e' ); ?>">
	<?php a2e_the_icon( 'whatsapp' ); ?><span class="wa-float__label"><?php esc_html_e( 'Chat with us', 'a2e' ); ?></span>
</a>
<button type="button" class="to-top" data-to-top aria-label="<?php esc_attr_e( 'Back to top', 'a2e' ); ?>"><?php a2e_the_icon( 'arrow-up-right' ); ?></button>

<?php wp_footer(); ?>
</body>
</html>
