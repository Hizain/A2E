<?php
/**
 * Shortcodes. Every shortcode used by the previous theme keeps working (pages on the live
 * site contain them), now rendered with the new design.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capture a component's echoed output.
 *
 * @param callable $fn   Callback.
 * @param array    $args Arguments.
 * @return string
 */
function a2e_capture( $fn, $args = array() ) {
	ob_start();
	call_user_func_array( $fn, $args );
	return ob_get_clean();
}

/**
 * Checklist groups.
 *
 * @return array title => items
 */
function a2e_checklist_groups() {
	return array(
		__( 'Store setup', 'a2e' )          => array(
			__( 'Business policies set for shipping, returns and payment', 'a2e' ),
			__( 'Handling time you can actually meet, every time', 'a2e' ),
			__( 'Store name, logo and “About” section that build trust', 'a2e' ),
			__( 'Store categories that match what you sell', 'a2e' ),
			__( 'Selling limits checked before you plan listing volume', 'a2e' ),
		),
		__( 'Product research', 'a2e' )     => array(
			__( 'Sold history on eBay (not just active listings) for every product', 'a2e' ),
			__( 'Sell-through rate checked — aim for products that sell consistently', 'a2e' ),
			__( 'Profit worked out after eBay fees, ad spend and tax', 'a2e' ),
			__( 'VeRO / brand restrictions and prohibited items checked', 'a2e' ),
			__( 'Supplier stock and delivery times confirmed', 'a2e' ),
		),
		__( 'Listings', 'a2e' )             => array(
			__( 'Title uses the 80 characters well: brand, product, key features', 'a2e' ),
			__( 'Every relevant item specific filled in', 'a2e' ),
			__( 'Clear photos with a clean main image', 'a2e' ),
			__( 'Mobile-friendly description that answers buyer questions', 'a2e' ),
			__( 'Price checked against sold comparables, not just active ones', 'a2e' ),
		),
		__( 'Inventory & pricing', 'a2e' )  => array(
			__( 'Daily check for out-of-stock supplier items', 'a2e' ),
			__( 'Price-change monitoring so margins never go negative', 'a2e' ),
			__( 'Rules for when to end or revise a listing', 'a2e' ),
		),
		__( 'Orders & customers', 'a2e' )   => array(
			__( 'Orders processed within your handling time', 'a2e' ),
			__( 'Tracking uploaded promptly and accurately', 'a2e' ),
			__( 'Saved replies for common buyer questions', 'a2e' ),
			__( 'Process for returns and “item not received” cases', 'a2e' ),
		),
		__( 'Weekly account health', 'a2e' ) => array(
			__( 'Seller dashboard and defect rate reviewed', 'a2e' ),
			__( 'Late shipment and tracking rates checked', 'a2e' ),
			__( 'Listings with no views or sales refreshed or ended', 'a2e' ),
			__( 'Next week’s research and listing goals set', 'a2e' ),
		),
	);
}

/**
 * Interactive checklist (progress is saved in the visitor's browser).
 */
function a2e_render_checklist() {
	$groups = a2e_checklist_groups();
	$total  = 0;
	foreach ( $groups as $items ) {
		$total += count( $items );
	}
	?>
	<div class="checklist" data-checklist="a2e-checklist-v1">
		<div class="checklist__progress" aria-live="polite">
			<div class="checklist__bar"><span data-progress></span></div>
			<p><strong data-done>0</strong> / <?php echo (int) $total; ?> <?php esc_html_e( 'done', 'a2e' ); ?> <button type="button" class="link-btn" data-reset><?php esc_html_e( 'Reset', 'a2e' ); ?></button></p>
		</div>
		<div class="checklist__groups">
			<?php
			$n = 0;
			foreach ( $groups as $title => $items ) :
				?>
				<section class="checklist__group card" data-reveal>
					<h3><span class="checklist__num"><?php echo esc_html( sprintf( '%02d', ++$n ) ); ?></span><?php echo esc_html( $title ); ?></h3>
					<ul>
						<?php foreach ( $items as $i => $item ) : ?>
							<li><label><input type="checkbox" data-key="<?php echo esc_attr( $n . '-' . $i ); ?>"><span class="checklist__box" aria-hidden="true"><?php a2e_the_icon( 'check' ); ?></span><span><?php echo esc_html( $item ); ?></span></label></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Portfolio grid.
 *
 * @param int $limit Limit.
 */
function a2e_render_portfolio( $limit = 12 ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'a2e_portfolio',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
		)
	);
	if ( ! $query->have_posts() ) {
		echo '<p class="empty-note">' . esc_html__( 'Portfolio projects will appear here.', 'a2e' ) . '</p>';
		return;
	}
	$icons = array( 'scan', 'gauge', 'repeat', 'pen', 'chart', 'store' );
	echo '<div class="work-grid">';
	$i = 0;
	while ( $query->have_posts() ) {
		$query->the_post();
		$result = (string) get_post_meta( get_the_ID(), '_a2e_result', true );
		$client = (string) get_post_meta( get_the_ID(), '_a2e_client_name', true );
		$svc    = (string) get_post_meta( get_the_ID(), '_a2e_service', true );
		?>
		<article class="work-card" data-reveal style="--d:<?php echo (int) ( $i % 3 ); ?>">
			<a href="<?php the_permalink(); ?>" class="work-card__media">
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'a2e-card', array( 'loading' => 'lazy' ) );
				} else {
					echo '<span class="work-card__art work-card__art--' . (int) ( $i % 4 ) . '">' . a2e_icon( $icons[ $i % count( $icons ) ] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</a>
			<div class="work-card__body">
				<?php if ( $svc ) : ?><span class="tag"><?php echo esc_html( $svc ); ?></span><?php endif; ?>
				<h3 class="work-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p class="work-card__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
				<?php if ( $result ) : ?><p class="work-card__result"><?php a2e_the_icon( 'trend' ); ?><?php echo esc_html( $result ); ?></p><?php endif; ?>
				<?php if ( $client ) : ?><p class="work-card__client"><?php /* translators: %s: client */ printf( esc_html__( 'Client: %s', 'a2e' ), esc_html( $client ) ); ?></p><?php endif; ?>
			</div>
		</article>
		<?php
		++$i;
	}
	wp_reset_postdata();
	echo '</div>';
}

