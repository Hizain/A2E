<?php
/**
 * The composer: write or load a template, choose who it goes to (a group or one person),
 * preview it, send a test, then send. Messages are stored so you have a history with progress.
 *
 * @package A2E_Mail
 */

namespace A2E\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Composer.
 */
class Composer {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_a2email_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_a2email_test', array( __CLASS__, 'test' ) );
		add_action( 'admin_post_a2email_send', array( __CLASS__, 'send' ) );
		add_action( 'admin_post_a2email_send_one', array( __CLASS__, 'send_one' ) );
		add_action( 'admin_post_a2email_pause', array( __CLASS__, 'pause' ) );
		add_action( 'admin_post_a2email_resume', array( __CLASS__, 'resume' ) );
		add_action( 'wp_ajax_a2email_preview', array( __CLASS__, 'ajax_preview' ) );
	}

	/**
	 * Register the message post type (admin only).
	 */
	public static function register() {
		register_post_type(
			CPT,
			array(
				'labels'       => array(
					'name'          => __( 'Messages', 'a2e-mail' ),
					'singular_name' => __( 'Message', 'a2e-mail' ),
				),
				'public'       => false,
				'show_ui'      => false,
				'supports'     => array( 'title', 'editor', 'author' ),
			)
		);
	}

	/**
	 * Read a message as an array.
	 *
	 * @param int $id Id.
	 * @return array|null
	 */
	public static function get_message( $id ) {
		$post = get_post( $id );
		if ( ! $post || CPT !== $post->post_type ) {
			return null;
		}
		return array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'body'       => $post->post_content,
			'subject'    => (string) get_post_meta( $id, '_subject', true ),
			'preheader'  => (string) get_post_meta( $id, '_preheader', true ),
			'segment'    => (string) get_post_meta( $id, '_segment', true ),
			'status'     => (string) ( get_post_meta( $id, '_status', true ) ? get_post_meta( $id, '_status', true ) : 'draft' ),
			'total'      => (int) get_post_meta( $id, '_total', true ),
			'sent_count' => (int) get_post_meta( $id, '_sent_count', true ),
			'fail_count' => (int) get_post_meta( $id, '_fail_count', true ),
		);
	}

	/**
	 * Update message meta.
	 *
	 * @param int   $id     Id.
	 * @param array $fields Fields.
	 */
	public static function update_message( $id, $fields ) {
		foreach ( $fields as $key => $value ) {
			update_post_meta( $id, '_' . $key, $value );
		}
	}

	/**
	 * Atomically increment a counter.
	 *
	 * @param int    $id  Id.
	 * @param string $key Key.
	 */
	public static function bump( $id, $key ) {
		$current = (int) get_post_meta( $id, '_' . $key, true );
		update_post_meta( $id, '_' . $key, $current + 1 );
	}

	/**
	 * Save (or create) a message from the compose form. Returns the id.
	 *
	 * @param bool $redirect Redirect after save.
	 * @return int
	 */
	protected static function save_from_request( $redirect = true ) {
		check_admin_referer( 'a2email_compose' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$id       = isset( $_POST['message_id'] ) ? absint( $_POST['message_id'] ) : 0;
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$subject  = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$pre      = isset( $_POST['preheader'] ) ? sanitize_text_field( wp_unslash( $_POST['preheader'] ) ) : '';
		$segment  = isset( $_POST['segment'] ) ? sanitize_key( wp_unslash( $_POST['segment'] ) ) : 'all';
		$body     = isset( $_POST['body'] ) ? self::sanitize_body( wp_unslash( $_POST['body'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$title    = $title ? $title : ( $subject ? $subject : __( 'Untitled message', 'a2e-mail' ) );

		$data = array( 'post_type' => CPT, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $body );
		if ( $id ) {
			$data['ID'] = $id;
			wp_update_post( wp_slash( $data ) );
		} else {
			$id = wp_insert_post( wp_slash( $data ) );
		}
		self::update_message( $id, array( 'subject' => $subject, 'preheader' => $pre, 'segment' => $segment ) );
		if ( ! get_post_meta( $id, '_status', true ) ) {
			self::update_message( $id, array( 'status' => 'draft' ) );
		}
		return (int) $id;
	}

	/**
	 * Allow a safe subset of characters in the body (our light format, not raw HTML).
	 *
	 * @param string $body Body.
	 * @return string
	 */
	protected static function sanitize_body( $body ) {
		$body = wp_check_invalid_utf8( $body );
		// Strip any real tags: the body format is plain text with our own markers.
		$body = wp_strip_all_tags( $body );
		return $body;
	}

	/**
	 * Save handler.
	 */
	public static function save() {
		$id = self::save_from_request();
		set_transient( 'a2email_notice_' . get_current_user_id(), array( 'success', __( 'Message saved.', 'a2e-mail' ) ), 60 );
		wp_safe_redirect( admin_page_url( 'compose', array( 'message' => $id ) ) );
		exit;
	}

	/**
	 * Send a test handler.
	 */
	public static function test() {
		$id  = self::save_from_request( false );
		$to  = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$msg = self::get_message( $id );
		$contact = array( 'name' => __( 'there', 'a2e-mail' ), 'email' => $to, 'token' => 'preview' );
		$render  = Templates::render( $msg, $contact );
		$ok      = Mailer::send( $to, $render['subject'], $render['html'], $render['text'], Unsubscribe::url( 'preview' ) );
		$notice  = $ok ? array( 'success', sprintf( __( 'Test sent to %s.', 'a2e-mail' ), $to ) ) : array( 'error', __( 'Test could not be sent. Check Settings.', 'a2e-mail' ) );
		set_transient( 'a2email_notice_' . get_current_user_id(), $notice, 60 );
		wp_safe_redirect( admin_page_url( 'compose', array( 'message' => $id ) ) );
		exit;
	}

	/**
	 * Send to audience handler.
	 */
	public static function send() {
		$id  = self::save_from_request( false );
		$res = Queue::enqueue( $id );
		set_transient( 'a2email_notice_' . get_current_user_id(), array( $res['ok'] ? 'success' : 'error', $res['message'] ), 60 );
		// Kick off the first batch right away.
		if ( $res['ok'] ) {
			Queue::process();
		}
		wp_safe_redirect( admin_page_url( 'messages', array( 'message' => $id ) ) );
		exit;
	}

	/**
	 * Send to one existing contact handler.
	 */
	public static function send_one() {
		check_admin_referer( 'a2email_compose' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'a2e-mail' ) );
		}
		$contact_id = isset( $_POST['contact_id'] ) ? absint( $_POST['contact_id'] ) : 0;
		$message    = array(
			'subject'   => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
			'preheader' => isset( $_POST['preheader'] ) ? sanitize_text_field( wp_unslash( $_POST['preheader'] ) ) : '',
			'body'      => isset( $_POST['body'] ) ? self::sanitize_body( wp_unslash( $_POST['body'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		);
		$res    = Queue::send_one( $contact_id, $message );
		set_transient( 'a2email_notice_' . get_current_user_id(), array( $res['ok'] ? 'success' : 'error', $res['message'] ), 60 );
		wp_safe_redirect( admin_page_url( 'contacts' ) );
		exit;
	}

	/**
	 * Pause handler.
	 */
	public static function pause() {
		check_admin_referer( 'a2email_message' );
		$id = isset( $_GET['message'] ) ? absint( $_GET['message'] ) : 0;
		Queue::pause( $id );
		wp_safe_redirect( admin_page_url( 'messages', array( 'message' => $id ) ) );
		exit;
	}

	/**
	 * Resume handler.
	 */
	public static function resume() {
		check_admin_referer( 'a2email_message' );
		$id = isset( $_GET['message'] ) ? absint( $_GET['message'] ) : 0;
		Queue::resume( $id );
		Queue::process();
		wp_safe_redirect( admin_page_url( 'messages', array( 'message' => $id ) ) );
		exit;
	}

	/**
	 * AJAX: render a live preview of the body for the first matching contact (or a sample).
	 */
	public static function ajax_preview() {
		check_ajax_referer( 'a2email_preview', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( '', '', array( 'response' => 403 ) );
		}
		$message = array(
			'subject'   => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
			'preheader' => isset( $_POST['preheader'] ) ? sanitize_text_field( wp_unslash( $_POST['preheader'] ) ) : '',
			'body'      => isset( $_POST['body'] ) ? self::sanitize_body( wp_unslash( $_POST['body'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		);
		$segment = isset( $_POST['segment'] ) ? sanitize_key( wp_unslash( $_POST['segment'] ) ) : 'all';
		$sample  = Contacts::recipients( $segment, 1 );
		$contact = $sample ? $sample[0] : array( 'name' => __( 'Jordan Lee', 'a2e-mail' ), 'email' => 'jordan@example.com', 'token' => 'preview' );
		$render  = Templates::render( $message, $contact );
		wp_send_json_success( array( 'subject' => $render['subject'], 'html' => $render['html'] ) );
	}

	/**
	 * Render the compose screen.
	 */
	public static function render_compose() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$templates = Templates::all();
		$segments  = Contacts::segments();
		$mid       = isset( $_GET['message'] ) ? absint( $_GET['message'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current   = $mid ? self::get_message( $mid ) : null;
		$preset    = isset( $_GET['template'] ) ? absint( $_GET['template'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $current && $preset && isset( $templates[ $preset ] ) ) {
			$t       = $templates[ $preset ];
			$current = array( 'id' => 0, 'title' => $t['name'], 'subject' => $t['subject'], 'preheader' => $t['preheader'], 'body' => $t['body'], 'segment' => 'all', 'status' => 'draft' );
		}
		$v = wp_parse_args(
			(array) $current,
			array( 'id' => 0, 'title' => '', 'subject' => '', 'preheader' => '', 'body' => '', 'segment' => 'all', 'status' => 'draft' )
		);
		$sent_lock = in_array( $v['status'], array( 'sending', 'sent' ), true );
		$post_url  = esc_url( admin_url( 'admin-post.php' ) );
		?>
		<div class="wrap a2email">
			<h1 class="a2email-h1"><?php esc_html_e( 'Compose a message', 'a2e-mail' ); ?></h1>
			<p class="a2email-lead"><?php esc_html_e( 'Write your message or start from a template, choose who it goes to, preview it, send a test, then send. Long dashes are removed automatically and your contact details and an unsubscribe link are added to every email.', 'a2e-mail' ); ?></p>
			<?php Admin::print_notice(); ?>

			<div class="a2email-compose">
				<form method="post" action="<?php echo $post_url; // phpcs:ignore ?>" class="a2email-compose__form" id="a2email-form">
					<?php wp_nonce_field( 'a2email_compose' ); ?>
					<input type="hidden" name="message_id" value="<?php echo (int) $v['id']; ?>">

					<p class="a2email-field">
						<label for="a2email-template"><?php esc_html_e( 'Start from a template', 'a2e-mail' ); ?></label>
						<select id="a2email-template" class="a2email-input">
							<option value=""><?php esc_html_e( 'Blank (keep what I have)', 'a2e-mail' ); ?></option>
							<?php foreach ( $templates as $tid => $t ) : ?>
								<option value="<?php echo (int) $tid; ?>" data-subject="<?php echo esc_attr( $t['subject'] ); ?>" data-preheader="<?php echo esc_attr( $t['preheader'] ); ?>" data-body="<?php echo esc_attr( $t['body'] ); ?>"><?php echo esc_html( $t['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="a2email-help"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . TEMPLATE ) ); ?>"><?php esc_html_e( 'Manage templates', 'a2e-mail' ); ?></a></span>
					</p>

					<p class="a2email-field">
						<label for="a2email-title"><?php esc_html_e( 'Internal name (only you see this)', 'a2e-mail' ); ?></label>
						<input type="text" id="a2email-title" name="title" class="a2email-input" value="<?php echo esc_attr( $v['title'] ); ?>" placeholder="<?php esc_attr_e( 'October client update', 'a2e-mail' ); ?>">
					</p>

					<p class="a2email-field">
						<label for="a2email-subject"><?php esc_html_e( 'Subject line', 'a2e-mail' ); ?></label>
						<input type="text" id="a2email-subject" name="subject" class="a2email-input" value="<?php echo esc_attr( $v['subject'] ); ?>" placeholder="<?php esc_attr_e( 'A quick update on your eBay store', 'a2e-mail' ); ?>" required>
					</p>

					<p class="a2email-field">
						<label for="a2email-preheader"><?php esc_html_e( 'Preview text (shown after the subject in the inbox)', 'a2e-mail' ); ?></label>
						<input type="text" id="a2email-preheader" name="preheader" class="a2email-input" value="<?php echo esc_attr( $v['preheader'] ); ?>">
					</p>

					<p class="a2email-field">
						<label for="a2email-body"><?php esc_html_e( 'Message', 'a2e-mail' ); ?></label>
						<textarea id="a2email-body" name="body" class="a2email-input a2email-body" rows="16"><?php echo esc_textarea( $v['body'] ); ?></textarea>
						<span class="a2email-help"><?php esc_html_e( 'Blank line between paragraphs. Start a line with ## for a heading, or - for a bullet. Button: [[Label|https://link]]. Merge tags: {{first_name}}, {{whatsapp}}, {{phone}}, {{contact_email}}, {{site_url}}.', 'a2e-mail' ); ?></span>
					</p>

					<p class="a2email-field">
						<label for="a2email-segment"><?php esc_html_e( 'Send to', 'a2e-mail' ); ?></label>
						<select id="a2email-segment" name="segment" class="a2email-input">
							<?php foreach ( $segments as $key => $seg ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $v['segment'], $key ); ?>><?php echo esc_html( $seg['label'] ); ?> (<?php echo (int) $seg['count']; ?>)</option>
							<?php endforeach; ?>
						</select>
					</p>

					<div class="a2email-actions">
						<button type="submit" formaction="<?php echo $post_url; // phpcs:ignore ?>" name="action" value="a2email_save" class="button button-secondary"><?php esc_html_e( 'Save draft', 'a2e-mail' ); ?></button>
						<span class="a2email-test">
							<input type="email" name="test_email" class="a2email-input a2email-input--inline" placeholder="<?php esc_attr_e( 'you@example.com', 'a2e-mail' ); ?>" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
							<button type="submit" name="action" value="a2email_test" class="button"><?php esc_html_e( 'Send a test', 'a2e-mail' ); ?></button>
						</span>
						<?php if ( ! $sent_lock ) : ?>
							<button type="submit" name="action" value="a2email_send" class="button button-primary" id="a2email-send" data-confirm="<?php esc_attr_e( 'Send this message to everyone in the selected audience?', 'a2e-mail' ); ?>"><?php esc_html_e( 'Send to audience', 'a2e-mail' ); ?></button>
						<?php else : ?>
							<span class="a2email-pill a2email-pill--ok"><?php esc_html_e( 'Already sent', 'a2e-mail' ); ?></span>
						<?php endif; ?>
					</div>
				</form>

				<div class="a2email-preview">
					<div class="a2email-preview__bar">
						<span class="a2email-preview__label"><?php esc_html_e( 'Live preview', 'a2e-mail' ); ?></span>
						<span class="a2email-preview__subject" id="a2email-preview-subject"></span>
					</div>
					<iframe id="a2email-preview-frame" title="<?php esc_attr_e( 'Email preview', 'a2e-mail' ); ?>"></iframe>
				</div>
			</div>
		</div>
		<?php
	}
}
