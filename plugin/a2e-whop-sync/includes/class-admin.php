<?php
/**
 * Admin screens: Connect, Plans (post type), Subscribers, Activity, dashboard widget.
 *
 * @package A2E_Whop_Sync
 */

namespace A2E\WhopSync;

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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_a2ews_connect', array( __CLASS__, 'connect' ) );
		add_action( 'admin_post_a2ews_disconnect', array( __CLASS__, 'disconnect' ) );
		add_action( 'admin_post_a2ews_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_a2ews_webhook', array( __CLASS__, 'recreate_webhook' ) );
		add_action( 'admin_post_a2ews_sync_all', array( __CLASS__, 'sync_all' ) );
		add_action( 'admin_post_a2ews_import', array( __CLASS__, 'import' ) );
		add_action( 'admin_post_a2ews_cancel', array( __CLASS__, 'cancel' ) );
		add_action( 'admin_post_a2ews_clear_log', array( __CLASS__, 'clear_log' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( A2EWS_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_menu_page( __( 'Whop Subscriptions', 'a2e-whop' ), __( 'Whop', 'a2e-whop' ), 'manage_options', 'a2e-whop', array( __CLASS__, 'render_connect' ), 'dashicons-money-alt', 4 );
		add_submenu_page( 'a2e-whop', __( 'Connect Whop', 'a2e-whop' ), __( 'Connect', 'a2e-whop' ), 'manage_options', 'a2e-whop', array( __CLASS__, 'render_connect' ) );
		add_submenu_page( 'a2e-whop', __( 'Subscribers', 'a2e-whop' ), __( 'Subscribers', 'a2e-whop' ), 'manage_options', 'a2e-whop-subscribers', array( __CLASS__, 'render_subscribers' ) );
		add_submenu_page( 'a2e-whop', __( 'Activity', 'a2e-whop' ), __( 'Activity', 'a2e-whop' ), 'manage_options', 'a2e-whop-activity', array( __CLASS__, 'render_activity' ) );
	}

	/**
	 * Plugin list link.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( settings_url() ) . '">' . esc_html__( 'Connect', 'a2e-whop' ) . '</a>' );
		return $links;
	}

	/**
	 * Admin CSS.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( false !== strpos( $hook, 'a2e-whop' ) || ( $screen && CPT === $screen->post_type ) || 'index.php' === $hook ) {
			wp_enqueue_style( 'a2ews-admin', A2EWS_URL . 'assets/admin.css', array(), VERSION );
		}
	}

	/**
	 * Redirect back with a notice.
	 *
	 * @param string $page    Page slug.
	 * @param string $type    success|error|warning.
	 * @param string $message Message.
	 */
	protected static function back( $page, $type, $message ) {
		set_transient( 'a2ews_notice_' . get_current_user_id(), array( $type, $message ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
		exit;
	}

	/**
	 * Guard an admin-post action.
	 *
	 * @param string $action Nonce action.
	 */
	protected static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-whop' ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Connect: verify key, detect account, create webhook, sync plans.
	 */
	public static function connect() {
		self::guard( 'a2ews_connect' );
		$key     = isset( $_POST['api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) ) : '';
		$account = isset( $_POST['account_id'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['account_id'] ) ) ) : '';
		$sandbox = empty( $_POST['sandbox'] ) ? '0' : '1';
		if ( '' === $key && api_key() ) {
			$key = api_key(); // Re-connect with the saved key.
		}
		if ( '' === $key ) {
			self::back( 'a2e-whop', 'error', __( 'Paste your Whop API key first.', 'a2e-whop' ) );
		}
		update_settings( array( 'sandbox' => $sandbox ) );

		$me = Api::me( $key );
		if ( is_wp_error( $me ) ) {
			$data   = $me->get_error_data();
			$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;
			if ( 401 === $status ) {
				self::back( 'a2e-whop', 'error', __( 'Whop rejected that API key. Check you copied the whole key (and that Sandbox matches the key type).', 'a2e-whop' ) );
			}
			if ( '' === $account ) {
				/* translators: %s: error */
				self::back( 'a2e-whop', 'error', sprintf( __( 'Could not read your Whop account automatically (%s). Enter your Account / Company ID (starts with biz_) under “Advanced” and connect again.', 'a2e-whop' ), $me->get_error_message() ) );
			}
			$title = $account;
		} else {
			$account = (string) pick( $me, array( 'id', 'data.id' ) ) ? (string) pick( $me, array( 'id', 'data.id' ) ) : $account;
			$title   = (string) pick( $me, array( 'title', 'name', 'data.title' ) );
		}

		update_settings(
			array(
				'api_key'       => encrypt( $key ),
				'account_id'    => $account,
				'account_title' => $title,
				'connected_at'  => (string) time(),
			)
		);
		log( 'connect', sprintf( 'Connected to Whop account %s (%s).', $title, $account ) );

		$messages = array( __( 'Connected to Whop.', 'a2e-whop' ) );
		$hook     = self::ensure_webhook();
		$messages[] = true === $hook ? __( 'Webhook created — subscriptions will sync automatically.', 'a2e-whop' ) : $hook;

		$synced = 0;
		foreach ( get_posts( array( 'post_type' => CPT, 'post_status' => 'publish', 'posts_per_page' => 50, 'fields' => 'ids' ) ) as $pid ) {
			if ( ! get_post_meta( $pid, '_a2ews_plan_id', true ) && true === Plans::sync( $pid ) ) {
				++$synced;
			}
		}
		if ( $synced ) {
			/* translators: %d: count */
			$messages[] = sprintf( _n( '%d plan created on Whop.', '%d plans created on Whop.', $synced, 'a2e-whop' ), $synced );
		}
		self::back( 'a2e-whop', true === $hook ? 'success' : 'warning', implode( ' ', $messages ) );
	}

	/**
	 * Make sure a webhook for this site exists. Returns true or a message for manual setup.
	 *
	 * @param bool $force Delete and recreate.
	 * @return true|string
	 */
	protected static function ensure_webhook( $force = false ) {
		if ( ! $force && setting( 'webhook_id' ) && setting( 'webhook_secret' ) && setting( 'webhook_url' ) === Webhooks::url() ) {
			return true;
		}
		if ( setting( 'webhook_id' ) ) {
			Api::delete_webhook( setting( 'webhook_id' ) );
		}
		$res = Api::create_webhook( Webhooks::url(), Webhooks::EVENTS );
		if ( is_wp_error( $res ) ) {
			log( 'webhook', 'Could not create the webhook automatically: ' . $res->get_error_message(), false );
			/* translators: 1: error 2: URL */
			return sprintf( __( 'The webhook could not be created automatically (%1$s). Create it in Whop → Developer → Webhooks with the URL %2$s and paste its secret below.', 'a2e-whop' ), $res->get_error_message(), Webhooks::url() );
		}
		$secret = (string) pick( $res, array( 'webhook_secret', 'secret', 'data.webhook_secret' ) );
		update_settings(
			array(
				'webhook_id'     => (string) pick( $res, array( 'id', 'data.id' ) ),
				'webhook_secret' => encrypt( $secret ),
				'webhook_url'    => Webhooks::url(),
			)
		);
		log( 'webhook', 'Webhook created on Whop.' );
		return $secret ? true : __( 'Webhook created, but Whop did not return its secret. Copy the secret from Whop → Developer → Webhooks and paste it below.', 'a2e-whop' );
	}

	/**
	 * Recreate webhook.
	 */
	public static function recreate_webhook() {
		self::guard( 'a2ews_webhook' );
		$res = self::ensure_webhook( true );
		self::back( 'a2e-whop', true === $res ? 'success' : 'warning', true === $res ? __( 'Webhook recreated.', 'a2e-whop' ) : $res );
	}

	/**
	 * Disconnect.
	 */
	public static function disconnect() {
		self::guard( 'a2ews_disconnect' );
		if ( setting( 'webhook_id' ) ) {
			Api::delete_webhook( setting( 'webhook_id' ) );
		}
		update_settings(
			array(
				'api_key'        => '',
				'connected_at'   => '',
				'webhook_id'     => '',
				'webhook_secret' => '',
				'webhook_url'    => '',
			)
		);
		log( 'connect', 'Disconnected from Whop.' );
		self::back( 'a2e-whop', 'success', __( 'Disconnected. Your plans stay on Whop and keep working; the website falls back to the theme’s plans.', 'a2e-whop' ) );
	}

	/**
	 * Save options.
	 */
	public static function save_settings() {
		self::guard( 'a2ews_settings' );
		$values = array(
			'redirect_url' => isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : '',
			'portal_url'   => isset( $_POST['portal_url'] ) ? esc_url_raw( wp_unslash( $_POST['portal_url'] ) ) : '',
			'notify_admin' => empty( $_POST['notify_admin'] ) ? '0' : '1',
			'create_users' => empty( $_POST['create_users'] ) ? '0' : '1',
		);
		$secret = isset( $_POST['webhook_secret'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ) ) ) : '';
		if ( '' !== $secret ) {
			$values['webhook_secret'] = encrypt( $secret );
			$values['webhook_url']    = Webhooks::url();
		}
		update_settings( $values );
		self::back( 'a2e-whop', 'success', __( 'Settings saved.', 'a2e-whop' ) );
	}

	/**
	 * Sync every published plan.
	 */
	public static function sync_all() {
		self::guard( 'a2ews_sync_all' );
		$ok  = 0;
		$bad = 0;
		foreach ( get_posts( array( 'post_type' => CPT, 'post_status' => 'publish', 'posts_per_page' => 50, 'fields' => 'ids' ) ) as $pid ) {
			if ( true === Plans::sync( $pid ) ) {
				++$ok;
			} else {
				++$bad;
			}
		}
		/* translators: 1: ok 2: failed */
		self::back( 'a2e-whop', $bad ? 'warning' : 'success', sprintf( __( '%1$d plans synced, %2$d failed (see Activity).', 'a2e-whop' ), $ok, $bad ) );
	}

	/**
	 * Import subscribers.
	 */
	public static function import() {
		self::guard( 'a2ews_import' );
		$res = Members::import_all();
		if ( is_wp_error( $res ) ) {
			self::back( 'a2e-whop-subscribers', 'error', $res->get_error_message() );
		}
		/* translators: %d: count */
		self::back( 'a2e-whop-subscribers', 'success', sprintf( __( '%d subscriptions imported from Whop.', 'a2e-whop' ), (int) $res ) );
	}

	/**
	 * Cancel at period end.
	 */
	public static function cancel() {
		$id = isset( $_GET['membership'] ) ? sanitize_text_field( wp_unslash( $_GET['membership'] ) ) : '';
		self::guard( 'a2ews_cancel_' . $id );
		$res = Api::cancel_membership( $id );
		if ( is_wp_error( $res ) ) {
			self::back( 'a2e-whop-subscribers', 'error', $res->get_error_message() );
		}
		Members::upsert( array_merge( is_array( $res ) ? $res : array(), array( 'id' => $id, 'cancel_at_period_end' => true ) ) );
		log( 'cancel', sprintf( 'Cancelled %s at period end from WordPress.', $id ) );
		self::back( 'a2e-whop-subscribers', 'success', __( 'Subscription will cancel at the end of the current period. The client keeps access until then.', 'a2e-whop' ) );
	}

	/**
	 * Clear activity log.
	 */
	public static function clear_log() {
		self::guard( 'a2ews_clear_log' );
		delete_option( 'a2ews_log' );
		self::back( 'a2e-whop-activity', 'success', __( 'Activity cleared.', 'a2e-whop' ) );
	}

	/**
	 * Notice output for our screens.
	 */
	protected static function notice() {
		$key    = 'a2ews_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( $notice ) {
			delete_transient( $key );
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $notice[0] ), esc_html( $notice[1] ) );
		}
	}

	/**
	 * Hidden form fields.
	 *
	 * @param string $action Action.
	 */
	protected static function form_fields( $action ) {
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
		wp_nonce_field( $action );
	}

	/**
	 * Stat cards.
	 */
	protected static function stat_cards() {
		$s = Members::stats();
		?>
		<div class="a2ews-stats">
			<div><span><?php esc_html_e( 'Active subscribers', 'a2e-whop' ); ?></span><strong><?php echo (int) $s['active']; ?></strong></div>
			<div><span><?php esc_html_e( 'Monthly recurring revenue (est.)', 'a2e-whop' ); ?></span><strong><?php echo esc_html( format_price( $s['mrr'] ) ); ?></strong></div>
			<div><span><?php esc_html_e( 'Cancelling at period end', 'a2e-whop' ); ?></span><strong><?php echo (int) $s['canceling']; ?></strong></div>
			<div><span><?php esc_html_e( 'Revenue recorded', 'a2e-whop' ); ?></span><strong><?php echo esc_html( format_price( $s['total_paid'] ) ); ?></strong></div>
		</div>
		<?php
	}

	/**
	 * Connect screen.
	 */
	public static function render_connect() {
		$s         = settings();
		$connected = is_connected();
		$plans     = wp_count_posts( CPT );
		$local     = 0 !== strpos( home_url( '/' ), 'https://' ) || preg_match( '#//(localhost|127\.0\.0\.1|[^/]+\.local)[:/]#', home_url( '/' ) );
		?>
		<div class="wrap a2ews">
			<h1 class="a2ews-title"><?php esc_html_e( 'Whop Subscriptions', 'a2e-whop' ); ?></h1>
			<p class="a2ews-lead"><?php esc_html_e( 'Connect your Whop account once. After that, create and edit plans right here in WordPress — they are created on Whop automatically, appear on your pricing pages with secure checkout, and every new subscriber, renewal and cancellation syncs back.', 'a2e-whop' ); ?></p>
			<?php self::notice(); ?>

			<?php if ( $connected ) : ?>
				<div class="a2ews-card a2ews-card--ok">
					<div class="a2ews-card__row">
						<span class="a2ews-dot"></span>
						<div>
							<h2><?php /* translators: %s: account */ printf( esc_html__( 'Connected to %s', 'a2e-whop' ), '<strong>' . esc_html( $s['account_title'] ? $s['account_title'] : $s['account_id'] ) . '</strong>' ); ?></h2>
							<p><?php echo esc_html( $s['account_id'] ); ?><?php echo '1' === $s['sandbox'] ? ' · <strong>' . esc_html__( 'Sandbox', 'a2e-whop' ) . '</strong>' : ''; ?> · <?php /* translators: %s: time */ printf( esc_html__( 'since %s', 'a2e-whop' ), esc_html( wp_date( get_option( 'date_format' ), (int) $s['connected_at'] ) ) ); ?></p>
						</div>
					</div>
					<div class="a2ews-actions">
						<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . CPT ) ); ?>"><?php esc_html_e( 'Create a plan', 'a2e-whop' ); ?></a>
						<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . CPT ) ); ?>"><?php /* translators: %d: count */ printf( esc_html__( 'Manage plans (%d)', 'a2e-whop' ), (int) $plans->publish ); ?></a>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php self::form_fields( 'a2ews_sync_all' ); ?><button class="button"><?php esc_html_e( 'Re-sync all plans', 'a2e-whop' ); ?></button></form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Disconnect Whop? Plans stay on Whop and keep selling.', 'a2e-whop' ) ); ?>')"><?php self::form_fields( 'a2ews_disconnect' ); ?><button class="button-link button-link-delete"><?php esc_html_e( 'Disconnect', 'a2e-whop' ); ?></button></form>
					</div>
				</div>
				<?php self::stat_cards(); ?>
			<?php else : ?>
				<div class="a2ews-card">
					<h2><?php esc_html_e( 'Connect your Whop account', 'a2e-whop' ); ?></h2>
					<ol class="a2ews-steps">
						<li><?php echo wp_kses( __( 'In Whop, open your <strong>Dashboard → Developer → API keys</strong> and create a key. The <strong>Admin</strong> role is simplest (or give it permissions for products, plans, memberships incl. email, and webhooks).', 'a2e-whop' ), array( 'strong' => array() ) ); ?> <a href="https://whop.com/dashboard/" target="_blank" rel="noopener"><?php esc_html_e( 'Open Whop ↗', 'a2e-whop' ); ?></a></li>
						<li><?php esc_html_e( 'Paste the key below and press Connect. We detect your account and set up the webhook automatically.', 'a2e-whop' ); ?></li>
						<li><?php esc_html_e( 'Create your plans under Whop → Plans. Done — they go live on your pricing pages.', 'a2e-whop' ); ?></li>
					</ol>
				</div>
			<?php endif; ?>

			<?php if ( $local ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'This site is not on a public https:// address, so Whop cannot deliver webhooks to it. Plan creation still works; subscriber sync starts once the site is live.', 'a2e-whop' ); ?></p></div>
			<?php endif; ?>

			<div class="a2ews-grid">
				<form class="a2ews-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php self::form_fields( 'a2ews_connect' ); ?>
					<h2><?php echo $connected ? esc_html__( 'Update API key', 'a2e-whop' ) : esc_html__( 'API key', 'a2e-whop' ); ?></h2>
					<p><label for="a2ews-key" class="screen-reader-text"><?php esc_html_e( 'Whop API key', 'a2e-whop' ); ?></label>
						<input type="password" id="a2ews-key" name="api_key" class="large-text code" autocomplete="off" placeholder="<?php echo $connected ? esc_attr__( '•••••••• saved — paste a new key to replace it', 'a2e-whop' ) : 'apik_…'; ?>"></p>
					<p><label><input type="checkbox" name="sandbox" value="1" <?php checked( '1', $s['sandbox'] ); ?>> <?php esc_html_e( 'Use Whop sandbox (test payments only)', 'a2e-whop' ); ?></label></p>
					<details><summary><?php esc_html_e( 'Advanced', 'a2e-whop' ); ?></summary>
						<p><label><?php esc_html_e( 'Account / Company ID (only if automatic detection fails)', 'a2e-whop' ); ?><br><input type="text" name="account_id" class="regular-text code" value="<?php echo esc_attr( $s['account_id'] ); ?>" placeholder="biz_…"></label></p>
					</details>
					<p><button class="button button-primary button-hero"><?php echo $connected ? esc_html__( 'Reconnect', 'a2e-whop' ) : esc_html__( 'Connect Whop', 'a2e-whop' ); ?></button></p>
					<p class="description"><?php esc_html_e( 'The key is stored encrypted and only ever sent to Whop.', 'a2e-whop' ); ?></p>
				</form>

				<form class="a2ews-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php self::form_fields( 'a2ews_settings' ); ?>
					<h2><?php esc_html_e( 'Settings', 'a2e-whop' ); ?></h2>
					<p><label><?php esc_html_e( 'After purchase, send customers to', 'a2e-whop' ); ?><br><input type="url" name="redirect_url" class="large-text" value="<?php echo esc_attr( $s['redirect_url'] ); ?>" placeholder="<?php echo esc_attr( add_query_arg( 'purchase', 'success', home_url( '/account/' ) ) ); ?>"></label></p>
					<p><label><?php esc_html_e( 'Customer billing portal (Manage subscription link)', 'a2e-whop' ); ?><br><input type="url" name="portal_url" class="large-text" value="<?php echo esc_attr( $s['portal_url'] ); ?>"></label></p>
					<p><label><input type="checkbox" name="notify_admin" value="1" <?php checked( '1', $s['notify_admin'] ); ?>> <?php esc_html_e( 'Email me about new subscriptions, cancellations and failed payments', 'a2e-whop' ); ?></label></p>
					<p><label><input type="checkbox" name="create_users" value="1" <?php checked( '1', $s['create_users'] ); ?>> <?php esc_html_e( 'Create a WordPress account (Subscriber role) for each new client', 'a2e-whop' ); ?></label></p>
					<hr>
					<h3><?php esc_html_e( 'Webhook', 'a2e-whop' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Endpoint URL:', 'a2e-whop' ); ?> <code><?php echo esc_html( Webhooks::url() ); ?></code><br>
						<?php echo $s['webhook_secret'] ? '<span class="a2ews-pill a2ews-pill--ok">' . esc_html__( 'Signing secret saved', 'a2e-whop' ) . '</span>' : '<span class="a2ews-pill a2ews-pill--warn">' . esc_html__( 'No signing secret yet', 'a2e-whop' ) . '</span>'; ?></p>
					<p><label><?php esc_html_e( 'Paste a webhook secret manually (only if you created the webhook in Whop yourself)', 'a2e-whop' ); ?><br><input type="password" name="webhook_secret" class="large-text code" autocomplete="off" placeholder="ws_…"></label></p>
					<p><button class="button button-primary"><?php esc_html_e( 'Save settings', 'a2e-whop' ); ?></button></p>
				</form>
			</div>
			<?php if ( $connected ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2ews-inline"><?php self::form_fields( 'a2ews_webhook' ); ?><button class="button"><?php esc_html_e( 'Recreate webhook on Whop', 'a2e-whop' ); ?></button></form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Subscribers screen.
	 */
	public static function render_subscribers() {
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		list( $rows, $total ) = Members::query( array( 'status' => $status, 'search' => $search, 'paged' => $paged, 'per_page' => 30 ) );
		$tabs   = array( '' => __( 'All', 'a2e-whop' ), 'active' => __( 'Active', 'a2e-whop' ), 'canceled' => __( 'Cancelled', 'a2e-whop' ), 'expired' => __( 'Expired', 'a2e-whop' ), 'past_due' => __( 'Past due', 'a2e-whop' ) );
		?>
		<div class="wrap a2ews">
			<h1 class="a2ews-title"><?php esc_html_e( 'Subscribers', 'a2e-whop' ); ?></h1>
			<?php self::notice(); ?>
			<?php self::stat_cards(); ?>
			<div class="a2ews-toolbar">
				<ul class="subsubsub">
					<?php
					$links = array();
					foreach ( $tabs as $key => $label ) {
						$links[] = '<li><a href="' . esc_url( add_query_arg( array( 'page' => 'a2e-whop-subscribers', 'status' => $key ), admin_url( 'admin.php' ) ) ) . '"' . ( $key === $status ? ' class="current"' : '' ) . '>' . esc_html( $label ) . '</a>';
					}
					echo implode( ' | </li>', $links ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</ul>
				<form method="get" class="a2ews-search"><input type="hidden" name="page" value="a2e-whop-subscribers"><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Email, name or ID', 'a2e-whop' ); ?>"><button class="button"><?php esc_html_e( 'Search', 'a2e-whop' ); ?></button></form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php self::form_fields( 'a2ews_import' ); ?><button class="button" <?php disabled( ! is_connected() ); ?>><?php esc_html_e( 'Import from Whop', 'a2e-whop' ); ?></button></form>
			</div>
			<table class="widefat striped a2ews-table">
				<thead><tr><th><?php esc_html_e( 'Client', 'a2e-whop' ); ?></th><th><?php esc_html_e( 'Plan', 'a2e-whop' ); ?></th><th><?php esc_html_e( 'Status', 'a2e-whop' ); ?></th><th><?php esc_html_e( 'Renews / ends', 'a2e-whop' ); ?></th><th><?php esc_html_e( 'Paid', 'a2e-whop' ); ?></th><th><?php esc_html_e( 'Since', 'a2e-whop' ); ?></th><th></th></tr></thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="7"><?php echo is_connected() ? esc_html__( 'No subscribers yet. New ones appear here automatically; use “Import from Whop” to load existing ones.', 'a2e-whop' ) : esc_html__( 'Connect Whop to start syncing subscribers.', 'a2e-whop' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $r ) : ?>
						<?php
						$active = in_array( $r['status'], array( 'active', 'trialing', 'past_due' ), true );
						$label  = $r['cancel_at_period_end'] && $active ? __( 'cancelling', 'a2e-whop' ) : $r['status'];
						$class  = $r['cancel_at_period_end'] && $active ? 'warn' : ( $active ? 'ok' : 'err' );
						?>
						<tr>
							<td><strong><?php echo esc_html( $r['name'] ? $r['name'] : ( $r['username'] ? $r['username'] : '—' ) ); ?></strong><br><?php echo $r['email'] ? '<a href="mailto:' . esc_attr( $r['email'] ) . '">' . esc_html( $r['email'] ) . '</a>' : '<small>' . esc_html( $r['id'] ) . '</small>'; ?></td>
							<td><?php echo $r['wp_plan_id'] ? '<a href="' . esc_url( get_edit_post_link( (int) $r['wp_plan_id'] ) ) . '">' . esc_html( get_the_title( (int) $r['wp_plan_id'] ) ) . '</a>' : '<code>' . esc_html( $r['plan_id'] ) . '</code>'; ?></td>
							<td><span class="a2ews-pill a2ews-pill--<?php echo esc_attr( $class ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $label ) ) ); ?></span></td>
							<td><?php echo $r['period_end'] ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $r['period_end'] . ' UTC' ) ) ) : '—'; ?></td>
							<td><?php echo esc_html( format_price( (float) $r['total_paid'], $r['currency'] ) ); ?></td>
							<td><?php echo $r['created_at'] ? esc_html( wp_date( get_option( 'date_format' ), strtotime( $r['created_at'] . ' UTC' ) ) ) : '—'; ?></td>
							<td class="a2ews-row-actions">
								<?php if ( $r['manage_url'] ) : ?><a href="<?php echo esc_url( $r['manage_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open in Whop', 'a2e-whop' ); ?></a><?php endif; ?>
								<?php if ( $active && ! $r['cancel_at_period_end'] && is_connected() ) : ?>
									<a class="a2ews-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a2ews_cancel&membership=' . rawurlencode( $r['id'] ) ), 'a2ews_cancel_' . $r['id'] ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Cancel this subscription at the end of the current period?', 'a2e-whop' ) ); ?>')"><?php esc_html_e( 'Cancel at period end', 'a2e-whop' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			$pages = (int) ceil( $total / 30 );
			if ( $pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
	}

	/**
	 * Activity screen.
	 */
	public static function render_activity() {
		$entries = get_option( 'a2ews_log', array() );
		?>
		<div class="wrap a2ews">
			<h1 class="a2ews-title"><?php esc_html_e( 'Activity', 'a2e-whop' ); ?></h1>
			<?php self::notice(); ?>
			<table class="widefat striped">
				<thead><tr><th style="width:160px"><?php esc_html_e( 'When', 'a2e-whop' ); ?></th><th style="width:240px"><?php esc_html_e( 'Event', 'a2e-whop' ); ?></th><th><?php esc_html_e( 'Details', 'a2e-whop' ); ?></th></tr></thead>
				<tbody>
					<?php if ( ! $entries ) : ?><tr><td colspan="3"><?php esc_html_e( 'Nothing yet.', 'a2e-whop' ); ?></td></tr><?php endif; ?>
					<?php foreach ( (array) $entries as $e ) : ?>
						<tr><td><?php echo esc_html( wp_date( 'M j, H:i', (int) $e['t'] ) ); ?></td><td><span class="a2ews-pill a2ews-pill--<?php echo $e['ok'] ? 'ok' : 'err'; ?>"><?php echo esc_html( $e['ty'] ); ?></span></td><td><?php echo esc_html( $e['m'] ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $entries ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2ews-inline"><?php self::form_fields( 'a2ews_clear_log' ); ?><button class="button"><?php esc_html_e( 'Clear activity', 'a2e-whop' ); ?></button></form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Dashboard widget.
	 */
	public static function dashboard_widget() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'a2ews_widget',
			__( 'Whop subscriptions', 'a2e-whop' ),
			function () {
				if ( ! is_connected() ) {
					echo '<p>' . esc_html__( 'Connect Whop to sell your plans with secure checkout.', 'a2e-whop' ) . ' <a href="' . esc_url( settings_url() ) . '">' . esc_html__( 'Connect →', 'a2e-whop' ) . '</a></p>';
					return;
				}
				self::stat_cards();
				$entries = array_slice( (array) get_option( 'a2ews_log', array() ), 0, 5 );
				if ( $entries ) {
					echo '<ul class="a2ews-mini-log">';
					foreach ( $entries as $e ) {
						echo '<li><span>' . esc_html( human_time_diff( (int) $e['t'] ) ) . '</span> ' . esc_html( $e['m'] ) . '</li>';
					}
					echo '</ul>';
				}
				echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=a2e-whop-subscribers' ) ) . '">' . esc_html__( 'All subscribers →', 'a2e-whop' ) . '</a></p>';
			}
		);
	}
}
