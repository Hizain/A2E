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
		add_action( 'admin_post_a2email_test_sequence', array( __CLASS__, 'test_sequence' ) );
	}

	/**
	 * Result of the last start() call (how many emails went out straight away).
	 *
	 * @var array
	 */
	public static $last_start = array( 'enrolled' => 0, 'sent' => 0 );

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
			'manual'       => array( 'label' => __( 'You add a contact by hand', 'a2e-mail' ), 'source' => 'manual', 'lead' => true ),
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
		$steps    = json_decode( (string) get_post_meta( $id, '_steps', true ), true );
		$triggers = json_decode( (string) get_post_meta( $id, '_triggers', true ), true );
		if ( ! is_array( $triggers ) || ! $triggers ) {
			// Sequences saved before 1.3 have a single trigger.
			$triggers = array_filter( array( (string) get_post_meta( $id, '_trigger', true ) ) );
		}
		$triggers = array_values( array_intersect( $triggers, array_keys( self::triggers() ) ) );
		return array(
			'id'             => $post->ID,
			'name'           => $post->post_title,
			'trigger'        => $triggers ? $triggers[0] : '',
			'triggers'       => $triggers,
			'active'         => '1' === get_post_meta( $id, '_active', true ),
			'stop_on_client' => '1' === get_post_meta( $id, '_stop_on_client', true ),
			'once'           => '1' === get_post_meta( $id, '_once', true ),
			'steps'          => is_array( $steps ) ? $steps : array(),
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
			if ( $seq && $seq['active'] && in_array( $trigger, $seq['triggers'], true ) && ! empty( $seq['steps'] ) ) {
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
			self::start( (int) $contact['id'], $trigger );
		}
	}

	/**
	 * Enrol a contact for a trigger and send any step that is due now (a "wait 0" first step)
	 * straight away, so a new contact gets the welcome email the moment they are added.
	 *
	 * @param int    $contact_id Contact id.
	 * @param string $trigger    Trigger key.
	 * @return array enrolled, sent.
	 */
	public static function start( $contact_id, $trigger ) {
		$enrolled         = self::enroll_contact( $contact_id, $trigger );
		$sent             = $enrolled ? self::run_for_contact( $contact_id ) : 0;
		self::$last_start = array( 'enrolled' => $enrolled, 'sent' => $sent );
		return self::$last_start;
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
		$made = 0;
		foreach ( self::active_for( $trigger ) as $seq ) {
			$made += self::enroll_in( $seq, $contact_id );
		}
		return $made;
	}

	/**
	 * Enrol one contact into one sequence. Skips people already in it, and, for "once per
	 * person" sequences, anyone who was ever in it.
	 *
	 * @param array $seq        Sequence.
	 * @param int   $contact_id Contact id.
	 * @return int 1 when enrolled, else 0.
	 */
	protected static function enroll_in( $seq, $contact_id ) {
		global $wpdb;
		$table  = self::table();
		$status = $seq['once'] ? '' : " AND status = 'active'";
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE sequence_id = %d AND contact_id = %d" . $status, $seq['id'], $contact_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		if ( $exists ) {
			return 0;
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
		return 1;
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
		$due   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'active' AND next_run <= %s ORDER BY next_run ASC LIMIT %d", current_time( 'mysql', true ), min( $allow, 50 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		return self::process_rows( $due );
	}

	/**
	 * Send the due steps for one contact right now (within the sending limits).
	 *
	 * @param int $contact_id Contact id.
	 * @return int Number sent.
	 */
	public static function run_for_contact( $contact_id ) {
		global $wpdb;
		$allow = Queue::allowance();
		if ( $allow < 1 ) {
			return 0;
		}
		$table = self::table();
		$due   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE contact_id = %d AND status = 'active' AND next_run <= %s ORDER BY next_run ASC LIMIT %d", $contact_id, current_time( 'mysql', true ), min( $allow, 5 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
		return self::process_rows( $due );
	}

	/**
	 * Send the given due enrollment rows and move each one on to its next step.
	 *
	 * @param array $due Enrollment rows.
	 * @return int Number sent.
	 */
	protected static function process_rows( $due ) {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql', true );
		$sent  = 0;

		foreach ( (array) $due as $row ) {
			$seq     = self::get( (int) $row['sequence_id'] );
			$contact = Contacts::get( (int) $row['contact_id'] );

			if ( ! $seq || ! $seq['active'] || ! $contact || Contacts::STATUS_SUBSCRIBED !== $contact['status'] ) {
				$wpdb->update( $table, array( 'status' => 'stopped', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}
			// Goal reached for lead sequences.
			if ( $seq['stop_on_client'] && 'client' === $contact['source'] && ! in_array( 'new_client', $seq['triggers'], true ) ) {
				$wpdb->update( $table, array( 'status' => 'stopped', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}
			$index = (int) $row['step_index'];
			if ( ! isset( $seq['steps'][ $index ] ) ) {
				$wpdb->update( $table, array( 'status' => 'completed', 'updated_at' => $now ), array( 'id' => $row['id'] ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				continue;
			}
			// Claim the step first, so a scheduled run and an instant send never both send it.
			$claimed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET next_run = %s WHERE id = %d AND status = 'active' AND step_index = %d AND next_run = %s", gmdate( 'Y-m-d H:i:s', time() + 900 ), $row['id'], $index, $row['next_run'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
			if ( ! $claimed ) {
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
			/* translators: %d: emails */
			log( 'automation', sprintf( _n( 'Sent %d automatic email.', 'Sent %d automatic emails.', $sent, 'a2e-mail' ), $sent ) );
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
	 * The welcome email every new contact gets straight away: who A2E is and what we offer.
	 *
	 * @return array Step.
	 */
	public static function welcome_step() {
		return array(
			'delay'     => 0,
			'unit'      => 'minutes',
			'subject'   => __( '{{first_name}}, here is how A2E Dropshipping can grow your store', 'a2e-mail' ),
			'preheader' => __( 'Amazon to eBay dropshipping, branded eBay stores and Shopify stores, powered by Claude automation.', 'a2e-mail' ),
			'body'      => __( "Hello {{first_name}},

Thank you for connecting with us. We are **A2E Dropshipping**, and we help sellers like you grow their online stores, with a real team behind you and Claude automation doing the heavy lifting.

## What we can do for you

- **Amazon to eBay dropshipping stores:** profit-checked products, SEO titles that get found, and daily price and stock checks so you do not sell at a loss
- **Branded eBay stores:** your own brand, a clean store design and listings that build trust and repeat buyers
- **Shopify stores:** setup, products and ongoing management, so your store keeps selling
- **Claude automation:** we use Claude AI to research products, write listings and handle routine store work faster and more consistently

## How we work

- Real people you can message on WhatsApp
- Honest advice: if something will not work for your store, we tell you first
- Clear plans and prices, and monthly plans you can cancel any time

The easiest first step is a free store audit. We look at your store and send you a short, practical plan.

[[Get my free store audit|{{audit_url}}]]

Prefer to talk first? Reply to this email or message us on WhatsApp {{whatsapp}}. We are happy to answer questions.

{{weekly_invite}}

{{signature}}", 'a2e-mail' ),
		);
	}

	/**
	 * Create the welcome automation once, switched on, for every new contact. It only starts
	 * for people added from now on, never for contacts you already have.
	 */
	public static function seed_welcome() {
		if ( get_option( 'a2email_welcome_seeded' ) ) {
			return;
		}
		update_option( 'a2email_welcome_seeded', VERSION, false );
		$id = self::create(
			__( 'Welcome: what we offer', 'a2e-mail' ),
			array( 'manual', 'newsletter', 'contact', 'audit', 'consultation', 'health_check' ),
			array( self::welcome_step() ),
			true,
			true
		);
		if ( $id ) {
			update_post_meta( $id, '_active', '1' );
			update_option( 'a2email_welcome_sequence', $id, false );
		}
	}

	/**
	 * The welcome automation (if it still exists).
	 *
	 * @return array|null
	 */
	public static function welcome() {
		$id = (int) get_option( 'a2email_welcome_sequence', 0 );
		return $id ? self::get( $id ) : null;
	}

	/**
	 * How many people an automation has emailed at least once.
	 *
	 * @param int $sequence_id Sequence id.
	 * @return int
	 */
	public static function reached_count( $sequence_id ) {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE sequence_id = %d AND ( step_index > 0 OR status = 'completed' )", $sequence_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Create a sequence.
	 *
	 * @param string       $name           Name.
	 * @param string|array $triggers       Trigger key, or several.
	 * @param array        $steps          Steps.
	 * @param bool         $stop_on_client Stop when the contact becomes a client.
	 * @param bool         $once           Send to each person only once, ever.
	 * @return int
	 */
	public static function create( $name, $triggers, $steps, $stop_on_client = true, $once = false ) {
		$id = wp_insert_post( array( 'post_type' => SEQUENCE, 'post_status' => 'publish', 'post_title' => $name ) );
		if ( ! $id || is_wp_error( $id ) ) {
			return 0;
		}
		$triggers = array_values( (array) $triggers );
		update_post_meta( $id, '_trigger', $triggers[0] );
		update_post_meta( $id, '_triggers', wp_json_encode( $triggers ) );
		update_post_meta( $id, '_steps', wp_slash( wp_json_encode( $steps ) ) );
		update_post_meta( $id, '_active', '0' );
		update_post_meta( $id, '_stop_on_client', $stop_on_client ? '1' : '0' );
		update_post_meta( $id, '_once', $once ? '1' : '0' );
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
		$picked   = isset( $_POST['triggers'] ) && is_array( $_POST['triggers'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['triggers'] ) ) : array();
		$triggers = array_values( array_intersect( array_keys( self::triggers() ), $picked ) );
		if ( ! $triggers ) {
			self::bail( __( 'Tick at least one "Start when" option.', 'a2e-mail' ), 'error', $id );
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
		update_post_meta( $id, '_trigger', $triggers[0] );
		update_post_meta( $id, '_triggers', wp_json_encode( $triggers ) );
		update_post_meta( $id, '_steps', wp_slash( wp_json_encode( $steps ) ) );
		update_post_meta( $id, '_stop_on_client', empty( $_POST['stop_on_client'] ) ? '0' : '1' );
		update_post_meta( $id, '_once', empty( $_POST['once'] ) ? '0' : '1' );
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
		$id       = isset( $_POST['sequence_id'] ) ? absint( $_POST['sequence_id'] ) : 0;
		$seq      = self::get( $id );
		$triggers = self::triggers();
		if ( ! $seq || ! $seq['triggers'] ) {
			self::bail( __( 'Automation not found.', 'a2e-mail' ) );
		}
		if ( ! $seq['active'] ) {
			self::bail( __( 'Turn the automation on first, then enrol existing contacts.', 'a2e-mail' ) );
		}
		$sources = array();
		foreach ( $seq['triggers'] as $t ) {
			$sources[] = $triggers[ $t ]['source'];
		}
		$made = 0;
		foreach ( Contacts::recipients_by_sources( $sources ) as $r ) {
			$made += self::enroll_in( $seq, (int) $r['id'] );
		}
		log( 'automation', sprintf( __( 'Enrolled %1$d existing contacts into "%2$s".', 'a2e-mail' ), $made, $seq['name'] ) );
		self::bail( sprintf( __( 'Enrolled %d existing contacts. They will receive the sequence on schedule.', 'a2e-mail' ), $made ), 'success', $id );
	}

	/**
	 * Send every step of a sequence to one address, as a test.
	 */
	public static function test_sequence() {
		check_admin_referer( 'a2email_test_sequence' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$id  = isset( $_POST['sequence_id'] ) ? absint( $_POST['sequence_id'] ) : 0;
		$to  = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$seq = self::get( $id );
		if ( ! $seq || ! is_email( $to ) ) {
			self::bail( __( 'Enter a valid email address to test.', 'a2e-mail' ), 'error', $id );
		}
		$contact = array( 'name' => wp_get_current_user()->display_name, 'email' => $to, 'token' => 'preview' );
		$sent    = 0;
		foreach ( $seq['steps'] as $i => $step ) {
			$render = Templates::render( array( 'subject' => $step['subject'], 'preheader' => isset( $step['preheader'] ) ? $step['preheader'] : '', 'body' => $step['body'] ), $contact );
			/* translators: 1: step number 2: subject */
			$subject = count( $seq['steps'] ) > 1 ? sprintf( __( '[Test step %1$d] %2$s', 'a2e-mail' ), $i + 1, $render['subject'] ) : sprintf( __( '[Test] %s', 'a2e-mail' ), $render['subject'] );
			if ( Mailer::send( $to, $subject, $render['html'], $render['text'], Unsubscribe::url( 'preview' ) ) ) {
				$sent++;
			}
		}
		if ( $sent ) {
			self::bail( sprintf( __( 'Sent %1$d test email(s) to %2$s.', 'a2e-mail' ), $sent, $to ), 'success', $id );
		}
		self::bail( __( 'The test could not be sent. Check A2E Mail > Settings.', 'a2e-mail' ), 'error', $id );
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
			<p class="a2email-lead"><?php esc_html_e( 'Set up your follow-ups once, and they send themselves. A trigger (like a new contact, a new audit request or a new client) enrols the person, and the steps go out on the schedule you choose. A first step set to wait 0 minutes is sent the moment the person is added. Each sequence stops on its own when the person unsubscribes or becomes a client, so nobody is ever over-emailed.', 'a2e-mail' ); ?></p>
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
							<td><?php echo esc_html( implode( '; ', array_map( function ( $t ) use ( $triggers ) { return $triggers[ $t ]['label']; }, $seq['triggers'] ) ) ); ?></td>
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
		$blank = array( 'id' => 0, 'name' => '', 'trigger' => 'audit', 'triggers' => array( 'audit' ), 'active' => false, 'stop_on_client' => true, 'once' => false, 'steps' => array() );
		$seq   = $id ? self::get( $id ) : null;
		if ( ! $seq ) {
			$id  = 0;
			$seq = $blank;
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

				<fieldset class="a2email-field a2email-triggers"><legend><?php esc_html_e( 'Start this sequence when (tick any)', 'a2e-mail' ); ?></legend>
					<?php foreach ( $triggers as $key => $t ) : ?>
						<label><input type="checkbox" name="triggers[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $seq['triggers'], true ) ); ?>> <?php echo esc_html( $t['label'] ); ?></label>
					<?php endforeach; ?>
					<span class="a2email-help"><?php esc_html_e( 'Tip: if two automations start from the same form and both send straight away, that person gets two emails at once. Untick the form in one of them, or make one wait a day.', 'a2e-mail' ); ?></span>
				</fieldset>

				<p class="a2email-field a2email-checks">
					<label><input type="checkbox" name="once" value="1" <?php checked( $seq['once'] ); ?>> <?php esc_html_e( 'Send to each person only once, even if they use another form later', 'a2e-mail' ); ?></label><br>
					<label><input type="checkbox" name="stop_on_client" value="1" <?php checked( $seq['stop_on_client'] ); ?>> <?php esc_html_e( 'Stop sending to anyone who becomes a client (the goal is reached)', 'a2e-mail' ); ?></label><br>
					<label><input type="checkbox" name="active" value="1" <?php checked( $seq['active'] ); ?>> <?php esc_html_e( 'Turn this automation on', 'a2e-mail' ); ?></label>
				</p>

				<h2 class="a2email-h2"><?php esc_html_e( 'Steps', 'a2e-mail' ); ?></h2>
				<p class="a2email-help"><?php esc_html_e( 'Each step waits the chosen time after the previous one, then sends (wait 0 minutes = straight away). Merge tags: {{first_name}}, {{whatsapp}}, {{phone}}, {{contact_email}}, {{site_url}}, {{audit_url}}, {{consultation_url}}, {{pricing_url}}, {{tools_url}}, {{blog_url}}. {{weekly_invite}} adds a "join our weekly tips" button when your weekly newsletter is on (and nothing for people already on it). Long dashes are removed and your details and an unsubscribe link are added automatically.', 'a2e-mail' ); ?></p>

				<div id="a2email-steps">
					<?php foreach ( $seq['steps'] as $i => $step ) : ?>
						<?php self::step_row( $i, $step ); ?>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button" id="a2email-add-step"><?php esc_html_e( 'Add a step', 'a2e-mail' ); ?></button></p>

				<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save automation', 'a2e-mail' ); ?></button>
				<a class="button" href="<?php echo esc_url( admin_page_url( 'automations' ) ); ?>"><?php esc_html_e( 'Back', 'a2e-mail' ); ?></a></p>
			</form>

			<?php if ( $id ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-inline">
					<?php wp_nonce_field( 'a2email_test_sequence' ); ?>
					<input type="hidden" name="action" value="a2email_test_sequence">
					<input type="hidden" name="sequence_id" value="<?php echo (int) $id; ?>">
					<input type="email" name="to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required>
					<button type="submit" class="button"><?php esc_html_e( 'Send me a test of every step', 'a2e-mail' ); ?></button>
					<span class="a2email-muted"><?php esc_html_e( 'Saved version. Sent with sample details.', 'a2e-mail' ); ?></span>
				</form>
			<?php endif; ?>

			<?php if ( $id && $seq['active'] ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="a2email-inline" onsubmit="return confirm('<?php echo esc_js( __( 'Send this automation to the contacts you already have that match it? Each person gets it only once.', 'a2e-mail' ) ); ?>');">
					<?php wp_nonce_field( 'a2email_enroll_existing' ); ?>
					<input type="hidden" name="action" value="a2email_enroll_existing">
					<input type="hidden" name="sequence_id" value="<?php echo (int) $id; ?>">
					<button type="submit" class="button"><?php esc_html_e( 'Send it to my existing matching contacts', 'a2e-mail' ); ?></button>
					<span class="a2email-muted"><?php esc_html_e( 'Optional one-off catch-up for people you already have. New people get it automatically from now on.', 'a2e-mail' ); ?></span>
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
			<textarea name="steps[<?php echo esc_attr( $i ); ?>][body]" rows="14" class="a2email-input a2email-body" placeholder="<?php esc_attr_e( 'Message. Blank line between paragraphs, ## heading, - bullet, [[Label|link]] button.', 'a2e-mail' ); ?>"><?php echo esc_textarea( $step['body'] ); ?></textarea>
		</div>
		<?php
	}
}
