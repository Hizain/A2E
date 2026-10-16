<?php
/**
 * Uninstall cleanup for A2E Header Menu Link.
 *
 * @package A2E_Menu
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'a2e_menu_settings' );
