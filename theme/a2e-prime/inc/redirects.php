<?php
/**
 * Redirects: built-in 301s for retired/alternative URLs, a manager under A2E → Redirects for
 * your own rules, and a 404 log so any broken old link can be redirected in one click.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Built-in map of old or likely-typed paths to their current page (all 301).
 *
 * @return array from-path => to-path (both without leading/trailing slashes).
 */
function a2e_builtin_redirects() {
	$map = array(
		'case-studies-overview'          => 'case-studies',
		'services'                       => 'amazon-to-ebay-dropshipping-service',
		'our-services'                   => 'amazon-to-ebay-dropshipping-service',
		'service-guides'                 => 'amazon-to-ebay-dropshipping-service',
		'service-guides/pricing-service' => 'pricing',
		'service-guides/consultation'    => 'consultation',
		'tools'                          => 'free-tools',
		'free-tool'                      => 'free-tools',
		'blogs'                          => 'blog',
		'about-us'                       => 'about',
		'reviews'                        => 'testimonials',
		'plans'                          => 'pricing',
		'subscriptions'                  => 'subscription-plans',
		'subscription'                   => 'subscription-plans',
		'quote'                          => 'quote-request',
		'get-a-quote'                    => 'quote-request',
		'free-audit'                     => 'quote-request',
		'book'                           => 'consultation',
		'book-a-call'                    => 'consultation',
		'checklist'                      => 'amazon-to-ebay-dropshipping-checklist',
		'contact-us'                     => 'contact',
		'client-area'                    => 'account',
	);
	if ( ! class_exists( 'WooCommerce' ) ) {
		$map['shop']         = 'shop';
		$map['package-shop'] = 'shop';
		$map['products']     = 'shop';
		$map['cart']         = 'shop';
		$map['checkout']     = 'shop';
		$map['my-account']   = 'account';
	}
	foreach ( array_keys( a2e_tools() ) as $slug ) {
		$map[ $slug ]          = 'free-tools/' . $slug;
		$map[ 'tools/' . $slug ] = 'free-tools/' . $slug;
	}
	foreach ( array_keys( a2e_services() ) as $slug ) {
		$map[ 'services/' . $slug ] = 'service-guides/' . $slug;
	}
	if ( ! a2e_seo_plugin_active() ) {
		$map['sitemap.xml']       = 'wp-sitemap.xml';
		$map['sitemap_index.xml'] = 'wp-sitemap.xml';
	}
	return apply_filters( 'a2e_builtin_redirects', $map );
}

/**
 * Custom rules saved in A2E → Redirects.
 *
 * @return array list of [ from, to, code, hits ]
 */
function a2e_custom_redirects() {
	$rules = get_option( 'a2e_redirects', array() );
	return is_array( $rules ) ? $rules : array();
}

/**
 * Normalise a path for matching.
 *
 * @param string $path Path or URL.
 * @return string
 */
function a2e_normalize_path( $path ) {
	$path = (string) $path;
	if ( preg_match( '#^https?://#i', $path ) ) {
		$path = (string) wp_parse_url( $path, PHP_URL_PATH );
	}
	return strtolower( trim( rawurldecode( $path ), '/ ' ) );
}

/**
 * Apply redirects. Custom rules run on every request (so a live page can be moved);
 * built-in rules only run when the request would otherwise 404, or for the overview page
 * that is now the /case-studies/ archive.
 */
