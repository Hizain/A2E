<?php
/**
 * Automatic follow-up sequences. A trigger (a new audit request, a new client, a health-check
 * signup, and so on) enrols a contact into a sequence of timed emails. The sequence sends the
 * steps on schedule and stops on its own when the person unsubscribes or reaches the goal
 * (for lead sequences, when they become a client). This removes the slow, tiring job of
 * following up by hand, while only ever emailing people you already have a relationship with.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automations.
 */
class Automations {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		// Live triggers (never fire for the historical base imported on first sync).
		add_action( 'a2e_lead_received', array( __CLASS__, 'on_lead_event' ), 20, 4 );
		add_action( 'a2email_client_added', array( __CLASS__, 'on_client_added' ) );
		// Processing runs on the same one-minute schedule as the queue.
		add_action( CRON_SEND, array( __CLASS__, 'process' ), 20 );
		// Admin actions.
		add_action( 'admin_post_a2email_save_sequence', array( __CLASS__, 'save_sequence' ) );
		add_action( 'admin_post_a2email_toggle_sequence', array( __CLASS__, 'toggle_sequence' ) );
		add_action( 'admin_post_a2email_enroll_existing', array( __CLASS__, 'enroll_existing' ) );
	}

	/**
	 * The triggers a sequence can start from.
	 *
	 * @return array key => [ label, segment-source, is_lead ]
	 */
	public static function triggers() {
		return array(
			'audit'        => array( 'label' => __( 'Someone requests a free store audit', 'a2e-mail' ), 'source' => 'quote', 'lead' => true ),
			'consultation' => array( 'label' => __( 'Someone requests a consultation', 'a2e-mail' ), 'source' => 'consultation', 'lead' => true ),
			'contact'      => array( 'label' => __( 'Someone uses the contact form', 'a2e-mail' ), 'source' => 'contact', 'lead' => true ),
			'newsletter'   => array( 'label' => __( 'Someone subscribes to the newsletter', 'a2e-mail' ), 'source' => 'newsletter', 'lead' => true ),
			'health_check' => array( 'label' => __( 'Someone runs the account health checker', 'a2e-mail' ), 'source' => 'health_check', 'lead' => true ),
			'new_client'   => array( 'label' => __( 'Someone becomes a client (Whop)', 'a2e-mail' ), 'source' => 'client', 'lead' => false ),
		);
	}

	/**
	 * Register the sequence post type (admin only).
	 */
	public static function register() {
		register_post_type(
			SEQUENCE,
			array(
				'labels'       => array( 'name' => __( 'Automations', 'a2e-mail' ), 'singular_name' => __( 'Automation', 'a2e-mail' ) ),
				'public'       => false,
				'show_ui'      => false,
				'supports'     => array( 'title' ),
			)
		);
	}

	/**
	 * Enrollments table.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'a2email_enrollments';
	}

	/**
	 * Create / upgrade the table.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table   = self::table();
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				sequence_id bigint(20) unsigned NOT NULL DEFAULT 0,
				contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
				step_index smallint(5) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'active',
				next_run datetime NULL DEFAULT NULL,
				created_at datetime NULL DEFAULT NULL,
				updated_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY sequence_id (sequence_id),
				KEY contact_id (contact_id),
				KEY status_run (status, next_run)
			) {$charset};"
		);
	}

	/**
	 * Read a sequence.
	 *
	 * @param int $id Id.
	 * @return array|null
	 */
	public static function get( $id ) {
		$post = get_post( $id );
		if ( ! $post || SEQUENCE !== $post->post_type ) {
			return null;
		}
		$steps = json_decode( (string) get_post_meta( $id, '_steps', true ), true );
		return array(
			'id'            => $post->ID,
			'name'          => $post->post_title,
			'trigger'       => (string) get_post_meta( $id, '_trigger', true ),
			'active'        => '1' === get_post_meta( $id, '_active', true ),
			'stop_on_client' => '1' === get_post_meta( $id, '_stop_on_client', true ),
			'steps'         => is_array( $steps ) ? $steps : array(),
		);
	}

	/**
	 * All sequences.
	 *
	 * @return array
	 */
	public static function all() {
		$posts = get_posts( array( 'post_type' => SEQUENCE, 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'ASC' ) );
		return array_map( function ( $p ) { return self::get( $p->ID ); }, $posts );
	}

	/**
	 * Active sequences for a trigger.
	 *
	 * @param string $trigger Trigger key.
	 * @return array
	 */
	public static function active_for( $trigger ) {
		$out = array();
		foreach ( self::all() as $seq ) {
			if ( $seq && $seq['active'] && $seq['trigger'] === $trigger && ! empty( $seq['steps'] ) ) {
				$out[] = $seq;
			}
		}
		return $out;
	}

	/**
	 * A new website lead arrived: enrol into matching sequences.
	 *
	 * @param string $type    Lead type.
	 * @param string $email   Email.
	 * @param string $name    Name.
	 * @param int    $lead_id Lead id.
	 */
	public static function on_lead_event( $type, $email, $name, $lead_id ) {
		$map     = array( 'quote' => 'audit', 'consultation' => 'consultation', 'contact' => 'contact', 'newsletter' => 'newsletter', 'health-check' => 'health_check' );
		$trigger = isset( $map[ $type ] ) ? $map[ $type ] : '';
		if ( ! $trigger ) {
			return;
		}
		$contact = Contacts::get_by_email( $email );
		if ( $contact ) {
			self::enroll_contact( (int) $contact['id'], $trigger );
		}
	}

	/**
	 * A new client appeared: enrol into new-client sequences, and stop any lead sequences
	 * (the goal is reached).
	 *
	 * @param int $contact_id Contact id.
	 */
	public static function on_client_added( $contact_id ) {
		self::stop_lead_sequences( $contact_id );
		self::enroll_contact( $contact_id, 'new_client' );
	}

	/**
	 * Enrol one contact into every active sequence for a trigger (skip if already active in it).
	 *
	 * @param int    $contact_id Contact id.
	 * @param string $trigger    Trigger key.
	 * @return int Number of new enrollments.
	 */
	public static function enroll_contact( $contact_id, $trigger ) {
		global $wpdb;
		$contact = Contacts::get( $contact_id );
		if ( ! $contact || Contacts::STATUS_SUBSCRIBED !== $contact['status'] ) {
			return 0;
		}
		$table = self::table();
		$made  = 0;
		foreach ( self::active_for( $trigger ) as $seq ) {
			$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE sequence_id = %d AND contact_id = %d AND status = 'active'", $seq['id'], $contact_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
			if ( $exists ) {
				continue;
			}
			$first_delay = isset( $seq['steps'][0] ) ? self::delay_seconds( $seq['steps'][0] ) : 0;
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'sequence_id' => $seq['id'],
					'contact_id'  => $contact_id,
					'step_index'  => 0,
					'status'      => 'active',
					'next_run'    => gmdate( 'Y-m-d H:i:s', time() + $first_delay ),
					'created_at'  => current_time( 'mysql', true ),
					'updated_at'  => current_time( 'mysql', true ),
				)
			);
			$made++;
		}
		return $made;
	}

	/**
	 * Stop a contact's active lead sequences (used when they convert to a client).
	 *
	 * @param int $contact_id Contact id.
	 */
	public static function stop_lead_sequences( $contact_id ) {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT e.id, e.sequence_id FROM {$table} e WHERE e.contact_id = %d AND e.status = 'active'", $contact_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		foreach ( (array) $rows as $row ) {
			$seq = self::get( (int) $row['sequence_id'] );
			if ( $seq && $seq['stop_on_client'] ) {
				$wpdb->update( $table, array( 'status' => 'stopped', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
	}

	/**
	 * Delay of a step in seconds.
	 *
	 * @param array $step Step.
	 * @return int
	 */
	protected static function delay_seconds( $step ) {
		$value = isset( $step['delay'] ) ? max( 0, (int) $step['delay'] ) : 0;
		$unit  = isset( $step['unit'] ) ? $step['unit'] : 'days';
		$mult  = 'hours' === $unit ? 3600 : ( 'minutes' === $unit ? 60 : 86400 );
		return $value * $mult;
	}

	/**
	 * Process due enrollments (rate-limited, suppression-aware).
	 *
	 * @return int Number sent.
	 */
	public static function process() {
		global $wpdb;
		$allow = Queue::allowance();
		if ( $allow < 1 ) {
			return 0;
		}
		$table = self::table();
		$now   = current_time( 'mysql', true );
		$due   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'active' AND next_run <= %s ORDER BY next_run ASC LIMIT %d", $now, min( $allow, 50 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		$sent  = 0;

		foreach ( (array) $due as $row ) {
			$seq     = self::get( (int) $row['sequence_id'] );
			$contact = Contacts::get( (int) $row['contact_id'] );

			if ( ! $seq || ! $seq['active'] || ! $contact || Contacts::STATUS_SUBSCRIBED !== $contact['status'] ) {
				$wpdb->update( $table, array( 'status' => 'stopped', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}
			// Goal reached for lead sequences.
			if ( $seq['stop_on_client'] && 'client' === $contact['source'] && 'new_client' !== $seq['trigger'] ) {
				$wpdb->update( $table, array( 'status' => 'stopped', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}
			$index = (int) $row['step_index'];
			if ( ! isset( $seq['steps'][ $index ] ) ) {
				$wpdb->update( $table, array( 'status' => 'completed', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}
			$step   = $seq['steps'][ $index ];
			$render = Templates::render(
				array( 'subject' => $step['subject'], 'preheader' => isset( $step['preheader'] ) ? $step['preheader'] : '', 'body' => $step['body'] ),
				$contact
			);
			$ok = Mailer::send( $contact['email'], $render['subject'], $render['html'], $render['text'], Unsubscribe::url( $contact['token'] ) );

			if ( $ok ) {
				Contacts::mark_sent( (int) $contact['id'] );
				Queue::note_external_send( $contact['email'] );
				$sent++;
				$next = $index + 1;
				if ( isset( $seq['steps'][ $next ] ) ) {
					$wpdb->update( $table, array( 'step_index' => $next, 'next_run' => gmdate( 'Y-m-d H:i:s', time() + self::delay_seconds( $seq['steps'][ $next ] ) ), 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				} else {
					$wpdb->update( $table, array( 'status' => 'completed', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				}
			} else {
				// Try again next run.
				$wpdb->update( $table, array( 'next_run' => gmdate( 'Y-m-d H:i:s', time() + 900 ), 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
		if ( $sent > 0 ) {
			log( 'automation', sprintf( __( 'Sent %d follow-up emails.', 'a2e-mail' ), $sent ) );
		}
		return $sent;
	}

	/**
	 * Count of active enrollments in a sequence.
	 *
	 * @param int $sequence_id Sequence id.
	 * @return int
	 */
	public static function active_count( $sequence_id ) {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE sequence_id = %d AND status = 'active'", $sequence_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Create the two default sequences once, inactive, for the user to review and switch on.
	 */
	public static function seed() {
		if ( get_option( 'a2email_sequences_seeded' ) ) {
			return;
		}
		update_option( 'a2email_sequences_seeded', VERSION, false );

		$audit = array(
			array( 'delay' => 0, 'unit' => 'minutes', 'subject' => __( 'Your free eBay store audit, {{first_name}}', 'a2e-mail' ), 'preheader' => __( 'A clear plan, a fixed price, no pressure.', 'a2e-mail' ), 'body' => __( "Hello {{first_name}},\n\nThank you for asking us to look at your eBay store. We take this seriously, so I want to be useful rather than pushy.\n\nTo make your audit accurate, it helps to know your main niches and where you feel stuck right now. You can reply here, or reach me directly on WhatsApp {{whatsapp}} or by phone {{phone}}.\n\nWe will send a short written plan with a fixed price. If we are not the right fit, we will say so honestly.\n\n[[Request your free audit|{{site_url}}]]\n\n{{signature}}", 'a2e-mail' ) ),
			array( 'delay' => 3, 'unit' => 'days', 'subject' => __( 'A quick thought on your store, {{first_name}}', 'a2e-mail' ), 'preheader' => __( 'One change that often lifts sales.', 'a2e-mail' ), 'body' => __( "Hello {{first_name}},\n\nI wanted to follow up gently. In most stores we review, the fastest win is listing titles: using all 80 characters with the words buyers actually search. It is simple, and it lifts views without spending a cent on ads.\n\nIf you would like, send me one listing and I will tell you exactly how I would rewrite it, free.\n\nWhatsApp {{whatsapp}} or email {{contact_email}}.\n\n{{signature}}", 'a2e-mail' ) ),
			array( 'delay' => 7, 'unit' => 'days', 'subject' => __( 'Shall I close your file, {{first_name}}?', 'a2e-mail' ), 'preheader' => __( 'No pressure either way.', 'a2e-mail' ), 'body' => __( "Hello {{first_name}},\n\nI do not want to crowd your inbox, so this is my last note for now. If the timing is not right, that is completely fine and I will leave it with you.\n\nWhenever you want a careful, honest look at your store, I am one message away on WhatsApp {{whatsapp}}.\n\nWishing you strong sales either way.\n\n{{signature}}", 'a2e-mail' ) ),
		);
		$client = array(
			array( 'delay' => 0, 'unit' => 'minutes', 'subject' => __( 'Welcome to A2E, {{first_name}}', 'a2e-mail' ), 'preheader' => __( 'What happens next with your store.', 'a2e-mail' ), 'body' => __( "Hello {{first_name}},\n\nThank you for trusting A2E with your eBay store. A real person will confirm your store details and access within one business day, then we get to work.\n\nYou can always reach us on WhatsApp {{whatsapp}}, by phone {{phone}} or email {{contact_email}}.\n\n{{signature}}", 'a2e-mail' ) ),
			array( 'delay' => 14, 'unit' => 'days', 'subject' => __( 'Two weeks in: how is it feeling?', 'a2e-mail' ), 'preheader' => __( 'A quick check-in.', 'a2e-mail' ), 'body' => __( "Hello {{first_name}},\n\nA short check-in two weeks in. Is there anything you would like us to focus on next, or any question about what we have done so far?\n\nReply here or message me on WhatsApp {{whatsapp}}. Your feedback shapes the work.\n\n{{signature}}", 'a2e-mail' ) ),
		);

		self::create( __( 'Free audit follow-up', 'a2e-mail' ), 'audit', $audit, true );
		self::create( __( 'New client welcome', 'a2e-mail' ), 'new_client', $client, false );
	}

	/**
	 * Create a sequence.
	 *
	 * @param string $name           Name.
	 * @param string $trigger        Trigger.
	 * @param array  $steps          Steps.
	 * @param bool   $stop_on_client Stop when the contact becomes a client.
	 * @return int
	 */
	public static function create( $name, $trigger, $steps, $stop_on_client = true ) {
		$id = wp_insert_post( array( 'post_type' => SEQUENCE, 'post_status' => 'publish', 'post_title' => $name ) );
		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, '_trigger', $trigger );
		update_post_meta( $id, '_steps', wp_slash( wp_json_encode( $steps ) ) );
		update_post_meta( $id, '_active', '0' );
		update_post_meta( $id, '_stop_on_client', $stop_on_client ? '1' : '0' );
		return (int) $id;
	}

	/**
	 * Save a sequence from the editor.
	 */
	public static function save_sequence() {
		check_admin_referer( 'a2email_sequence' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$id      = isset( $_POST['sequence_id'] ) ? absint( $_POST['sequence_id'] ) : 0;
		$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : __( 'Untitled automation', 'a2e-mail' );
		$trigger = isset( $_POST['trigger'] ) ? sanitize_key( wp_unslash( $_POST['trigger'] ) ) : 'audit';
		if ( ! array_key_exists( $trigger, self::triggers() ) ) {
			$trigger = 'audit';
		}
		$steps_in = isset( $_POST['steps'] ) && is_array( $_POST['steps'] ) ? wp_unslash( $_POST['steps'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$steps    = array();
		foreach ( $steps_in as $step ) {
			$subject = isset( $step['subject'] ) ? sanitize_text_field( $step['subject'] ) : '';
			$body    = isset( $step['body'] ) ? wp_strip_all_tags( $step['body'] ) : '';
			if ( '' === trim( $subject ) && '' === trim( $body ) ) {
				continue;
			}
			$steps[] = array(
				'delay'     => isset( $step['delay'] ) ? max( 0, (int) $step['delay'] ) : 0,
				'unit'      => in_array( ( $step['unit'] ?? 'days' ), array( 'minutes', 'hours', 'days' ), true ) ? $step['unit'] : 'days',
				'subject'   => $subject,
				'preheader' => isset( $step['preheader'] ) ? sanitize_text_field( $step['preheader'] ) : '',
				'body'      => $body,
			);
		}
		if ( $id ) {
			wp_update_post( array( 'ID' => $id, 'post_title' => $name ) );
		} else {
			$id = wp_insert_post( array( 'post_type' => SEQUENCE, 'post_status' => 'publish', 'post_title' => $name ) );
		}
		update_post_meta( $id, '_trigger', $trigger );
		update_post_meta( $id, '_steps', wp_slash( wp_json_encode( $steps ) ) );
		update_post_meta( $id, '_stop_on_client', empty( $_POST['stop_on_client'] ) ? '0' : '1' );
		update_post_meta( $id, '_active', empty( $_POST['active'] ) ? '0' : '1' );
		set_transient( 'a2email_notice_' . get_current_user_id(), array( 'success', __( 'Automation saved.', 'a2e-mail' ) ), 60 );
		wp_safe_redirect( admin_page_url( 'automations', array( 'sequence' => $id ) ) );
		exit;
	}

	/**
	 * Toggle active state.
	 */
	public static function toggle_sequence() {
		check_admin_referer( 'a2email_toggle' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$id  = isset( $_GET['sequence'] ) ? absint( $_GET['sequence'] ) : 0;
		$now = '1' === get_post_meta( $id, '_active', true );
		update_post_meta( $id, '_active', $now ? '0' : '1' );
		set_transient( 'a2email_notice_' . get_current_user_id(), array( 'success', $now ? __( 'Automation turned off.', 'a2e-mail' ) : __( 'Automation turned on.', 'a2e-mail' ) ), 60 );
		wp_safe_redirect( admin_page_url( 'automations' ) );
		exit;
	}

	/**
	 * Enrol the contacts you already have that match this sequence's trigger (a one-off catch-up).
	 */
	public static function enroll_existing() {
		check_admin_referer( 'a2email_enroll_existing' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$id      = isset( $_POST['sequence_id'] ) ? absint( $_POST['sequence_id'] ) : 0;
		$seq     = self::get( $id );
		$trigger = self::triggers();
		if ( ! $seq || ! isset( $trigger[ $seq['trigger'] ] ) ) {
			self::bail( __( 'Automation not found.', 'a2e-mail' ) );
		}
		if ( ! $seq['active'] ) {
			self::bail( __( 'Turn the automation on first, then enrol existing contacts.', 'a2e-mail' ) );
		}
		$source = $trigger[ $seq['trigger'] ]['source'];
		$rows   = Contacts::recipients( self::segment_for_source( $source ) );
		$made   = 0;
		foreach ( $rows as $r ) {
			$made += self::enroll_contact( (int) $r['id'], $seq['trigger'] );
		}
		log( 'automation', sprintf( __( 'Enrolled %1$d existing contacts into "%2$s".', 'a2e-mail' ), $made, $seq['name'] ) );
		self::bail( sprintf( __( 'Enrolled %d existing contacts. They will receive the sequence on schedule.', 'a2e-mail' ), $made ), 'success', $id );
	}

	/**
	 * Map a source to the matching segment key.
	 *
	 * @param string $source Source.
	 * @return string
	 */
	protected static function segment_for_source( $source ) {
		$map = array( 'client' => 'clients', 'newsletter' => 'newsletter', 'health_check' => 'health_check', 'manual' => 'manual' );
		if ( isset( $map[ $source ] ) ) {
			return $map[ $source ];
		}
		return 'form_leads';
	}

	/**
	 * Redirect back with a notice.
	 *
	 * @param string $msg  Message.
	 * @param string $type Type.
	 * @param int    $id   Sequence id.
	 */
	protected static function bail( $msg, $type = 'error', $id = 0 ) {
		set_transient( 'a2email_notice_' . get_current_user_id(), array( $type, $msg ), 60 );
		wp_safe_redirect( admin_page_url( 'automations', $id ? array( 'sequence' => $id ) : array() ) );
		exit;
	}

	/**
	 * Render the Automations screen (list + editor).
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$edit = isset( $_GET['sequence'] ) ? absint( $_GET['sequence'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$new  = isset( $_GET['new'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $edit || $new ) {
			self::render_editor( $edit );
			return;
		}
		$sequences = self::all();
		$triggers  = self::triggers();
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'Automations', 'a2e-mail' ); ?> <a class="page-title-action" href="<?php echo esc_url( admin_page_url( 'automations', array( 'new' => 1 ) ) ); ?>"><?php esc_html_e( 'Add automation', 'a2e-mail' ); ?></a></h1>
			<p class="a2email-lead"><?php esc_html_e( 'Set up your follow-ups once, and they send themselves. A trigger (like a new audit request or a new client) enrols the person, and the steps go out on the schedule you choose. Each sequence stops on its own when the person unsubscribes or becomes a client, so nobody is ever over-emailed.', 'a2e-mail' ); ?></p>
			<?php Admin::print_notice(); ?>

			<?php if ( ! $sequences ) : ?>
				<p><?php esc_html_e( 'No automations yet.', 'a2e-mail' ); ?></p>
			<?php else : ?>
				<table class="widefat striped a2email-table">
					<thead><tr>
						<th><?php esc_html_e( 'Automation', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Starts when', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Steps', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'People in it', 'a2e-mail' ); ?></th>
						<th><?php esc_html_e( 'Status', 'a2e-mail' ); ?></th>
						<th></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $sequences as $seq ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $seq['name'] ); ?></strong></td>
							<td><?php echo esc_html( isset( $triggers[ $seq['trigger'] ] ) ? $triggers[ $seq['trigger'] ]['label'] : $seq['trigger'] ); ?></td>
							<td><?php echo (int) count( $seq['steps'] ); ?></td>
							<td><?php echo (int) self::active_count( $seq['id'] ); ?></td>
							<td><span class="a2email-pill a2email-pill--<?php echo $seq['active'] ? 'sent' : 'paused'; ?>"><?php echo $seq['active'] ? esc_html__( 'On', 'a2e-mail' ) : esc_html__( 'Off', 'a2e-mail' ); ?></span></td>
							<td class="a2email-row-actions">
								<a href="<?php echo esc_url( admin_page_url( 'automations', array( 'sequence' => $seq['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'a2e-mail' ); ?></a>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=a2email_toggle_sequence&sequence=' . $seq['id'] ), 'a2email_toggle' ) ); ?>"><?php echo $seq['active'] ? esc_html__( 'Turn off', 'a2e-mail' ) : esc_html__( 'Turn on', 'a2e-mail' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the sequence editor.
	 *
	 * @param int $id Sequence id (0 for new).
	 */
	protected static function render_editor( $id ) {
		$seq = $id ? self::get( $id ) : array( 'id' => 0, 'name' => '', 'trigger' => 'audit', 'active' => false, 'stop_on_client' => true, 'steps' => array( array( 'delay' => 0, 'unit' => 'days', 'subject' => '', 'preheader' => '', 'body' => '' ) ) );
		if ( ! $seq ) {
			$seq = array( 'id' => 0, 'name' => '', 'trigger' => 'audit', 'active' => false, 'stop_on_client' => true, 'steps' => array() );
		}
		if ( empty( $seq['steps'] ) ) {
			$seq['steps'] = array( array( 'delay' => 0, 'unit' => 'days', 'subject' => '', 'preheader' => '', 'body' => '' ) );
		}
		$triggers = self::triggers();
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php echo $id ? esc_html__( 'Edit automation', 'a2e-mail' ) : esc_html__( 'New automation', 'a2e-mail' ); ?></h1>
			<?php Admin::print_notice(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-seq">
				<?php wp_nonce_field( 'a2email_sequence' ); ?>
				<input type="hidden" name="action" value="a2email_save_sequence">
				<input type="hidden" name="sequence_id" value="<?php echo (int) $seq['id']; ?>">

				<p class="a2email-field"><label><?php esc_html_e( 'Name', 'a2e-mail' ); ?></label><input type="text" name="name" class="a2email-input" value="<?php echo esc_attr( $seq['name'] ); ?>" placeholder="<?php esc_attr_e( 'Free audit follow-up', 'a2e-mail' ); ?>"></p>

				<p class="a2email-field"><label><?php esc_html_e( 'Start this sequence when', 'a2e-mail' ); ?></label>
					<select name="trigger" class="a2email-input">
						<?php foreach ( $triggers as $key => $t ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $seq['trigger'], $key ); ?>><?php echo esc_html( $t['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<p class="a2email-field a2email-checks">
					<label><input type="checkbox" name="stop_on_client" value="1" <?php checked( $seq['stop_on_client'] ); ?>> <?php esc_html_e( 'Stop sending to anyone who becomes a client (the goal is reached)', 'a2e-mail' ); ?></label><br>
					<label><input type="checkbox" name="active" value="1" <?php checked( $seq['active'] ); ?>> <?php esc_html_e( 'Turn this automation on', 'a2e-mail' ); ?></label>
				</p>

				<h2 class="a2email-h2"><?php esc_html_e( 'Steps', 'a2e-mail' ); ?></h2>
				<p class="a2email-help"><?php esc_html_e( 'Each step waits the chosen time after the previous one, then sends. Use merge tags like {{first_name}}, {{whatsapp}}, {{phone}}, {{contact_email}}. Long dashes are removed and your details and an unsubscribe link are added automatically.', 'a2e-mail' ); ?></p>

				<div id="a2email-steps">
					<?php foreach ( $seq['steps'] as $i => $step ) : ?>
						<?php self::step_row( $i, $step ); ?>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button" id="a2email-add-step"><?php esc_html_e( 'Add a step', 'a2e-mail' ); ?></button></p>

				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save automation', 'a2e-mail' ); ?></button>
				<a class="button" href="<?php echo esc_url( admin_page_url( 'automations' ) ); ?>"><?php esc_html_e( 'Back', 'a2e-mail' ); ?></a></p>
			</form>

			<?php if ( $id && $seq['active'] ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-inline" onsubmit="return confirm('<?php echo esc_js( __( 'Enrol the contacts you already have that match this trigger?', 'a2e-mail' ) ); ?>');">
					<?php wp_nonce_field( 'a2email_enroll_existing' ); ?>
					<input type="hidden" name="action" value="a2email_enroll_existing">
					<input type="hidden" name="sequence_id" value="<?php echo (int) $id; ?>">
					<button type="submit" class="button"><?php esc_html_e( 'Enrol my existing matching contacts', 'a2e-mail' ); ?></button>
					<span class="a2email-muted"><?php esc_html_e( 'Optional one-off catch-up. New people are enrolled automatically from now on.', 'a2e-mail' ); ?></span>
				</form>
			<?php endif; ?>

			<template id="a2email-step-template"><?php self::step_row( '__i__', array( 'delay' => 2, 'unit' => 'days', 'subject' => '', 'preheader' => '', 'body' => '' ) ); ?></template>
		</div>
		<?php
	}

	/**
	 * One step row in the editor.
	 *
	 * @param mixed $i    Index or placeholder.
	 * @param array $step Step.
	 */
	protected static function step_row( $i, $step ) {
		$step = wp_parse_args( $step, array( 'delay' => 0, 'unit' => 'days', 'subject' => '', 'preheader' => '', 'body' => '' ) );
		?>
		<div class="a2email-step">
			<div class="a2email-step__when">
				<?php esc_html_e( 'Wait', 'a2e-mail' ); ?>
				<input type="number" min="0" name="steps[<?php echo esc_attr( $i ); ?>][delay]" value="<?php echo esc_attr( $step['delay'] ); ?>" class="small-text">
				<select name="steps[<?php echo esc_attr( $i ); ?>][unit]">
					<?php foreach ( array( 'minutes' => __( 'minutes', 'a2e-mail' ), 'hours' => __( 'hours', 'a2e-mail' ), 'days' => __( 'days', 'a2e-mail' ) ) as $u => $l ) : ?>
						<option value="<?php echo esc_attr( $u ); ?>" <?php selected( $step['unit'], $u ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php esc_html_e( 'then send:', 'a2e-mail' ); ?>
				<button type="button" class="button-link a2email-step__remove"><?php esc_html_e( 'Remove', 'a2e-mail' ); ?></button>
			</div>
			<input type="text" name="steps[<?php echo esc_attr( $i ); ?>][subject]" value="<?php echo esc_attr( $step['subject'] ); ?>" class="a2email-input" placeholder="<?php esc_attr_e( 'Subject line', 'a2e-mail' ); ?>">
			<input type="text" name="steps[<?php echo esc_attr( $i ); ?>][preheader]" value="<?php echo esc_attr( $step['preheader'] ); ?>" class="a2email-input" placeholder="<?php esc_attr_e( 'Preview text (optional)', 'a2e-mail' ); ?>">
			<textarea name="steps[<?php echo esc_attr( $i ); ?>][body]" rows="7" class="a2email-input a2email-body" placeholder="<?php esc_attr_e( 'Message. Blank line between paragraphs, ## heading, - bullet, [[Label|link]] button.', 'a2e-mail' ); ?>"><?php echo esc_textarea( $step['body'] ); ?></textarea>
		</div>
		<?php
	}
}
