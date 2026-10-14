<?php
/**
 * Site header: announcement bar, sticky glass header with mega menus, mobile drawer.
 *
 * @package A2E_Prime
 */

$a2e_services = a2e_services();
$a2e_tools    = a2e_tools();
$a2e_cats     = a2e_tool_categories();
$a2e_plans    = a2e_get_plans();
$a2e_monthly  = array_values(
	array_filter(
		$a2e_plans,
		function ( $p ) {
			return 'month' === $p['interval'];
		}
	)
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'a2e' ); ?></a>

<?php if ( a2e_opt( 'announcement' ) ) : ?>
	<div class="announce">
		<a class="announce__link" href="<?php echo esc_url( a2e_resolve_url( a2e_opt( 'announcement_url' ) ) ); ?>">
			<span class="announce__tag"><?php esc_html_e( 'New', 'a2e' ); ?></span>
			<span><?php echo esc_html( preg_replace( '/^New:\s*/i', '', a2e_opt( 'announcement' ) ) ); ?></span>
			<?php a2e_the_icon( 'arrow-right' ); ?>
		</a>
	</div>
<?php endif; ?>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<?php echo a2e_logo( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<nav class="nav" aria-label="<?php esc_attr_e( 'Main', 'a2e' ); ?>">
			<?php if ( '1' === a2e_opt( 'use_wp_menus' ) && has_nav_menu( 'primary' ) ) : ?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'nav__list nav__list--wp',
						'depth'          => 2,
					)
				);
				?>
			<?php else : ?>
				<ul class="nav__list">
					<li class="nav__item has-mega" data-mega>
						<button type="button" class="nav__link" aria-expanded="false" aria-controls="mega-services"><?php esc_html_e( 'Services', 'a2e' ); ?><?php a2e_the_icon( 'chevron-down', 'nav__chev' ); ?></button>
						<div class="mega" id="mega-services">
							<div class="mega__inner">
								<div class="mega__grid mega__grid--2">
									<?php foreach ( a2e_featured_service_slugs() as $slug ) : $s = $a2e_services[ $slug ]; ?>
										<a class="mega__link" href="<?php echo esc_url( a2e_service_url( $slug ) ); ?>">
											<span class="mega__icon"><?php a2e_the_icon( $s['icon'] ); ?></span>
											<span><strong><?php echo esc_html( $s['nav'] ); ?></strong><small><?php echo esc_html( $s['short'] ); ?></small></span>
										</a>
									<?php endforeach; ?>
								</div>
								<aside class="mega__aside">
									<p class="mega__kicker"><?php esc_html_e( 'Done-for-you', 'a2e' ); ?></p>
									<p class="mega__title"><?php esc_html_e( 'Let us run your eBay store every day', 'a2e' ); ?></p>
									<?php if ( $a2e_monthly ) : ?>
										<p class="mega__price"><?php /* translators: 1: price 2: period */ printf( esc_html__( 'Plans from %1$s%2$s', 'a2e' ), '<strong>' . esc_html( $a2e_monthly[0]['price'] ) . '</strong>', esc_html( $a2e_monthly[0]['period'] ) ); ?></p>
									<?php endif; ?>
									<a class="btn btn--primary btn--sm" href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>"><span><?php esc_html_e( 'See plans', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
									<a class="mega__all" href="<?php echo esc_url( a2e_url( 'amazon-to-ebay-dropshipping-service' ) ); ?>"><?php esc_html_e( 'All services', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></a>
								</aside>
							</div>
						</div>
					</li>
					<li class="nav__item has-mega" data-mega>
						<button type="button" class="nav__link" aria-expanded="false" aria-controls="mega-tools"><?php esc_html_e( 'Free Tools', 'a2e' ); ?><span class="nav__badge">13</span><?php a2e_the_icon( 'chevron-down', 'nav__chev' ); ?></button>
						<div class="mega mega--tools" id="mega-tools">
							<div class="mega__inner">
								<div class="mega__cols">
									<?php foreach ( $a2e_cats as $cat_key => $cat_label ) : ?>
										<div class="mega__col">
											<p class="mega__kicker"><?php echo esc_html( $cat_label ); ?></p>
											<ul>
												<?php foreach ( $a2e_tools as $tool ) : if ( $tool['cat'] !== $cat_key ) { continue; } ?>
													<li><a href="<?php echo esc_url( a2e_tool_url( $tool['slug'] ) ); ?>"><?php a2e_the_icon( $tool['icon'] ); ?><?php echo esc_html( $tool['name'] ); ?></a></li>
												<?php endforeach; ?>
											</ul>
										</div>
									<?php endforeach; ?>
								</div>
								<div class="mega__foot">
									<span><?php a2e_the_icon( 'sparkle' ); ?><?php esc_html_e( 'Free forever · no sign-up · fee rates kept up to date', 'a2e' ); ?></span>
									<a href="<?php echo esc_url( a2e_url( 'free-tools' ) ); ?>"><?php esc_html_e( 'All free tools', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></a>
								</div>
							</div>
						</div>
					</li>
					<li class="nav__item"><a class="nav__link" href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Pricing', 'a2e' ); ?></a></li>
					<li class="nav__item has-mega has-mega--small" data-mega>
						<button type="button" class="nav__link" aria-expanded="false" aria-controls="mega-results"><?php esc_html_e( 'Results', 'a2e' ); ?><?php a2e_the_icon( 'chevron-down', 'nav__chev' ); ?></button>
						<div class="mega mega--small" id="mega-results">
							<div class="mega__inner">
								<div class="mega__grid">
									<a class="mega__link" href="<?php echo esc_url( a2e_url( 'portfolio' ) ); ?>"><span class="mega__icon"><?php a2e_the_icon( 'grid' ); ?></span><span><strong><?php esc_html_e( 'Portfolio', 'a2e' ); ?></strong><small><?php esc_html_e( 'Recent client projects', 'a2e' ); ?></small></span></a>
									<a class="mega__link" href="<?php echo esc_url( get_post_type_archive_link( 'a2e_case_study' ) ); ?>"><span class="mega__icon"><?php a2e_the_icon( 'line' ); ?></span><span><strong><?php esc_html_e( 'Case studies', 'a2e' ); ?></strong><small><?php esc_html_e( 'Problem, work, result', 'a2e' ); ?></small></span></a>
									<a class="mega__link" href="<?php echo esc_url( get_post_type_archive_link( 'a2e_testimonial' ) ); ?>"><span class="mega__icon"><?php a2e_the_icon( 'star' ); ?></span><span><strong><?php esc_html_e( 'Client reviews', 'a2e' ); ?></strong><small><?php /* translators: 1: rating 2: count */ printf( esc_html__( '%1$s ★ from %2$s reviews', 'a2e' ), esc_html( a2e_opt( 'rating' ) ), esc_html( a2e_opt( 'reviews' ) ) ); ?></small></span></a>
									<a class="mega__link" href="<?php echo esc_url( a2e_url( 'about' ) ); ?>"><span class="mega__icon"><?php a2e_the_icon( 'users' ); ?></span><span><strong><?php esc_html_e( 'About A2E', 'a2e' ); ?></strong><small><?php /* translators: %s: year */ printf( esc_html__( 'Selling on eBay since %s', 'a2e' ), esc_html( a2e_opt( 'since' ) ) ); ?></small></span></a>
								</div>
							</div>
						</div>
					</li>
					<li class="nav__item"><a class="nav__link" href="<?php echo esc_url( a2e_blog_url() ); ?>"><?php esc_html_e( 'Blog', 'a2e' ); ?></a></li>
					<li class="nav__item"><a class="nav__link" href="<?php echo esc_url( a2e_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'a2e' ); ?></a></li>
				</ul>
			<?php endif; ?>
		</nav>

		<div class="header-actions">
			<button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="<?php esc_attr_e( 'Switch between light and dark mode', 'a2e' ); ?>" title="<?php esc_attr_e( 'Light / dark mode', 'a2e' ); ?>">
				<?php a2e_the_icon( 'sun', 'theme-toggle__sun' ); ?><?php a2e_the_icon( 'moon', 'theme-toggle__moon' ); ?>
			</button>
			<a class="icon-btn header-account" href="<?php echo esc_url( a2e_account_url() ); ?>" aria-label="<?php esc_attr_e( 'Client area', 'a2e' ); ?>" title="<?php esc_attr_e( 'Client area', 'a2e' ); ?>"><?php a2e_the_icon( 'user' ); ?></a>
			<a class="btn btn--primary btn--sm header-cta" href="<?php echo esc_url( a2e_url( 'quote-request' ) ); ?>"><span><?php esc_html_e( 'Free store audit', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
			<button type="button" class="icon-btn burger" data-burger aria-expanded="false" aria-controls="mnav" aria-label="<?php esc_attr_e( 'Open menu', 'a2e' ); ?>"><span></span><span></span><span></span></button>
		</div>
	</div>
</header>

<div class="mnav" id="mnav" data-mnav hidden>
	<div class="mnav__panel">
		<nav aria-label="<?php esc_attr_e( 'Mobile', 'a2e' ); ?>">
			<details class="mnav__group" open>
				<summary><?php esc_html_e( 'Services', 'a2e' ); ?><?php a2e_the_icon( 'chevron-down' ); ?></summary>
				<ul>
					<?php foreach ( a2e_featured_service_slugs() as $slug ) : ?>
						<li><a href="<?php echo esc_url( a2e_service_url( $slug ) ); ?>"><?php a2e_the_icon( $a2e_services[ $slug ]['icon'] ); ?><?php echo esc_html( $a2e_services[ $slug ]['nav'] ); ?></a></li>
					<?php endforeach; ?>
					<li><a href="<?php echo esc_url( a2e_url( 'amazon-to-ebay-dropshipping-service' ) ); ?>"><?php a2e_the_icon( 'arrow-right' ); ?><?php esc_html_e( 'All services', 'a2e' ); ?></a></li>
				</ul>
			</details>
			<details class="mnav__group">
				<summary><?php esc_html_e( 'Free tools', 'a2e' ); ?><?php a2e_the_icon( 'chevron-down' ); ?></summary>
				<ul>
					<?php foreach ( $a2e_tools as $tool ) : ?>
						<li><a href="<?php echo esc_url( a2e_tool_url( $tool['slug'] ) ); ?>"><?php a2e_the_icon( $tool['icon'] ); ?><?php echo esc_html( $tool['name'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</details>
			<ul class="mnav__links">
				<li><a href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>"><?php esc_html_e( 'Pricing', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( a2e_url( 'portfolio' ) ); ?>"><?php esc_html_e( 'Portfolio', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'a2e_case_study' ) ); ?>"><?php esc_html_e( 'Case studies', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'a2e_testimonial' ) ); ?>"><?php esc_html_e( 'Reviews', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( a2e_blog_url() ); ?>"><?php esc_html_e( 'Blog', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( a2e_url( 'about' ) ); ?>"><?php esc_html_e( 'About', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( a2e_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'a2e' ); ?></a></li>
				<li><a href="<?php echo esc_url( a2e_account_url() ); ?>"><?php esc_html_e( 'Client area', 'a2e' ); ?></a></li>
			</ul>
		</nav>
		<div class="mnav__cta">
			<?php
			a2e_buttons(
				array(
					array( __( 'Get a free store audit', 'a2e' ), a2e_url( 'quote-request' ), 'primary' ),
					array( __( 'Chat on WhatsApp', 'a2e' ), a2e_wa_link( 'Hi A2E! I have a question about my eBay store.' ), 'wa' ),
				)
			);
			?>
		</div>
	</div>
</div>

<main id="main" class="site-main">
