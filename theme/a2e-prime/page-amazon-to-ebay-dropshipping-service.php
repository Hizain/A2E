<?php
/**
 * /amazon-to-ebay-dropshipping-service/ — services hub.
 *
 * @package A2E_Prime
 */

get_header();
$a2e_visual  = '<div class="hero-tile"><span class="hero-tile__icon">' . a2e_icon( 'layers' ) . '</span><span class="hero-tile__chips">';
$a2e_visual .= '<span>' . a2e_icon( 'check' ) . esc_html__( 'Profit checked after fees', 'a2e' ) . '</span>';
$a2e_visual .= '<span>' . a2e_icon( 'check' ) . esc_html__( 'VeRO-safe products', 'a2e' ) . '</span>';
$a2e_visual .= '<span>' . a2e_icon( 'check' ) . esc_html__( 'Daily store care', 'a2e' ) . '</span>';
$a2e_visual .= '</span></div>';
a2e_page_hero(
	array(
		'eyebrow' => __( 'Our services', 'a2e' ),
		'title'   => __( 'Every eBay dropshipping service, <em>from one team.</em>', 'a2e' ),
		'intro'   => __( 'Product research, SEO listings, store setup, inventory monitoring, order routines and daily account management — done for you by a team that does this every day.', 'a2e' ),
		'actions' => array(
			array( __( 'See plans & pricing', 'a2e' ), a2e_url( 'pricing' ), 'primary' ),
			array( __( 'Get a free store audit', 'a2e' ), a2e_url( 'quote-request' ), 'ghost', 'scan' ),
		),
		'visual'  => $a2e_visual,
	)
);
?>
<section class="section">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Services', 'a2e' ), 'title' => __( 'Pick one service — or let us <em>run it all</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_services( array_keys( a2e_services() ), 'cards' ); ?>
	</div>
</section>

<section class="section section--alt">
	<div class="container">
		<div class="split">
			<div>
				<?php a2e_section_head( array( 'eyebrow' => __( 'Why sellers choose A2E', 'a2e' ), 'title' => __( 'One connected workflow beats <em>three freelancers.</em>', 'a2e' ), 'intro' => __( 'Research, listings and management handled by the same team — so the products we research are the products we list, price and look after.', 'a2e' ), 'align' => 'left' ) ); ?>
				<ul class="check-list" data-reveal>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Profit-first research — every product checked against real eBay fees', 'a2e' ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Honest advice — we tell you what will not work before you spend', 'a2e' ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php /* translators: 1: rating 2: reviews */ printf( esc_html__( 'Proven — rated %1$s from %2$s public reviews', 'a2e' ), esc_html( a2e_opt( 'rating' ) ), esc_html( a2e_opt( 'reviews' ) ) ); ?></li>
					<li><?php a2e_the_icon( 'check' ); ?><?php esc_html_e( 'Compliance-aware — sourcing and fulfilment set up around eBay’s current rules', 'a2e' ); ?></li>
				</ul>
			</div>
			<div><?php a2e_render_compare(); ?></div>
		</div>
	</div>
</section>

<section class="section">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'How it works', 'a2e' ), 'title' => __( 'From free audit to <em>first sales</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_process(); ?>
	</div>
</section>

<section class="section section--alt">
	<div class="container">
		<?php a2e_section_head( array( 'eyebrow' => __( 'Plans', 'a2e' ), 'title' => __( 'Simple plans. <em>Real work every month.</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_pricing(); ?>
	</div>
</section>

<section class="section">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'FAQ', 'a2e' ), 'title' => __( 'Questions about <em>our services</em>', 'a2e' ) ) ); ?>
		<?php a2e_render_faq( a2e_site_faq() ); ?>
	</div>
</section>
<?php
a2e_designed_extra_content();
a2e_render_cta();
get_footer();
