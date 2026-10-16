<?php
/**
 * Plugin Name:       A2E Header Menu Link
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       Adds a highlighted link to your site header (desktop and mobile) without editing your theme, so you can feature a page such as the eBay Account Health Checker even when the header menu is not editable from Appearance > Menus. Fully controlled from Settings > Header Link: label, target page, placement and highlight.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-menu
 *
 * @package A2E_Menu
 */

namespace A2E\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '1.0.0';
const OPTION  = 'a2e_menu_settings';

define( 'A2E_MENU_FILE', __FILE__ );
define( 'A2E_MENU_URL', plugin_dir_url( __FILE__ ) );

/**
 * Settings with defaults. Defaults aim straight at the health checker page and the
 * A2E Prime header, but every value can be changed, so it works with other themes too.
 *
 * @return array
 */
function settings() {
	$defaults = array(
		'enabled'      => '1',
		'label'        => __( 'Health Checker', 'a2e-menu' ),
		'link_type'    => 'page',
		'page_id'      => (int) get_option( 'a2ehc_page_id' ), // The health checker plugin's page, if present.
		'url'          => '',
		'placement'    => 'main',   // main | tools | both.
		'highlight'    => '1',
		'newtab'       => '0',
		'position'     => 'end',    // end | start.
		'sel_desktop'  => '.nav__list',
		'sel_mobile'   => '.mnav__links',
		'sel_tools'    => '#mega-tools .mega__cols .mega__col:last-child ul',
		'sel_tools_m'  => '.mnav__group:nth-of-type(2) > ul',
	);
	$saved = get_option( OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * The resolved destination URL.
 *
 * @return string
 */
function target_url() {
	$s = settings();
	if ( 'url' === $s['link_type'] && ! empty( $s['url'] ) ) {
		return $s['url'];
	}
	if ( $s['page_id'] && get_post( (int) $s['page_id'] ) ) {
		return (string) get_permalink( (int) $s['page_id'] );
	}
	return '';
}

/**
 * Front-end: register and enqueue the tiny injector with its config.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$s   = settings();
		$url = target_url();
		if ( '1' !== $s['enabled'] || '' === $url || '' === trim( (string) $s['label'] ) ) {
			return;
		}
		wp_enqueue_style( 'a2e-menu', A2E_MENU_URL . 'assets/menu.css', array(), VERSION );
		wp_enqueue_script( 'a2e-menu', A2E_MENU_URL . 'assets/menu.js', array(), VERSION, true );
		wp_localize_script(
			'a2e-menu',
			'a2eMenuData',
			array(
				'label'      => wp_strip_all_tags( (string) $s['label'] ),
				'url'        => $url,
				'highlight'  => '1' === $s['highlight'] ? 1 : 0,
				'newtab'     => '1' === $s['newtab'] ? 1 : 0,
				'placement'  => in_array( $s['placement'], array( 'main', 'tools', 'both' ), true ) ? $s['placement'] : 'main',
				'position'   => 'start' === $s['position'] ? 'start' : 'end',
				'selMain'    => (string) $s['sel_desktop'],
				'selMobile'  => (string) $s['sel_mobile'],
				'selTools'   => (string) $s['sel_tools'],
				'selToolsM'  => (string) $s['sel_tools_m'],
			)
		);
	}
);

/**
 * Admin menu under Settings.
 */
add_action(
	'admin_menu',
	function () {
		add_options_page( __( 'Header Link', 'a2e-menu' ), __( 'Header Link', 'a2e-menu' ), 'manage_options', 'a2e-menu', __NAMESPACE__ . '\\render_settings' );
	}
);

/**
 * Settings link on the plugins list.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=a2e-menu' ) ) . '">' . esc_html__( 'Settings', 'a2e-menu' ) . '</a>' );
		return $links;
	}
);

/**
 * Save handler.
 */
add_action(
	'admin_post_a2e_menu_save',
	function () {
		check_admin_referer( 'a2e_menu_save' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-menu' ) );
		}
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$clean = array(
			'enabled'     => empty( $in['enabled'] ) ? '0' : '1',
			'label'       => isset( $in['label'] ) ? sanitize_text_field( $in['label'] ) : '',
			'link_type'   => ( isset( $in['link_type'] ) && 'url' === $in['link_type'] ) ? 'url' : 'page',
			'page_id'     => isset( $in['page_id'] ) ? (int) $in['page_id'] : 0,
			'url'         => isset( $in['url'] ) ? esc_url_raw( $in['url'] ) : '',
			'placement'   => ( isset( $in['placement'] ) && in_array( $in['placement'], array( 'main', 'tools', 'both' ), true ) ) ? $in['placement'] : 'main',
			'highlight'   => empty( $in['highlight'] ) ? '0' : '1',
			'newtab'      => empty( $in['newtab'] ) ? '0' : '1',
			'position'    => ( isset( $in['position'] ) && 'start' === $in['position'] ) ? 'start' : 'end',
			'sel_desktop' => isset( $in['sel_desktop'] ) ? sanitize_text_field( $in['sel_desktop'] ) : '.nav__list',
			'sel_mobile'  => isset( $in['sel_mobile'] ) ? sanitize_text_field( $in['sel_mobile'] ) : '.mnav__links',
			'sel_tools'   => isset( $in['sel_tools'] ) ? sanitize_text_field( $in['sel_tools'] ) : '',
			'sel_tools_m' => isset( $in['sel_tools_m'] ) ? sanitize_text_field( $in['sel_tools_m'] ) : '',
		);
		update_option( OPTION, array_merge( settings(), $clean ), false );
		set_transient( 'a2e_menu_notice_' . get_current_user_id(), __( 'Header link saved.', 'a2e-menu' ), 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=a2e-menu' ) );
		exit;
	}
);

/**
 * Render the settings screen.
 */
function render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s      = settings();
	$url    = target_url();
	$notice = get_transient( 'a2e_menu_notice_' . get_current_user_id() );
	if ( $notice ) {
		delete_transient( 'a2e_menu_notice_' . get_current_user_id() );
	}
	$pages = get_pages( array( 'sort_column' => 'post_title', 'number' => 300 ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Header Link', 'a2e-menu' ); ?></h1>
		<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

		<div class="card" style="max-width:860px;padding:4px 20px 14px;">
			<h2><?php esc_html_e( 'What this does', 'a2e-menu' ); ?></h2>
			<p><?php esc_html_e( 'Your theme builds its header menu in code, so it cannot be edited from Appearance > Menus. This plugin adds one highlighted link to the header (and the mobile menu) without changing your theme. By default it points at your eBay Account Health Checker page.', 'a2e-menu' ); ?></p>
			<?php if ( $url ) : ?>
				<p><strong><?php esc_html_e( 'It currently links to:', 'a2e-menu' ); ?></strong> <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $url ); ?></a></p>
			<?php else : ?>
				<p style="color:#b32d2e;"><strong><?php esc_html_e( 'No destination set yet.', 'a2e-menu' ); ?></strong> <?php esc_html_e( 'Pick a page or enter a URL below.', 'a2e-menu' ); ?></p>
			<?php endif; ?>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'a2e_menu_save' ); ?>
			<input type="hidden" name="action" value="a2e_menu_save">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show the link', 'a2e-menu' ); ?></th>
					<td><label><input type="checkbox" name="enabled" value="1" <?php checked( '1', $s['enabled'] ); ?>> <?php esc_html_e( 'Add the link to the site header', 'a2e-menu' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label for="a2e-menu-label"><?php esc_html_e( 'Menu label', 'a2e-menu' ); ?></label></th>
					<td><input type="text" id="a2e-menu-label" name="label" class="regular-text" value="<?php echo esc_attr( $s['label'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Links to', 'a2e-menu' ); ?></th>
					<td>
						<p><label><input type="radio" name="link_type" value="page" <?php checked( 'page', $s['link_type'] ); ?>> <?php esc_html_e( 'A page on this site:', 'a2e-menu' ); ?></label>
						<select name="page_id">
							<option value="0"><?php esc_html_e( '— Select a page —', 'a2e-menu' ); ?></option>
							<?php foreach ( $pages as $p ) : ?>
								<option value="<?php echo (int) $p->ID; ?>" <?php selected( (int) $s['page_id'], (int) $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
							<?php endforeach; ?>
						</select></p>
						<p><label><input type="radio" name="link_type" value="url" <?php checked( 'url', $s['link_type'] ); ?>> <?php esc_html_e( 'Or a custom URL:', 'a2e-menu' ); ?></label>
						<input type="url" name="url" class="regular-text" value="<?php echo esc_attr( $s['url'] ); ?>" placeholder="https://a2edropshipping.com/ebay-account-health-checker/"></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Where in the header', 'a2e-menu' ); ?></th>
					<td>
						<select name="placement">
							<option value="main" <?php selected( 'main', $s['placement'] ); ?>><?php esc_html_e( 'Main header (top level, recommended)', 'a2e-menu' ); ?></option>
							<option value="tools" <?php selected( 'tools', $s['placement'] ); ?>><?php esc_html_e( 'Inside the "Free Tools" dropdown', 'a2e-menu' ); ?></option>
							<option value="both" <?php selected( 'both', $s['placement'] ); ?>><?php esc_html_e( 'Both places', 'a2e-menu' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'On the main header the link shows as a highlighted button. Inside the Free Tools dropdown it shows as a highlighted list item.', 'a2e-menu' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Highlight', 'a2e-menu' ); ?></th>
					<td><label><input type="checkbox" name="highlight" value="1" <?php checked( '1', $s['highlight'] ); ?>> <?php esc_html_e( 'Make it stand out (brand colour pill with a subtle glow)', 'a2e-menu' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Position', 'a2e-menu' ); ?></th>
					<td>
						<label style="margin-right:16px;"><input type="radio" name="position" value="end" <?php checked( 'end', $s['position'] ); ?>> <?php esc_html_e( 'After the other items', 'a2e-menu' ); ?></label>
						<label><input type="radio" name="position" value="start" <?php checked( 'start', $s['position'] ); ?>> <?php esc_html_e( 'Before the other items', 'a2e-menu' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Open in a new tab', 'a2e-menu' ); ?></th>
					<td><label><input type="checkbox" name="newtab" value="1" <?php checked( '1', $s['newtab'] ); ?>> <?php esc_html_e( 'Open the link in a new browser tab', 'a2e-menu' ); ?></label></td>
				</tr>
			</table>

			<h2 class="title"><?php esc_html_e( 'Advanced', 'a2e-menu' ); ?></h2>
			<p class="description" style="max-width:720px;"><?php esc_html_e( 'These CSS selectors tell the plugin where your header menus are. The defaults match the A2E Prime theme. Only change them if you use a different theme and the link does not appear.', 'a2e-menu' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Desktop menu selector', 'a2e-menu' ); ?></th><td><input type="text" name="sel_desktop" class="regular-text code" value="<?php echo esc_attr( $s['sel_desktop'] ); ?>"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Mobile menu selector', 'a2e-menu' ); ?></th><td><input type="text" name="sel_mobile" class="regular-text code" value="<?php echo esc_attr( $s['sel_mobile'] ); ?>"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Free Tools list (desktop)', 'a2e-menu' ); ?></th><td><input type="text" name="sel_tools" class="regular-text code" value="<?php echo esc_attr( $s['sel_tools'] ); ?>"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Free Tools list (mobile)', 'a2e-menu' ); ?></th><td><input type="text" name="sel_tools_m" class="regular-text code" value="<?php echo esc_attr( $s['sel_tools_m'] ); ?>"></td></tr>
			</table>

			<?php submit_button( __( 'Save header link', 'a2e-menu' ) ); ?>
		</form>
	</div>
	<?php
}
