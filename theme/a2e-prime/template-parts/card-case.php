<?php
/**
 * Case study card.
 *
 * @package A2E_Prime
 */

$a2e_i     = isset( $args['i'] ) ? (int) $args['i'] : 0;
$a2e_stats = array();
foreach ( a2e_lines( (string) get_post_meta( get_the_ID(), '_a2e_case_stats', true ) ) as $a2e_line ) {
	$a2e_parts = array_map( 'trim', explode( '|', $a2e_line, 2 ) );
	if ( 2 === count( $a2e_parts ) ) {
		$a2e_stats[] = $a2e_parts;
	}
}
$a2e_tag   = (string) get_post_meta( get_the_ID(), '_a2e_case_tag', true );
$a2e_icons = array( 'store', 'scan', 'pen', 'gauge' );
?>
<article class="case-card" data-reveal style="--d:<?php echo (int) ( $a2e_i % 3 ); ?>">
	<div class="case-card__top">
		<span class="case-card__icon"><?php a2e_the_icon( $a2e_icons[ $a2e_i % count( $a2e_icons ) ] ); ?></span>
		<span class="tag"><?php echo esc_html( $a2e_tag ? $a2e_tag : __( 'Case study', 'a2e' ) ); ?></span>
	</div>
	<h3 class="case-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
	<p class="case-card__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
	<?php if ( $a2e_stats ) : ?>
		<ul class="case-card__stats">
			<?php foreach ( array_slice( $a2e_stats, 0, 2 ) as $a2e_stat ) : ?><li><strong><?php echo esc_html( $a2e_stat[0] ); ?></strong><span><?php echo esc_html( $a2e_stat[1] ); ?></span></li><?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<a class="link-arrow" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read the case study', 'a2e' ); ?> <?php a2e_the_icon( 'arrow-right' ); ?></a>
</article>