function a2e_do_redirects() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$path = a2e_normalize_path( a2e_request_path() );
	if ( '' === $path ) {
		return;
	}
	$query = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';

	$rules = a2e_custom_redirects();
	foreach ( $rules as $i => $rule ) {
		if ( a2e_normalize_path( $rule['from'] ) === $path ) {
			$rules[ $i ]['hits'] = isset( $rule['hits'] ) ? (int) $rule['hits'] + 1 : 1;
			update_option( 'a2e_redirects', $rules, false );
			$to = preg_match( '#^https?://#i', $rule['to'] ) ? $rule['to'] : home_url( '/' . ltrim( $rule['to'], '/' ) );
			wp_redirect( $to . ( $query && false === strpos( $to, '?' ) ? '?' . $query : '' ), in_array( (int) $rule['code'], array( 301, 302, 307, 308 ), true ) ? (int) $rule['code'] : 301, 'A2E' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}
	}

	$map = a2e_builtin_redirects();
	if ( isset( $map[ $path ] ) && ( is_404() || 'case-studies-overview' === $path || 'service-guides' === $path ) ) {
		$target = $map[ $path ];
		$url    = 'case-studies' === $target ? get_post_type_archive_link( 'a2e_case_study' ) : ( 'testimonials' === $target ? get_post_type_archive_link( 'a2e_testimonial' ) : ( 'blog' === $target ? a2e_blog_url() : ( 'wp-sitemap.xml' === $target ? home_url( '/wp-sitemap.xml' ) : ( 'shop' === $target ? ( a2e_shop_page_id() ? a2e_shop_url() : a2e_url( 'pricing' ) ) : a2e_url( $target ) ) ) ) );
		if ( $url ) {
			wp_safe_redirect( $url . ( $query ? '?' . $query : '' ), 301, 'A2E' );
			exit;
		}
	}
}
add_action( 'template_redirect', 'a2e_do_redirects', 1 );

/**
 * Log 404s (path, hits, last seen, referrer) for the Redirects screen.
 */
function a2e_log_404() {
	if ( ! is_404() || is_admin() ) {
		return;
	}
	$path = a2e_normalize_path( a2e_request_path() );
	if ( '' === $path || preg_match( '#\.(php|env|git|xml\.gz|sql|bak|ini|log)$|^wp-(admin|content|includes)/|xmlrpc#', $path ) ) {
		return; // Ignore bot probes.
	}
	$log = get_option( 'a2e_404_log', array() );
	$log = is_array( $log ) ? $log : array();
	if ( ! isset( $log[ $path ] ) && count( $log ) >= 200 ) {
		uasort(
			$log,
			function ( $a, $b ) {
				return $a['last'] - $b['last'];
			}
		);
		array_shift( $log );
	}
	$ref           = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	$log[ $path ]  = array(
		'hits' => isset( $log[ $path ] ) ? (int) $log[ $path ]['hits'] + 1 : 1,
		'last' => time(),
		'ref'  => $ref ? $ref : ( isset( $log[ $path ]['ref'] ) ? $log[ $path ]['ref'] : '' ),
	);
	update_option( 'a2e_404_log', $log, false );
}
add_action( 'template_redirect', 'a2e_log_404', 99 );

/**
 * Admin page.
 */
function a2e_redirects_menu() {
	add_submenu_page( 'a2e-settings', __( 'Redirects', 'a2e' ), __( 'Redirects', 'a2e' ), 'manage_options', 'a2e-redirects', 'a2e_render_redirects_page' );
}
add_action( 'admin_menu', 'a2e_redirects_menu', 20 );

/**
 * Handle add / delete / bulk import / clear log.
 */
