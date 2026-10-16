<?php
/**
 * The "email me my report" capture. It validates (honeypot and fill-time), stores the lead,
 * emails you, and fires the same a2e_lead_received hook the A2E theme uses, so the A2E Mail
 * Studio plugin (when active) adds the contact and can enrol them in a follow-up sequence.
 *
 * @package A2E_Health
 */

namespace A2E\Health;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leads.
 */
class Leads {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'admin_post_nopriv_a2ehc_lead', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_a2ehc_lead', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Placeholder for future schema work.
	 */
	public static function install() {}

	/**
	 * Register the plugin's own lead store, used only when the A2E theme's Leads is not present.
	 */
	public static function register_cpt() {
		if ( post_type_exists( 'a2e_lead' ) ) {
			return;
		}
		register_post_type(
			CPT,
			array(
				'labels'       => array( 'name' => __( 'Health leads', 'a2e-health' ), 'singular_name' => __( 'Health lead', 'a2e-health' ) ),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'a2ehc-settings',
				'supports'     => array( 'title', 'editor' ),
				'menu_icon'    => 'dashicons-heart',
			)
		);
	}

	/**
	 * Handle a capture submission.
	 */
	public static function handle() {
		$is_ajax = ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) );
		$back    = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		$fail = function ( $msg ) use ( $is_ajax, $back ) {
			if ( $is_ajax ) {
				wp_send_json_error( array( 'message' => $msg ), 400 );
			}
			wp_safe_redirect( add_query_arg( 'a2ehc_error', '1', $back ) );
			exit;
		};

		// Honeypot and fill-time trap (no nonce, so it works on fully cached pages).
		if ( ! empty( $_POST['a2ehc_url'] ) ) {
			$fail( __( 'Something went wrong. Please try again.', 'a2e-health' ) );
		}
		$started = isset( $_POST['a2ehc_t'] ) ? absint( $_POST['a2ehc_t'] ) : 0;
		if ( $started && ( time() - $started ) < 3 ) {
			$fail( __( 'That was a little fast. Please try again.', 'a2e-health' ) );
		}
		// Light rate limit per IP.
		$ip_key = 'a2ehc_rl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x' );
		if ( (int) get_transient( $ip_key ) >= 6 ) {
			$fail( __( 'Too many tries. Please wait a few minutes.', 'a2e-health' ) );
		}
		set_transient( $ip_key, (int) get_transient( $ip_key ) + 1, 15 * MINUTE_IN_SECONDS );

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$report = isset( $_POST['report'] ) ? sanitize_textarea_field( wp_unslash( $_POST['report'] ) ) : '';
		if ( ! is_email( $email ) ) {
			$fail( __( 'Please enter a valid email address.', 'a2e-health' ) );
		}

		$title = sprintf( '%s: %s', __( 'Account health report request', 'a2e-health' ), $name ? $name : $email );
		$body  = __( 'Name', 'a2e-health' ) . ': ' . $name . "\n" . __( 'Email', 'a2e-health' ) . ': ' . $email . "\n\n" . $report;

		// Store the lead. Prefer the A2E theme's Leads so everything lives in one place.
		$lead_type = post_type_exists( 'a2e_lead' ) ? 'a2e_lead' : CPT;
		$lead_id   = wp_insert_post(
			array(
				'post_type'    => $lead_type,
				'post_status'  => 'private',
				'post_title'   => $title,
				'post_content' => $body,
			)
		);
		if ( $lead_id && ! is_wp_error( $lead_id ) ) {
			update_post_meta( $lead_id, '_a2e_email', $email );
			update_post_meta( $lead_id, '_a2e_form', 'health-check' );
			update_post_meta( $lead_id, '_a2e_report', $report );
		}

		// Email you.
		$to = setting( 'lead_email' );
		$to = is_email( $to ) ? $to : get_option( 'admin_email' );
		wp_mail(
			$to,
			sprintf( '[%s] %s', wp_specialchars_decode( setting( 'business_name' ), ENT_QUOTES ), $title ),
			$body,
			array( 'Reply-To: ' . ( $name ? $name . ' ' : '' ) . '<' . $email . '>' )
		);

		/**
		 * Let A2E Mail Studio (or anything listening) capture this lead and start a sequence.
		 * This is the same hook the A2E theme fires for its own forms.
		 */
		do_action( 'a2e_lead_received', 'health-check', $email, $name, (int) $lead_id );

		$success = __( 'Your report is on its way. A real person will also look at your numbers and send a short, honest action plan, usually within one business day.', 'a2e-health' );
		if ( $is_ajax ) {
			wp_send_json_success( array( 'message' => $success ) );
		}
		wp_safe_redirect( add_query_arg( 'a2ehc_sent', '1', $back ) );
		exit;
	}
}
