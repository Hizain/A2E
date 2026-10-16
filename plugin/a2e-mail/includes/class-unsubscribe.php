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
		$token = isset( $_GET['t'] ) ? sanitize_text_field( wp_unslash( $_GET['t'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post  = 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET' );

		if ( 'preview' === $token ) {
			self::page( __( 'This is a preview', 'a2e-mail' ), __( 'This was a test email, so there is nothing to unsubscribe.', 'a2e-mail' ) );
		}
		$contact = Contacts::get_by_token( $token );
		if ( ! $contact ) {
			self::page( __( 'Link not recognised', 'a2e-mail' ), __( 'This unsubscribe link is not valid. If you keep getting emails, reply to one and we will remove you by hand.', 'a2e-mail' ) );
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
	 * A minimal branded page.
	 *
	 * @param string $title Title.
	 * @param string $body  HTML body.
	 * @param bool   $raw   True if body is already HTML.
	 */
	protected static function page( $title, $body, $raw = false ) {
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
<body><div class="wrap"><div class="card"><div class="head"><?php echo esc_html( $business ); ?></div><div class="body"><h1><?php echo esc_html( $title ); ?></h1><?php echo wp_kses_post( $body ); ?></div></div></div></body></html>
		<?php
		exit;
	}
}
