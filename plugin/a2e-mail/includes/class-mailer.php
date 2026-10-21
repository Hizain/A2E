<?php
/**
 * Sending: applies your SMTP settings, forces a consistent From address, and adds the
 * List-Unsubscribe headers that let inboxes offer one-click unsubscribe (which keeps your
 * sending domain trusted).
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mailer.
 */
class Mailer {

	/**
	 * The unsubscribe URL for the email currently being sent (for the List-Unsubscribe header).
	 *
	 * @var string
	 */
	protected static $current_unsub = '';

	/**
	 * True while A2E Mail is sending one of its own emails (site-wide hooks step aside).
	 *
	 * @var bool
	 */
	public static $sending = false;

	/**
	 * Send one email. Applies SMTP + headers around wp_mail.
	 *
	 * @param string $to        Recipient.
	 * @param string $subject   Subject.
	 * @param string $html      HTML body.
	 * @param string $text      Plain-text alternative.
	 * @param string $unsub_url One-click unsubscribe URL.
	 * @return bool
	 */
	public static function send( $to, $subject, $html, $text = '', $unsub_url = '' ) {
		self::$current_unsub = $unsub_url;

		add_filter( 'wp_mail_from', array( __CLASS__, 'from_email' ), 99 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'from_name' ), 99 );
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'content_type' ), 99 );
		add_action( 'phpmailer_init', array( __CLASS__, 'configure' ), 99 );

		$headers = array();
		if ( setting( 'reply_to' ) && is_email( setting( 'reply_to' ) ) ) {
			$headers[] = 'Reply-To: ' . setting( 'reply_to' );
		}
		if ( $text ) {
			add_action( 'phpmailer_init', array( __CLASS__, 'alt_body' ), 100 );
			$GLOBALS['a2email_alt_body'] = $text;
		}

		self::$sending = true;
		$ok            = wp_mail( $to, $subject, $html, $headers );
		self::$sending = false;

		remove_filter( 'wp_mail_from', array( __CLASS__, 'from_email' ), 99 );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'from_name' ), 99 );
		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'content_type' ), 99 );
		remove_action( 'phpmailer_init', array( __CLASS__, 'configure' ), 99 );
		remove_action( 'phpmailer_init', array( __CLASS__, 'alt_body' ), 100 );
		unset( $GLOBALS['a2email_alt_body'] );
		self::$current_unsub = '';

		return (bool) $ok;
	}

	/**
	 * From email.
	 *
	 * @return string
	 */
	public static function from_email() {
		$from = setting( 'from_email' );
		return is_email( $from ) ? $from : get_option( 'admin_email' );
	}

	/**
	 * From name.
	 *
	 * @return string
	 */
	public static function from_name() {
		$name = setting( 'from_name' );
		return $name ? $name : get_bloginfo( 'name' );
	}

	/**
	 * HTML content type.
	 *
	 * @return string
	 */
	public static function content_type() {
		return 'text/html';
	}

	/**
	 * Set the plain-text alternative body.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer.
	 */
	public static function alt_body( $phpmailer ) {
		if ( ! empty( $GLOBALS['a2email_alt_body'] ) ) {
			$phpmailer->AltBody = $GLOBALS['a2email_alt_body']; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
		}
	}

	/**
	 * Configure PHPMailer: SMTP (when enabled), a sensible From, and unsubscribe headers.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer.
	 */
	public static function configure( $phpmailer ) {
		self::transport( $phpmailer );
		self::marketing_headers( $phpmailer );
	}

	/**
	 * Is SMTP switched on and filled in?
	 *
	 * @return bool
	 */
	public static function smtp_on() {
		return '1' === setting( 'smtp_enabled' ) && '' !== (string) setting( 'smtp_host' );
	}

	/**
	 * Apply the sending server (SMTP) and align the From / envelope sender.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer.
	 */
	public static function transport( $phpmailer ) {
		if ( self::smtp_on() ) {
			$phpmailer->isSMTP();
			$phpmailer->Host       = setting( 'smtp_host' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
			$phpmailer->Port       = (int) setting( 'smtp_port' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
			$secure                = setting( 'smtp_secure' );
			$phpmailer->SMTPSecure = in_array( $secure, array( 'tls', 'ssl' ), true ) ? $secure : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
			$phpmailer->SMTPAutoTLS = 'none' !== $secure; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
			if ( setting( 'smtp_user' ) ) {
				$phpmailer->SMTPAuth = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
				$phpmailer->Username = setting( 'smtp_user' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
				$phpmailer->Password = smtp_pass(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
			}
		}

		// Keep the envelope sender aligned with the From domain where the host allows it.
		if ( '1' === setting( 'smtp_from_forced' ) ) {
			$from = self::from_email();
			if ( is_email( $from ) ) {
				$phpmailer->setFrom( $from, self::from_name(), false );
				$phpmailer->Sender = $from; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCase
			}
		}
	}

	/**
	 * Headers that only belong on A2E Mail's own emails (not on site notifications).
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer.
	 */
	public static function marketing_headers( $phpmailer ) {
		if ( self::$current_unsub ) {
			$phpmailer->addCustomHeader( 'List-Unsubscribe', '<' . self::$current_unsub . '>, <mailto:' . self::from_email() . '?subject=unsubscribe>' );
			$phpmailer->addCustomHeader( 'List-Unsubscribe-Post', 'List-Unsubscribe=One-Click' );
		}
		$phpmailer->addCustomHeader( 'X-Mailer', 'A2E Mail Studio' );
		$phpmailer->addCustomHeader( 'Precedence', 'bulk' );
	}

	/**
	 * Send a test email to a chosen address using the current settings.
	 *
	 * @param string $to Address.
	 * @return array ok, message.
	 */
	public static function send_test( $to ) {
		$to = sanitize_email( $to );
		if ( ! is_email( $to ) ) {
			return array( 'ok' => false, 'message' => __( 'Enter a valid email address to test.', 'a2e-mail' ) );
		}
		$contact = array( 'name' => __( 'there', 'a2e-mail' ), 'email' => $to, 'token' => 'preview' );
		$render  = Templates::render(
			array(
				'subject'   => __( 'Test email from A2E Mail Studio', 'a2e-mail' ),
				'preheader' => __( 'If you can read this, sending works.', 'a2e-mail' ),
				'body'      => __( "Hello {{first_name}},\n\nThis is a test email from A2E Mail Studio. If it has arrived and looks right, your sending settings are working.\n\n- WhatsApp: {{whatsapp}}\n- Call: {{phone}}\n- Email: {{contact_email}}", 'a2e-mail' ),
			),
			$contact
		);
		$error = '';
		$catch = function ( $wp_error ) use ( &$error ) {
			$error = $wp_error->get_error_message();
		};
		add_action( 'wp_mail_failed', $catch );
		$ok = self::send( $to, $render['subject'], $render['html'], $render['text'], Unsubscribe::url( 'preview' ) );
		remove_action( 'wp_mail_failed', $catch );
		if ( $ok ) {
			log( 'test', sprintf( 'Sent a test email to %s.', $to ) );
			return array( 'ok' => true, 'message' => sprintf( __( 'Test email sent to %s.', 'a2e-mail' ), $to ) );
		}
		return array( 'ok' => false, 'message' => $error ? $error : __( 'The test email could not be sent. Check your SMTP settings.', 'a2e-mail' ) );
	}

	/**
	 * Advisory DNS check for SPF, DKIM hint and DMARC on the From domain.
	 *
	 * @return array domain, spf, dmarc, dkim_hint, checked (bool)
	 */
	public static function dns_report() {
		$domain = strtolower( (string) substr( strrchr( self::from_email(), '@' ), 1 ) );
		$out    = array( 'domain' => $domain, 'spf' => null, 'dmarc' => null, 'checked' => false );
		if ( ! $domain || ! function_exists( 'dns_get_record' ) ) {
			return $out;
		}
		$out['checked'] = true;
		$txt = @dns_get_record( $domain, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$out['spf'] = false;
		foreach ( (array) $txt as $rec ) {
			if ( isset( $rec['txt'] ) && 0 === stripos( $rec['txt'], 'v=spf1' ) ) {
				$out['spf'] = true;
			}
		}
		$dmarc = @dns_get_record( '_dmarc.' . $domain, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$out['dmarc'] = false;
		foreach ( (array) $dmarc as $rec ) {
			if ( isset( $rec['txt'] ) && 0 === stripos( $rec['txt'], 'v=DMARC1' ) ) {
				$out['dmarc'] = true;
			}
		}
		return $out;
	}
}
