<?php
/**
 * Service guide (/service-guides/{slug}/).
 *
 * Sections come from inc/content.php; headline/intro/SEO can be overridden per post.
 * A guide you add yourself (slug not in the built-in list) shows its editor content.
 *
 * @package A2E_Prime
 */

get_header();
while ( have_posts() ) :
	the_post();
	$a2e_slug     = get_post_field( 'post_name' );
	$a2e_services = a2e_services();
	$a2e_s        = isset( $a2e_services[ $a2e_slug ] ) ? $a2e_services[ $a2e_slug ] : null;

	if ( ! $a2e_s ) {
		a2e_page_hero( array( 'eyebrow' => __( 'Service', 'a2e' ), 'title' => esc_html( get_the_title() ), 'intro' => has_excerpt() ? esc_html( get_the_excerpt() ) : '' ) );
		echo '<section class="page-body"><div class="container container--narrow prose">';
		the_content();
		echo '</div></section>';
		a2e_render_cta();
		continue;
	}

	$a2e_visual  = '<div class="hero-tile"><span class="hero-tile__icon">' . a2e_icon( $a2e_s['icon'] ) . '</span><span class="hero-tile__chips">';
	foreach ( array_slice( $a2e_s['outcomes'], 0, 3 ) as $a2e_o ) {
		$a2e_visual .= '<span>' . a2e_icon( 'check' ) . esc_html( $a2e_o ) . '</span>';
	}
	$a2e_visual .= '</span></div>';

	a2e_page_hero(
		array(
			'eyebrow' => __( 'Service', 'a2e' ) . ' · ' . $a2e_s['title'],
			'title'   => $a2e_s['headline'],
			'intro'   => esc_html( $a2e_s['intro'] ),
			'actions' => array(
				array( __( 'See plans & pricing', 'a2e' ), a2e_url( 'pricing' ), 'primary' ),
				array( __( 'Get a free store audit', 'a2e' ), add_query_arg( 'plan', rawurlencode( $a2e_s['title'] ), a2e_url( 'quote-request' ) ), 'ghost', 'scan' ),
			),
			'visual'  => $a2e_visual,
		)
	);
	?>
	<section class="section">
		<div class="container">
			<?php a2e_section_head( array( 'eyebrow' => __( 'What you get', 'a2e' ), 'title' => sprintf( /* translators: %s: service */ __( 'Inside our %s service', 'a2e' ), '<em>' . esc_html( strtolower( $a2e_s['title'] ) ) . '</em>' ) ) ); ?>
			<div class="deliver-grid">
				<?php foreach ( $a2e_s['deliver'] as $a2e_i => $a2e_d ) : ?>
					<div class="deliver" data-reveal style="--d:<?php echo (int) ( $a2e_i % 3 ); ?>">
						<span class="deliver__icon"><?php a2e_the_icon( $a2e_d[0] ); ?></span>
						<h3><?php echo esc_html( $a2e_d[1] ); ?></h3>
						<p><?php echo esc_html( $a2e_d[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="section section--alt">
		<div class="container">
			<div class="fit">
				<div class="fit__card" data-reveal>
					<h3><?php esc_html_e( 'Best for', 'a2e' ); ?></h3>
					<ul class="check-list">
						<?php foreach ( $a2e_s['best_for'] as $a2e_b ) : ?><li><?php a2e_the_icon( 'check' ); ?><?php echo esc_html( $a2e_b ); ?></li><?php endforeach; ?>
					</ul>
				</div>
				<div class="fit__card aside-card--dark" data-reveal style="--d:1">
					<h3><?php esc_html_e( 'Included in our monthly plans', 'a2e' ); ?></h3>
					<p><?php esc_html_e( 'Take this service on its own, or get it as part of done-for-you management with research, listings and daily store care.', 'a2e' ); ?></p>
					<div class="btn-row"><?php a2e_buttons( array( array( __( 'Compare plans', 'a2e' ), a2e_url( 'pricing' ), 'light' ), array( __( 'Ask on WhatsApp', 'a2e' ), a2e_wa_link( sprintf( 'Hi A2E, I have a question about your %s service.', $a2e_s['title'] ) ), 'wa' ) ) ); ?></div>
				</div>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="container">
			<?php a2e_section_head( array( 'eyebrow' => __( 'How it works', 'a2e' ), 'title' => __( 'Simple process, <em>clear results</em>', 'a2e' ) ) ); ?>
			<?php a2e_render_process(); ?>
		</div>
	</section>

	<?php
	$a2e_tools = a2e_tools();
	$a2e_rel   = array_filter(
		$a2e_s['tools'],
		function ( $t ) use ( $a2e_tools ) {
			return isset( $a2e_tools[ $t ] );
		}
	);
	if ( $a2e_rel ) :
		?>
		<section class="section section--alt">
			<div class="container">
				<?php a2e_section_head( array( 'eyebrow' => __( 'Free tools', 'a2e' ), 'title' => __( 'Try the checks <em>we use</em>', 'a2e' ) ) ); ?>
				<div class="tool-grid">
					<?php
					$a2e_i = 0;
					foreach ( $a2e_rel as $a2e_t ) {
						echo a2e_tool_card( $a2e_tools[ $a2e_t ], $a2e_i++ ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( get_post_meta( get_the_ID(), '_a2e_show_content', true ) && '' !== trim( get_the_content() ) ) : ?>
		<section class="section"><div class="container container--narrow prose"><?php the_content(); ?></div></section>
	<?php endif; ?>

	<section class="section">
		<div class="container container--mid">
			<?php a2e_section_head( array( 'eyebrow' => __( 'FAQ', 'a2e' ), 'title' => esc_html( $a2e_s['title'] ) . ' — <em>' . esc_html__( 'questions', 'a2e' ) . '</em>' ) ); ?>
			<?php a2e_render_faq( $a2e_s['faq'] ); ?>
		</div>
	</section>

	<section class="section section--alt">
		<div class="container">
			<div class="sec-row">
				<?php a2e_section_head( array( 'eyebrow' => __( 'More services', 'a2e' ), 'title' => __( 'Works even better <em>together</em>', 'a2e' ), 'align' => 'left' ) ); ?>
				<a class="btn btn--ghost" href="<?php echo esc_url( a2e_url( 'amazon-to-ebay-dropshipping-service' ) ); ?>"><span><?php esc_html_e( 'All services', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
			</div>
			<?php
			$a2e_others = array_values( array_diff( a2e_featured_service_slugs(), array( $a2e_slug ) ) );
			a2e_render_services( array_slice( $a2e_others, 0, 3 ), 'cards' );
			?>
		</div>
	</section>
	<?php
	a2e_render_cta();
endwhile;
get_footer();
