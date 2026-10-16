<?php
/**
 * Plugin Name:       A2E Editable Header Menu
 * Plugin URI:        https://a2edropshipping.com/
 * Description:       Make your site header editable from Appearance > Menus. It builds a ready "Header Menu" that mirrors your current navigation and adds your eBay Account Health Checker, then lets you switch your header to it with one click (and switch back to the built-in mega menu just as easily). After that you edit everything the normal WordPress way in Appearance > Menus.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            A2E Dropshipping
 * License:           GPL-2.0-or-later
 * Text Domain:       a2e-header-menu
 *
 * @package A2E_Header_Menu
 */

namespace A2E\HeaderMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MENU_NAME   = 'Header Menu';
const THEME_OPT   = 'a2e_prime';
const LOCATION    = 'primary';
const SEED_FLAG   = 'a2e_hm_seeded';

/**
 * Is the A2E Prime theme (or a child of it) active?
 *
 * @return bool
 */
function theme_active() {
	return function_exists( 'a2e_opt' ) || in_array( 'a2e-prime', array( get_template(), get_stylesheet() ), true );
}

/**
 * Activation: seed a starter menu and assign it to the header location, without
 * changing which menu the header currently shows. The user switches over from the
 * settings screen when they are ready.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( theme_active() ) {
			seed_menu( false );
		}
	}
);

/**
 * Create (or find) the Header Menu and fill it with a starter set of items that
 * mirrors the designed header, plus the Account Health Checker.
 *
 * @param bool $force Rebuild the items even if the menu already has some.
 * @return int|\WP_Error Menu id.
 */
function seed_menu( $force = false ) {
	$menu = wp_get_nav_menu_object( MENU_NAME );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( MENU_NAME );
		if ( is_wp_error( $menu_id ) ) {
			return $menu_id;
		}
	} else {
		$menu_id = (int) $menu->term_id;
	}

	$existing = wp_get_nav_menu_items( $menu_id );
	if ( ! empty( $existing ) && ! $force ) {
		// Already has items; leave the user's edits alone. Just make sure it is assigned.
		assign_to_location( $menu_id );
		return $menu_id;
	}

	// Clear items on a forced rebuild.
	if ( $force && ! empty( $existing ) ) {
		foreach ( $existing as $item ) {
			wp_delete_post( $item->ID, true );
		}
	}

	$add = function ( $title, $url, $parent = 0 ) use ( $menu_id ) {
		if ( ! $url ) {
			return 0;
		}
		$id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $url,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-parent-id' => (int) $parent,
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	};

	$u = function ( $path ) {
		return function_exists( 'a2e_url' ) ? a2e_url( $path ) : home_url( '/' . ltrim( $path, '/' ) );
	};

	// Services, with the service guides nested under it (links to the services hub page).
	$services_id = $add( __( 'Services', 'a2e-header-menu' ), $u( 'amazon-to-ebay-dropshipping-service' ) );
	if ( function_exists( 'a2e_services' ) && function_exists( 'a2e_service_url' ) ) {
		foreach ( a2e_services() as $slug => $svc ) {
			$label = isset( $svc['nav'] ) ? $svc['nav'] : ( isset( $svc['name'] ) ? $svc['name'] : ucwords( str_replace( '-', ' ', $slug ) ) );
			$add( $label, a2e_service_url( $slug ), $services_id );
		}
	}

	// Free Tools, with every tool nested under it.
	$tools_id = $add( __( 'Free Tools', 'a2e-header-menu' ), $u( 'free-tools' ) );
	if ( function_exists( 'a2e_tools' ) && function_exists( 'a2e_tool_url' ) ) {
		foreach ( a2e_tools() as $slug => $tool ) {
			$label = isset( $tool['name'] ) ? $tool['name'] : ucwords( str_replace( '-', ' ', $slug ) );
			$add( $label, a2e_tool_url( $slug ), $tools_id );
		}
	}

	// Top-level items.
	$add( __( 'Pricing', 'a2e-header-menu' ), $u( 'pricing' ) );
	if ( function_exists( 'a2e_shop_url' ) && function_exists( 'a2e_shop_page_id' ) && a2e_shop_page_id() ) {
		$add( __( 'Shop', 'a2e-header-menu' ), a2e_shop_url() );
	}
	// Results, with its real pages nested under it.
	$results_id = $add( __( 'Results', 'a2e-header-menu' ), $u( 'portfolio' ) );
	$add( __( 'Portfolio', 'a2e-header-menu' ), $u( 'portfolio' ), $results_id );
	$cs = get_post_type_archive_link( 'a2e_case_study' );
	if ( $cs ) {
		$add( __( 'Case studies', 'a2e-header-menu' ), $cs, $results_id );
	}
	$rv = get_post_type_archive_link( 'a2e_testimonial' );
	if ( $rv ) {
		$add( __( 'Reviews', 'a2e-header-menu' ), $rv, $results_id );
	}
	$add( __( 'Blog', 'a2e-header-menu' ), function_exists( 'a2e_blog_url' ) ? a2e_blog_url() : $u( 'blog' ) );
	$add( __( 'Contact', 'a2e-header-menu' ), $u( 'contact' ) );

	// The new tool.
	$hc_url = health_checker_url();
	if ( $hc_url ) {
		$add( __( 'eBay Account Health Checker', 'a2e-header-menu' ), $hc_url );
	}

	assign_to_location( $menu_id );
	update_option( SEED_FLAG, '1', false );
	return $menu_id;
}