function a2e_redirects_actions() {
	if ( ! current_user_can( 'manage_options' ) || empty( $_POST['a2e_redirect_action'] ) ) {
		return;
	}
	check_admin_referer( 'a2e_redirects' );
	$action = sanitize_key( wp_unslash( $_POST['a2e_redirect_action'] ) );
	$rules  = a2e_custom_redirects();

	$add = function ( $from, $to, $code ) use ( &$rules ) {
		$from = '/' . a2e_normalize_path( $from ) . '/';
		$to   = trim( $to );
		if ( '//' === $from || '' === $to ) {
			return;
		}
		$to = preg_match( '#^https?://#i', $to ) ? esc_url_raw( $to ) : '/' . ltrim( sanitize_text_field( $to ), '/' );
		foreach ( $rules as $i => $r ) {
			if ( a2e_normalize_path( $r['from'] ) === a2e_normalize_path( $from ) ) {
				unset( $rules[ $i ] );
			}
		}
		$rules[] = array( 'from' => $from, 'to' => $to, 'code' => in_array( (int) $code, array( 301, 302 ), true ) ? (int) $code : 301, 'hits' => 0 );
	};

	if ( 'add' === $action ) {
		$add( isset( $_POST['from'] ) ? wp_unslash( $_POST['from'] ) : '', isset( $_POST['to'] ) ? wp_unslash( $_POST['to'] ) : '', isset( $_POST['code'] ) ? absint( $_POST['code'] ) : 301 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = isset( $_POST['from'] ) ? a2e_normalize_path( sanitize_text_field( wp_unslash( $_POST['from'] ) ) ) : '';
		$log  = get_option( 'a2e_404_log', array() );
		if ( is_array( $log ) && isset( $log[ $path ] ) ) {
			unset( $log[ $path ] );
			update_option( 'a2e_404_log', $log, false );
		}
	} elseif ( 'import' === $action && ! empty( $_POST['bulk'] ) ) {
		foreach ( preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( wp_unslash( $_POST['bulk'] ) ) ) as $line ) {
			$parts = array_map( 'trim', preg_split( '/\s*(,|\t|->|=>)\s*|\s+/', trim( $line ), 3 ) );
			if ( count( $parts ) >= 2 && '' !== $parts[0] ) {
				$add( $parts[0], $parts[1], isset( $parts[2] ) ? (int) $parts[2] : 301 );
			}
		}
	} elseif ( 'delete' === $action && isset( $_POST['index'] ) ) {
		$index = absint( $_POST['index'] );
		$rules = array_values( $rules );
		unset( $rules[ $index ] );
	} elseif ( 'clear_log' === $action ) {
		delete_option( 'a2e_404_log' );
	}
	update_option( 'a2e_redirects', array_values( $rules ), false );
	wp_safe_redirect( admin_url( 'admin.php?page=a2e-redirects&updated=1' ) );
	exit;
}
add_action( 'admin_init', 'a2e_redirects_actions' );

/**
 * Render the Redirects screen.
 */
