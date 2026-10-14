<?php
/**
 * Blog post card.
 *
 * @package A2E_Prime
 */

$a2e_cats = get_the_category();
?>
<article <?php post_class( 'post-card' ); ?> data-reveal>
	<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'a2e-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<span class="post-card__art"><span class="post-card__art-icon"><?php a2e_the_icon( 'book' ); ?></span></span>
		<?php endif; ?>
	</a>
	<div class="post-card__body">
		<div class="post-card__meta">
			<?php if ( $a2e_cats && 'uncategorized' !== $a2e_cats[0]->slug ) : ?><a class="tag" href="<?php echo esc_url( get_category_link( $a2e_cats[0] ) ); ?>"><?php echo esc_html( $a2e_cats[0]->name ); ?></a><?php endif; ?>
			<span><?php /* translators: %d: minutes */ printf( esc_html__( '%d min read', 'a2e' ), (int) a2e_reading_time() ); ?></span>
		</div>
		<h3 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
		<span class="post-card__date"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
	</div>
</article>
