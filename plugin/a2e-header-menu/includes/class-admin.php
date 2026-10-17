<?php
/**
 * Admin: the settings screen (Appearance > Header Menu (A2E)) and the extra fields on
 * every item in Appearance > Menus.
 *
 * @package A2E_Header_Menu
 */

namespace A2E\HeaderMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin.
 */
class Admin {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_a2e_hm_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_a2e_hm_rebuild', array( __CLASS__, 'rebuild' ) );
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'item_fields' ), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_item' ), 10, 2 );
		add_action( 'admin_head-nav-menus.php', array( __CLASS__, 'menu_screen_css' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( A2E_HM_FILE ), array( __CLASS__, 'links' ) );
	}

	/**
	 * Menu entry.
	 */
	public static function menu() {
		add_theme_page( __( 'Header Menu (A2E)', 'a2e-header-menu' ), __( 'Header Menu (A2E)', 'a2e-header-menu' ), 'edit_theme_options', 'a2e-header-menu', array( __CLASS__, 'render' ) );
	}

	/**
	 * Plugins list link.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'themes.php?page=a2e-header-menu' ) ) . '">' . esc_html__( 'Settings', 'a2e-header-menu' ) . '</a>' );
		return $links;
	}

	/**
	 * Extra fields on each item in Appearance > Menus.
	 *
	 * @param int      $item_id Item id.
	 * @param \WP_Post $item    Item.
	 */
	public static function item_fields( $item_id, $item ) {
		$mega = Render::mega( $item_id );
		$icon = (string) get_post_meta( $item_id, '_a2e_icon', true );
		$desc = (string) get_post_meta( $item_id, '_a2e_desc', true );
		?>
		<div class="a2e-hm-fields description-wide">
			<p class="a2e-hm-fields__title"><?php esc_html_e( 'A2E header', 'a2e-header-menu' ); ?></p>
			<p class="description description-wide">
				<label for="a2e-mega-<?php echo (int) $item_id; ?>"><?php esc_html_e( 'Dropdown style (when this is a top-level item)', 'a2e-header-menu' ); ?><br>
					<select id="a2e-mega-<?php echo (int) $item_id; ?>" name="a2e_mega[<?php echo (int) $item_id; ?>]" class="widefat">
						<option value="auto" <?php selected( 'auto', $mega ); ?>><?php esc_html_e( 'Normal: a link, or a card dropdown of its sub-items', 'a2e-header-menu' ); ?></option>
						<option value="services" <?php selected( 'services', $mega ); ?>><?php esc_html_e( 'Services mega panel (designed)', 'a2e-header-menu' ); ?></option>
						<option value="tools" <?php selected( 'tools', $mega ); ?>><?php esc_html_e( 'Free Tools mega panel (all tools, with count)', 'a2e-header-menu' ); ?></option>
					</select>
				</label>
			</p>
			<p class="description description-thin">
				<label for="a2e-icon-<?php echo (int) $item_id; ?>"><?php esc_html_e( 'Icon (inside a dropdown)', 'a2e-header-menu' ); ?><br>
					<select id="a2e-icon-<?php echo (int) $item_id; ?>" name="a2e_icon[<?php echo (int) $item_id; ?>]" class="widefat">
						<?php foreach ( Render::icons() as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $icon ? $icon : 'arrow-right' ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>
			<p class="description description-thin">
				<label for="a2e-desc-<?php echo (int) $item_id; ?>"><?php esc_html_e( 'Card text (inside a dropdown)', 'a2e-header-menu' ); ?><br>
					<input type="text" id="a2e-desc-<?php echo (int) $item_id; ?>" name="a2e_desc[<?php echo (int) $item_id; ?>]" value="<?php echo esc_attr( $desc ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Recent client projects', 'a2e-header-menu' ); ?>">
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Save the extra fields (only when they were posted from the menu screen).
	 *
	 * @param int $menu_id Menu id.
	 * @param int $item_id Item id.
	 */
	public static function save_item( $menu_id, $item_id ) {
		if ( ! current_user_can( 'edit_theme_options' ) || ! isset( $_POST['a2e_mega'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- core verified the menu nonce.
			return;
		}
		$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $post['a2e_mega'][ $item_id ] ) ) {
			$v = sanitize_key( $post['a2e_mega'][ $item_id ] );
			update_post_meta( $item_id, '_a2e_mega', in_array( $v, array( 'services', 'tools' ), true ) ? $v : 'auto' );
		}
		if ( isset( $post['a2e_icon'][ $item_id ] ) ) {
			$v = sanitize_key( $post['a2e_icon'][ $item_id ] );
			update_post_meta( $item_id, '_a2e_icon', isset( Render::icons()[ $v ] ) ? $v : 'arrow-right' );
		}
		if ( isset( $post['a2e_desc'][ $item_id ] ) ) {
			update_post_meta( $item_id, '_a2e_desc', sanitize_text_field( $post['a2e_desc'][ $item_id ] ) );
		}
	}

	/**
	 * A little spacing for the fields on the menu screen.
	 */
	public static function menu_screen_css() {
		echo '<style>.a2e-hm-fields{clear:both;box-sizing:border-box;width:100%;margin:6px 0 10px;padding:8px 10px 2px;border:1px solid #dcdcde;border-radius:4px;background:#f6f7f7}.a2e-hm-fields__title{margin:0 0 4px;font-weight:600}.a2e-hm-fields .description-thin{box-sizing:border-box}</style>';
	}

	/**
	 * Settings screen.
	 */
	public static function render() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		$s      = settings();
		$menu   = header_menu();
		$notice = get_transient( 'a2e_hm_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'a2e_hm_notice_' . get_current_user_id() );
		}
		$edit = $menu ? admin_url( 'nav-menus.php?action=edit&menu=' . (int) $menu->term_id ) : admin_url( 'nav-menus.php' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Header Menu (A2E)', 'a2e-header-menu' ); ?></h1>
			<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
			<?php if ( ! theme_active() ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'This plugin works with the A2E Prime theme. Activate it to use these settings.', 'a2e-header-menu' ); ?></p></div>
			<?php endif; ?>

			<div class="card" style="max-width:880px;padding:6px 22px 16px;">
				<h2><?php esc_html_e( 'Your header, fully editable, with the designed mega menu', 'a2e-header-menu' ); ?></h2>
				<p>
					<?php if ( 'mega' === $s['mode'] && $menu ) : ?>
						<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#e6f6ed;color:#116b3a;font-weight:600;"><?php esc_html_e( 'On', 'a2e-header-menu' ); ?></span>
						<?php esc_html_e( 'Your header is drawn from the "Header Menu" in Appearance > Menus, in the designed mega-menu style, on desktop and on phones.', 'a2e-header-menu' ); ?>
					<?php else : ?>
						<span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#eef1ff;color:#2340d8;font-weight:600;"><?php esc_html_e( 'Off', 'a2e-header-menu' ); ?></span>
						<?php esc_html_e( 'Your header is the theme\'s built-in menu, which cannot be edited.', 'a2e-header-menu' ); ?>
					<?php endif; ?>
				</p>
				<p><a class="button button-primary button-hero" href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit the header in Appearance > Menus', 'a2e-header-menu' ); ?></a></p>
				<h3><?php esc_html_e( 'How it works', 'a2e-header-menu' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Add, rename, reorder or remove items in Appearance > Menus like any WordPress menu. Removing Pricing or Shop removes it from the header and the phone menu.', 'a2e-header-menu' ); ?></li>
					<li><?php esc_html_e( 'Each item has an "A2E header" box. Set "Dropdown style" to Services panel or Free Tools panel to show the designed mega panels. The Free Tools panel lists every tool automatically, with the count badge.', 'a2e-header-menu' ); ?></li>
					<li><?php esc_html_e( 'Drag items under a top-level item to make a card dropdown (like Results). Give each one an icon and short card text. Card text can use {rating}, {reviews} and {since} from your Site Settings.', 'a2e-header-menu' ); ?></li>
				</ol>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'a2e_hm_save' ); ?>
				<input type="hidden" name="action" value="a2e_hm_save">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Header menu', 'a2e-header-menu' ); ?></th>
						<td>
							<label style="display:block;margin-bottom:6px;"><input type="radio" name="mode" value="mega" <?php checked( 'mega', $s['mode'] ); ?>> <strong><?php esc_html_e( 'Editable mega menu (recommended)', 'a2e-header-menu' ); ?></strong> <?php esc_html_e( 'designed look, edited in Appearance > Menus', 'a2e-header-menu' ); ?></label>
							<label style="display:block;"><input type="radio" name="mode" value="theme" <?php checked( 'theme', $s['mode'] ); ?>> <?php esc_html_e( 'Theme\'s built-in menu (not editable)', 'a2e-header-menu' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Header button', 'a2e-header-menu' ); ?></th>
						<td>
							<label><input type="checkbox" name="cta_show" value="1" <?php checked( '1', $s['cta_show'] ); ?>> <?php esc_html_e( 'Show the button at the top right (and in the phone menu)', 'a2e-header-menu' ); ?></label>
							<p><input type="text" name="cta_label" class="regular-text" value="<?php echo esc_attr( $s['cta_label'] ); ?>" placeholder="<?php esc_attr_e( 'Free store audit', 'a2e-header-menu' ); ?>"> <span class="description"><?php esc_html_e( 'Label (empty = theme default)', 'a2e-header-menu' ); ?></span></p>
							<p><input type="url" name="cta_url" class="regular-text" value="<?php echo esc_attr( $s['cta_url'] ); ?>" placeholder="<?php echo esc_attr( function_exists( 'a2e_url' ) ? a2e_url( 'quote-request' ) : '' ); ?>"> <span class="description"><?php esc_html_e( 'Link (empty = theme default)', 'a2e-header-menu' ); ?></span></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Services panel box', 'a2e-header-menu' ); ?></th>
						<td>
							<label><input type="checkbox" name="aside_show" value="1" <?php checked( '1', $s['aside_show'] ); ?>> <?php esc_html_e( 'Show the dark "Done-for-you" box in the Services panel', 'a2e-header-menu' ); ?></label>
							<p><input type="text" name="aside_label" class="regular-text" value="<?php echo esc_attr( $s['aside_label'] ); ?>" placeholder="<?php esc_attr_e( 'See plans', 'a2e-header-menu' ); ?>"> <span class="description"><?php esc_html_e( 'Button label', 'a2e-header-menu' ); ?></span></p>
							<p><input type="url" name="aside_url" class="regular-text" value="<?php echo esc_attr( $s['aside_url'] ); ?>" placeholder="<?php echo esc_attr( function_exists( 'a2e_url' ) ? a2e_url( 'pricing' ) : '' ); ?>"> <span class="description"><?php esc_html_e( 'Button link, for example your Shop instead of Pricing', 'a2e-header-menu' ); ?></span></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Phone menu', 'a2e-header-menu' ); ?></th>
						<td><label><input type="checkbox" name="client_area" value="1" <?php checked( '1', $s['client_area'] ); ?>> <?php esc_html_e( 'Add a "Client area" link at the end of the phone menu', 'a2e-header-menu' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save settings', 'a2e-header-menu' ) ); ?>
			</form>

			<div class="card" style="max-width:880px;padding:6px 22px 16px;">
				<h2><?php esc_html_e( 'Start over', 'a2e-header-menu' ); ?></h2>
				<p><?php esc_html_e( 'Rebuild "Header Menu" as a copy of the designed header: Services panel, Free Tools panel, Pricing, Shop, Results (Portfolio, Case studies, Client reviews, About), Blog and Contact. Your current menu is kept, renamed with "(backup)", so nothing is lost.', 'a2e-header-menu' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Rebuild the header menu? Your current one is kept as a backup.', 'a2e-header-menu' ) ); ?>');">
					<?php wp_nonce_field( 'a2e_hm_rebuild' ); ?>
					<input type="hidden" name="action" value="a2e_hm_rebuild">
					<?php submit_button( __( 'Rebuild the header menu', 'a2e-header-menu' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	public static function save() {
		check_admin_referer( 'a2e_hm_save' );
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-header-menu' ) );
		}
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_option(
			OPTION,
			array(
				'mode'        => ( isset( $in['mode'] ) && 'theme' === $in['mode'] ) ? 'theme' : 'mega',
				'cta_show'    => empty( $in['cta_show'] ) ? '0' : '1',
				'cta_label'   => isset( $in['cta_label'] ) ? sanitize_text_field( $in['cta_label'] ) : '',
				'cta_url'     => isset( $in['cta_url'] ) ? esc_url_raw( $in['cta_url'] ) : '',
				'aside_show'  => empty( $in['aside_show'] ) ? '0' : '1',
				'aside_label' => isset( $in['aside_label'] ) ? sanitize_text_field( $in['aside_label'] ) : '',
				'aside_url'   => isset( $in['aside_url'] ) ? esc_url_raw( $in['aside_url'] ) : '',
				'client_area' => empty( $in['client_area'] ) ? '0' : '1',
			),
			false
		);
		if ( ! header_menu() ) {
			seed_menu();
		}
		set_transient( 'a2e_hm_notice_' . get_current_user_id(), __( 'Settings saved.', 'a2e-header-menu' ), 60 );
		wp_safe_redirect( admin_url( 'themes.php?page=a2e-header-menu' ) );
		exit;
	}

	/**
	 * Rebuild the starter menu (keeping the current one as a backup).
	 */
	public static function rebuild() {
		check_admin_referer( 'a2e_hm_rebuild' );
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-header-menu' ) );
		}
		$old = header_menu();
		if ( $old ) {
			wp_update_nav_menu_object( (int) $old->term_id, array( 'menu-name' => unique_menu_name( MENU_NAME . ' (backup)' ) ) );
		}
		seed_menu();
		set_transient( 'a2e_hm_notice_' . get_current_user_id(), __( 'The header menu was rebuilt. Your previous menu is saved as a backup in Appearance > Menus.', 'a2e-header-menu' ), 60 );
		wp_safe_redirect( admin_url( 'themes.php?page=a2e-header-menu' ) );
		exit;
	}
}
