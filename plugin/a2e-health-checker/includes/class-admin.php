<?php
/**
 * Settings screen: where to place the tool, the WhatsApp and hire call to action, and the
 * report capture.
 *
 * @package A2E_Health
 */

namespace A2E\Health;

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
		add_action( 'admin_post_a2ehc_save', array( __CLASS__, 'save' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( A2EHC_FILE ), array( __CLASS__, 'links' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_menu_page( __( 'Health Checker', 'a2e-health' ), __( 'Health Checker', 'a2e-health' ), 'manage_options', 'a2ehc-settings', array( __CLASS__, 'render' ), 'dashicons-heart', 58 );
	}

	/**
	 * Settings link on the plugins list.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=a2ehc-settings' ) ) . '">' . esc_html__( 'Settings', 'a2e-health' ) . '</a>' );
		return $links;
	}

	/**
	 * Render settings.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s       = settings();
		$page_id = (int) get_option( 'a2ehc_page_id' );
		$notice  = get_transient( 'a2ehc_notice_' . get_current_user_id() );
		if ( $notice ) {
			delete_transient( 'a2ehc_notice_' . get_current_user_id() );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'eBay Account Health Checker', 'a2e-health' ); ?></h1>
			<?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>

			<div class="card" style="max-width:820px;padding:4px 20px 16px;">
				<h2><?php esc_html_e( 'Where to place it', 'a2e-health' ); ?></h2>
				<p><?php esc_html_e( 'A page has been created for you. Add it to your menu, or feature it anywhere.', 'a2e-health' ); ?></p>
				<ul style="list-style:disc;margin-left:20px;">
					<?php if ( $page_id && get_post( $page_id ) ) : ?>
						<li><?php esc_html_e( 'Ready page:', 'a2e-health' ); ?> <a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $page_id ) ); ?></a> (<a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>"><?php esc_html_e( 'edit', 'a2e-health' ); ?></a>)</li>
					<?php endif; ?>
					<li><?php esc_html_e( 'Shortcode for any page or post:', 'a2e-health' ); ?> <code>[a2e_health_checker]</code></li>
					<li><?php esc_html_e( 'Block editor: add the "eBay Account Health Checker" block.', 'a2e-health' ); ?></li>
				</ul>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'a2ehc_save' ); ?>
				<input type="hidden" name="action" value="a2ehc_save">
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php esc_html_e( 'WhatsApp number', 'a2e-health' ); ?></th><td><input type="text" name="whatsapp" class="regular-text" value="<?php echo esc_attr( $s['whatsapp'] ); ?>" placeholder="+1 206 776 4962"><p class="description"><?php esc_html_e( 'Used for the "Hire us on WhatsApp" button.', 'a2e-health' ); ?></p></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Business name', 'a2e-health' ); ?></th><td><input type="text" name="business_name" class="regular-text" value="<?php echo esc_attr( $s['business_name'] ); ?>"></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Send report requests to', 'a2e-health' ); ?></th><td><input type="email" name="lead_email" class="regular-text" value="<?php echo esc_attr( $s['lead_email'] ); ?>"></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Hire call to action title', 'a2e-health' ); ?></th><td><input type="text" name="cta_title" class="large-text" value="<?php echo esc_attr( $s['cta_title'] ); ?>"></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Hire call to action text', 'a2e-health' ); ?></th><td><textarea name="cta_text" class="large-text" rows="3"><?php echo esc_textarea( $s['cta_text'] ); ?></textarea></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Plans page link (optional)', 'a2e-health' ); ?></th><td><input type="url" name="cta_url" class="regular-text" value="<?php echo esc_attr( $s['cta_url'] ); ?>" placeholder="https://a2edropshipping.com/pricing/"></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Report capture', 'a2e-health' ); ?></th><td><label><input type="checkbox" name="capture" value="1" <?php checked( '1', $s['capture'] ); ?>> <?php esc_html_e( 'Show the "email me my report" form (recommended)', 'a2e-health' ); ?></label></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Highlight', 'a2e-health' ); ?></th><td><label><input type="checkbox" name="highlight" value="1" <?php checked( '1', $s['highlight'] ); ?>> <?php esc_html_e( 'Wrap the tool in a highlighted, standout container wherever it appears', 'a2e-health' ); ?></label></td></tr>
				</table>
				<?php submit_button( __( 'Save settings', 'a2e-health' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save.
	 */
	public static function save() {
		check_admin_referer( 'a2ehc_save' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-health' ) );
		}
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		update_settings(
			array(
				'whatsapp'      => isset( $in['whatsapp'] ) ? sanitize_text_field( $in['whatsapp'] ) : '',
				'business_name' => isset( $in['business_name'] ) ? sanitize_text_field( $in['business_name'] ) : '',
				'lead_email'    => isset( $in['lead_email'] ) ? sanitize_email( $in['lead_email'] ) : '',
				'cta_title'     => isset( $in['cta_title'] ) ? sanitize_text_field( $in['cta_title'] ) : '',
				'cta_text'      => isset( $in['cta_text'] ) ? sanitize_textarea_field( $in['cta_text'] ) : '',
				'cta_url'       => isset( $in['cta_url'] ) ? esc_url_raw( $in['cta_url'] ) : '',
				'capture'       => empty( $in['capture'] ) ? '0' : '1',
				'highlight'     => empty( $in['highlight'] ) ? '0' : '1',
			)
		);
		set_transient( 'a2ehc_notice_' . get_current_user_id(), __( 'Settings saved.', 'a2e-health' ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=a2ehc-settings' ) );
		exit;
	}
}
