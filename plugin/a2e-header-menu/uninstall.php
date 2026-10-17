<?php
/**
 * Uninstall cleanup for A2E Header Menu.
 *
 * Your menus are left in place (delete them in Appearance > Menus if you want). The theme
 * falls back to its built-in header automatically once the plugin is gone.
 *
 * @package A2E_Header_Menu
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'a2e_hm_settings' );
delete_option( 'a2e_hm_version' );
delete_option( 'a2e_hm_seeded' );
