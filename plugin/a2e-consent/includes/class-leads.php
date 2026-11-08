<?php
/**
 * When someone who allowed analytics sends a form, record the lead and save how they found you
 * on the lead (shown on the lead's screen and in the Leads list).
 *
 * @package A2E_Consent
 */

namespace A2E\Consent;

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
		add_action( 'a2e_lead_received', array( __CLASS__, 'on_lead' ), 20, 4 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		foreach ( lead_types() as $type ) {
			add_filter( 'manage_' . $type . '_posts_columns', array( __CLASS__, 'columns' ), 20 ); // After the theme's own columns.
			add_action( 'manage_' . $type . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		}
	}

	/**
	 * A form was sent.
	 *
	 * @param string $type    Form type.
	 * @param string $email   Email.
	 * @param string $name    Name.
	 * @param int    $lead_id Lead post ID.
	 */
	public static function on_lead( $type, $email, $name, $lead_id ) {
		unset( $email, $name );
		if ( '1' !== opt( 'banner_on' ) || '1' !== opt( 'insights' ) || ! analytics_allowed() || is_excluded_staff() || is_bot( user_agent() ) ) {
			return;
		}
		$visitor = visitor_id();
		if ( ! $visitor ) {
			return;
		}
		$session = isset( $_COOKIE[ COOKIE_SESSION ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ COOKIE_SESSION ] ) ) : '';
		$session = preg_match( '/^[a-f0-9]{16}$/', $session ) ? $session : '';
		$link    = '1' === opt( 'link_leads' ) && $lead_id;
		$ref     = wp_get_referer();
		Rest::record(
			$visitor,
			$session,
			'lead',
			Rest::clean_path( $ref ? $ref : '/' ),
			substr( form_label( sanitize_key( $type ) ), 0, 80 ),
			sanitize_key( $type ),
			$link ? (int) $lead_id : 0
		);
		if ( $link ) {
			update_post_meta( $lead_id, '_a2ec_vid', $visitor );
			update_post_meta( $lead_id, '_a2ec_journey', Insights::journey_summary( $visitor ) );
		}
	}

	/**
	 * "How they found you" box on the lead screen.
	 */
	public static function meta_box() {
		foreach ( lead_types() as $type ) {
			if ( post_type_exists( $type ) ) {
				add_meta_box( 'a2ec-journey', __( 'How they found you', 'a2e-consent' ), array( __CLASS__, 'render_box' ), $type, 'normal', 'high' );
			}
		}
	}

	/**
	 * Source label.
	 *
	 * @param string $source Source.
	 * @param string $medium Medium.
	 * @return string
	 */
	public static function source_text( $source, $medium ) {
		if ( ! $source ) {
			return '';
		}
		return 'direct' === $medium ? $source : $source . ' (' . medium_label( $medium ) . ')';
	}

	/**
	 * Render the box.
	 *
	 * @param \WP_Post $post Lead.
	 */
	public static function render_box( $post ) {
		$j = get_post_meta( $post->ID, '_a2ec_journey', true );
		if ( ! is_array( $j ) || empty( $j['visitor'] ) ) {
			echo '<p>' . esc_html__( 'No visit history for this lead. They did not allow analytics cookies, sent the form before A2E Consent & Insights was installed, or their history was deleted.', 'a2e-consent' ) . '</p>';
			return;
		}
		$days = max( 0, (int) floor( ( strtotime( $post->post_date_gmt . ' UTC' ) - strtotime( $j['first_seen'] . ' UTC' ) ) / DAY_IN_SECONDS ) );
		$rows = array(
			__( 'First found you', 'a2e-consent' )        => self::source_text( $j['first_source'], $j['first_medium'] ) . ( $j['first_camp'] ? ' · ' . sprintf( /* translators: %s: campaign */ __( 'campaign "%s"', 'a2e-consent' ), $j['first_camp'] ) : '' ),
			__( 'First visit', 'a2e-consent' )            => local_time( $j['first_seen'] ),
			__( 'Landed on', 'a2e-consent' )              => $j['landing'],
			__( 'Visit that sent the form', 'a2e-consent' ) => self::source_text( $j['last_source'], $j['last_medium'] ) . ( $j['last_camp'] ? ' · ' . sprintf( /* translators: %s: campaign */ __( 'campaign "%s"', 'a2e-consent' ), $j['last_camp'] ) : '' ),
			__( 'Before enquiring', 'a2e-consent' )       => implode(
				' · ',
				array(
					/* translators: %d: visits */
					sprintf( _n( '%d visit', '%d visits', (int) $j['visits'], 'a2e-consent' ), (int) $j['visits'] ),
					/* translators: %d: pages */
					sprintf( _n( '%d page', '%d pages', (int) $j['views'], 'a2e-consent' ), (int) $j['views'] ),
					/* translators: %s: time */
					sprintf( __( '%s on the site', 'a2e-consent' ), duration( $j['seconds'] ) ),
					/* translators: %d: days */
					0 === $days ? __( 'same day as first visit', 'a2e-consent' ) : sprintf( _n( '%d day after first visit', '%d days after first visit', $days, 'a2e-consent' ), $days ),
				)
			),
			__( 'Device', 'a2e-consent' )                 => ucfirst( (string) $j['device'] ) . ( $j['country'] ? ' · ' . $j['country'] : '' ),
		);
		if ( ! empty( $j['clicks'] ) ) {
			$c = array();
			foreach ( $j['clicks'] as $k => $n ) {
				$c[] = Admin::click_label( $k ) . ' × ' . (int) $n;
			}
			$rows[ __( 'Clicked', 'a2e-consent' ) ] = implode( ', ', $c );
		}
		echo '<table class="widefat striped a2ec-box"><tbody>';
		foreach ( $rows as $label => $value ) {
			if ( '' === trim( (string) $value ) ) {
				continue;
			}
			echo '<tr><th scope="row" style="width:190px">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		echo '</tbody></table>';
		if ( ! empty( $j['pages'] ) ) {
			echo '<h4 style="margin:16px 0 6px">' . esc_html__( 'Pages they read (most recent last)', 'a2e-consent' ) . '</h4><ol style="margin:0 0 0 20px">';
			foreach ( $j['pages'] as $p ) {
				echo '<li><span style="color:#646970">' . esc_html( local_time( $p[0], 'j M, H:i' ) ) . '</span> · ' . esc_html( $p[2] ? $p[2] : $p[1] ) . ' <code>' . esc_html( $p[1] ) . '</code>' . ( $p[3] ? ' · ' . esc_html( duration( $p[3] ) ) : '' ) . '</li>';
			}
			echo '</ol>';
		}
		printf(
			'<p><a class="button" href="%s">%s</a></p>',
			esc_url( admin_url( 'admin.php?page=a2ec-visitors&visitor=' . rawurlencode( $j['visitor'] ) ) ),
			esc_html__( 'See the full journey', 'a2e-consent' )
		);
	}

	/**
	 * Leads list column.
	 *
	 * @param array $cols Columns.
	 * @return array
	 */
	public static function columns( $cols ) {
		$out = array();
		foreach ( $cols as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'title' === $k ) {
				$out['a2ec_source'] = __( 'Found you via', 'a2e-consent' );
			}
		}
		if ( ! isset( $out['a2ec_source'] ) ) {
			$out['a2ec_source'] = __( 'Found you via', 'a2e-consent' );
		}
		return $out;
	}

	/**
	 * Leads list column value.
	 *
	 * @param string $col     Column.
	 * @param int    $post_id Lead.
	 */
	public static function column( $col, $post_id ) {
		if ( 'a2ec_source' !== $col ) {
			return;
		}
		$j = get_post_meta( $post_id, '_a2ec_journey', true );
		if ( ! is_array( $j ) || empty( $j['first_source'] ) ) {
			echo '<span style="color:#8c8f94">' . esc_html__( 'Not known', 'a2e-consent' ) . '</span>';
			return;
		}
		echo esc_html( self::source_text( $j['first_source'], $j['first_medium'] ) );
		if ( $j['last_source'] && ( $j['last_source'] !== $j['first_source'] || $j['last_medium'] !== $j['first_medium'] ) ) {
			echo '<br><span style="color:#646970">' . esc_html( sprintf( /* translators: %s: source */ __( 'then %s', 'a2e-consent' ), self::source_text( $j['last_source'], $j['last_medium'] ) ) ) . '</span>';
		}
	}
}
