<?php
/**
 * The front-end widget: shortcode, block, assets and markup. The seller-level logic,
 * recovery maths, suggestions and report capture all run in the browser from
 * assets/health.js against eBay US seller standards.
 *
 * @package A2E_Health
 */

namespace A2E\Health;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget.
 */
class Widget {

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'a2e_health_checker', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Register (do not enqueue) assets.
	 */
	public static function register_assets() {
		wp_register_style( 'a2ehc', A2EHC_URL . 'assets/health.css', array(), VERSION );
		wp_register_script( 'a2ehc', A2EHC_URL . 'assets/health.js', array(), VERSION, true );
		wp_localize_script(
			'a2ehc',
			'a2ehcData',
			array(
				'ajax'     => admin_url( 'admin-post.php' ),
				'capture'  => '1' === setting( 'capture' ) ? 1 : 0,
				'business' => setting( 'business_name' ),
			)
		);
	}

	/**
	 * Enqueue on demand (shortcodes run after wp_enqueue_scripts).
	 */
	protected static function enqueue() {
		wp_enqueue_style( 'a2ehc' );
		wp_enqueue_script( 'a2ehc' );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts( array( 'highlight' => '' ), $atts, 'a2e_health_checker' );
		self::enqueue();
		$highlight = '' !== $atts['highlight'] ? ( '1' === $atts['highlight'] || 'yes' === $atts['highlight'] || 'true' === $atts['highlight'] ) : ( '1' === setting( 'highlight' ) );
		ob_start();
		self::render( $highlight );
		return ob_get_clean();
	}

