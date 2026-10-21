<?php
/**
 * Admin screens: dashboard, contacts, settings, activity. Menus and shared helpers.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

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
		add_action( 'admin_post_a2email_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_a2email_sync_now', array( __CLASS__, 'sync_now' ) );
		add_action( 'admin_post_a2email_add_contact', array( __CLASS__, 'add_contact' ) );
		add_action( 'admin_post_a2email_unsub_contact', array( __CLASS__, 'unsub_contact' ) );
		add_action( 'admin_post_a2email_test_smtp', array( __CLASS__, 'test_smtp' ) );
		add_filter( 'manage_' . TEMPLATE . '_posts_columns', array( __CLASS__, 'template_columns' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'template_meta_box' ) );
		add_action( 'save_post_' . TEMPLATE, array( __CLASS__, 'save_template' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu() {
		add_menu_page( __( 'A2E Mail Studio', 'a2e-mail' ), __( 'A2E Mail', 'a2e-mail' ), 'manage_options', 'a2e-mail', array( __CLASS__, 'render_dashboard' ), 'dashicons-email-alt', 5 );
		add_submenu_page( 'a2e-mail', __( 'Dashboard', 'a2e-mail' ), __( 'Dashboard', 'a2e-mail' ), 'manage_options', 'a2e-mail', array( __CLASS__, 'render_dashboard' ) );
		add_submenu_page( 'a2e-mail', __( 'Compose', 'a2e-mail' ), __( 'Compose', 'a2e-mail' ), 'manage_options', 'a2e-mail-compose', array( Composer::class, 'render_compose' ) );
		add_submenu_page( 'a2e-mail', __( 'Messages', 'a2e-mail' ), __( 'Messages', 'a2e-mail' ), 'manage_options', 'a2e-mail-messages', array( __CLASS__, 'render_messages' ) );
		add_submenu_page( 'a2e-mail', __( 'Weekly newsletter', 'a2e-mail' ), __( 'Newsletter', 'a2e-mail' ), 'manage_options', 'a2e-mail-newsletter', array( Newsletter::class, 'render' ) );
		add_submenu_page( 'a2e-mail', __( 'Automations', 'a2e-mail' ), __( 'Automations', 'a2e-mail' ), 'manage_options', 'a2e-mail-automations', array( Automations::class, 'render' ) );
		add_submenu_page( 'a2e-mail', __( 'Contacts', 'a2e-mail' ), __( 'Contacts', 'a2e-mail' ), 'manage_options', 'a2e-mail-contacts', array( __CLASS__, 'render_contacts' ) );
		add_submenu_page( 'a2e-mail', __( 'Templates', 'a2e-mail' ), __( 'Templates', 'a2e-mail' ), 'manage_options', 'edit.php?post_type=' . TEMPLATE );
		add_submenu_page( 'a2e-mail', __( 'Settings', 'a2e-mail' ), __( 'Settings', 'a2e-mail' ), 'manage_options', 'a2e-mail-settings', array( __CLASS__, 'render_settings' ) );
	}

	/**
	 * Assets on our screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( $hook ) {
		$screen = get_current_screen();
		$ours   = false !== strpos( $hook, 'a2e-mail' ) || ( $screen && TEMPLATE === $screen->post_type );
		if ( ! $ours ) {
			return;
		}
		wp_enqueue_style( 'a2email-admin', A2EMAIL_URL . 'assets/admin.css', array(), VERSION );
		wp_enqueue_script( 'a2email-admin', A2EMAIL_URL . 'assets/admin.js', array(), VERSION, true );
		wp_localize_script(
			'a2email-admin',
			'a2emailData',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'a2email_preview' ),
			)
		);
	}

	/**
	 * Show (and clear) a stored notice.
	 */
	public static function print_notice() {
		$key    = 'a2email_notice_' . get_current_user_id();
		$notice = get_transient( $key );
		if ( ! $notice ) {
			return;
		}
		delete_transient( $key );
		printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( 'error' === $notice[0] ? 'error' : 'success' ), esc_html( $notice[1] ) );
	}

	/**
	 * Redirect back with a notice.
	 *
	 * @param string $page Page.
	 * @param string $type success|error.
	 * @param string $msg  Message.
	 */
	protected static function back( $page, $type, $msg ) {
		set_transient( 'a2email_notice_' . get_current_user_id(), array( $type, $msg ), 60 );
		wp_safe_redirect( admin_page_url( $page ) );
		exit;
	}

	/**
	 * Dashboard.
	 */
	public static function render_dashboard() {
		$stats    = Contacts::stats();
		$segments = Contacts::segments();
		$last     = (int) get_option( 'a2email_last_sync', 0 );
		$log      = get_option( 'a2email_log', array() );
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'A2E Mail Studio', 'a2e-mail' ); ?></h1>
			<p class="a2email-lead"><?php esc_html_e( 'Send professional, on-brand emails to the people you already have a relationship with: your clients and everyone who contacted you or subscribed on your website. This never collects addresses from the web and never emails anyone who unsubscribed.', 'a2e-mail' ); ?></p>
			<?php self::print_notice(); ?>

			<div class="a2email-stats">
				<div class="a2email-stat"><span><?php esc_html_e( 'Subscribed contacts', 'a2e-mail' ); ?></span><strong><?php echo (int) $stats['subscribed']; ?></strong></div>
				<div class="a2email-stat"><span><?php esc_html_e( 'Clients', 'a2e-mail' ); ?></span><strong><?php echo (int) $segments['clients']['count']; ?></strong></div>
				<div class="a2email-stat"><span><?php esc_html_e( 'Newsletter', 'a2e-mail' ); ?></span><strong><?php echo (int) $segments['newsletter']['count']; ?></strong></div>
				<div class="a2email-stat"><span><?php esc_html_e( 'Unsubscribed', 'a2e-mail' ); ?></span><strong><?php echo (int) $stats['unsubscribed']; ?></strong></div>
			</div>

			<div class="a2email-cards">
				<div class="a2email-card">
					<h2><?php esc_html_e( 'Find new contacts', 'a2e-mail' ); ?></h2>
					<p><?php esc_html_e( 'New form submissions are added the moment they arrive, and everything is re-checked automatically every few minutes. You can also check right now.', 'a2e-mail' ); ?></p>
					<?php if ( $last ) : ?>
						<p class="a2email-muted"><?php printf( esc_html__( 'Last checked %s ago.', 'a2e-mail' ), esc_html( human_time_diff( $last ) ) ); ?></p>
					<?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'a2email_sync' ); ?>
						<input type="hidden" name="action" value="a2email_sync_now">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Check for new contacts now', 'a2e-mail' ); ?></button>
						<button type="submit" name="deep" value="1" class="button"><?php esc_html_e( 'Search hard (full rescan)', 'a2e-mail' ); ?></button>
					</form>
				</div>
				<?php
				$welcome = Automations::welcome();
				$nl      = Newsletter::settings();
				$nl_on   = '1' === $nl['enabled'];
				$nl_seg  = isset( $segments[ $nl['audience'] ] ) ? $segments[ $nl['audience'] ] : $segments['weekly'];
				?>
				<div class="a2email-card">
					<h2><?php esc_html_e( 'Welcome email', 'a2e-mail' ); ?> <span class="a2email-pill a2email-pill--<?php echo ( $welcome && $welcome['active'] ) ? 'sent' : 'paused'; ?>"><?php echo ( $welcome && $welcome['active'] ) ? esc_html__( 'On', 'a2e-mail' ) : esc_html__( 'Off', 'a2e-mail' ); ?></span></h2>
					<p><?php esc_html_e( 'Every new contact (added by hand, or from a form or newsletter sign-up) gets "who we are and what we offer" straight away: Amazon to eBay dropshipping, branded eBay stores, Shopify stores and Claude automation.', 'a2e-mail' ); ?></p>
					<?php if ( $welcome ) : ?>
						<?php $reached = Automations::reached_count( $welcome['id'] ); ?>
						<p class="a2email-muted"><?php /* translators: %d: people */ printf( esc_html( _n( 'Sent to %d person so far.', 'Sent to %d people so far.', $reached, 'a2e-mail' ) ), (int) $reached ); ?></p>
						<a class="button button-primary" href="<?php echo esc_url( admin_page_url( 'automations', array( 'sequence' => $welcome['id'] ) ) ); ?>"><?php esc_html_e( 'Edit welcome email', 'a2e-mail' ); ?></a>
					<?php else : ?>
						<a class="button" href="<?php echo esc_url( admin_page_url( 'automations' ) ); ?>"><?php esc_html_e( 'Automations', 'a2e-mail' ); ?></a>
					<?php endif; ?>
				</div>
				<div class="a2email-card">
					<h2><?php esc_html_e( 'Weekly newsletter', 'a2e-mail' ); ?> <span class="a2email-pill a2email-pill--<?php echo $nl_on ? 'sent' : 'paused'; ?>"><?php echo $nl_on ? esc_html__( 'On', 'a2e-mail' ) : esc_html__( 'Off', 'a2e-mail' ); ?></span></h2>
					<p><?php esc_html_e( 'An issue built for you each week from your new blog posts, a tip of the week, a free tool of the week and your offer.', 'a2e-mail' ); ?></p>
					<p class="a2email-muted">
						<?php
						if ( $nl_on ) {
							$next = Newsletter::state()['next_at'];
							/* translators: 1: date 2: people */
							printf( esc_html__( 'Next issue: %1$s, to %2$d people.', 'a2e-mail' ), esc_html( Newsletter::format_time( $next ? $next : Newsletter::next_slot( time() ) ) ), (int) $nl_seg['count'] );
						} else {
							/* translators: %d: people */
							printf( esc_html__( 'Off. %d people are on the weekly list.', 'a2e-mail' ), (int) $segments['weekly']['count'] );
						}
						?>
					</p>
					<a class="button button-primary" href="<?php echo esc_url( admin_page_url( 'newsletter' ) ); ?>"><?php echo $nl_on ? esc_html__( 'Open newsletter', 'a2e-mail' ) : esc_html__( 'Set up the newsletter', 'a2e-mail' ); ?></a>
				</div>
				<div class="a2email-card">
					<h2><?php esc_html_e( 'Write a message', 'a2e-mail' ); ?></h2>
					<p><?php esc_html_e( 'Start from a template or a blank branded message. Preview it, send a test, then send to a group or one person.', 'a2e-mail' ); ?></p>
					<a class="button button-primary" href="<?php echo esc_url( admin_page_url( 'compose' ) ); ?>"><?php esc_html_e( 'Compose', 'a2e-mail' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_page_url( 'contacts' ) ); ?>"><?php esc_html_e( 'View contacts', 'a2e-mail' ); ?></a>
				</div>
			</div>

			<?php if ( ! empty( $log ) ) : ?>
				<h2 class="a2email-h2"><?php esc_html_e( 'Recent activity', 'a2e-mail' ); ?></h2>
				<ul class="a2email-log">
					<?php foreach ( array_slice( $log, 0, 12 ) as $entry ) : ?>
						<li><span><?php echo esc_html( human_time_diff( $entry['t'] ) . ' ' . __( 'ago', 'a2e-mail' ) ); ?></span> <?php echo esc_html( $entry['m'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Manual sync.
	 */
	public static function sync_now() {
		check_admin_referer( 'a2email_sync' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$res = Contacts::sync();
		$msg = $res['added'] > 0
			? sprintf( __( 'Found %1$d new contacts (%2$d from forms, %3$d clients).', 'a2e-mail' ), $res['added'], $res['detail']['leads'], $res['detail']['clients'] )
			: __( 'No new contacts since last time. New ones are added automatically as people contact you.', 'a2e-mail' );
		self::back( '', 'success', $msg );
	}

	/**
	 * Messages (history) with live progress.
	 */
	public static function render_messages() {
		$one = isset( $_GET['message'] ) ? absint( $_GET['message'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$posts = get_posts( array( 'post_type' => CPT, 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'Messages', 'a2e-mail' ); ?> <a class="page-title-action" href="<?php echo esc_url( admin_page_url( 'compose' ) ); ?>"><?php esc_html_e( 'Compose', 'a2e-mail' ); ?></a></h1>
			<?php self::print_notice(); ?>
			<?php if ( ! $posts ) : ?>
				<p><?php esc_html_e( 'No messages yet.', 'a2e-mail' ); ?></p>
			<?php else : ?>
				<table class="widefat striped a2email-table">
					<thead><tr>
						<th><?php esc_html_e( 'Message', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Audience', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Status', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Progress', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'When', 'a2e-mail' ); ?></th>
						<th></th>
					</tr></thead>
					<tbody>
					<?php
					foreach ( $posts as $p ) :
						$m    = Composer::get_message( $p->ID );
						$prog = Queue::progress( $p->ID );
						$segs = Contacts::segments();
						$seg  = isset( $segs[ $m['segment'] ] ) ? $segs[ $m['segment'] ]['label'] : $m['segment'];
						?>
						<tr<?php echo $one === $p->ID ? ' class="a2email-row--focus"' : ''; ?>>
							<td><strong><?php echo esc_html( $m['title'] ); ?></strong><br><span class="a2email-muted"><?php echo esc_html( $m['subject'] ); ?></span></td>
							<td><?php echo esc_html( $seg ); ?></td>
							<td><span class="a2email-pill a2email-pill--<?php echo esc_attr( $m['status'] ); ?>"><?php echo esc_html( self::status_label( $m['status'] ) ); ?></span></td>
							<td>
								<?php if ( $prog['total'] ) : ?>
									<?php echo (int) $prog['sent']; ?> / <?php echo (int) $prog['total']; ?>
									<?php if ( $prog['failed'] ) : ?><span class="a2email-danger">(<?php echo (int) $prog['failed']; ?> <?php esc_html_e( 'failed', 'a2e-mail' ); ?>)</span><?php endif; ?>
								<?php else : ?>
									<span class="a2email-muted"><?php esc_html_e( 'not sent', 'a2e-mail' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( get_the_date( '', $p ) . ' ' . get_the_time( '', $p ) ); ?></td>
							<td class="a2email-row-actions">
								<a href="<?php echo esc_url( admin_page_url( 'compose', array( 'message' => $p->ID ) ) ); ?>"><?php echo in_array( $m['status'], array( 'sending', 'sent' ), true ) ? esc_html__( 'View', 'a2e-mail' ) : esc_html__( 'Edit', 'a2e-mail' ); ?></a>
								<?php if ( 'sending' === $m['status'] || 'queued' === $m['status'] ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a2email_pause&message=' . $p->ID ), 'a2email_message' ) ); ?>"><?php esc_html_e( 'Pause', 'a2e-mail' ); ?></a>
								<?php elseif ( 'paused' === $m['status'] ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a2email_resume&message=' . $p->ID ), 'a2email_message' ) ); ?>"><?php esc_html_e( 'Resume', 'a2e-mail' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="a2email-muted"><?php esc_html_e( 'Sending runs in the background within your limits. Reload this page to see progress update.', 'a2e-mail' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Human label for a message status.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	protected static function status_label( $status ) {
		$map = array(
			'draft'   => __( 'Draft', 'a2e-mail' ),
			'queued'  => __( 'Queued', 'a2e-mail' ),
			'sending' => __( 'Sending', 'a2e-mail' ),
			'paused'  => __( 'Paused', 'a2e-mail' ),
			'sent'    => __( 'Sent', 'a2e-mail' ),
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}

	/**
	 * Contacts screen.
	 */
	public static function render_contacts() {
		$view    = isset( $_GET['status'] ) && 'unsubscribed' === sanitize_key( wp_unslash( $_GET['status'] ) ) ? 'unsubscribed' : 'subscribed'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status  = 'subscribed' === $view ? Contacts::STATUS_SUBSCRIBED : Contacts::STATUS_UNSUBSCRIBED;
		$source  = isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$result  = Contacts::query( array( 'status' => $status, 'source' => $source, 'search' => $search, 'paged' => $paged, 'per_page' => 50 ) );
		$stats   = Contacts::stats();
		$sources = array( '' => __( 'All sources', 'a2e-mail' ), 'client' => __( 'Clients', 'a2e-mail' ), 'newsletter' => __( 'Newsletter', 'a2e-mail' ), 'quote' => __( 'Audit requests', 'a2e-mail' ), 'consultation' => __( 'Consultations', 'a2e-mail' ), 'contact' => __( 'Contact form', 'a2e-mail' ), 'health_check' => __( 'Health checker', 'a2e-mail' ), 'manual' => __( 'Added by hand', 'a2e-mail' ) );
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'Contacts', 'a2e-mail' ); ?></h1>
			<?php self::print_notice(); ?>

			<details class="a2email-add">
				<summary><?php esc_html_e( 'Add a contact by hand', 'a2e-mail' ); ?></summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-add__form">
					<?php wp_nonce_field( 'a2email_add_contact' ); ?>
					<input type="hidden" name="action" value="a2email_add_contact">
					<p><input type="email" name="email" class="regular-text" placeholder="<?php esc_attr_e( 'name@business.com', 'a2e-mail' ); ?>" required></p>
					<p><input type="text" name="name" class="regular-text" placeholder="<?php esc_attr_e( 'Name (optional)', 'a2e-mail' ); ?>"></p>
					<p><input type="text" name="note" class="regular-text" placeholder="<?php esc_attr_e( 'How you know them, e.g. met at a seller meetup', 'a2e-mail' ); ?>"></p>
					<p class="a2email-consent"><label><input type="checkbox" name="weekly" value="1"> <?php esc_html_e( 'They asked for the weekly newsletter too', 'a2e-mail' ); ?></label></p>
					<p class="a2email-consent"><label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'I confirm this person asked to hear from us or gave us their address to be contacted. I am not adding a bought or scraped list.', 'a2e-mail' ); ?></label></p>
					<?php
					$welcome = Automations::welcome();
					if ( $welcome && $welcome['active'] && in_array( 'manual', $welcome['triggers'], true ) ) :
						?>
						<p class="a2email-muted"><?php esc_html_e( 'They will get your welcome email ("what we offer") the moment you add them.', 'a2e-mail' ); ?> <a href="<?php echo esc_url( admin_page_url( 'automations', array( 'sequence' => $welcome['id'] ) ) ); ?>"><?php esc_html_e( 'Edit it', 'a2e-mail' ); ?></a></p>
					<?php endif; ?>
					<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Add contact', 'a2e-mail' ); ?></button></p>
				</form>
			</details>

			<ul class="subsubsub">
				<li><a href="<?php echo esc_url( admin_page_url( 'contacts' ) ); ?>" class="<?php echo 'subscribed' === $view ? 'current' : ''; ?>"><?php esc_html_e( 'Subscribed', 'a2e-mail' ); ?> <span class="count">(<?php echo (int) $stats['subscribed']; ?>)</span></a> |</li>
				<li><a href="<?php echo esc_url( admin_page_url( 'contacts', array( 'status' => 'unsubscribed' ) ) ); ?>" class="<?php echo 'unsubscribed' === $view ? 'current' : ''; ?>"><?php esc_html_e( 'Unsubscribed and suppressed', 'a2e-mail' ); ?> <span class="count">(<?php echo (int) ( $stats['unsubscribed'] + $stats['bounced'] ); ?>)</span></a></li>
			</ul>

			<form method="get" class="a2email-filter">
				<input type="hidden" name="page" value="a2e-mail-contacts">
				<?php if ( 'unsubscribed' === $view ) : ?><input type="hidden" name="status" value="unsubscribed"><?php endif; ?>
				<select name="source">
					<?php foreach ( $sources as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $source, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name or email', 'a2e-mail' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'a2e-mail' ); ?></button>
			</form>

			<table class="widefat striped a2email-table">
				<thead><tr>
					<th><?php esc_html_e( 'Email', 'a2e-mail' ); ?></th>
					<th><?php esc_html_e( 'Name', 'a2e-mail' ); ?></th>
					<th><?php esc_html_e( 'Source', 'a2e-mail' ); ?></th>
					<th><?php esc_html_e( 'How we may contact them', 'a2e-mail' ); ?></th>
					<th><?php esc_html_e( 'Status', 'a2e-mail' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
				<?php if ( ! $result['rows'] ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No contacts here yet.', 'a2e-mail' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $result['rows'] as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['email'] ); ?></td>
							<td><?php echo esc_html( $row['name'] ); ?></td>
							<td><?php echo esc_html( self::source_label( $row['source'] ) ); ?></td>
							<td class="a2email-muted"><?php echo esc_html( $row['consent_note'] ); ?></td>
							<td><span class="a2email-pill a2email-pill--<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( $row['status'] ); ?></span><?php if ( Contacts::STATUS_SUBSCRIBED === $row['status'] && Contacts::has_tag( $row['tags'], Contacts::TAG_WEEKLY ) ) : ?> <span class="a2email-pill a2email-pill--queued"><?php esc_html_e( 'Weekly', 'a2e-mail' ); ?></span><?php endif; ?></td>
							<td class="a2email-row-actions">
								<?php if ( Contacts::STATUS_SUBSCRIBED === $row['status'] ) : ?>
									<a href="<?php echo esc_url( admin_page_url( 'compose' ) ); ?>"><?php esc_html_e( 'Email', 'a2e-mail' ); ?></a>
									<a class="a2email-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a2email_unsub_contact&id=' . $row['id'] ), 'a2email_unsub_contact' ) ); ?>"><?php esc_html_e( 'Unsubscribe', 'a2e-mail' ); ?></a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<?php
			$pages = (int) ceil( $result['total'] / 50 );
			if ( $pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) );
				echo '</div></div>';
			}
			?>
		</div>
		<?php
	}

	/**
	 * Source label.
	 *
	 * @param string $source Source.
	 * @return string
	 */
	protected static function source_label( $source ) {
		$map = array( 'client' => __( 'Client', 'a2e-mail' ), 'newsletter' => __( 'Newsletter', 'a2e-mail' ), 'quote' => __( 'Audit request', 'a2e-mail' ), 'consultation' => __( 'Consultation', 'a2e-mail' ), 'contact' => __( 'Contact form', 'a2e-mail' ), 'health_check' => __( 'Health checker', 'a2e-mail' ), 'manual' => __( 'Added by hand', 'a2e-mail' ) );
		return isset( $map[ $source ] ) ? $map[ $source ] : $source;
	}

	/**
	 * Add a contact by hand.
	 */
	public static function add_contact() {
		check_admin_referer( 'a2email_add_contact' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		if ( empty( $_POST['confirm'] ) ) {
			self::back( 'contacts', 'error', __( 'Please confirm you have permission to email this person.', 'a2e-mail' ) );
		}
		$res = Contacts::add_manual(
			isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '',
			empty( $_POST['weekly'] ) ? '' : Contacts::TAG_WEEKLY
		);
		if ( ! $res['ok'] ) {
			self::back( 'contacts', 'error', $res['message'] );
		}
		// Start "you add a contact by hand" automations and send the welcome email now.
		$auto    = Automations::start( (int) $res['id'], 'manual' );
		$contact = Contacts::get( (int) $res['id'] );
		$msg     = $res['message'];
		if ( $auto['sent'] ) {
			/* translators: %s: email */
			$msg .= ' ' . sprintf( __( 'Your welcome email was sent to %s.', 'a2e-mail' ), $contact['email'] );
		} elseif ( $auto['enrolled'] ) {
			$msg .= ' ' . __( 'Their welcome email is queued and goes out within a few minutes.', 'a2e-mail' );
		}
		self::back( 'contacts', 'success', $msg );
	}

	/**
	 * Admin unsubscribes a contact.
	 */
	public static function unsub_contact() {
		check_admin_referer( 'a2email_unsub_contact' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		Contacts::unsubscribe( $id, 'admin' );
		self::back( 'contacts', 'success', __( 'Contact unsubscribed and suppressed.', 'a2e-mail' ) );
	}

	/**
	 * Settings screen.
	 */
	public static function render_settings() {
		$s   = settings();
		$dns = Mailer::dns_report();
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'Settings', 'a2e-mail' ); ?></h1>
			<?php self::print_notice(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'a2email_settings' ); ?>
				<input type="hidden" name="action" value="a2email_settings">

				<h2 class="a2email-h2"><?php esc_html_e( 'Who the emails come from', 'a2e-mail' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><label for="from_name"><?php esc_html_e( 'From name', 'a2e-mail' ); ?></label></th><td><input type="text" id="from_name" name="from_name" class="regular-text" value="<?php echo esc_attr( $s['from_name'] ); ?>"></td></tr>
					<tr><th><label for="from_email"><?php esc_html_e( 'From email', 'a2e-mail' ); ?></label></th><td><input type="email" id="from_email" name="from_email" class="regular-text" value="<?php echo esc_attr( $s['from_email'] ); ?>"><p class="description"><?php esc_html_e( 'For example support@a2edropshipping.com. Use an address on your own domain so your emails pass checks.', 'a2e-mail' ); ?></p></td></tr>
					<tr><th><label for="reply_to"><?php esc_html_e( 'Reply-to (optional)', 'a2e-mail' ); ?></label></th><td><input type="email" id="reply_to" name="reply_to" class="regular-text" value="<?php echo esc_attr( $s['reply_to'] ); ?>"></td></tr>
				</table>

				<h2 class="a2email-h2"><?php esc_html_e( 'Your details (shown in every email)', 'a2e-mail' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><label for="business_name"><?php esc_html_e( 'Business name', 'a2e-mail' ); ?></label></th><td><input type="text" id="business_name" name="business_name" class="regular-text" value="<?php echo esc_attr( $s['business_name'] ); ?>"></td></tr>
					<tr><th><label for="whatsapp"><?php esc_html_e( 'WhatsApp number', 'a2e-mail' ); ?></label></th><td><input type="text" id="whatsapp" name="whatsapp" class="regular-text" value="<?php echo esc_attr( $s['whatsapp'] ); ?>" placeholder="+1 206 776 4962"></td></tr>
					<tr><th><label for="phone"><?php esc_html_e( 'Call number', 'a2e-mail' ); ?></label></th><td><input type="text" id="phone" name="phone" class="regular-text" value="<?php echo esc_attr( $s['phone'] ); ?>"></td></tr>
					<tr><th><label for="contact_email"><?php esc_html_e( 'Contact email', 'a2e-mail' ); ?></label></th><td><input type="email" id="contact_email" name="contact_email" class="regular-text" value="<?php echo esc_attr( $s['contact_email'] ); ?>"></td></tr>
					<tr><th><label for="postal_address"><?php esc_html_e( 'Postal address', 'a2e-mail' ); ?></label></th><td><input type="text" id="postal_address" name="postal_address" class="large-text" value="<?php echo esc_attr( $s['postal_address'] ); ?>"><p class="description"><?php esc_html_e( 'A real mailing address in the footer is required by anti-spam law and helps your emails reach the inbox.', 'a2e-mail' ); ?></p></td></tr>
				</table>

				<h2 class="a2email-h2"><?php esc_html_e( 'Sending speed (protects your domain)', 'a2e-mail' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr><th><label for="per_minute"><?php esc_html_e( 'Emails per minute', 'a2e-mail' ); ?></label></th><td><input type="number" min="1" id="per_minute" name="per_minute" value="<?php echo esc_attr( $s['per_minute'] ); ?>" class="small-text"></td></tr>
					<tr><th><label for="per_hour"><?php esc_html_e( 'Emails per hour', 'a2e-mail' ); ?></label></th><td><input type="number" min="1" id="per_hour" name="per_hour" value="<?php echo esc_attr( $s['per_hour'] ); ?>" class="small-text"></td></tr>
					<tr><th><label for="per_day"><?php esc_html_e( 'Emails per day', 'a2e-mail' ); ?></label></th><td><input type="number" min="1" id="per_day" name="per_day" value="<?php echo esc_attr( $s['per_day'] ); ?>" class="small-text"><p class="description"><?php esc_html_e( 'Start low on a new domain (for example 200 a day) and raise it slowly as your reputation builds.', 'a2e-mail' ); ?></p></td></tr>
				</table>

				<h2 class="a2email-h2"><?php esc_html_e( 'Sending server (SMTP)', 'a2e-mail' ); ?></h2>
				<p class="description"><?php esc_html_e( 'By default emails use your WordPress host. For better delivery, send through a provider such as your own mailbox host, Amazon SES, Postmark or similar. Leave this off if your host already sends reliably.', 'a2e-mail' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Use SMTP', 'a2e-mail' ); ?></th><td><label><input type="checkbox" name="smtp_enabled" value="1" <?php checked( '1', $s['smtp_enabled'] ); ?>> <?php esc_html_e( 'Send through an SMTP server', 'a2e-mail' ); ?></label></td></tr>
					<tr><th><label for="smtp_host"><?php esc_html_e( 'Host', 'a2e-mail' ); ?></label></th><td><input type="text" id="smtp_host" name="smtp_host" class="regular-text" value="<?php echo esc_attr( $s['smtp_host'] ); ?>" placeholder="smtp.yourhost.com"></td></tr>
					<tr><th><label for="smtp_port"><?php esc_html_e( 'Port', 'a2e-mail' ); ?></label></th><td><input type="number" id="smtp_port" name="smtp_port" class="small-text" value="<?php echo esc_attr( $s['smtp_port'] ); ?>"> <select name="smtp_secure"><?php foreach ( array( 'tls' => 'TLS', 'ssl' => 'SSL', 'none' => __( 'None', 'a2e-mail' ) ) as $k => $l ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['smtp_secure'], $k ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></td></tr>
					<tr><th><label for="smtp_user"><?php esc_html_e( 'Username', 'a2e-mail' ); ?></label></th><td><input type="text" id="smtp_user" name="smtp_user" class="regular-text" value="<?php echo esc_attr( $s['smtp_user'] ); ?>" autocomplete="off"></td></tr>
					<tr><th><label for="smtp_pass"><?php esc_html_e( 'Password', 'a2e-mail' ); ?></label></th><td><input type="password" id="smtp_pass" name="smtp_pass" class="regular-text" value="" autocomplete="new-password" placeholder="<?php echo $s['smtp_pass'] ? esc_attr__( 'saved; leave blank to keep', 'a2e-mail' ) : ''; ?>"><p class="description"><?php esc_html_e( 'Stored encrypted. You can also define A2EMAIL_SMTP_PASS in wp-config.php.', 'a2e-mail' ); ?></p></td></tr>
					<tr><th><?php esc_html_e( 'Website emails', 'a2e-mail' ); ?></th><td><label><input type="checkbox" name="site_mail" value="1" <?php checked( '1', $s['site_mail'] ); ?>> <?php esc_html_e( 'Use these settings for every email this site sends (contact form notifications, Health Checker reports, WordPress emails)', 'a2e-mail' ); ?></label><p class="description"><?php esc_html_e( 'Recommended. Otherwise WordPress sends them from "wordpress@" your domain through the server\'s basic mail function, which is often rejected or sent to spam.', 'a2e-mail' ); ?></p></td></tr>
				</table>

				<?php submit_button( __( 'Save settings', 'a2e-mail' ) ); ?>
			</form>

			<h2 class="a2email-h2"><?php esc_html_e( 'Deliverability check', 'a2e-mail' ); ?></h2>
			<div class="a2email-card">
				<?php if ( $dns['checked'] ) : ?>
					<p><?php printf( esc_html__( 'Domain: %s', 'a2e-mail' ), '<code>' . esc_html( $dns['domain'] ) . '</code>' ); // phpcs:ignore ?></p>
					<ul class="a2email-dns">
						<li><?php echo $dns['spf'] ? '✅' : '⚠️'; ?> <?php esc_html_e( 'SPF record', 'a2e-mail' ); ?> <?php echo $dns['spf'] ? '' : '<span class="a2email-muted">' . esc_html__( 'not found; add an SPF TXT record for your sender', 'a2e-mail' ) . '</span>'; // phpcs:ignore ?></li>
						<li><?php echo $dns['dmarc'] ? '✅' : '⚠️'; ?> <?php esc_html_e( 'DMARC record', 'a2e-mail' ); ?> <?php echo $dns['dmarc'] ? '' : '<span class="a2email-muted">' . esc_html__( 'not found; add a DMARC policy to protect your domain', 'a2e-mail' ) . '</span>'; // phpcs:ignore ?></li>
						<li>ℹ️ <?php esc_html_e( 'DKIM is set up at your email provider; make sure it is signing for this domain.', 'a2e-mail' ); ?></li>
					</ul>
				<?php else : ?>
					<p class="a2email-muted"><?php esc_html_e( 'DNS lookups are not available on this server, so SPF and DMARC cannot be checked here. Confirm them with your email provider.', 'a2e-mail' ); ?></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-inline">
					<?php wp_nonce_field( 'a2email_test_smtp' ); ?>
					<input type="hidden" name="action" value="a2email_test_smtp">
					<input type="email" name="to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
					<button type="submit" class="button"><?php esc_html_e( 'Send a test email', 'a2e-mail' ); ?></button>
				</form>
			</div>

			<?php Site_Mail::render_panel(); ?>
		</div>
		<?php
	}

	/**
	 * Save settings.
	 */
	public static function save_settings() {
		check_admin_referer( 'a2email_settings' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$in   = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$text = function ( $key ) use ( $in ) {
			return isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : '';
		};
		$values = array(
			'from_name'      => $text( 'from_name' ),
			'from_email'     => isset( $in['from_email'] ) ? sanitize_email( $in['from_email'] ) : '',
			'reply_to'       => isset( $in['reply_to'] ) ? sanitize_email( $in['reply_to'] ) : '',
			'business_name'  => $text( 'business_name' ),
			'whatsapp'       => $text( 'whatsapp' ),
			'phone'          => $text( 'phone' ),
			'contact_email'  => isset( $in['contact_email'] ) ? sanitize_email( $in['contact_email'] ) : '',
			'postal_address' => $text( 'postal_address' ),
			'per_minute'     => (string) max( 1, (int) ( $in['per_minute'] ?? 20 ) ),
			'per_hour'       => (string) max( 1, (int) ( $in['per_hour'] ?? 200 ) ),
			'per_day'        => (string) max( 1, (int) ( $in['per_day'] ?? 1000 ) ),
			'smtp_enabled'   => empty( $in['smtp_enabled'] ) ? '0' : '1',
			'smtp_host'      => $text( 'smtp_host' ),
			'smtp_port'      => (string) max( 1, (int) ( $in['smtp_port'] ?? 587 ) ),
			'smtp_secure'    => in_array( ( $in['smtp_secure'] ?? 'tls' ), array( 'tls', 'ssl', 'none' ), true ) ? $in['smtp_secure'] : 'tls',
			'smtp_user'      => $text( 'smtp_user' ),
			'site_mail'      => empty( $in['site_mail'] ) ? '0' : '1',
		);
		if ( ! empty( $in['smtp_pass'] ) ) {
			$values['smtp_pass'] = encrypt( $in['smtp_pass'] );
		}
		update_settings( $values );
		self::back( 'settings', 'success', __( 'Settings saved.', 'a2e-mail' ) );
	}

	/**
	 * Send a test email from settings.
	 */
	public static function test_smtp() {
		check_admin_referer( 'a2email_test_smtp' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$res = Mailer::send_test( isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '' );
		self::back( 'settings', $res['ok'] ? 'success' : 'error', $res['message'] );
	}

	/**
	 * Template list columns.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function template_columns( $cols ) {
		$new = array( 'cb' => $cols['cb'], 'title' => __( 'Template', 'a2e-mail' ), 'a2email_subject' => __( 'Subject', 'a2e-mail' ) );
		return $new;
	}

	/**
	 * Template subject/preheader/category meta box.
	 */
	public static function template_meta_box() {
		add_meta_box( 'a2email_template', __( 'Email details', 'a2e-mail' ), array( __CLASS__, 'render_template_box' ), TEMPLATE, 'normal', 'high' );
	}

	/**
	 * Render the template meta box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_template_box( $post ) {
		wp_nonce_field( 'a2email_template', 'a2email_template_nonce' );
		$subject = get_post_meta( $post->ID, '_subject', true );
		$pre     = get_post_meta( $post->ID, '_preheader', true );
		echo '<p><label><strong>' . esc_html__( 'Subject line', 'a2e-mail' ) . '</strong><br><input type="text" name="a2email_subject" class="widefat" value="' . esc_attr( $subject ) . '"></label></p>';
		echo '<p><label><strong>' . esc_html__( 'Preview text', 'a2e-mail' ) . '</strong><br><input type="text" name="a2email_preheader" class="widefat" value="' . esc_attr( $pre ) . '"></label></p>';
		echo '<p class="description">' . esc_html__( 'Write the message in the main editor using plain text: blank line between paragraphs, ## for a heading, - for a bullet, [[Label|https://link]] for a button, and merge tags like {{first_name}}.', 'a2e-mail' ) . '</p>';
	}

	/**
	 * Save template meta.
	 *
	 * @param int $post_id Post id.
	 */
	public static function save_template( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$nonce = isset( $_POST['a2email_template_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['a2email_template_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'a2email_template' ) ) {
			return;
		}
		if ( isset( $_POST['a2email_subject'] ) ) {
			update_post_meta( $post_id, '_subject', sanitize_text_field( wp_unslash( $_POST['a2email_subject'] ) ) );
		}
		if ( isset( $_POST['a2email_preheader'] ) ) {
			update_post_meta( $post_id, '_preheader', sanitize_text_field( wp_unslash( $_POST['a2email_preheader'] ) ) );
		}
	}
}
