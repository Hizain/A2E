<?php
/**
 * Homepage.
 *
 * @package A2E_Prime
 */

get_header();

$a2e_points = a2e_lines( a2e_opt( 'hero_points' ) );
?>

<section class="hero">
	<div class="hero__bg" aria-hidden="true">
		<span class="aurora aurora--a"></span><span class="aurora aurora--b"></span><span class="aurora aurora--c"></span>
		<span class="grid-fade"></span>
		<span class="hero__beam"></span>
	</div>

	<div class="container hero__head">
		<div class="hero__floats" aria-hidden="true" data-floats>
			<?php
			$a2e_floats = array( 'amazon', 'walmart', 'aliexpress', 'target', 'homedepot', 'ebay' );
			foreach ( $a2e_floats as $a2e_n => $a2e_key ) {
				echo '<span class="ft ft--' . esc_attr( $a2e_key ) . '" style="--i:' . (int) $a2e_n . '">' . a2e_brand_mark( $a2e_key, 'md' ) . ( 'ebay' === $a2e_key ? '<span class="ft__sold">' . a2e_icon( 'check' ) . esc_html__( 'Sold', 'a2e' ) . '</span>' : '' ) . '</span>'; // phpcs:ignore
			}
			?>
		</div>

		<div class="hero__copy">
			<?php if ( a2e_opt( 'hero_kicker' ) ) : ?>
				<p class="hero__kicker"><span class="hero__kicker-dot" aria-hidden="true"></span><?php echo esc_html( a2e_opt( 'hero_kicker' ) ); ?></p>
			<?php endif; ?>
			<h1 class="hero__title">
				<span class="hero__title-main"><?php echo esc_html( a2e_opt( 'hero_title' ) ); ?></span>
				<em class="hero__title-accent"><?php echo esc_html( a2e_opt( 'hero_accent' ) ); ?></em>
			</h1>
			<p class="hero__lead"><?php echo esc_html( a2e_opt( 'hero_lead' ) ); ?></p>
			<div class="btn-row hero__actions">
				<?php
				a2e_buttons(
					array(
						array( a2e_opt( 'hero_primary_label' ), a2e_resolve_url( a2e_opt( 'hero_primary_url' ) ), 'primary' ),
						array( a2e_opt( 'hero_secondary_label' ), a2e_resolve_url( a2e_opt( 'hero_secondary_url' ) ), 'ghost', 'scan' ),
					)
				);
				?>
			</div>
			<ul class="hero__trust">
				<li class="hero__rating">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'a2e_testimonial' ) ); ?>">
						<?php echo a2e_stars( (float) a2e_opt( 'rating' ) ); // phpcs:ignore ?>
						<span><?php /* translators: 1: rating 2: reviews */ printf( esc_html__( '%1$s from %2$s reviews', 'a2e' ), '<strong>' . esc_html( a2e_opt( 'rating' ) ) . '</strong>', esc_html( a2e_opt( 'reviews' ) ) ); ?></span>
					</a>
				</li>
				<?php foreach ( $a2e_points as $a2e_point ) : ?>
					<li><?php a2e_the_icon( 'check-circle' ); ?><?php echo esc_html( $a2e_point ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<div class="container hero__stage">
		<?php get_template_part( 'template-parts/hero-board' ); ?>
	</div>

	<div class="container hero__suppliers">
		<p class="hero__suppliers-label"><?php esc_html_e( 'We source from 20+ trusted suppliers', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?> <?php esc_html_e( 'and sell where the buyers are', 'a2e' ); ?> <?php echo a2e_brand_mark( 'ebay', 'sm' ); // phpcs:ignore ?></p>
		<?php a2e_render_marquee(); ?>
	</div>
</section>

<section class="section section--stats">
	<div class="container"><?php a2e_render_stats(); ?></div>
</section>

<section class="section" id="why">
	<div class="container">
		<div class="split">
			<div class="split__copy">
				<?php
				a2e_section_head(
					array(
						'eyebrow' => __( 'Why most eBay stores stall', 'a2e' ),
						'title'   => __( 'Dropshipping is simple. <em>Doing it well, every day,</em> is not.', 'a2e' ),
						'intro'   => __( 'Stores rarely fail from one big mistake. They fail from a hundred small ones: the wrong products, weak titles, a missed price change, a slow reply. We replace guesswork with a system, and we run it for you.', 'a2e' ),
						'align'   => 'left',
					)
				);
				?>
				<div class="btn-row" data-reveal>
					<?php a2e_buttons( array( array( __( 'How we work', 'a2e' ), '#how', 'ghost', 'arrow-down' ) ) ); ?>
				</div>
			</div>
			<div class="split__visual"><?php a2e_render_compare(); ?></div>
		</div>
	</div>
</section>

<section class="section section--alt" id="services">
	<div class="container">
		<?php
		a2e_section_head(
			array(
				'eyebrow' => __( 'Everything your store needs', 'a2e' ),
				'title'   => __( 'One team for research, listings <em>and daily management.</em>', 'a2e' ),
				'intro'   => __( 'Pick a single service or let us run the whole workflow. Every part is connected, so nothing falls between freelancers.', 'a2e' ),
			)
		);
		a2e_render_services( array(), 'bento' );
		?>
		<p class="section__more" data-reveal><a class="link-arrow" href="<?php echo esc_url( a2e_url( 'amazon-to-ebay-dropshipping-service' ) ); ?>"><?php esc_html_e( 'Compare all services', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></a></p>
	</div>
</section>

<section class="section" id="how">
	<div class="container">
		<?php
		a2e_section_head(
			array(
				'eyebrow' => __( 'How it works', 'a2e' ),
				'title'   => __( 'From free audit to <em>first sales</em> in four steps.', 'a2e' ),
				'intro'   => __( 'No long onboarding and no jargon. You get a written plan and a fixed price before any work starts.', 'a2e' ),
			)
		);
		a2e_render_process();
		?>
	</div>
</section>

<?php if ( '1' === a2e_opt( 'show_simulator' ) ) : ?>
<section class="section section--dark" id="simulator">
	<div class="section__bg" aria-hidden="true"><span class="aurora aurora--a"></span><span class="aurora aurora--b"></span><span class="grid-fade"></span></div>
	<div class="container">
		<?php
		a2e_section_head(
			array(
				'eyebrow' => __( 'Profit simulator', 'a2e' ),
				'title'   => __( 'See what steady, researched listings <em>could add up to.</em>', 'a2e' ),
				'intro'   => __( 'Move the sliders to match your store. Growth comes from adding profit-checked listings every month, which is exactly what our plans do.', 'a2e' ),
			)
		);
		get_template_part( 'template-parts/simulator' );
		?>
	</div>
</section>
<?php endif; ?>

<section class="section" id="reviews">
	<div class="container">
		<?php
		a2e_section_head(
			array(
				'eyebrow' => __( 'Client results', 'a2e' ),
				/* translators: %s: number of reviews */
				'title'   => sprintf( __( '%s sellers have rated us <em>five stars.</em>', 'a2e' ), esc_html( a2e_opt( 'reviews' ) ) ),
				'intro'   => __( 'Real feedback from sellers we have worked with. Read every review on our public profile.', 'a2e' ),
			)
		);
		?>
	</div>
	<?php a2e_render_reviews( 12, 'wall' ); ?>
	<div class="container">
		<?php if ( '1' === a2e_opt( 'show_founder' ) ) { a2e_render_founder(); } ?>
	</div>
</section>

<section class="section section--alt" id="pricing">
	<div class="container">
		<?php
		a2e_section_head(
			array(
				'eyebrow' => __( 'Plans & pricing', 'a2e' ),
				'title'   => __( 'Simple plans. <em>Real work every month.</em>', 'a2e' ),
				'intro'   => __( 'Start with a one-time listing package or let us run your store month after month. No long-term contract.', 'a2e' ),
			)
		);
		a2e_render_pricing();
		?>
	</div>
</section>

<?php if ( '1' === a2e_opt( 'show_tools' ) ) : ?>
<section class="section" id="tools">
	<div class="container">
		<div class="tools-teaser">
			<div class="tools-teaser__copy">
				<?php
				a2e_section_head(
					array(
						'eyebrow' => __( '13 free seller tools', 'a2e' ),
						'title'   => __( 'Check fees and profit <em>before you list.</em>', 'a2e' ),
						'intro'   => __( 'The same calculators our team uses every day. Free, no sign-up, fee rates kept up to date.', 'a2e' ),
						'align'   => 'left',
					)
				);
				?>
				<div class="quickfee card" data-quickfee data-reveal>
					<div class="quickfee__head"><?php a2e_the_icon( 'calculator' ); ?><strong><?php esc_html_e( 'Quick eBay fee check', 'a2e' ); ?></strong></div>
					<label class="quickfee__field"><span><?php esc_html_e( 'Sold price', 'a2e' ); ?></span><span class="quickfee__input"><i>$</i><input type="number" inputmode="decimal" value="49.99" min="0" step="any" data-qf="price" aria-label="<?php esc_attr_e( 'Sold price', 'a2e' ); ?>"></span></label>
					<label class="quickfee__field"><span><?php esc_html_e( 'Item cost', 'a2e' ); ?></span><span class="quickfee__input"><i>$</i><input type="number" inputmode="decimal" value="30" min="0" step="any" data-qf="cost" aria-label="<?php esc_attr_e( 'Item cost', 'a2e' ); ?>"></span></label>
					<div class="quickfee__out">
						<div><span><?php esc_html_e( 'eBay fees', 'a2e' ); ?></span><strong data-qf-out="fees">-</strong></div>
						<div><span><?php esc_html_e( 'You keep', 'a2e' ); ?></span><strong class="is-good" data-qf-out="profit">-</strong></div>
						<div><span><?php esc_html_e( 'Margin', 'a2e' ); ?></span><strong data-qf-out="margin">-</strong></div>
					</div>
					<a class="link-arrow" href="<?php echo esc_url( a2e_tool_url( 'ebay-fee-calculator' ) ); ?>"><?php esc_html_e( 'Open the full fee calculator', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></a>
				</div>
			</div>
			<div class="tools-teaser__grid">
				<?php
				$a2e_featured_tools = array( 'amazon-to-ebay-profit-calculator', 'ebay-title-generator', 'ebay-product-research-scorecard', 'ebay-bulk-profit-calculator', 'ebay-dropshipping-income-calculator' );
				$a2e_all_tools      = a2e_tools();
				foreach ( $a2e_featured_tools as $a2e_i => $a2e_slug ) {
					echo a2e_tool_card( $a2e_all_tools[ $a2e_slug ], $a2e_i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<a class="tool-card tool-card--all" href="<?php echo esc_url( a2e_url( 'free-tools' ) ); ?>" data-reveal><span class="tool-card__icon"><?php a2e_the_icon( 'grid' ); ?></span><h3 class="tool-card__title"><?php esc_html_e( 'See all 13 free tools', 'a2e' ); ?></h3><span class="tool-card__go"><?php esc_html_e( 'Browse tools', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></span></a>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>

<?php
if ( '1' === a2e_opt( 'show_blog' ) ) :
	$a2e_posts = new WP_Query(
		array(
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( $a2e_posts->have_posts() ) :
		?>
<section class="section section--alt" id="blog">
	<div class="container">
		<div class="sec-row">
			<?php
			a2e_section_head(
				array(
					'eyebrow' => __( 'From the blog', 'a2e' ),
					'title'   => __( 'Guides for sellers who <em>play the long game.</em>', 'a2e' ),
					'align'   => 'left',
				)
			);
			?>
			<a class="btn btn--ghost" href="<?php echo esc_url( a2e_blog_url() ); ?>" data-reveal><span><?php esc_html_e( 'Read all guides', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
		</div>
		<div class="post-grid">
			<?php
			while ( $a2e_posts->have_posts() ) {
				$a2e_posts->the_post();
				get_template_part( 'template-parts/card-post' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
		<?php
	endif;
endif;
?>

<section class="section" id="faq">
	<div class="container container--mid">
		<?php
		a2e_section_head(
			array(
				'eyebrow' => __( 'FAQ', 'a2e' ),
				'title'   => __( 'Questions sellers ask <em>before they start.</em>', 'a2e' ),
				'intro'   => sprintf(
					/* translators: %s: WhatsApp link */
					__( 'Still unsure? %s and get a straight answer from a real person.', 'a2e' ),
					'<a href="' . esc_url( a2e_wa_link( 'Hi A2E, I have a question before I start.' ) ) . '">' . esc_html__( 'Message us on WhatsApp', 'a2e' ) . '</a>'
				),
			)
		);
		a2e_render_faq( a2e_site_faq() );
		?>
	</div>
</section>

<?php
a2e_render_cta();
get_footer();
