<?php
/**
 * Comments.
 *
 * @package A2E_Prime
 */

if ( post_password_required() ) {
	return;
}
?>
<section id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="h3">
			<?php
			/* translators: %d: comment count */
			printf( esc_html( _n( '%d comment', '%d comments', get_comments_number(), 'a2e' ) ), (int) get_comments_number() );
			?>
		</h2>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?></ol>
		<?php the_comments_pagination(); ?>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
