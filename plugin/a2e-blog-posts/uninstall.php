<?php
/**
 * Uninstall: remove the plugin's settings. Your posts and theme settings are untouched.
 *
 * @package A2E_Blog_Posts
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'a2ebp_settings' );
