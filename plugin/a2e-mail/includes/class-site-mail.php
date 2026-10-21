<?php
/**
 * Every email the site sends (contact form notifications, Health Checker reports,
 * password resets...) goes out with your From address and, when it is set up, your SMTP
 * server, the same way A2E Mail's own emails do. Without this, WordPress sends them from
 * "wordpress@yourdomain" through the server's basic mail function, which mailbox
 * providers often reject or put in spam, and nobody is told.
 *
 * Also keeps a short delivery log of those emails, with the exact error when one fails.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Site-wide sending.
 */
class Site_Mail {

	const LOG  = 'a2email_delivery';
	const FAIL = 'a2email_last_fail';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'phpmailer_init', array( __CLASS__, 'phpmailer' ), 98 );
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'succeeded' ) );
		add_action( 'wp_mail_failed', array( __CLASS__, 'failed' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_post_a2email_test_notify', array( __CLASS__, 'test_action' ) );
	}

	/**
	 * Is site-wide sending switched on?
	 *
	 * @return bool
	 */
	public static function on() {
		return '1' === setting( 'site_mail' );
	}

	/**
	 * Apply the sending settings to a site email (A2E Mail's own sends handle themselves).
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer.
	 */
	public static function phpmailer( $phpmailer ) {
		if ( Mailer::$sending || ! self::on() ) {
			return;
		}
		$smtp = Mailer::smtp_on();
		if ( $smtp ) {
			Mailer::transport( $phpmailer ); // SMTP, plus From alignment when "keep aligned" is on.
		}
		// Replace WordPress's made-up sender with your real address. Mail servers check that
		// the sender exists and belongs to the domain; "wordpress@" usually does not.
		$from    = Mailer::from_email();
		$current = strtolower( (string) $phpmailer->From ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
		if ( is_email( $from ) && ( '' === $current || 0 === strpos( $current, 'wordpress@' ) ) ) {
			$phpmailer->setFrom( $from, Mailer::from_name(), false );
			$phpmailer->Sender = $from; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
		}
	}

	/**
	 * How site emails are being sent right now.
	 *
	 * @return string
	 */
	public static function route() {
		if ( self::on() && Mailer::smtp_on() ) {
			/* translators: %s: SMTP host */
			return sprintf( __( 'SMTP (%s)', 'a2e-mail' ), setting( 'smtp_host' ) );
		}
		return __( 'Server mail function', 'a2e-mail' );
	}

	/**
	 * Record a site email in the delivery log.
	 *
	 * @param array  $data  wp_mail data (to, subject...).
	 * @param bool   $ok    Sent.
	 * @param string $error Error message.
	 */
	protected static function record( $data, $ok, $error = '' ) {
		$to      = isset( $data['to'] ) ? implode( ', ', (array) $data['to'] ) : '';
		$subject = isset( $data['subject'] ) ? (string) $data['subject'] : '';
		$entry   = array(
			't'  => time(),
			'to' => sanitize_text_field( $to ),
			's'  => sanitize_text_field( wp_specialchars_decode( $subject, ENT_QUOTES ) ),
			'ok' => $ok ? 1 : 0,
			'e'  => sanitize_text_field( $error ),
			'v'  => self::route(),
		);
		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? $log : array();
		array_unshift( $log, $entry );
		update_option( self::LOG, array_slice( $log, 0, 100 ), false );
		if ( $ok ) {
			delete_option( self::FAIL );
		} else {
			update_option( self::FAIL, $entry, false );
		}
	}

	/**
	 * A site email was handed over successfully.
	 *
	 * @param array $data Mail data.
	 */
	public static function succeeded( $data ) {
		if ( ! Mailer::$sending ) {
			self::record( (array) $data, true );
		}
	}

	/**
	 * A site email failed.
	 *
	 * @param \WP_Error $error Error.
	 */
	public static function failed( $error ) {
		if ( Mailer::$sending || ! is_wp_error( $error ) ) {
			return; // A2E Mail tracks its own sends in Messages.
		}
		self::record( (array) $error->get_error_data(), false, $error->get_error_message() );
	}

	/**
	 * Where contact form notifications are sent.
	 *
	 * @return string
	 */
	public static function lead_address() {
		$to = function_exists( 'a2e_opt' ) ? (string) a2e_opt( 'lead_email' ) : '';
		return is_email( $to ) ? $to : (string) get_option( 'admin_email' );
	}

	/**
	 * Send a notification exactly like the contact form does, to the same address.
	 *
	 * @return array ok, message.
	 */
	public static function send_test_notification() {
		$to    = self::lead_address();
		$error = '';
		$catch = function ( $e ) use ( &$error ) {
			$error = $e->get_error_message();
		};
		add_action( 'wp_mail_failed', $catch );
		$ok = wp_mail(
			$to,
			sprintf( '[%s] %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), __( 'Test notification', 'a2e-mail' ) ),
			__( 'This is a test of the notifications your website forms send.', 'a2e-mail' ) . "\n\n"
				. __( 'If it reached you, contact form messages will reach you at this address too.', 'a2e-mail' ) . "\n\n"
				. admin_url( 'edit.php?post_type=a2e_lead' ),
			array( 'Reply-To: ' . get_bloginfo( 'name' ) . ' <' . Mailer::from_email() . '>' )
		);
		remove_action( 'wp_mail_failed', $catch );
		if ( $ok ) {
			$tip = ( self::on() && Mailer::smtp_on() )
				? __( 'If it is not there in a few minutes, check spam, then check the SMTP details above.', 'a2e-mail' )
				: __( 'If it is not there in a few minutes, the receiving server refused it: turn on SMTP above with your mailbox details.', 'a2e-mail' );
			/* translators: 1: address 2: route 3: advice */
			return array( 'ok' => true, 'message' => sprintf( __( 'Test notification sent to %1$s via %2$s. Check that inbox. %3$s', 'a2e-mail' ), $to, self::route(), $tip ) );
		}
		/* translators: 1: address 2: error */
		return array( 'ok' => false, 'message' => sprintf( __( 'The test notification to %1$s could not be sent: %2$s', 'a2e-mail' ), $to, $error ? $error : __( 'the server refused to send it.', 'a2e-mail' ) ) );
	}

	/**
	 * Settings button handler.
	 */
	public static function test_action() {
		check_admin_referer( 'a2email_test_notify' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$res = self::send_test_notification();
		set_transient( 'a2email_notice_' . get_current_user_id(), array( $res['ok'] ? 'success' : 'error', $res['message'] ), 60 );
		wp_safe_redirect( admin_page_url( 'settings' ) . '#a2email-delivery' );
		exit;
	}

	/**
	 * Warn on the dashboard and A2E Mail screens when a site email failed recently.
	 */
	public static function notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( 'dashboard' !== $screen->id && false === strpos( (string) $screen->id, 'a2e-mail' ) ) ) {
			return;
		}
		$fail = get_option( self::FAIL );
		if ( ! is_array( $fail ) || empty( $fail['t'] ) || ( time() - (int) $fail['t'] ) > 7 * DAY_IN_SECONDS ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
			esc_html__( 'A website email could not be sent.', 'a2e-mail' ),
			/* translators: 1: subject 2: recipient 3: error */
			esc_html( sprintf( __( '"%1$s" to %2$s: %3$s', 'a2e-mail' ), $fail['s'], $fail['to'], $fail['e'] ) ),
			esc_url( admin_page_url( 'settings' ) . '#a2email-delivery' ),
			esc_html__( 'Fix sending settings', 'a2e-mail' )
		);
	}

	/**
	 * The delivery log table and test button (on the Settings screen).
	 */
	public static function render_panel() {
		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? array_slice( $log, 0, 25 ) : array();
		?>
		<h2 class="a2email-h2" id="a2email-delivery"><?php esc_html_e( 'Website emails (form notifications)', 'a2e-mail' ); ?></h2>
		<div class="a2email-card">
			<p>
				<?php
				printf(
					/* translators: 1: address 2: route */
					esc_html__( 'Contact, quote and consultation form messages are emailed to %1$s and are currently sent via %2$s.', 'a2e-mail' ),
					'<strong>' . esc_html( self::lead_address() ) . '</strong>',
					'<strong>' . esc_html( self::route() ) . '</strong>'
				);
				?>
				<?php if ( function_exists( 'a2e_opt' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=a2e-settings' ) ); ?>"><?php esc_html_e( 'Change the address in A2E > Site Settings ("Send new leads to")', 'a2e-mail' ); ?></a>
				<?php endif; ?>
			</p>
			<?php if ( ! Mailer::smtp_on() || ! self::on() ) : ?>
				<p class="a2email-muted"><?php esc_html_e( 'Tip: turn on SMTP above with your support@ mailbox details and keep "Use for every email this site sends" ticked. That is the reliable way for form notifications to arrive.', 'a2e-mail' ); ?></p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-inline">
				<?php wp_nonce_field( 'a2email_test_notify' ); ?>
				<input type="hidden" name="action" value="a2email_test_notify">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Send a test notification', 'a2e-mail' ); ?></button>
				<span class="a2email-muted"><?php esc_html_e( 'Sent exactly the way your contact form sends, to the address above.', 'a2e-mail' ); ?></span>
			</form>
			<h3><?php esc_html_e( 'Recent website emails', 'a2e-mail' ); ?></h3>
			<?php if ( ! $log ) : ?>
				<p class="a2email-muted"><?php esc_html_e( 'Nothing recorded yet. Website emails are listed here from now on, with the reason if one fails.', 'a2e-mail' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'When', 'a2e-mail' ); ?></th><th><?php esc_html_e( 'To', 'a2e-mail' ); ?></th><th><?php esc_html_e( 'Subject', 'a2e-mail' ); ?></th><th><?php esc_html_e( 'Result', 'a2e-mail' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( $log as $row ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $row['t'] ) ); ?></td>
							<td><?php echo esc_html( $row['to'] ); ?></td>
							<td><?php echo esc_html( $row['s'] ); ?></td>
							<td>
								<?php if ( $row['ok'] ) : ?>
									<span style="color:#116b3a;font-weight:600;"><?php esc_html_e( 'Sent', 'a2e-mail' ); ?></span> <span class="a2email-muted"><?php echo esc_html( $row['v'] ); ?></span>
								<?php else : ?>
									<span style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'Failed', 'a2e-mail' ); ?></span> <span class="a2email-muted"><?php echo esc_html( $row['e'] ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p class="a2email-muted"><?php esc_html_e( '"Sent" means your server or SMTP provider accepted the email. If one shows Sent but never arrived, check spam; with SMTP on this is rare.', 'a2e-mail' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