	/**
	 * Register the block (renders the shortcode, so block themes can insert it).
	 */
	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		wp_register_script(
			'a2ehc-block',
			A2EHC_URL . 'assets/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			VERSION,
			true
		);
		register_block_type(
			'a2e/health-checker',
			array(
				'api_version'     => 2,
				'editor_script'   => 'a2ehc-block',
				'render_callback' => array( __CLASS__, 'block_render' ),
				'attributes'      => array( 'highlight' => array( 'type' => 'boolean', 'default' => true ) ),
			)
		);
	}

	/**
	 * Block render callback.
	 *
	 * @param array $attributes Attributes.
	 * @return string
	 */
	public static function block_render( $attributes ) {
		self::enqueue();
		$highlight = isset( $attributes['highlight'] ) ? (bool) $attributes['highlight'] : ( '1' === setting( 'highlight' ) );
		ob_start();
		self::render( $highlight );
		return ob_get_clean();
	}

	/**
	 * A number field.
	 *
	 * @param string $name  Name.
	 * @param string $label Label.
	 * @param string $value Default value.
	 * @param array  $opts  suffix, help, step, min, placeholder.
	 * @return string
	 */
	protected static function num( $name, $label, $value, $opts = array() ) {
		$id   = 'a2ehc-' . $name;
		$help = isset( $opts['help'] ) ? '<small class="a2ehc-help">' . esc_html( $opts['help'] ) . '</small>' : '';
		$suf  = isset( $opts['suffix'] ) ? '<span class="a2ehc-suffix">' . esc_html( $opts['suffix'] ) . '</span>' : '';
		$ph   = isset( $opts['placeholder'] ) ? ' placeholder="' . esc_attr( $opts['placeholder'] ) . '"' : '';
		return '<p class="a2ehc-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
			. '<span class="a2ehc-input' . ( $suf ? ' a2ehc-input--suffix' : '' ) . '"><input type="number" inputmode="decimal" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( isset( $opts['min'] ) ? $opts['min'] : '0' ) . '" step="' . esc_attr( isset( $opts['step'] ) ? $opts['step'] : 'any' ) . '"' . $ph . '>' . $suf . '</span>' . $help . '</p>';
	}

	/**
	 * A date field.
	 *
	 * @param string $name  Name.
	 * @param string $label Label.
	 * @param string $help  Help text.
	 * @return string
	 */
	protected static function date_field( $name, $label, $help = '' ) {
		$id = 'a2ehc-' . $name;
		return '<p class="a2ehc-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
			. '<span class="a2ehc-input"><input type="date" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"></span>'
			. ( $help ? '<small class="a2ehc-help">' . esc_html( $help ) . '</small>' : '' ) . '</p>';
	}

	/**
	 * Render the whole widget.
	 *
	 * @param bool $highlight Wrap in a highlighted container.
	 */
	public static function render( $highlight = true ) {
		$wa      = wa_link( __( 'Hi A2E! I ran the account health checker and would like help getting my eBay account to Top Rated.', 'a2e-health' ) );
		$cta_url = setting( 'cta_url' );
		$classes = 'a2ehc' . ( $highlight ? ' a2ehc--highlight' : '' );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-a2ehc>
			<div class="a2ehc__head">
				<span class="a2ehc__badge"><?php a2ehc_icon( 'shield' ); ?><?php esc_html_e( 'Free eBay tool', 'a2e-health' ); ?></span>
				<h2 class="a2ehc__title"><?php esc_html_e( 'eBay Account Health Checker', 'a2e-health' ); ?></h2>
				<p class="a2ehc__intro"><?php esc_html_e( 'Enter the numbers from your eBay Seller Dashboard to see your exact seller level (Below Standard, Above Standard or Top Rated), checked against eBay US standards, and the precise number of clean sales it takes to reach Top Rated. Nothing you type leaves your browser.', 'a2e-health' ); ?></p>
			</div>

			<div class="a2ehc__grid">
				<form class="a2ehc__form" onsubmit="return false" aria-label="<?php esc_attr_e( 'Account numbers', 'a2e-health' ); ?>">
					<fieldset>
						<legend><?php a2ehc_icon( 'calendar' ); ?><?php esc_html_e( 'Evaluation window', 'a2e-health' ); ?></legend>
						<div class="a2ehc-row">
							<?php
							echo self::date_field( 'startdate', __( 'Data from', 'a2e-health' ) ); // phpcs:ignore
							echo self::date_field( 'enddate', __( 'Data to', 'a2e-health' ) ); // phpcs:ignore
							?>
						</div>
						<?php echo self::date_field( 'nextdate', __( 'Your next evaluation date', 'a2e-health' ), __( 'eBay evaluates on the 20th each month. Leave blank to use the next 20th.', 'a2e-health' ) ); // phpcs:ignore ?>
					</fieldset>
					<fieldset>
						<legend><?php a2ehc_icon( 'info' ); ?><?php esc_html_e( 'From your Seller Dashboard', 'a2e-health' ); ?></legend>
						<?php
						echo self::num( 'transactions', __( 'Transactions in this window', 'a2e-health' ), '400', array( 'step' => '1' ) ); // phpcs:ignore
						echo self::num( 'defects', __( 'Transactions with a defect', 'a2e-health' ), '2', array( 'step' => '1', 'help' => __( 'Your own cancellations plus cases closed without your resolution.', 'a2e-health' ) ) ); // phpcs:ignore
						echo self::num( 'buyers', __( 'Of those, from different buyers', 'a2e-health' ), '', array( 'step' => '1', 'placeholder' => __( 'same as above', 'a2e-health' ), 'help' => __( 'eBay only counts your defect rate when 4 or more different buyers are involved.', 'a2e-health' ) ) ); // phpcs:ignore
						echo self::num( 'late', __( 'Late shipments', 'a2e-health' ), '3', array( 'step' => '1' ) ); // phpcs:ignore
						echo self::num( 'cases', __( 'Cases closed without your resolution', 'a2e-health' ), '0', array( 'step' => '1' ) ); // phpcs:ignore
						echo self::num( 'tracking', __( 'Valid tracking uploaded on time', 'a2e-health' ), '97', array( 'suffix' => '%' ) ); // phpcs:ignore
						?>
						<p class="a2ehc-check"><label><input type="checkbox" name="eligible" value="1" checked> <?php esc_html_e( 'Account is 90+ days old with 100+ sales and $1,000+ to US buyers in the last 12 months', 'a2e-health' ); ?></label></p>
					</fieldset>
				</form>

				<div class="a2ehc__results" aria-live="polite">
					<div class="a2ehc-level" data-level>
						<div class="a2ehc-level__top">
							<span class="a2ehc-level__pill" data-out="level"><?php esc_html_e( 'Enter your numbers', 'a2e-health' ); ?></span>
							<span class="a2ehc-level__eval" data-out="evalNote"></span>
						</div>
						<div class="a2ehc-meter" data-meter>
							<div class="a2ehc-meter__track">
								<span class="a2ehc-meter__fill" data-meter-fill></span>
							</div>
							<ul class="a2ehc-meter__ticks">
								<li data-seg="below"><?php esc_html_e( 'Below', 'a2e-health' ); ?></li>
								<li data-seg="above"><?php esc_html_e( 'Above Standard', 'a2e-health' ); ?></li>
								<li data-seg="top"><?php esc_html_e( 'Top Rated', 'a2e-health' ); ?></li>
							</ul>
						</div>
						<p class="a2ehc-level__text" data-out="levelText"><?php esc_html_e( 'Fill in the fields to see where this account stands against eBay US seller standards.', 'a2e-health' ); ?></p>
					</div>

					<ul class="a2ehc-metrics" data-out-html="metrics"></ul>
				</div>
			</div>

			<div class="a2ehc__panels" aria-live="polite">
				<div class="a2ehc-recover" data-recover hidden>
					<p class="a2ehc-recover__title"><?php a2ehc_icon( 'target' ); ?><?php esc_html_e( 'Your path to Top Rated', 'a2e-health' ); ?></p>
					<div class="a2ehc-recover__grid" data-out-html="recover"></div>
					<p class="a2ehc-recover__note" data-out="recoverNote"></p>
				</div>

				<div class="a2ehc-fix">
					<p class="a2ehc-fix__title"><?php a2ehc_icon( 'wand' ); ?><?php esc_html_e( 'What to fix first', 'a2e-health' ); ?></p>
					<ul class="a2ehc-fix__list" data-out-html="suggestions"></ul>
					<p class="a2ehc-trplus" data-out-html="trplus"></p>
				</div>
			</div>

			<div class="a2ehc-hire">
				<div class="a2ehc-hire__copy">
					<h3><?php echo esc_html( setting( 'cta_title' ) ); ?></h3>
					<p><?php echo esc_html( no_dashes( setting( 'cta_text' ) ) ); ?></p>
				</div>
				<div class="a2ehc-hire__actions">
					<?php if ( $wa ) : ?>
						<a class="a2ehc-btn a2ehc-btn--wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php a2ehc_icon( 'whatsapp' ); ?><span><?php esc_html_e( 'Hire us on WhatsApp', 'a2e-health' ); ?></span></a>
					<?php endif; ?>
					<?php if ( $cta_url ) : ?>
						<a class="a2ehc-btn a2ehc-btn--ghost" href="<?php echo esc_url( $cta_url ); ?>"><span><?php esc_html_e( 'See our plans', 'a2e-health' ); ?></span></a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( '1' === setting( 'capture' ) ) : ?>
				<div class="a2ehc-capture" data-capture>
					<div class="a2ehc-capture__copy">
						<h3><?php esc_html_e( 'Get your full report and a free action plan', 'a2e-health' ); ?></h3>
						<p><?php esc_html_e( 'We will email your results with a short, honest plan to reach Top Rated. No pressure, unsubscribe any time.', 'a2e-health' ); ?></p>
					</div>
					<form class="a2ehc-capture__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-capture-form novalidate>
						<input type="hidden" name="action" value="a2ehc_lead">
						<input type="hidden" name="a2ehc_t" value="<?php echo esc_attr( (string) time() ); ?>">
						<textarea class="a2ehc-sr" name="report" data-out="report" tabindex="-1" aria-hidden="true"></textarea>
						<div class="a2ehc-hp" aria-hidden="true"><label>Website<input type="text" name="a2ehc_url" tabindex="-1" autocomplete="off"></label></div>
						<input type="text" name="name" placeholder="<?php esc_attr_e( 'Your name', 'a2e-health' ); ?>" autocomplete="name">
						<input type="email" name="email" required placeholder="<?php esc_attr_e( 'you@email.com', 'a2e-health' ); ?>" autocomplete="email">
						<button type="submit" class="a2ehc-btn a2ehc-btn--primary"><span><?php esc_html_e( 'Email me my report', 'a2e-health' ); ?></span></button>
						<div class="a2ehc-result" data-result hidden></div>
					</form>
				</div>
			<?php endif; ?>

			<p class="a2ehc__fine"><?php esc_html_e( 'Estimates based on eBay US seller standards. eBay evaluates monthly and only counts your defect rate when 4 or more different buyers are involved, so treat this as guidance and confirm in your Seller Dashboard.', 'a2e-health' ); ?></p>
		</div>
		<?php
	}
}
