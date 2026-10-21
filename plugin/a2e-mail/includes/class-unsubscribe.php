<?php
/**
 * Unsubscribe: a public endpoint that honours one-click unsubscribe (RFC 8058) and a friendly
 * confirmation page for people who click the footer link. Unsubscribed people are suppressed
 * for good.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unsubscribe.
 */
class Unsubscribe {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_post_nopriv_a2email_unsub', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_a2email_unsub', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_a2email_resub', array( __CLASS__, 'resubscribe' ) );
		add_action( 'admin_post_a2email_resub', array( __CLASS__, 'resubscribe' ) );
	}

	/**
	 * The unsubscribe URL for a token.
	 *
	 * @param string $token Token.
	 * @return string
	 */
	public static function url( $token ) {
		return add_query_arg(
			array( 'action' => 'a2email_unsub', 't' => rawurlencode( $token ) ),
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Handle a click (GET shows a confirm page) or a one-click POST (unsubscribes at once).
	 */
	public static function handle() {
		// The token comes in the link (GET, and the inbox one-click POST) or from the confirm form.
		$token = isset( $_REQUEST['t'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post  = 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET' );

		if ( 'preview' === $token ) {
			self::page( __( 'This is a preview', 'a2e-mail' ), __( 'This was a test email, so there is nothing to unsubscribe.', 'a2e-mail' ) );
		}
		$contact = Contacts::get_by_token( $token );
		if ( ! $contact ) {
			self::page( __( 'Link not recognised', 'a2e-mail' ), __( 'This unsubscribe link is not valid. If you keep getting emails, reply to one and we will remove you by hand.', 'a2e-mail' ) );
		}

		if ( $post && isset( $_POST['scope'] ) && 'weekly' === sanitize_key( wp_unslash( $_POST['scope'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			Contacts::remove_tag( (int) $contact['id'], Contacts::TAG_WEEKLY );
			log( 'unsub', sprintf( '%s left the weekly newsletter.', $contact['email'] ) );
			self::page( __( 'You have left the weekly newsletter', 'a2e-mail' ), sprintf( __( 'Done. %s will no longer get the weekly newsletter. We will still reply to anything you ask us, and you can unsubscribe from all emails with the link in any message.', 'a2e-mail' ), $contact['email'] ) );
		}

		if ( $post ) {
			Contacts::unsubscribe( (int) $contact['id'] );
			log( 'unsub', sprintf( 'Unsubscribed %s.', $contact['email'] ) );
			self::done( $contact );
		}

		// GET: confirmation page with a one-click button.
		self::confirm( $contact );
	}

	/**
	 * Resubscribe (from the "done" page, in case of a mistake).
	 */
	public static function resubscribe() {
		$token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$contact = Contacts::get_by_token( $token );
		if ( $contact && Contacts::STATUS_BOUNCED !== $contact['status'] ) {
			global $wpdb;
			$wpdb->update( Contacts::table(), array( 'status' => Contacts::STATUS_SUBSCRIBED, 'unsubscribed_at' => null, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $contact['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::page( __( 'You are subscribed again', 'a2e-mail' ), __( 'Welcome back. You will keep receiving our emails. You can unsubscribe at any time from the link in any message.', 'a2e-mail' ) );
		}
		self::page( __( 'Link not recognised', 'a2e-mail' ), __( 'This link is not valid.', 'a2e-mail' ) );
	}

	/**
	 * Confirmation page (GET).
	 *
	 * @param array $contact Contact.
	 */
	protected static function confirm( $contact ) {
		$action = esc_url( admin_url( 'admin-post.php' ) );
		$body   = '<p>' . esc_html( sprintf( __( 'Do you want to stop receiving emails at %s?', 'a2e-mail' ), $contact['email'] ) ) . '</p>'
			. '<form method="post" action="' . $action . '" style="margin-top:18px;">'
			. '<input type="hidden" name="action" value="a2email_unsub">'
			. '<input type="hidden" name="t" value="' . esc_attr( $contact['token'] ) . '">'
			. '<button type="submit" class="a2e-btn">' . esc_html__( 'Yes, unsubscribe me', 'a2e-mail' ) . '</button>'
			. '</form>';
		if ( Contacts::STATUS_SUBSCRIBED === $contact['status'] && Contacts::has_tag( $contact, Contacts::TAG_WEEKLY ) ) {
			$body .= '<form method="post" action="' . $action . '" style="margin-top:14px;">'
				. '<input type="hidden" name="action" value="a2email_unsub">'
				. '<input type="hidden" name="t" value="' . esc_attr( $contact['token'] ) . '">'
				. '<input type="hidden" name="scope" value="weekly">'
				. '<button type="submit" style="border:0;background:none;padding:0;color:#2b4cf2;font-size:15px;text-decoration:underline;cursor:pointer;">' . esc_html__( 'Only stop the weekly newsletter', 'a2e-mail' ) . '</button>'
				. '</form>';
		}
		self::page( __( 'Unsubscribe', 'a2e-mail' ), $body, true );
	}

	/**
	 * Done page with a resubscribe option.
	 *
	 * @param array $contact Contact.
	 */
	protected static function done( $contact ) {
		$resub = add_query_arg( array( 'action' => 'a2email_resub', 't' => rawurlencode( $contact['token'] ) ), admin_url( 'admin-post.php' ) );
		$body  = '<p>' . esc_html( sprintf( __( 'Done. We have removed %s from our emails.', 'a2e-mail' ), $contact['email'] ) ) . '</p>'
			. '<p style="margin-top:10px;font-size:14px;color:#7c83a2;">' . esc_html__( 'Unsubscribed by mistake?', 'a2e-mail' ) . ' <a href="' . esc_url( $resub ) . '">' . esc_html__( 'Resubscribe', 'a2e-mail' ) . '</a></p>';
		self::page( __( 'You are unsubscribed', 'a2e-mail' ), $body, true );
	}

	/**
	 * Post HTML plus the small forms these pages use (wp_kses_post alone strips form and input,
	 * which would leave the confirm buttons doing nothing).
	 *
	 * @return array
	 */
	protected static function allowed_html() {
		$tags           = wp_kses_allowed_html( 'post' );
		$tags['form']   = array( 'method' => true, 'action' => true, 'style' => true );
		$tags['input']  = array( 'type' => true, 'name' => true, 'value' => true );
		$tags['button'] = array( 'type' => true, 'class' => true, 'style' => true, 'name' => true, 'value' => true );
		return $tags;
	}

	/**
	 * A minimal branded page.
	 *
	 * @param string $title Title.
	 * @param string $body  HTML body.
	 * @param bool   $raw   True if body is already HTML.
	 */
	public static function page( $title, $body, $raw = false ) {
		$c        = Templates::palette();
		$business = setting( 'business_name' );
		if ( ! $raw ) {
			$body = '<p>' . esc_html( $body ) . '</p>';
		}
		nocache_headers();
		status_header( 200 );
		?><!doctype html>
<html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html( $title . ' | ' . $business ); ?></title>
<style>
body{margin:0;background:<?php echo esc_attr( $c['bg'] ); ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:<?php echo esc_attr( $c['ink'] ); ?>;}
.wrap{max-width:520px;margin:8vh auto;padding:0 20px;}
.card{background:<?php echo esc_attr( $c['surface'] ); ?>;border:1px solid <?php echo esc_attr( $c['line'] ); ?>;border-radius:16px;overflow:hidden;box-shadow:0 20px 50px -30px rgba(20,30,90,.35);}
.head{padding:20px 28px;background:linear-gradient(120deg,<?php echo esc_attr( $c['brand2'] ); ?>,<?php echo esc_attr( $c['brand'] ); ?> 55%,<?php echo esc_attr( $c['brand3'] ); ?>);color:#fff;font-size:20px;font-weight:800;}
.body{padding:26px 28px;font-size:16px;line-height:1.6;color:<?php echo esc_attr( $c['muted'] ); ?>;}
.body h1{margin:0 0 12px;font-size:21px;color:<?php echo esc_attr( $c['ink'] ); ?>;}
.a2e-btn{display:inline-block;padding:12px 24px;border:0;border-radius:10px;background:linear-gradient(120deg,<?php echo esc_attr( $c['brand2'] ); ?>,<?php echo esc_attr( $c['brand'] ); ?>);color:#fff;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;}
a{color:<?php echo esc_attr( $c['brand'] ); ?>;}
</style></head>
<body><div class="wrap"><div class="card"><div class="head"><?php echo esc_html( $business ); ?></div><div class="body"><h1><?php echo esc_html( $title ); ?></h1><?php echo wp_kses( $body, self::allowed_html() ); ?></div></div></div></body></html>
		<?php
		exit;
	}
}
