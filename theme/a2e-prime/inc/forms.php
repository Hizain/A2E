<?php
/**
 * Lead forms (free audit / quote, consultation, contact, newsletter).
 *
 * Every submission is saved under A2E → Leads and emailed to the lead address. Spam is
 * blocked with a nonce, a honeypot, a minimum fill time and a per-visitor rate limit.
 * Forms work without JavaScript; with it they submit inline.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Status message after a non-JS submission.
 *
 * @param string $type Form type.
 * @return string
 */
function a2e_form_notice( $type ) {
	$sent  = isset( $_GET['a2e_sent'] ) ? sanitize_key( wp_unslash( $_GET['a2e_sent'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$error = isset( $_GET['a2e_error'] ) ? sanitize_key( wp_unslash( $_GET['a2e_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $sent === $type ) {
		return '<div class="form-notice form-notice--ok" role="status">' . a2e_icon( 'check-circle' ) . '<p>' . esc_html( a2e_form_success_text( $type ) ) . '</p></div>';
	}
	if ( $error && isset( $_GET['a2e_form'] ) && sanitize_key( wp_unslash( $_GET['a2e_form'] ) ) === $type ) { // phpcs:ignore WordPress.Security.NonceVerification
		return '<div class="form-notice form-notice--err" role="alert">' . a2e_icon( 'alert' ) . '<p>' . esc_html( a2e_form_error_text( $error ) ) . '</p></div>';
	}
	return '';
}

/**
 * Success copy.
 *
 * @param string $type Form type.
 * @return string
 */
function a2e_form_success_text( $type ) {
	if ( 'newsletter' === $type ) {
		return __( 'You are on the list. We will email you when we publish new guides and tools.', 'a2e' );
	}
	return __( 'Thank you — your message is in. A real person will reply within one business day (usually much sooner). Want an instant answer? Message us on WhatsApp.', 'a2e' );
}

/**
 * Error copy.
 *
 * @param string $code Code.
 * @return string
 */
function a2e_form_error_text( $code ) {
	$map = array(
		'missing' => __( 'Please fill in your name and a valid email address.', 'a2e' ),
		'spam'    => __( 'Your message could not be sent. Please try again or contact us on WhatsApp.', 'a2e' ),
		'rate'    => __( 'You have sent several messages already. Please wait a few minutes or message us on WhatsApp.', 'a2e' ),
	);
	return isset( $map[ $code ] ) ? $map[ $code ] : $map['spam'];
}

/**
 * Hidden fields shared by every form.
 *
 * @param string $type Form type.
 */
function a2e_form_hidden( $type ) {
	wp_nonce_field( 'a2e_lead', 'a2e_nonce' );
	?>
	<input type="hidden" name="action" value="a2e_lead">
	<input type="hidden" name="a2e_form" value="<?php echo esc_attr( $type ); ?>">
	<input type="hidden" name="a2e_t" value="<?php echo esc_attr( (string) time() ); ?>">
	<input type="hidden" name="a2e_ref" value="<?php echo esc_attr( a2e_request_path() ); ?>">
	<div class="hp" aria-hidden="true"><label>Company website <input type="text" name="a2e_website" tabindex="-1" autocomplete="off"></label></div>
	<?php
}

/**
 * Render a form.
 *
 * @param string $type quote|consultation|contact.
 */
function a2e_render_form( $type = 'quote' ) {
	$type   = in_array( $type, array( 'quote', 'consultation', 'contact' ), true ) ? $type : 'quote';
	$plan   = isset( $_GET['plan'] ) ? sanitize_text_field( wp_unslash( $_GET['plan'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$qty    = isset( $_GET['listing_quantity'] ) ? absint( $_GET['listing_quantity'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	$action = esc_url( admin_url( 'admin-post.php' ) );
	echo a2e_form_notice( $type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( 'quote' === $type ) :
		$needs = array(
			'research'   => __( 'Product research', 'a2e' ),
			'listings'   => __( 'Listings & eBay SEO', 'a2e' ),
			'setup'      => __( 'Store setup', 'a2e' ),
			'management' => __( 'Account management', 'a2e' ),
			'orders'     => __( 'Order fulfillment', 'a2e' ),
			'inventory'  => __( 'Inventory & pricing', 'a2e' ),
			'va'         => __( 'VA support', 'a2e' ),
			'unsure'     => __( 'Not sure yet', 'a2e' ),
		);
		$stages = array(
			'new'     => array( 'rocket', __( 'Starting from scratch', 'a2e' ), __( 'No store yet, or brand new', 'a2e' ) ),
			'slow'    => array( 'trend', __( 'Live, but few sales', 'a2e' ), __( 'Listings get little traction', 'a2e' ) ),
			'scale'   => array( 'chart', __( 'Selling — want to scale', 'a2e' ), __( 'Ready for more volume', 'a2e' ) ),
			'multi'   => array( 'layers', __( 'Several stores', 'a2e' ), __( 'Agency or multi-store owner', 'a2e' ) ),
		);
		?>
		<form class="lead-form lead-form--steps" method="post" action="<?php echo $action; // phpcs:ignore ?>" data-lead-form data-steps novalidate>
			<?php a2e_form_hidden( 'quote' ); ?>
			<?php if ( $plan ) : ?><input type="hidden" name="plan" value="<?php echo esc_attr( $plan ); ?>"><?php endif; ?>
			<ol class="stepper" aria-hidden="true"><li class="is-active"><span>1</span><?php esc_html_e( 'Your store', 'a2e' ); ?></li><li><span>2</span><?php esc_html_e( 'Your needs', 'a2e' ); ?></li><li><span>3</span><?php esc_html_e( 'Details', 'a2e' ); ?></li><li><span>4</span><?php esc_html_e( 'Contact', 'a2e' ); ?></li></ol>

			<fieldset class="form-step is-active" data-step="1">
				<legend><?php esc_html_e( 'Where is your store today?', 'a2e' ); ?></legend>
				<div class="choice-grid">
					<?php foreach ( $stages as $key => $s ) : ?>
						<label class="choice"><input type="radio" name="stage" value="<?php echo esc_attr( $key ); ?>" <?php checked( 'scale', $key ); ?>><span class="choice__body"><?php a2e_the_icon( $s[0] ); ?><strong><?php echo esc_html( $s[1] ); ?></strong><small><?php echo esc_html( $s[2] ); ?></small></span></label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="form-step" data-step="2">
				<legend><?php esc_html_e( 'What would you like us to take over?', 'a2e' ); ?></legend>
				<div class="check-grid">
					<?php foreach ( $needs as $key => $label ) : ?>
						<label class="check-pill"><input type="checkbox" name="needs[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, array( 'research', 'listings' ), true ) ); ?>><span><?php a2e_the_icon( 'check' ); ?><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="form-step" data-step="3">
				<legend><?php esc_html_e( 'A few details', 'a2e' ); ?></legend>
				<div class="field-grid">
					<div class="field"><label for="q-volume"><?php esc_html_e( 'Listings per month', 'a2e' ); ?></label>
						<select id="q-volume" name="volume">
							<?php foreach ( array( '100' => __( 'Around 100 (one-time batch)', 'a2e' ), '150' => '150 / month', '500' => '500 / month', '501' => __( 'More than 500', 'a2e' ), '0' => __( 'Not sure', 'a2e' ) ) as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $qty ? ( $qty > 500 ? '501' : (string) $qty ) : '150', (string) $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="field"><label for="q-budget"><?php esc_html_e( 'Monthly budget', 'a2e' ); ?></label>
						<select id="q-budget" name="budget">
							<?php foreach ( array( 'under-200' => __( 'Under $200', 'a2e' ), '200-400' => '$200 – $400', '400-800' => '$400 – $800', '800+' => '$800+', 'unsure' => __( 'Not sure yet', 'a2e' ) ) as $v => $l ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( '200-400', $v ); ?>><?php echo esc_html( $l ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="field field--wide"><label for="q-store"><?php esc_html_e( 'eBay store link (optional)', 'a2e' ); ?></label><input id="q-store" type="url" name="store_url" placeholder="https://www.ebay.com/str/yourstore"></div>
					<div class="field field--wide"><label for="q-msg"><?php esc_html_e( 'Anything we should know? (optional)', 'a2e' ); ?></label><textarea id="q-msg" name="message" rows="3" placeholder="<?php esc_attr_e( 'Goals, problems, niches you like…', 'a2e' ); ?>"></textarea></div>
				</div>
			</fieldset>

			<fieldset class="form-step" data-step="4">
				<legend><?php esc_html_e( 'Where should we send your audit?', 'a2e' ); ?></legend>
				<div class="field-grid">
					<div class="field"><label for="q-name"><?php esc_html_e( 'Your name', 'a2e' ); ?> *</label><input id="q-name" type="text" name="name" required autocomplete="name"></div>
					<div class="field"><label for="q-email"><?php esc_html_e( 'Email', 'a2e' ); ?> *</label><input id="q-email" type="email" name="email" required autocomplete="email"></div>
					<div class="field field--wide"><label for="q-phone"><?php esc_html_e( 'WhatsApp number (optional, for faster replies)', 'a2e' ); ?></label><input id="q-phone" type="tel" name="phone" autocomplete="tel" placeholder="+1 555 123 4567"></div>
				</div>
				<p class="form-fine"><?php a2e_the_icon( 'lock' ); ?><?php esc_html_e( 'We use your details only to reply to you. No spam, ever.', 'a2e' ); ?></p>
			</fieldset>

			<div class="form-nav">
				<button type="button" class="btn btn--ghost" data-prev hidden><?php a2e_the_icon( 'arrow-left' ); ?><span><?php esc_html_e( 'Back', 'a2e' ); ?></span></button>
				<button type="button" class="btn btn--primary" data-next><span><?php esc_html_e( 'Continue', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></button>
				<button type="submit" class="btn btn--primary" data-submit hidden><span><?php esc_html_e( 'Get my free audit', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></button>
			</div>
			<div class="form-result" data-result hidden></div>
		</form>
		<?php
		return;
	endif;

	if ( 'consultation' === $type ) :
		$topics = array( __( 'Store review', 'a2e' ), __( 'Product research', 'a2e' ), __( 'Listing SEO', 'a2e' ), __( 'Account health', 'a2e' ), __( 'Choosing a plan', 'a2e' ), __( 'Scaling', 'a2e' ) );
		?>
		<form class="lead-form" method="post" action="<?php echo $action; // phpcs:ignore ?>" data-lead-form novalidate>
			<?php a2e_form_hidden( 'consultation' ); ?>
			<div class="field-grid">
				<div class="field"><label for="c-name"><?php esc_html_e( 'Your name', 'a2e' ); ?> *</label><input id="c-name" type="text" name="name" required autocomplete="name"></div>
				<div class="field"><label for="c-email"><?php esc_html_e( 'Email', 'a2e' ); ?> *</label><input id="c-email" type="email" name="email" required autocomplete="email"></div>
				<div class="field"><label for="c-phone"><?php esc_html_e( 'WhatsApp (optional)', 'a2e' ); ?></label><input id="c-phone" type="tel" name="phone" autocomplete="tel"></div>
				<div class="field"><label for="c-time"><?php esc_html_e( 'Best time & time zone', 'a2e' ); ?></label><input id="c-time" type="text" name="time" placeholder="<?php esc_attr_e( 'e.g. Weekdays after 5pm EST', 'a2e' ); ?>"></div>
				<div class="field field--wide"><span class="field__label"><?php esc_html_e( 'What should we cover?', 'a2e' ); ?></span>
					<div class="check-grid check-grid--sm">
						<?php foreach ( $topics as $t ) : ?><label class="check-pill"><input type="checkbox" name="needs[]" value="<?php echo esc_attr( $t ); ?>"><span><?php a2e_the_icon( 'check' ); ?><?php echo esc_html( $t ); ?></span></label><?php endforeach; ?>
					</div>
				</div>
				<div class="field field--wide"><label for="c-store"><?php esc_html_e( 'eBay store link (optional)', 'a2e' ); ?></label><input id="c-store" type="url" name="store_url" placeholder="https://www.ebay.com/str/yourstore"></div>
				<div class="field field--wide"><label for="c-msg"><?php esc_html_e( 'Your questions', 'a2e' ); ?></label><textarea id="c-msg" name="message" rows="4"></textarea></div>
			</div>
			<button type="submit" class="btn btn--primary btn--block"><span><?php esc_html_e( 'Request my consultation', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></button>
			<p class="form-fine"><?php a2e_the_icon( 'lock' ); ?><?php echo esc_html( a2e_opt( 'response_time' ) ); ?></p>
			<div class="form-result" data-result hidden></div>
		</form>
		<?php
		return;
	endif;
	?>
	<form class="lead-form" method="post" action="<?php echo $action; // phpcs:ignore ?>" data-lead-form novalidate>
		<?php a2e_form_hidden( 'contact' ); ?>
		<div class="field-grid">
			<div class="field"><label for="m-name"><?php esc_html_e( 'Your name', 'a2e' ); ?> *</label><input id="m-name" type="text" name="name" required autocomplete="name"></div>
			<div class="field"><label for="m-email"><?php esc_html_e( 'Email', 'a2e' ); ?> *</label><input id="m-email" type="email" name="email" required autocomplete="email"></div>
			<div class="field"><label for="m-phone"><?php esc_html_e( 'WhatsApp (optional)', 'a2e' ); ?></label><input id="m-phone" type="tel" name="phone" autocomplete="tel"></div>
			<div class="field"><label for="m-topic"><?php esc_html_e( 'Topic', 'a2e' ); ?></label>
				<select id="m-topic" name="topic">
					<option><?php esc_html_e( 'A question about plans', 'a2e' ); ?></option>
					<option><?php esc_html_e( 'My current subscription', 'a2e' ); ?></option>
					<option><?php esc_html_e( 'Partnership', 'a2e' ); ?></option>
					<option><?php esc_html_e( 'Something else', 'a2e' ); ?></option>
				</select>
			</div>
			<div class="field field--wide"><label for="m-msg"><?php esc_html_e( 'Message', 'a2e' ); ?> *</label><textarea id="m-msg" name="message" rows="5" required></textarea></div>
		</div>
		<button type="submit" class="btn btn--primary btn--block"><span><?php esc_html_e( 'Send message', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></button>
		<div class="form-result" data-result hidden></div>
	</form>
	<?php
}

/**
 * Newsletter form.
 *
 * @param string $button Button label.
 * @param string $class  Extra class.
 */
function a2e_render_newsletter( $button = '', $class = '' ) {
	$button = $button ? $button : __( 'Subscribe', 'a2e' );
	echo a2e_form_notice( 'newsletter' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	<form class="news-form <?php echo esc_attr( $class ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-lead-form novalidate>
		<?php a2e_form_hidden( 'newsletter' ); ?>
		<label class="screen-reader-text" for="nl-<?php echo esc_attr( wp_unique_id() ); ?>"><?php esc_html_e( 'Email address', 'a2e' ); ?></label>
		<input type="email" name="email" required placeholder="<?php esc_attr_e( 'you@email.com', 'a2e' ); ?>" autocomplete="email">
		<button type="submit" class="btn btn--primary btn--sm"><span><?php echo esc_html( $button ); ?></span></button>
		<div class="form-result" data-result hidden></div>
	</form>
	<?php
}

/**
 * Handle a submission (admin-post, logged in or not).
 */
function a2e_handle_lead() {
	$is_ajax = ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) );
	$type    = isset( $_POST['a2e_form'] ) ? sanitize_key( wp_unslash( $_POST['a2e_form'] ) ) : 'contact';
	$type    = in_array( $type, array( 'quote', 'consultation', 'contact', 'newsletter' ), true ) ? $type : 'contact';
	$back    = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back    = remove_query_arg( array( 'a2e_sent', 'a2e_error', 'a2e_form' ), $back );

	$fail = function ( $code ) use ( $is_ajax, $back, $type ) {
		if ( $is_ajax ) {
			wp_send_json_error( array( 'message' => a2e_form_error_text( $code ) ), 400 );
		}
		wp_safe_redirect( add_query_arg( array( 'a2e_error' => $code, 'a2e_form' => $type ), $back ) . '#' . $type );
		exit;
	};

	// Logged-out visitors are checked by honeypot, fill time and rate limit instead of a nonce,
	// so forms keep working on pages served from a full-page cache (cached nonces expire).
	$nonce = isset( $_POST['a2e_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['a2e_nonce'] ) ) : '';
	if ( ( is_user_logged_in() && ! wp_verify_nonce( $nonce, 'a2e_lead' ) ) || ! empty( $_POST['a2e_website'] ) ) {
		$fail( 'spam' );
	}
	$started = isset( $_POST['a2e_t'] ) ? absint( $_POST['a2e_t'] ) : 0;
	if ( $started && ( time() - $started ) < 3 ) {
		$fail( 'spam' );
	}

	$ip_key = 'a2e_rl_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 6 ) {
		$fail( 'rate' );
	}
	set_transient( $ip_key, $count + 1, 15 * MINUTE_IN_SECONDS );

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	if ( ! is_email( $email ) || ( 'newsletter' !== $type && '' === $name ) ) {
		$fail( 'missing' );
	}

	$labels = array(
		'stage'     => __( 'Store stage', 'a2e' ),
		'needs'     => __( 'Needs', 'a2e' ),
		'volume'    => __( 'Listings per month', 'a2e' ),
		'budget'    => __( 'Budget', 'a2e' ),
		'store_url' => __( 'Store', 'a2e' ),
		'plan'      => __( 'Plan', 'a2e' ),
		'time'      => __( 'Best time', 'a2e' ),
		'topic'     => __( 'Topic', 'a2e' ),
		'phone'     => __( 'WhatsApp / phone', 'a2e' ),
		'message'   => __( 'Message', 'a2e' ),
		'a2e_ref'   => __( 'Sent from', 'a2e' ),
	);
	$lines  = array(
		__( 'Name', 'a2e' ) . ': ' . $name,
		__( 'Email', 'a2e' ) . ': ' . $email,
	);
	$phone  = '';
	foreach ( $labels as $key => $label ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_array( $raw ) ) {
			$value = implode( ', ', array_map( 'sanitize_text_field', $raw ) );
		} elseif ( 'message' === $key ) {
			$value = sanitize_textarea_field( $raw );
		} elseif ( 'store_url' === $key ) {
			$value = esc_url_raw( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}
		if ( '' === $value ) {
			continue;
		}
		if ( 'phone' === $key ) {
			$phone = $value;
		}
		if ( 'a2e_ref' === $key ) {
			$value = home_url( '/' . $value );
		}
		$lines[] = $label . ': ' . $value;
	}
	$body = implode( "\n", $lines );

	$titles = array(
		'quote'        => __( 'Free audit request', 'a2e' ),
		'consultation' => __( 'Consultation request', 'a2e' ),
		'contact'      => __( 'Contact message', 'a2e' ),
		'newsletter'   => __( 'Newsletter signup', 'a2e' ),
	);
	$lead_id = wp_insert_post(
		array(
			'post_type'    => 'a2e_lead',
			'post_status'  => 'private',
			'post_title'   => $titles[ $type ] . ' — ' . ( $name ? $name : $email ),
			'post_content' => $body,
		)
	);
	if ( $lead_id && ! is_wp_error( $lead_id ) ) {
		update_post_meta( $lead_id, '_a2e_form', $type );
		update_post_meta( $lead_id, '_a2e_email', $email );
		if ( $phone ) {
			update_post_meta( $lead_id, '_a2e_phone', $phone );
		}
	}

	$to = a2e_opt( 'lead_email' );
	$to = is_email( $to ) ? $to : get_option( 'admin_email' );
	wp_mail(
		$to,
		sprintf( '[%1$s] %2$s — %3$s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $titles[ $type ], $name ? $name : $email ),
		$body . "\n\n" . admin_url( 'edit.php?post_type=a2e_lead' ),
		array( 'Reply-To: ' . ( $name ? $name . ' ' : '' ) . '<' . $email . '>' )
	);

	do_action( 'a2e_lead_received', $type, $email, $name, $lead_id );

	if ( $is_ajax ) {
		wp_send_json_success( array( 'message' => a2e_form_success_text( $type ) ) );
	}
	wp_safe_redirect( add_query_arg( 'a2e_sent', $type, $back ) . '#' . $type );
	exit;
}
add_action( 'admin_post_a2e_lead', 'a2e_handle_lead' );
add_action( 'admin_post_nopriv_a2e_lead', 'a2e_handle_lead' );