/**
 * Case study cards.
 *
 * @param int $limit Limit.
 */
function a2e_render_case_studies( $limit = 12 ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'a2e_case_study',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
		)
	);
	if ( ! $query->have_posts() ) {
		return;
	}
	echo '<div class="case-grid">';
	$i = 0;
	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part( 'template-parts/card-case', null, array( 'i' => $i++ ) );
	}
	wp_reset_postdata();
	echo '</div>';
}

/**
 * "Doing it alone vs with A2E" comparison.
 */
function a2e_render_compare() {
	?>
	<div class="compare" data-compare>
		<div class="compare__toggle" role="tablist" aria-label="<?php esc_attr_e( 'Compare', 'a2e' ); ?>">
			<button type="button" role="tab" aria-selected="false" data-side="alone"><?php esc_html_e( 'Doing it alone', 'a2e' ); ?></button>
			<button type="button" role="tab" aria-selected="true" class="is-active" data-side="a2e"><?php esc_html_e( 'With A2E', 'a2e' ); ?></button>
			<span class="compare__pill" aria-hidden="true"></span>
		</div>
		<ul class="compare__list" data-side-active="a2e">
			<?php foreach ( a2e_compare_rows() as $i => $row ) : ?>
				<li class="compare__row" data-reveal style="--d:<?php echo (int) $i; ?>">
					<span class="compare__alone"><?php a2e_the_icon( 'circle-x' ); ?><?php echo esc_html( $row[0] ); ?></span>
					<span class="compare__a2e"><?php a2e_the_icon( 'check-circle' ); ?><?php echo esc_html( $row[1] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Contact layout: channel cards + form.
 */
function a2e_render_contact() {
	$email = a2e_opt( 'email' );
	?>
	<div class="contact-layout">
		<aside class="contact-cards">
			<a class="contact-card contact-card--wa" href="<?php echo esc_url( a2e_wa_link( 'Hi A2E, I have a question about my eBay store.' ) ); ?>" target="_blank" rel="noopener">
				<span class="contact-card__icon"><?php a2e_the_icon( 'whatsapp' ); ?></span>
				<span><strong><?php esc_html_e( 'WhatsApp', 'a2e' ); ?></strong><small><?php echo esc_html( a2e_opt( 'whatsapp' ) ); ?></small><em><?php esc_html_e( 'Fastest way to reach us', 'a2e' ); ?></em></span>
				<?php a2e_the_icon( 'arrow-up-right', 'contact-card__go' ); ?>
			</a>
			<?php if ( $email ) : ?>
				<a class="contact-card" href="mailto:<?php echo esc_attr( $email ); ?>">
					<span class="contact-card__icon"><?php a2e_the_icon( 'mail' ); ?></span>
					<span><strong><?php esc_html_e( 'Email', 'a2e' ); ?></strong><small><?php echo esc_html( $email ); ?></small><em><?php esc_html_e( 'For detailed questions and documents', 'a2e' ); ?></em></span>
					<?php a2e_the_icon( 'arrow-up-right', 'contact-card__go' ); ?>
				</a>
			<?php endif; ?>
			<a class="contact-card" href="<?php echo esc_url( a2e_opt( 'fiverr_url' ) ); ?>" target="_blank" rel="noopener">
				<span class="contact-card__icon"><?php a2e_the_icon( 'fiverr' ); ?></span>
				<span><strong><?php esc_html_e( 'Fiverr', 'a2e' ); ?></strong><small><?php /* translators: 1: rating 2: reviews */ printf( esc_html__( '%1$s ★ from %2$s reviews', 'a2e' ), esc_html( a2e_opt( 'rating' ) ), esc_html( a2e_opt( 'reviews' ) ) ); ?></small><em><?php esc_html_e( 'Read our public reviews', 'a2e' ); ?></em></span>
				<?php a2e_the_icon( 'arrow-up-right', 'contact-card__go' ); ?>
			</a>
			<div class="contact-note"><?php a2e_the_icon( 'clock' ); ?><p><?php echo esc_html( a2e_opt( 'response_time' ) ); ?></p></div>
		</aside>
		<div class="contact-form card">
			<h2 class="h3"><?php esc_html_e( 'Send us a message', 'a2e' ); ?></h2>
			<?php a2e_render_form( 'contact' ); ?>
		</div>
	</div>
	<?php
}

/**
 * Compact CTA box for use inside content.
 *
 * @param string $title Title.
 * @param string $text  Text.
 * @param string $label Button label.
 * @param string $url   Button URL.
 * @return string
 */
function a2e_inline_cta( $title, $text, $label, $url ) {
	return '<aside class="inline-cta"><div class="inline-cta__icon">' . a2e_icon( 'rocket' ) . '</div><div class="inline-cta__copy"><p class="inline-cta__title">' . esc_html( $title ) . '</p><p>' . esc_html( $text ) . '</p></div><a class="btn btn--primary" href="' . esc_url( $url ) . '"><span>' . esc_html( $label ) . '</span>' . a2e_icon( 'arrow-right', 'btn__icon' ) . '</a></aside>';
}

/* ----- Shortcode registrations --------------------------------------------------------- */

add_shortcode(
	'a2e_tool',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'slug' => '' ), $atts, 'a2e_tool' );
		return a2e_current_tool() === $atts['slug'] ? '' : a2e_render_tool( sanitize_title( $atts['slug'] ) );
	}
);
add_shortcode(
	'a2e_tools_hub',
	function () {
		return a2e_capture( 'a2e_render_tools_grid', array( 0, true ) );
	}
);
foreach ( array( 'a2e_services', 'a2e_services_grid' ) as $a2e_sc ) {
	add_shortcode(
		$a2e_sc,
		function () {
			return a2e_capture( 'a2e_render_services' );
		}
	);
}
foreach ( array( 'a2e_pricing', 'a2e_product_services', 'a2e_service_hub' ) as $a2e_sc ) {
	add_shortcode(
		$a2e_sc,
		function ( $atts ) {
			$atts = shortcode_atts( array( 'custom' => 'yes' ), $atts );
			return a2e_capture( 'a2e_render_pricing', array( array( 'custom' => 'no' !== $atts['custom'] ) ) );
		}
	);
}
foreach ( array( 'a2e_fiverr_reviews', 'a2e_reviews' ) as $a2e_sc ) {
	add_shortcode(
		$a2e_sc,
		function ( $atts ) {
			$atts = shortcode_atts( array( 'limit' => 6 ), $atts );
			return a2e_capture( 'a2e_render_reviews', array( absint( $atts['limit'] ), 'grid' ) );
		}
	);
}
unset( $a2e_sc );
add_shortcode(
	'a2e_client_wall',
	function () {
		return a2e_capture( 'a2e_render_reviews', array( 12, 'wall' ) );
	}
);
add_shortcode(
	'a2e_portfolio',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'limit' => 12 ), $atts );
		return a2e_capture( 'a2e_render_portfolio', array( absint( $atts['limit'] ) ) );
	}
);
add_shortcode(
	'a2e_case_studies',
	function () {
		return a2e_capture( 'a2e_render_case_studies' );
	}
);
add_shortcode(
	'a2e_cta',
	function () {
		return a2e_inline_cta( __( 'Want us to do this for you?', 'a2e' ), __( 'Get a free store audit and a clear plan with a fixed price.', 'a2e' ), __( 'Get my free audit', 'a2e' ), a2e_url( 'quote-request' ) );
	}
);
add_shortcode(
	'a2e_booking_cta',
	function () {
		return a2e_inline_cta( __( 'Not sure where to start?', 'a2e' ), __( 'Book a consultation and get a prioritised plan before you spend a cent.', 'a2e' ), __( 'Book a consultation', 'a2e' ), a2e_url( 'consultation' ) );
	}
);
add_shortcode(
	'a2e_service_comparison',
	function () {
		return a2e_capture( 'a2e_render_compare' );
	}
);
add_shortcode(
	'a2e_checklist',
	function () {
		return a2e_capture( 'a2e_render_checklist' );
	}
);
add_shortcode(
	'a2e_contact_layout',
	function () {
		return a2e_capture( 'a2e_render_contact' );
	}
);
add_shortcode(
	'a2e_faq',
	function () {
		return a2e_capture( 'a2e_render_faq', array( a2e_site_faq() ) );
	}
);
add_shortcode(
	'a2e_quote_form',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'type' => 'quote' ), $atts );
		return a2e_capture( 'a2e_render_form', array( sanitize_key( $atts['type'] ) ) );
	}
);
add_shortcode(
	'a2e_newsletter_form',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'button' => __( 'Subscribe', 'a2e' ) ), $atts );
		return a2e_capture( 'a2e_render_newsletter', array( $atts['button'] ) );
	}
);
add_shortcode(
	'a2e_marquee',
	function () {
		return a2e_capture( 'a2e_render_marquee' );
	}
);
add_shortcode(
	'a2e_stats',
	function () {
		return a2e_capture( 'a2e_render_stats' );
	}
);