function a2e_render_redirects_page() {
	$rules = array_values( a2e_custom_redirects() );
	$log   = get_option( 'a2e_404_log', array() );
	$log   = is_array( $log ) ? $log : array();
	uasort(
		$log,
		function ( $a, $b ) {
			return $b['hits'] - $a['hits'];
		}
	);
	$builtin = a2e_builtin_redirects();
	?>
	<div class="wrap a2e-admin">
		<h1 class="a2e-admin__title"><?php esc_html_e( 'Redirects', 'a2e' ); ?></h1>
		<p class="a2e-admin__lead"><?php esc_html_e( 'Every URL from the previous site keeps working. Add your own permanent (301) redirects here (for example when you rename a blog post) and fix any 404s visitors hit.', 'a2e' ); ?></p>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Saved.', 'a2e' ); ?></p></div><?php endif; ?>

		<div class="a2e-cards">
			<div class="a2e-card">
				<h2><?php esc_html_e( 'Add a redirect', 'a2e' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'a2e_redirects' ); ?>
					<input type="hidden" name="a2e_redirect_action" value="add">
					<p><label><?php esc_html_e( 'Old path', 'a2e' ); ?><br><input type="text" name="from" class="regular-text" placeholder="/old-page/" required value="<?php echo isset( $_GET['from'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['from'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>"></label></p>
					<p><label><?php esc_html_e( 'New path or full URL', 'a2e' ); ?><br><input type="text" name="to" class="regular-text" placeholder="/new-page/" required></label></p>
					<p><label><?php esc_html_e( 'Type', 'a2e' ); ?> <select name="code"><option value="301"><?php esc_html_e( '301: permanent (best for SEO)', 'a2e' ); ?></option><option value="302"><?php esc_html_e( '302: temporary', 'a2e' ); ?></option></select></label></p>
					<?php submit_button( __( 'Add redirect', 'a2e' ), 'primary', 'submit', false ); ?>
				</form>
			</div>
			<div class="a2e-card">
				<h2><?php esc_html_e( 'Bulk import', 'a2e' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'a2e_redirects' ); ?>
					<input type="hidden" name="a2e_redirect_action" value="import">
					<p><textarea name="bulk" rows="5" class="large-text code" placeholder="/old-page/, /new-page/&#10;/another-old/, https://example.com/, 302"></textarea></p>
					<p class="description"><?php esc_html_e( 'One per line: old, new, optional code (301/302). Separate with a comma, tab or space.', 'a2e' ); ?></p>
					<?php submit_button( __( 'Import', 'a2e' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>

		<h2><?php esc_html_e( 'Your redirects', 'a2e' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'From', 'a2e' ); ?></th><th><?php esc_html_e( 'To', 'a2e' ); ?></th><th><?php esc_html_e( 'Type', 'a2e' ); ?></th><th><?php esc_html_e( 'Hits', 'a2e' ); ?></th><th></th></tr></thead>
			<tbody>
				<?php if ( ! $rules ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No custom redirects yet.', 'a2e' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rules as $i => $r ) : ?>
					<tr>
						<td><code><?php echo esc_html( $r['from'] ); ?></code></td>
						<td><code><?php echo esc_html( $r['to'] ); ?></code></td>
						<td><?php echo (int) $r['code']; ?></td>
						<td><?php echo isset( $r['hits'] ) ? (int) $r['hits'] : 0; ?></td>
						<td><form method="post" style="margin:0"><?php wp_nonce_field( 'a2e_redirects' ); ?><input type="hidden" name="a2e_redirect_action" value="delete"><input type="hidden" name="index" value="<?php echo (int) $i; ?>"><button class="button-link-delete button-link" type="submit"><?php esc_html_e( 'Delete', 'a2e' ); ?></button></form></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2 style="margin-top:2em"><?php esc_html_e( 'Recent 404s (pages not found)', 'a2e' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Old links that visitors or Google still request. Click “Redirect” to send them somewhere useful.', 'a2e' ); ?></p>
		<table class="widefat striped">
			<thead><tr><th><?php esc_html_e( 'Path', 'a2e' ); ?></th><th><?php esc_html_e( 'Hits', 'a2e' ); ?></th><th><?php esc_html_e( 'Last seen', 'a2e' ); ?></th><th><?php esc_html_e( 'Came from', 'a2e' ); ?></th><th></th></tr></thead>
			<tbody>
				<?php if ( ! $log ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No 404s logged. 🎉', 'a2e' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( array_slice( $log, 0, 50, true ) as $path => $row ) : ?>
					<tr>
						<td><code>/<?php echo esc_html( $path ); ?>/</code></td>
						<td><?php echo (int) $row['hits']; ?></td>
						<td><?php echo esc_html( human_time_diff( (int) $row['last'] ) ); ?></td>
						<td><?php echo $row['ref'] ? '<a href="' . esc_url( $row['ref'] ) . '" target="_blank" rel="noopener">' . esc_html( wp_html_excerpt( $row['ref'], 50, '…' ) ) . '</a>' : '-'; ?></td>
						<td><a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'a2e-redirects', 'from' => '/' . $path . '/' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Redirect', 'a2e' ); ?></a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( $log ) : ?>
			<form method="post" style="margin-top:1em"><?php wp_nonce_field( 'a2e_redirects' ); ?><input type="hidden" name="a2e_redirect_action" value="clear_log"><?php submit_button( __( 'Clear 404 log', 'a2e' ), 'secondary small', 'submit', false ); ?></form>
		<?php endif; ?>

		<details style="margin-top:2em"><summary><strong><?php /* translators: %d: number of rules */ printf( esc_html__( 'Built-in redirects (%d), always on', 'a2e' ), count( $builtin ) ); ?></strong></summary>
			<table class="widefat striped" style="margin-top:1em"><tbody>
				<?php foreach ( $builtin as $from => $to ) : ?><tr><td><code>/<?php echo esc_html( $from ); ?></code></td><td>→ <code>/<?php echo esc_html( $to ); ?>/</code></td></tr><?php endforeach; ?>
			</tbody></table>
		</details>
	</div>
	<?php
}
