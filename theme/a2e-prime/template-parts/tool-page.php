<?php
/**
 * Template for every free tool (/free-tools/{slug}/).
 *
 * @package A2E_Prime
 */

$a2e_slug  = a2e_current_tool();
$a2e_tools = a2e_tools();
$a2e_tool  = $a2e_tools[ $a2e_slug ];
$a2e_cats  = a2e_tool_categories();

get_header();
$a2e_meta  = '<span>' . a2e_icon( 'check-circle' ) . esc_html__( 'Free', 'a2e' ) . '</span>';
$a2e_meta .= '<span>' . a2e_icon( 'lock' ) . esc_html__( 'No sign-up', 'a2e' ) . '</span>';
if ( 'fees' === $a2e_tool['cat'] ) {
	/* translators: %s: month */
	$a2e_meta .= '<span>' . a2e_icon( 'refresh' ) . esc_html( sprintf( __( 'eBay rates checked %s', 'a2e' ), a2e_opt( 'fee_checked' ) ) ) . '</span>';
}
a2e_page_hero(
	array(
		'eyebrow' => ( isset( $a2e_cats[ $a2e_tool['cat'] ] ) ? $a2e_cats[ $a2e_tool['cat'] ] : __( 'Free tool', 'a2e' ) ) . ' · ' . __( 'Free tool', 'a2e' ),
		'title'   => esc_html( $a2e_tool['name'] ),
		'intro'   => esc_html( $a2e_tool['intro'] ),
		'meta'    => $a2e_meta,
	)
);
?>
<section class="tool-section">
	<div class="container">
		<?php echo a2e_render_tool( $a2e_slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="tool-strip" data-reveal>
			<p><?php esc_html_e( 'Want products that pass these checks, listed for you?', 'a2e' ); ?> <span><?php esc_html_e( 'We research, price and list profit-checked products every month.', 'a2e' ); ?></span></p>
			<?php a2e_buttons( array( array( __( 'Get a free store audit', 'a2e' ), a2e_url( 'quote-request' ), 'primary' ) ) ); ?>
		</div>
	</div>
</section>

<section class="section section--alt">
	<div class="container">
		<div class="tool-article">
			<div class="prose"><?php echo wp_kses_post( a2e_tool_explainer( $a2e_tool['explainer'] ) ); ?></div>
			<aside class="tool-aside">
				<div class="aside-card aside-card--dark">
					<p class="aside-card__title"><?php esc_html_e( 'Rather not do the maths?', 'a2e' ); ?></p>
					<p><?php esc_html_e( 'Our team researches, prices and lists products that pass every check, then manages your store daily.', 'a2e' ); ?></p>
					<a class="btn btn--light btn--sm btn--block" href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>"><span><?php esc_html_e( 'See plans', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
				</div>
				<div class="aside-card">
					<p class="aside-card__title"><?php esc_html_e( 'More free tools', 'a2e' ); ?></p>
					<ul>
						<?php foreach ( a2e_related_tools( $a2e_slug, 6 ) as $a2e_t ) : ?>
							<li><a href="<?php echo esc_url( a2e_tool_url( $a2e_t['slug'] ) ); ?>"><?php a2e_the_icon( $a2e_t['icon'] ); ?><?php echo esc_html( $a2e_t['name'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</aside>
		</div>
	</div>
</section>

<?php
$a2e_faqs = array();
foreach ( $a2e_tool['faqs'] as $a2e_f ) {
	$a2e_faqs[] = array( $a2e_f['q'], $a2e_f['a'] );
}
?>
<section class="section">
	<div class="container container--mid">
		<?php a2e_section_head( array( 'eyebrow' => __( 'FAQ', 'a2e' ), 'title' => esc_html( $a2e_tool['name'] ) . ' <em>' . esc_html__( 'questions, answered', 'a2e' ) . '</em>' ) ); ?>
		<?php a2e_render_faq( $a2e_faqs ); ?>
	</div>
</section>

<section class="section section--alt">
	<div class="container">
		<div class="sec-row">
			<?php a2e_section_head( array( 'eyebrow' => __( 'Keep going', 'a2e' ), 'title' => __( 'Related free tools', 'a2e' ), 'align' => 'left' ) ); ?>
			<a class="btn btn--ghost" href="<?php echo esc_url( a2e_url( 'free-tools' ) ); ?>"><span><?php esc_html_e( 'All tools', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
		</div>
		<div class="tool-grid">
			<?php
			$a2e_i = 0;
			foreach ( a2e_related_tools( $a2e_slug, 3 ) as $a2e_t ) {
				echo a2e_tool_card( $a2e_t, $a2e_i++ ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</section>
<?php
if ( empty( $GLOBALS['a2e_virtual_tool'] ) ) {
	a2e_designed_extra_content();
}
a2e_render_cta();
get_footer();
