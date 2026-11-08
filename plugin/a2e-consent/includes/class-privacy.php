<?php
/**
 * Privacy policy text and WordPress's export / erase personal data tools.
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Privacy.
 */
class Privacy {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'policy_text' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'erasers' ) );
	}

	/**
	 * Suggested text for your privacy policy (Settings → Privacy → Policy guide).
	 */
	public static function policy_text() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$months = (int) opt( 'keep_hits' );
		$text   = '<h2>' . esc_html__( 'Cookies and how we measure this site', 'a2e-consent' ) . '</h2>'
			. '<p>' . esc_html__( 'When you first visit we ask which cookies you allow. Only necessary cookies are used until you choose, and you can change your choice at any time with the "Cookie settings" link at the bottom of every page. We keep a record of each choice (a random consent ID, the date, what you chose, the page and a short browser description such as "Chrome on Windows") as proof of consent. We do not store your IP address with it.', 'a2e-consent' ) . '</p>'
			. '<p>' . esc_html__( 'If you allow analytics cookies, we record the pages you view on this site, how long you stay, which website, search engine, advert or campaign brought you here, your device type and (where our hosting provides it) your country, plus clicks on our WhatsApp, phone, email and checkout links. This uses a random ID stored in a cookie, not your name. If you then send us a form, we link this visit history to your enquiry so we know how you found us.', 'a2e-consent' ) . '</p>'
			/* translators: %d: months */
			. '<p>' . esc_html( sprintf( __( 'Visit history is deleted automatically after %d months. You can ask us to export or delete it at any time.', 'a2e-consent' ), $months ) ) . '</p>'
			. '<p>' . esc_html__( 'If you allow them, Google Analytics, Microsoft Clarity and our advertising partners (Google Ads and Meta) may also set cookies. The full list is shown in Cookie settings.', 'a2e-consent' ) . '</p>'
			. '<p>' . esc_html__( 'Tip: add the [a2e_cookie_list] shortcode to this page to show the cookies we use.', 'a2e-consent' ) . '</p>';
		wp_add_privacy_policy_content( __( 'A2E Consent & Insights', 'a2e-consent' ), wp_kses_post( $text ) );
	}

	/**
	 * Register the exporter.
	 *
	 * @param array $exporters Exporters.
	 * @return array
	 */
	public static function exporters( $exporters ) {
		$exporters['a2e-consent'] = array(
			'exporter_friendly_name' => __( 'Visit history (A2E Insights)', 'a2e-consent' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	/**
	 * Register the eraser.
	 *
	 * @param array $erasers Erasers.
	 * @return array
	 */
	public static function erasers( $erasers ) {
		$erasers['a2e-consent'] = array(
			'eraser_friendly_name' => __( 'Visit history (A2E Insights)', 'a2e-consent' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * Export the visit history linked to an email address (through the forms they sent).
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array
	 */
	public static function export( $email, $page = 1 ) {
		unset( $page );
		$found = Insights::by_email( $email );
		$items = array();
		foreach ( $found['visitors'] as $visitor ) {
			foreach ( Insights::journey( $visitor ) as $i => $visit ) {
				foreach ( $visit['steps'] as $step ) {
					$items[] = array(
						'group_id'    => 'a2e-insights',
						'group_label' => __( 'Visit history', 'a2e-consent' ),
						'item_id'     => 'a2ec-' . $step['id'],
						'data'        => array(
							array( 'name' => __( 'Time', 'a2e-consent' ), 'value' => local_time( $step['created'] ) ),
							array( 'name' => __( 'What', 'a2e-consent' ), 'value' => 'view' === $step['type'] ? __( 'Viewed a page', 'a2e-consent' ) : ( 'lead' === $step['type'] ? __( 'Sent a form', 'a2e-consent' ) : Admin::click_label( $step['type'] ) ) ),
							array( 'name' => __( 'Page', 'a2e-consent' ), 'value' => home_url( $step['path'] ) ),
							array( 'name' => __( 'Details', 'a2e-consent' ), 'value' => trim( $step['title'] . ' ' . $step['target'] ) ),
							/* translators: %d: visit number */
							array( 'name' => __( 'Visit', 'a2e-consent' ), 'value' => sprintf( __( 'Visit %d', 'a2e-consent' ), $i + 1 ) ),
							array( 'name' => __( 'Came from', 'a2e-consent' ), 'value' => Leads::source_text( $step['source'], $step['medium'] ) ),
							array( 'name' => __( 'Device', 'a2e-consent' ), 'value' => $step['device'] ),
							array( 'name' => __( 'Country', 'a2e-consent' ), 'value' => $step['country'] ),
						),
					);
				}
			}
		}
		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/**
	 * Erase the visit history linked to an email address.
	 *
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array
	 */
	public static function erase( $email, $page = 1 ) {
		unset( $page );
		$found   = Insights::by_email( $email );
		$removed = 0;
		foreach ( $found['visitors'] as $visitor ) {
			$removed += Insights::forget( $visitor );
		}
		foreach ( $found['leads'] as $lead ) {
			delete_post_meta( $lead, '_a2ec_journey' );
			delete_post_meta( $lead, '_a2ec_vid' );
		}
		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array( __( 'Consent records are kept under a random consent ID and are not linked to an email address.', 'a2e-consent' ) ),
			'done'           => true,
		);
	}
}
