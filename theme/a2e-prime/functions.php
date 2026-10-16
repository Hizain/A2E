<?php
/**
 * A2E Prime theme bootstrap.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'A2E_VERSION', '6.3.0' );
define( 'A2E_DIR', get_template_directory() );
define( 'A2E_URI', get_template_directory_uri() );

$a2e_includes = array(
	'helpers',
	'icons',
	'brands',
	'options',
	'setup',
	'assets',
	'content',
	'post-types',
	'meta-boxes',
	'components',
	'shortcodes',
	'forms',
	'tools-data',
	'tools',
	'shop',
	'seo',
	'redirects',
	'seeder',
	'admin',
);

foreach ( $a2e_includes as $a2e_file ) {
	require_once A2E_DIR . '/inc/' . $a2e_file . '.php';
}
unset( $a2e_includes, $a2e_file );