/**
 * The Account Health Checker page URL, from the standalone plugin's page or by slug.
 *
 * @return string
 */
function health_checker_url() {
	$id = (int) get_option( 'a2ehc_page_id' );
	if ( $id && get_post( $id ) ) {
		return (string) get_permalink( $id );
	}
	$page = get_page_by_path( 'ebay-account-health-checker' );
	if ( $page ) {
		return (string) get_permalink( $page );
	}
	return '';
}

/**
 * Assign a menu to the header location (without disturbing other locations).
 *
 * @param int $menu_id Menu id.
 */
function assign_to_location( $menu_id ) {
	$locations             = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! is_array( $locations ) ) {
		$locations = array();
	}
	$locations[ LOCATION ] = (int) $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Turn the theme's "use WordPress menus" option on or off.
 *
 * @param bool $on On.
 */
function set_wp_menu_mode( $on ) {
	$o = get_option( THEME_OPT, array() );
	if ( ! is_array( $o ) ) {
		$o = array();
	}
	$o['use_wp_menus'] = $on ? '1' : '0';
	update_option( THEME_OPT, $o );
}

/**
 * Whether the header is currently using WordPress menus.
 *
 * @return bool
 */
function wp_menu_mode_on() {
	$o = get_option( THEME_OPT, array() );
	return is_array( $o ) && isset( $o['use_wp_menus'] ) && '1' === $o['use_wp_menus'];
}

/**
 * Admin menu under Appearance.
 */
add_action(
	'admin_menu',
	function () {
		add_theme_page( __( 'Header Menu (A2E)', 'a2e-header-menu' ), __( 'Header Menu (A2E)', 'a2e-header-menu' ), 'manage_options', 'a2e-header-menu', __NAMESPACE__ . '\\render' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'themes.php?page=a2e-header-menu' ) ) . '">' . esc_html__( 'Set up', 'a2e-header-menu' ) . '</a>' );
		return $links;
	}
);

/**
 * Handle the three actions (switch on, switch off, rebuild).
 */
add_action(
	'admin_post_a2e_hm_action',
	function () {
		check_admin_referer( 'a2e_hm_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-header-menu' ) );
		}
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$msg = '';
		switch ( $do ) {
			case 'enable':
				$menu_id = seed_menu( false ); // Ensure a menu exists and is assigned.
				if ( ! is_wp_error( $menu_id ) ) {
					set_wp_menu_mode( true );
					$msg = __( 'Your header now uses the editable menu. Edit it any time in Appearance > Menus.', 'a2e-header-menu' );
				} else {
					$msg = __( 'Could not create the menu. Please try again.', 'a2e-header-menu' );
				}
				break;
			case 'disable':
				set_wp_menu_mode( false );
				$msg = __( 'Switched back to the built-in mega menu. Your editable menu is still saved in Appearance > Menus.', 'a2e-header-menu' );
				break;
			case 'reseed':
				seed_menu( true );
				$msg = __( 'The starter menu was rebuilt from your current pages.', 'a2e-header-menu' );
				break;
		}
		set_transient( 'a2e_hm_notice_' . get_current_user_id(), $msg, 60 );
		wp_safe_redirect( admin_url( 'themes.php?page=a2e-header-menu' ) );
		exit;
	}
);

/**
 * Settings screen.
 */
