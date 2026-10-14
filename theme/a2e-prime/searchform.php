<?php
/**
 * Search form.
 *
 * @package A2E_Prime
 */

$a2e_id = 'search-' . wp_unique_id();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<?php a2e_the_icon( 'search' ); ?>
	<label class="screen-reader-text" for="<?php echo esc_attr( $a2e_id ); ?>"><?php esc_html_e( 'Search for:', 'a2e' ); ?></label>
	<input type="search" id="<?php echo esc_attr( $a2e_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search guides…', 'a2e' ); ?>">
	<button type="submit"><?php esc_html_e( 'Search', 'a2e' ); ?></button>
</form>
