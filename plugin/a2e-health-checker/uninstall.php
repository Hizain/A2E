<?php
/**
 * Uninstall cleanup for the A2E eBay Account Health Checker.
 *
 * Removes the plugin's own options and the ready page it created. Lead posts are left
 * in place so you do not lose contacts; delete them from the admin if you want them gone.
 *
 * @package A2E_Health
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'a2ehc_settings' );
delete_option( 'a2ehc_db_version' );

$page_id = (int) get_option( 'a2ehc_page_id' );
if ( $page_id && get_post( $page_id ) ) {
	// Only remove the auto-created page if it still holds just our shortcode.
	$content = (string) get_post_field( 'post_content', $page_id );
	if ( false !== strpos( $content, '[a2e_health_checker]' ) && strlen( wp_strip_all_tags( $content ) ) < 120 ) {
		wp_delete_post( $page_id, true );
	}
}
delete_option( 'a2ehc_page_id' );