function render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$on     = wp_menu_mode_on();
	$menu   = wp_get_nav_menu_object( MENU_NAME );
	$notice = get_transient( 'a2e_hm_notice_' . get_current_user_id() );
	if ( $notice ) {
		delete_transient( 'a2e_hm_notice_' . get_current_user_id() );
	}
	$btn = function ( $do, $label, $primary = true ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:0 8px 8px 0;">
			<?php wp_nonce_field( 'a2e_hm_action' ); ?>
			<input type="hidden" name="action" value="a2e_hm_action">
			<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
			<button type="submit" class="button <?php echo $primary ? 'button-primary button-hero' : ''; ?>"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Editable Header Menu', 'a2e-header-menu' ); ?></h1>
		<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
		<?php if ( ! theme_active() ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'This plugin is made for the A2E Prime theme. Activate that theme to use it.', 'a2e-header-menu' ); ?></p></div>
		<?php endif; ?>

		<div class="card" style="max-width:860px;padding:6px 22px 18px;">
			<h2><?php esc_html_e( 'Status', 'a2e-header-menu' ); ?></h2>
			<p style="font-size:15px;">
				<?php if ( $on ) : ?>
					<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#e6f6ed;color:#116b3a;font-weight:600;"><?php esc_html_e( 'Your header is editable', 'a2e-header-menu' ); ?></span>
					<?php esc_html_e( 'It is showing your WordPress menu. Edit it in Appearance > Menus.', 'a2e-header-menu' ); ?>
				<?php else : ?>
					<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#eef1ff;color:#2340d8;font-weight:600;"><?php esc_html_e( 'Using the built-in mega menu', 'a2e-header-menu' ); ?></span>
					<?php esc_html_e( 'Your header is the designed mega menu and is not yet editable. Switch it below.', 'a2e-header-menu' ); ?>
				<?php endif; ?>
			</p>
			<p>
				<?php
				if ( $menu ) {
					printf(
						/* translators: %s: edit menu link */
						esc_html__( 'A ready menu called "Header Menu" is saved for you. %s', 'a2e-header-menu' ),
						'<a href="' . esc_url( admin_url( 'nav-menus.php?action=edit&menu=' . (int) $menu->term_id ) ) . '">' . esc_html__( 'Open it in Appearance > Menus →', 'a2e-header-menu' ) . '</a>'
					);
				}
				?>
			</p>
		</div>

		<div class="card" style="max-width:860px;padding:6px 22px 18px;margin-top:16px;">
			<h2><?php esc_html_e( 'Switch your header', 'a2e-header-menu' ); ?></h2>
			<?php if ( ! $on ) : ?>
				<p><?php esc_html_e( 'This replaces the designed mega menu (the Services and Free Tools dropdowns with icons and columns) with a standard, fully editable WordPress menu. Your Services and Free Tools still appear as normal dropdowns. You can switch back any time with one click, and nothing else on your site changes.', 'a2e-header-menu' ); ?></p>
				<?php $btn( 'enable', __( 'Use the editable menu in my header', 'a2e-header-menu' ), true ); ?>
			<?php else : ?>
				<p><?php esc_html_e( 'Your header is editable now. To edit items, add pages, reorder or nest them, open Appearance > Menus and edit "Header Menu". If you prefer the designed mega menu back, use the button below.', 'a2e-header-menu' ); ?></p>
				<p>
					<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'nav-menus.php' . ( $menu ? '?action=edit&menu=' . (int) $menu->term_id : '' ) ) ); ?>"><?php esc_html_e( 'Edit the menu in Appearance > Menus', 'a2e-header-menu' ); ?></a>
				</p>
				<?php $btn( 'disable', __( 'Switch back to the built-in mega menu', 'a2e-header-menu' ), false ); ?>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:860px;padding:6px 22px 18px;margin-top:16px;">
			<h2><?php esc_html_e( 'Starter menu', 'a2e-header-menu' ); ?></h2>
			<p><?php esc_html_e( 'The starter menu mirrors your current header (Services with its guides, Free Tools with every tool, Pricing, Shop, Results, Blog, Contact) and adds the eBay Account Health Checker. Rebuilding replaces the saved "Header Menu" with a fresh copy from your current pages, so only use it if you want to start over (it discards edits you made to that menu).', 'a2e-header-menu' ); ?></p>
			<?php $btn( 'reseed', __( 'Rebuild the starter menu', 'a2e-header-menu' ), false ); ?>
		</div>
	</div>
	<?php
}
