<?php
/**
 * Uninstall cleanup for A2E Editable Header Menu.
 *
 * The saved "Header Menu" is intentionally left in place so you do not lose your work;
 * delete it from Appearance > Menus if you want it gone. We do not force the theme back
 * to the mega menu here, so your header keeps whatever mode you last chose.
 *
 * @package A2E_Header_Menu
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'a2e_hm_seeded' );
