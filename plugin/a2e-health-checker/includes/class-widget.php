<?php
/**
 * The front-end widget: shortcode, block, assets and markup. The scoring, recovery maths,
 * suggestions and report capture all run in the browser from assets/health.js.
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
				'ajax'      => admin_url( 'admin-post.php' ),
				'capture'   => '1' === setting( 'capture' ) ? 1 : 0,
				'business'  => setting( 'business_name' ),
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
	 * Number field.
	 *
	 * @param string $name  Name.
	 * @param string $label Label.
	 * @param string $value Default value.
	 * @param array  $opts  suffix, help, step, min.
	 * @return string
	 */
	protected static function num( $name, $label, $value, $opts = array() ) {
		$id   = 'a2ehc-' . $name;
		$help = isset( $opts['help'] ) ? '<small class="a2ehc-help">' . esc_html( $opts['help'] ) . '</small>' : '';
		$suf  = isset( $opts['suffix'] ) ? '<span class="a2ehc-suffix">' . esc_html( $opts['suffix'] ) . '</span>' : '';
		return '<p class="a2ehc-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
			. '<span class="a2ehc-input' . ( $suf ? ' a2ehc-input--suffix' : '' ) . '"><input type="number" inputmode="decimal" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( isset( $opts['min'] ) ? $opts['min'] : '0' ) . '" step="' . esc_attr( isset( $opts['step'] ) ? $opts['step'] : 'any' ) . '">' . $suf . '</span>' . $help . '</p>';
	}

	/**
	 * Render the whole widget.
	 *
	 * @param bool $highlight Wrap in a highlighted container.
	 */
	public static function render( $highlight = true ) {
		$wa    = wa_link( __( 'Hi A2E! I ran the account health checker and would like help with my eBay account.', 'a2e-health' ) );
		$cta_url = setting( 'cta_url' );
		$classes = 'a2ehc' . ( $highlight ? ' a2ehc--highlight' : '' );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-a2ehc>
			<div class="a2ehc__head">
				<span class="a2ehc__badge"><?php esc_html_e( 'Free tool', 'a2e-health' ); ?></span>
				<h2 class="a2ehc__title"><?php esc_html_e( 'eBay Account Health Checker', 'a2e-health' ); ?></h2>
				<p class="a2ehc__intro"><?php esc_html_e( 'Enter a few numbers from your Seller Dashboard to see your health score, where you stand against eBay standards, how to recover Top Rated, and what to fix first. Nothing you type is uploaded.', 'a2e-health' ); ?></p>
			</div>

			<div class="a2ehc__grid">
				<form class="a2ehc__form" onsubmit="return false" aria-label="<?php esc_attr_e( 'Account numbers', 'a2e-health' ); ?>">
					<fieldset>
						<legend><?php esc_html_e( 'Your evaluation', 'a2e-health' ); ?></legend>
						<p class="a2ehc-field"><label for="a2ehc-period"><?php esc_html_e( 'Evaluation period eBay uses', 'a2e-health' ); ?></label>
							<span class="a2ehc-input"><select id="a2ehc-period" name="period">
								<option value="3"><?php esc_html_e( 'Last 3 months (400+ sales)', 'a2e-health' ); ?></option>
								<option value="12"><?php esc_html_e( 'Last 12 months (smaller sellers)', 'a2e-health' ); ?></option>
							</select></span>
						</p>
						<p class="a2ehc-field"><label for="a2ehc-nextdate"><?php esc_html_e( 'Your next evaluation date', 'a2e-health' ); ?></label>
							<span class="a2ehc-input"><input type="date" id="a2ehc-nextdate" name="nextdate"></span>
							<small class="a2ehc-help"><?php esc_html_e( 'eBay evaluates on the 20th of each month. Leave blank to use the next 20th.', 'a2e-health' ); ?></small>
						</p>
						<?php
						echo self::num( 'orders', __( 'Orders you get per month (for the estimate)', 'a2e-health' ), '', array( 'step' => '1', 'help' => __( 'Optional. Leave blank and we estimate it from your numbers.', 'a2e-health' ) ) ); // phpcs:ignore
						?>
					</fieldset>
					<fieldset>
						<legend><?php esc_html_e( 'From your Seller Dashboard', 'a2e-health' ); ?></legend>
						<?php
						echo self::num( 'transactions', __( 'Transactions in the evaluation period', 'a2e-health' ), '400', array( 'step' => '1' ) ); // phpcs:ignore
						echo self::num( 'defects', __( 'Transactions with a defect', 'a2e-health' ), '3', array( 'step' => '1', 'help' => __( 'Cases closed without your resolution, plus your own cancellations.', 'a2e-health' ) ) ); // phpcs:ignore
						echo self::num( 'late', __( 'Late shipments', 'a2e-health' ), '6', array( 'step' => '1' ) ); // phpcs:ignore
						echo self::num( 'cases', __( 'Cases closed without your resolution', 'a2e-health' ), '1', array( 'step' => '1' ) ); // phpcs:ignore
						echo self::num( 'tracking', __( 'Valid tracking uploaded on time', 'a2e-health' ), '97', array( 'suffix' => '%' ) ); // phpcs:ignore
						?>
					</fieldset>
				</form>

				<div class="a2ehc__results" aria-live="polite">
					<div class="a2ehc__scorerow">
						<div class="a2ehc-score">
							<svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="a2ehc-score__track"/><circle cx="60" cy="60" r="52" class="a2ehc-score__bar" data-ring/></svg>
							<div class="a2ehc-score__num"><strong data-out="score">-</strong><span>/100</span></div>
						</div>
						<div class="a2ehc-band" data-band>
							<strong data-out="band">-</strong>
							<p data-out="bandText"></p>
						</div>
					</div>

					<ul class="a2ehc-metrics" data-out-html="metrics"></ul>
				</div>
			</div>

			<div class="a2ehc__panels" aria-live="polite">
				<div class="a2ehc-recover" data-recover hidden>
					<p class="a2ehc-recover__title"><?php a2ehc_icon( 'trend' ); ?><?php esc_html_e( 'Your path back to the top', 'a2e-health' ); ?></p>
					<div class="a2ehc-recover__grid" data-out-html="recover"></div>
					<p class="a2ehc-recover__note" data-out="recoverNote"></p>
				</div>

				<div class="a2ehc-fix">
					<p class="a2ehc-fix__title"><?php a2ehc_icon( 'wand' ); ?><?php esc_html_e( 'Suggestions to improve your health', 'a2e-health' ); ?></p>
					<ul class="a2ehc-fix__list" data-out-html="suggestions"></ul>
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
						<p><?php esc_html_e( 'We will email your results with a short, honest plan. No pressure, and you can unsubscribe any time.', 'a2e-health' ); ?></p>
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

			<p class="a2ehc__fine"><?php esc_html_e( 'Estimates based on eBay US seller standards. eBay evaluates monthly and needs a minimum number of defects from different buyers before your level changes, so treat this as guidance and confirm in your Seller Dashboard.', 'a2e-health' ); ?></p>
		</div>
		<?php
	}
}
