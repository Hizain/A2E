<?php
/**
 * Free tool markup. Calculations run in assets/js/tools.js; every tool works without a
 * page reload and nothing a visitor types is sent to the server.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form field.
 *
 * @param string $type  number|select|checkbox|text|textarea.
 * @param string $name  Field name.
 * @param string $label Label.
 * @param mixed  $value Default value.
 * @param array  $o     prefix, suffix, step, min, max, help, options, rows, placeholder, wide, attrs.
 * @return string
 */
function a2e_tf( $type, $name, $label, $value = '', $o = array() ) {
	$o  = wp_parse_args(
		$o,
		array(
			'prefix'      => '',
			'suffix'      => '',
			'step'        => 'any',
			'min'         => '0',
			'max'         => '',
			'help'        => '',
			'options'     => array(),
			'rows'        => 4,
			'placeholder' => '',
			'wide'        => false,
			'attrs'       => '',
		)
	);
	$id = 'tf-' . $name . '-' . wp_unique_id();
	$h  = '<div class="tf' . ( $o['wide'] ? ' tf--wide' : '' ) . ' tf--' . esc_attr( $type ) . '" data-field="' . esc_attr( $name ) . '">';
	if ( 'checkbox' === $type ) {
		$h .= '<label class="tf__check" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . ( $value ? ' checked' : '' ) . '><span class="tf__switch" aria-hidden="true"></span><span>' . esc_html( $label ) . '</span></label>';
	} else {
		$h .= '<label class="tf__label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		if ( 'select' === $type ) {
			$h .= '<div class="tf__control"><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $o['options'] as $k => $v ) {
				$h .= '<option value="' . esc_attr( $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
			}
			$h .= '</select>' . a2e_icon( 'chevron-down', 'tf__chev' ) . '</div>';
		} elseif ( 'textarea' === $type ) {
			$h .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . (int) $o['rows'] . '" placeholder="' . esc_attr( $o['placeholder'] ) . '" ' . $o['attrs'] . '>' . esc_textarea( $value ) . '</textarea>';
		} else {
			$h .= '<div class="tf__control' . ( $o['prefix'] ? ' has-prefix' : '' ) . ( $o['suffix'] ? ' has-suffix' : '' ) . '">';
			if ( $o['prefix'] ) {
				$h .= '<span class="tf__affix tf__affix--pre">' . esc_html( $o['prefix'] ) . '</span>';
			}
			if ( 'number' === $type ) {
				$h .= '<input type="number" inputmode="decimal" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" step="' . esc_attr( $o['step'] ) . '"' . ( '' !== $o['min'] ? ' min="' . esc_attr( $o['min'] ) . '"' : '' ) . ( '' !== $o['max'] ? ' max="' . esc_attr( $o['max'] ) . '"' : '' ) . ' ' . $o['attrs'] . '>';
			} else {
				$h .= '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $o['placeholder'] ) . '" ' . $o['attrs'] . '>';
			}
			if ( $o['suffix'] ) {
				$h .= '<span class="tf__affix tf__affix--suf">' . esc_html( $o['suffix'] ) . '</span>';
			}
			$h .= '</div>';
		}
	}
	if ( $o['help'] ) {
		$h .= '<p class="tf__help">' . esc_html( $o['help'] ) . '</p>';
	}
	return $h . '</div>';
}

/**
 * Result tile.
 *
 * @param string $key   Output key.
 * @param string $label Label.
 * @param string $class Modifier (big|good|muted).
 * @return string
 */
function a2e_tout( $key, $label, $class = '' ) {
	return '<div class="tout ' . esc_attr( $class ) . '"><span class="tout__label">' . esc_html( $label ) . '</span><strong class="tout__value" data-out="' . esc_attr( $key ) . '">-</strong></div>';
}

/**
 * Category select options for fee tools.
 *
 * @return array
 */
function a2e_fee_category_options() {
	$opts = array();
	foreach ( a2e_fee_settings()['categories'] as $cat ) {
		$opts[ $cat['id'] ] = $cat['label'] . ( 'custom' === $cat['id'] ? '' : ' (' . $cat['rate'] . '%)' );
	}
	return $opts;
}

/**
 * Seller level options.
 *
 * @return array
 */
function a2e_level_options() {
	return array(
		'standard' => __( 'Above Standard', 'a2e' ),
		'top'      => __( 'Top Rated Plus (discount)', 'a2e' ),
		'below'    => __( 'Below Standard (extra fee)', 'a2e' ),
	);
}

/**
 * The interactive part of a tool.
 *
 * @param string $slug Tool slug.
 * @return string
 */
function a2e_render_tool( $slug ) {
	$tools = a2e_tools();
	if ( ! isset( $tools[ $slug ] ) ) {
		return '';
	}
	$f = 'a2e_tf';
	ob_start();
	echo '<div class="tool" data-tool="' . esc_attr( $slug ) . '">';

	switch ( $slug ) {
		case 'ebay-fee-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<fieldset><legend><?php esc_html_e( 'The sale', 'a2e' ); ?></legend>
					<div class="tf-grid">
						<?php
						echo $f( 'number', 'price', __( 'Item sold price', 'a2e' ), '29.99', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'shipping', __( 'Shipping charged to buyer', 'a2e' ), '0', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'tax', __( 'Buyer sales tax', 'a2e' ), '7', array( 'suffix' => '%', 'help' => __( 'eBay charges its fee on tax too.', 'a2e' ) ) ); // phpcs:ignore
						echo $f( 'select', 'category', __( 'Category', 'a2e' ), 'default', array( 'options' => a2e_fee_category_options() ) ); // phpcs:ignore
						echo $f( 'number', 'customRate', __( 'Custom final value fee', 'a2e' ), '13.6', array( 'suffix' => '%' ) ); // phpcs:ignore
						echo $f( 'select', 'level', __( 'Seller level', 'a2e' ), 'standard', array( 'options' => a2e_level_options() ) ); // phpcs:ignore
						echo $f( 'number', 'adRate', __( 'Promoted listing ad rate', 'a2e' ), '0', array( 'suffix' => '%' ) ); // phpcs:ignore
						echo $f( 'checkbox', 'international', __( 'International buyer', 'a2e' ), false ); // phpcs:ignore
						?>
					</div>
				</fieldset>
				<fieldset><legend><?php esc_html_e( 'Your costs', 'a2e' ); ?></legend>
					<div class="tf-grid">
						<?php
						echo $f( 'number', 'cost', __( 'Item cost', 'a2e' ), '15', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'shipCost', __( 'Your shipping cost', 'a2e' ), '0', array( 'prefix' => '$' ) ); // phpcs:ignore
						?>
					</div>
				</fieldset>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'allFees', __( 'Total eBay fees', 'a2e' ), 'big' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'profit', __( 'Your profit', 'a2e' ), 'big good' ); // phpcs:ignore ?>
				</div>
				<div class="stackbar" data-stack aria-hidden="true"><span data-seg="cost"></span><span data-seg="fees"></span><span data-seg="profit"></span></div>
				<ul class="stackbar__legend"><li><i class="k-cost"></i><?php esc_html_e( 'Costs', 'a2e' ); ?></li><li><i class="k-fees"></i><?php esc_html_e( 'eBay fees', 'a2e' ); ?></li><li><i class="k-profit"></i><?php esc_html_e( 'Profit', 'a2e' ); ?></li></ul>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'fvf', __( 'Final value fee', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'orderFee', __( 'Per-order fee', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'ad', __( 'Ad fee', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'intl', __( 'International fee', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'feePct', __( 'Fees as % of price', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'margin', __( 'Profit margin', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'payout', __( 'eBay pays you', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'total', __( 'Total sale (fee basis)', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
			</div>
			<?php
			break;

		case 'amazon-to-ebay-profit-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<fieldset><legend><?php esc_html_e( 'Buying', 'a2e' ); ?></legend>
					<div class="tf-grid">
						<?php
						echo $f( 'number', 'cost', __( 'Supplier price (e.g. Amazon)', 'a2e' ), '24.00', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'costTax', __( 'Sales tax you pay', 'a2e' ), '7', array( 'suffix' => '%' ) ); // phpcs:ignore
						echo $f( 'number', 'other', __( 'Other costs per order', 'a2e' ), '0', array( 'prefix' => '$', 'help' => __( 'Packaging, software, returns allowance…', 'a2e' ) ) ); // phpcs:ignore
						?>
					</div>
				</fieldset>
				<fieldset><legend><?php esc_html_e( 'Selling on eBay', 'a2e' ); ?></legend>
					<div class="tf-grid">
						<?php
						echo $f( 'number', 'price', __( 'eBay price', 'a2e' ), '39.99', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'shipping', __( 'Shipping charged', 'a2e' ), '0', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'tax', __( 'Buyer sales tax', 'a2e' ), '7', array( 'suffix' => '%' ) ); // phpcs:ignore
						echo $f( 'number', 'adRate', __( 'Ad rate', 'a2e' ), '2', array( 'suffix' => '%' ) ); // phpcs:ignore
						echo $f( 'select', 'category', __( 'Category', 'a2e' ), 'default', array( 'options' => a2e_fee_category_options() ) ); // phpcs:ignore
						echo $f( 'number', 'customRate', __( 'Custom fee', 'a2e' ), '13.6', array( 'suffix' => '%' ) ); // phpcs:ignore
						echo $f( 'number', 'target', __( 'Target margin', 'a2e' ), '20', array( 'suffix' => '%' ) ); // phpcs:ignore
						?>
					</div>
				</fieldset>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="verdict" data-verdict><span class="verdict__dot"></span><strong data-out="verdict">-</strong><span data-out="verdictText"></span></div>
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'profit', __( 'Profit per sale', 'a2e' ), 'big good' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'margin', __( 'Margin', 'a2e' ), 'big' ); // phpcs:ignore ?>
				</div>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'roi', __( 'ROI', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'fees', __( 'eBay fees', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'totalCost', __( 'Total cost', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'breakEven', __( 'Break-even price', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'targetPrice', __( 'Price for target margin', 'a2e' ), 'wide' ); // phpcs:ignore
					?>
				</div>
			</div>
			<?php
			break;

		case 'ebay-price-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'number', 'cost', __( 'Item cost (incl. tax)', 'a2e' ), '18', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'shipCost', __( 'Your shipping cost', 'a2e' ), '0', array( 'prefix' => '$', 'help' => __( 'Offering free shipping? Put your cost here.', 'a2e' ) ) ); // phpcs:ignore
					echo $f( 'select', 'mode', __( 'I want', 'a2e' ), 'margin', array( 'options' => array( 'margin' => __( 'A target margin (%)', 'a2e' ), 'fixed' => __( 'A fixed profit ($)', 'a2e' ) ) ) ); // phpcs:ignore
					echo $f( 'number', 'margin', __( 'Target margin', 'a2e' ), '20', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'fixed', __( 'Target profit', 'a2e' ), '6', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'adRate', __( 'Ad rate', 'a2e' ), '2', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'tax', __( 'Buyer sales tax', 'a2e' ), '7', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'select', 'category', __( 'Category', 'a2e' ), 'default', array( 'options' => a2e_fee_category_options() ) ); // phpcs:ignore
					echo $f( 'number', 'customRate', __( 'Custom fee', 'a2e' ), '13.6', array( 'suffix' => '%' ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="tool__hero-out"><?php echo a2e_tout( 'listPrice', __( 'List it at', 'a2e' ), 'big good xl' ); // phpcs:ignore ?></div>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'fees', __( 'eBay fees at this price', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'profit', __( 'Profit', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'marginOut', __( 'Margin', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'charm', __( 'Rounded price', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
			</div>
			<?php
			break;

		case 'ebay-title-generator':
			?>
			<div class="tool__tabs" role="tablist">
				<button type="button" role="tab" class="is-active" aria-selected="true" data-tab="build"><?php esc_html_e( 'Build a title', 'a2e' ); ?></button>
				<button type="button" role="tab" aria-selected="false" data-tab="check"><?php esc_html_e( 'Check my title', 'a2e' ); ?></button>
			</div>
			<form class="tool__form" onsubmit="return false" data-panel="build">
				<div class="tf-grid">
					<?php
					echo $f( 'text', 'brand', __( 'Brand', 'a2e' ), 'Dyson', array( 'placeholder' => 'e.g. Dyson' ) ); // phpcs:ignore
					echo $f( 'text', 'type', __( 'Product type', 'a2e' ), 'Cordless Vacuum Filter', array( 'placeholder' => 'e.g. Cordless Vacuum Filter' ) ); // phpcs:ignore
					echo $f( 'text', 'model', __( 'Model / part number', 'a2e' ), 'V8 V7', array( 'placeholder' => 'e.g. V8 V7' ) ); // phpcs:ignore
					echo $f( 'text', 'features', __( 'Key features (comma separated)', 'a2e' ), 'Washable, HEPA, 2 Pack', array( 'placeholder' => 'e.g. Washable, HEPA' ) ); // phpcs:ignore
					echo $f( 'text', 'variant', __( 'Size / colour / quantity', 'a2e' ), '', array( 'placeholder' => 'e.g. Blue, Large' ) ); // phpcs:ignore
					echo $f( 'select', 'condition', __( 'Condition', 'a2e' ), 'New', array( 'options' => array( 'New' => 'New', 'Brand New' => 'Brand New', '' => __( '(leave out)', 'a2e' ), 'Used' => 'Used', 'Refurbished' => 'Refurbished' ) ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<form class="tool__form" onsubmit="return false" data-panel="check" hidden>
				<?php echo $f( 'textarea', 'title', __( 'Paste your eBay title', 'a2e' ), 'NEW!! L@@K Dyson filter for vacuum GREAT DEAL', array( 'rows' => 3, 'wide' => true ) ); // phpcs:ignore ?>
			</form>
			<div class="tool__results" aria-live="polite">
				<div data-panel="build">
					<p class="tool__label"><?php esc_html_e( 'Title ideas (click to copy)', 'a2e' ); ?></p>
					<ul class="title-list" data-out-list="titles"></ul>
				</div>
				<div data-panel="check" hidden>
					<div class="score">
						<svg class="score__ring" viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="score__track"/><circle cx="60" cy="60" r="52" class="score__bar" data-ring/></svg>
						<div class="score__num"><strong data-out="score">-</strong><span>/100</span></div>
					</div>
					<p class="score__chars" data-out="chars"></p>
					<ul class="issues" data-out-list="issues"></ul>
				</div>
			</div>
			<?php
			break;

		case 'ebay-sell-through-rate-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'number', 'sold', __( 'Sold listings', 'a2e' ), '45', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'number', 'active', __( 'Active listings', 'a2e' ), '60', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'select', 'days', __( 'Sold period', 'a2e' ), '90', array( 'options' => array( '30' => '30 days', '60' => '60 days', '90' => '90 days' ) ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="meter" data-meter><div class="meter__bar"><span data-meter-fill></span></div><div class="meter__scale"><span>0%</span><span>20%</span><span>50%</span><span>80%</span><span>100%</span></div></div>
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'str', __( 'Sell-through rate', 'a2e' ), 'big good' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'rating', __( 'Demand', 'a2e' ), 'big' ); // phpcs:ignore ?>
				</div>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'perDay', __( 'Market sales per day', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'daysToSell', __( 'Days to sell current supply', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
			</div>
			<?php
			break;

		case 'ebay-defect-rate-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'number', 'transactions', __( 'Transactions in the period', 'a2e' ), '400', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'number', 'defects', __( 'Transactions with a defect', 'a2e' ), '3', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'number', 'buyers', __( 'Different buyers affected', 'a2e' ), '3', array( 'step' => '1' ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'rate', __( 'Transaction defect rate', 'a2e' ), 'big' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'band', __( 'Seller level band', 'a2e' ), 'big' ); // phpcs:ignore ?>
				</div>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'topLeft', __( 'Defects left for Top Rated', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'stdLeft', __( 'Defects left for Above Standard', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
				<p class="tool__note" data-out="note"></p>
			</div>
			<?php
			break;

		case 'ebay-promoted-listings-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'number', 'price', __( 'Sale price', 'a2e' ), '39.99', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'shipping', __( 'Shipping charged', 'a2e' ), '0', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'cost', __( 'Item + shipping cost', 'a2e' ), '24', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'adRate', __( 'Ad rate', 'a2e' ), '5', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'tax', __( 'Buyer sales tax', 'a2e' ), '7', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'target', __( 'Minimum margin you want', 'a2e' ), '10', array( 'suffix' => '%' ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'profitAfter', __( 'Profit after ads', 'a2e' ), 'big good' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'maxRate', __( 'Max ad rate for your margin', 'a2e' ), 'big' ); // phpcs:ignore ?>
				</div>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'adFee', __( 'Ad fee per sale', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'profitBefore', __( 'Profit before ads', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'breakEven', __( 'Break-even ad rate', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'marginAfter', __( 'Margin after ads', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
			</div>
			<?php
			break;

		case 'ebay-store-name-generator':
			?>
			<form class="tool__form tool__form--inline" onsubmit="return false">
				<?php
				echo $f( 'text', 'keyword', __( 'Your niche or keyword', 'a2e' ), 'Kitchen', array( 'placeholder' => 'e.g. Kitchen, Pets, Tech' ) ); // phpcs:ignore
				echo $f( 'select', 'style', __( 'Style', 'a2e' ), 'brandable', array( 'options' => array( 'brandable' => __( 'Brandable', 'a2e' ), 'trust' => __( 'Trustworthy shop', 'a2e' ), 'modern' => __( 'Modern & short', 'a2e' ), 'fun' => __( 'Fun & catchy', 'a2e' ) ) ) ); // phpcs:ignore
				?>
				<button type="button" class="btn btn--primary" data-action="generate"><span><?php esc_html_e( 'Generate names', 'a2e' ); ?></span><?php a2e_the_icon( 'wand', 'btn__icon' ); ?></button>
			</form>
			<div class="tool__results" aria-live="polite">
				<ul class="name-cloud" data-out-list="names"></ul>
			</div>
			<?php
			break;

		case 'ebay-description-template-generator':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'text', 'title', __( 'Product title', 'a2e' ), 'Washable HEPA Filter for Dyson V8 V7, 2 Pack', array( 'wide' => true ) ); // phpcs:ignore
					echo $f( 'textarea', 'intro', __( 'One-line benefit', 'a2e' ), 'Restore full suction and trap 99% of fine dust. Rinse, dry and reuse.', array( 'rows' => 2, 'wide' => true ) ); // phpcs:ignore
					echo $f( 'textarea', 'features', __( 'Key features (one per line)', 'a2e' ), "Fits Dyson V7 and V8 cordless vacuums\nWashable and reusable HEPA filter\nTwist-on fit, no tools needed\n2 filters included", array( 'rows' => 4 ) ); // phpcs:ignore
					echo $f( 'textarea', 'specs', __( 'Specifications (Name: value)', 'a2e' ), "Compatible: Dyson V7, V8\nMaterial: HEPA, ABS\nQuantity: 2", array( 'rows' => 4 ) ); // phpcs:ignore
					echo $f( 'text', 'box', __( 'In the box', 'a2e' ), '2 × post filter', array() ); // phpcs:ignore
					echo $f( 'text', 'shipping', __( 'Shipping line', 'a2e' ), 'Free shipping. Dispatched within 2 business days with tracking.', array() ); // phpcs:ignore
					echo $f( 'text', 'returns', __( 'Returns line', 'a2e' ), '30-day free returns.', array() ); // phpcs:ignore
					echo $f( 'select', 'color', __( 'Accent colour', 'a2e' ), '#2b4cf2', array( 'options' => array( '#2b4cf2' => __( 'Blue', 'a2e' ), '#0f766e' => __( 'Teal', 'a2e' ), '#b91c1c' => __( 'Red', 'a2e' ), '#111827' => __( 'Black', 'a2e' ), '#7c3aed' => __( 'Violet', 'a2e' ) ) ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results tool__results--stack" aria-live="polite">
				<p class="tool__label"><?php esc_html_e( 'Preview', 'a2e' ); ?></p>
				<div class="desc-preview" data-out-html="preview"></div>
				<div class="copybox">
					<div class="copybox__head"><span><?php esc_html_e( 'HTML for eBay', 'a2e' ); ?></span><button type="button" class="btn btn--ghost btn--sm" data-copy="html"><?php a2e_the_icon( 'copy' ); ?><span><?php esc_html_e( 'Copy HTML', 'a2e' ); ?></span></button></div>
					<textarea readonly rows="6" data-out="html" aria-label="<?php esc_attr_e( 'Generated HTML', 'a2e' ); ?>"></textarea>
				</div>
			</div>
			<?php
			break;

		case 'ebay-bulk-profit-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<?php echo $f( 'textarea', 'rows', __( 'Paste products: name, cost, eBay price, shipping (optional)', 'a2e' ), "Product, Cost, eBay price, Shipping\nVacuum filter 2-pack, 9.99, 19.99, 0\nAir fryer liners, 7.49, 16.95, 0\nLED stair lights 6-pack, 21.00, 39.99, 0\nMagnetic car mount, 6.20, 9.99, 0\nPlant pot set, 18.50, 24.99, 0", array( 'rows' => 7, 'wide' => true, 'attrs' => 'spellcheck="false"' ) ); // phpcs:ignore ?>
				<div class="tf-grid tf-grid--3">
					<?php
					echo $f( 'number', 'tax', __( 'Buyer sales tax', 'a2e' ), '7', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'costTax', __( 'Tax you pay on cost', 'a2e' ), '0', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'adRate', __( 'Ad rate', 'a2e' ), '2', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'target', __( 'Target margin', 'a2e' ), '20', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'select', 'category', __( 'Category', 'a2e' ), 'default', array( 'options' => a2e_fee_category_options() ) ); // phpcs:ignore
					echo $f( 'number', 'customRate', __( 'Custom fee', 'a2e' ), '13.6', array( 'suffix' => '%' ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results tool__results--stack" aria-live="polite">
				<div class="bulk-summary">
					<?php
					echo a2e_tout( 'count', __( 'Products', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'listCount', __( 'Worth listing', 'a2e' ), 'good' ); // phpcs:ignore
					echo a2e_tout( 'avgMargin', __( 'Average margin', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'avgProfit', __( 'Average profit', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
				<div class="table-wrap"><table class="bulk-table"><thead><tr><th><?php esc_html_e( 'Product', 'a2e' ); ?></th><th><?php esc_html_e( 'Cost', 'a2e' ); ?></th><th><?php esc_html_e( 'Price', 'a2e' ); ?></th><th><?php esc_html_e( 'Fees', 'a2e' ); ?></th><th><?php esc_html_e( 'Profit', 'a2e' ); ?></th><th><?php esc_html_e( 'Margin', 'a2e' ); ?></th><th><?php esc_html_e( 'Verdict', 'a2e' ); ?></th></tr></thead><tbody data-out-html="tbody"></tbody></table></div>
				<button type="button" class="btn btn--ghost btn--sm" data-action="csv"><?php a2e_the_icon( 'download' ); ?><span><?php esc_html_e( 'Download CSV', 'a2e' ); ?></span></button>
			</div>
			<?php
			break;

		case 'ebay-product-research-scorecard':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'number', 'sold30', __( 'Sold on eBay in last 30 days', 'a2e' ), '24', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'number', 'str', __( 'Sell-through rate', 'a2e' ), '55', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'select', 'competition', __( 'Competition', 'a2e' ), 'medium', array( 'options' => array( 'low' => __( 'Low: few sellers, weak listings', 'a2e' ), 'medium' => __( 'Medium', 'a2e' ), 'high' => __( 'High: many strong listings', 'a2e' ) ) ) ); // phpcs:ignore
					echo $f( 'number', 'margin', __( 'Margin after all fees', 'a2e' ), '22', array( 'suffix' => '%' ) ); // phpcs:ignore
					echo $f( 'number', 'delivery', __( 'Supplier delivery time', 'a2e' ), '4', array( 'suffix' => __( 'days', 'a2e' ), 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'select', 'risk', __( 'Brand / return risk', 'a2e' ), 'low', array( 'options' => array( 'low' => __( 'Low: unbranded, sturdy', 'a2e' ), 'medium' => __( 'Medium: sizing or fragile', 'a2e' ), 'high' => __( 'High: VeRO brand or high returns', 'a2e' ) ) ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="score score--lg">
					<svg class="score__ring" viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="52" class="score__track"/><circle cx="60" cy="60" r="52" class="score__bar" data-ring/></svg>
					<div class="score__num"><strong data-out="score">-</strong><span>/100</span></div>
				</div>
				<div class="verdict" data-verdict><span class="verdict__dot"></span><strong data-out="verdict">-</strong><span data-out="verdictText"></span></div>
				<ul class="factor-bars" data-out-html="factors"></ul>
			</div>
			<?php
			break;

		case 'ebay-dropshipping-income-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<div class="tf-grid">
					<?php
					echo $f( 'number', 'listings', __( 'Active listings', 'a2e' ), '1000', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'number', 'rate', __( 'Sales per listing per month', 'a2e' ), '0.12', array( 'step' => '0.01', 'help' => __( 'Typical stores: 0.05-0.3', 'a2e' ) ) ); // phpcs:ignore
					echo $f( 'number', 'avgPrice', __( 'Average sale price', 'a2e' ), '32', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'avgProfit', __( 'Average profit per sale', 'a2e' ), '6.5', array( 'prefix' => '$' ) ); // phpcs:ignore
					echo $f( 'number', 'growth', __( 'New listings added per month', 'a2e' ), '150', array( 'step' => '1' ) ); // phpcs:ignore
					echo $f( 'number', 'goal', __( 'Monthly profit goal', 'a2e' ), '2000', array( 'prefix' => '$' ) ); // phpcs:ignore
					?>
				</div>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'profit', __( 'Monthly profit (now)', 'a2e' ), 'big good' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'profit12', __( 'Monthly profit in 12 months', 'a2e' ), 'big' ); // phpcs:ignore ?>
				</div>
				<div class="minichart" data-chart aria-hidden="true"></div>
				<div class="tout-grid">
					<?php
					echo a2e_tout( 'orders', __( 'Orders per month', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'revenue', __( 'Revenue per month', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'needed', __( 'Listings needed for goal', 'a2e' ) ); // phpcs:ignore
					echo a2e_tout( 'months', __( 'Months to reach goal', 'a2e' ) ); // phpcs:ignore
					?>
				</div>
				<p class="tool__note"><?php esc_html_e( 'Projection only. Real results depend on products, competition and account health.', 'a2e' ); ?></p>
			</div>
			<?php
			break;

		case 'ebay-store-management-cost-calculator':
			?>
			<form class="tool__form" onsubmit="return false">
				<fieldset><legend><?php esc_html_e( 'Hours per week you spend on…', 'a2e' ); ?></legend>
					<div class="tf-grid">
						<?php
						echo $f( 'number', 'hResearch', __( 'Product research', 'a2e' ), '5', array( 'suffix' => 'h' ) ); // phpcs:ignore
						echo $f( 'number', 'hListing', __( 'Writing & uploading listings', 'a2e' ), '6', array( 'suffix' => 'h' ) ); // phpcs:ignore
						echo $f( 'number', 'hStock', __( 'Stock & price checks', 'a2e' ), '3', array( 'suffix' => 'h' ) ); // phpcs:ignore
						echo $f( 'number', 'hOrders', __( 'Orders, tracking & returns', 'a2e' ), '3', array( 'suffix' => 'h' ) ); // phpcs:ignore
						echo $f( 'number', 'hMessages', __( 'Buyer messages & cases', 'a2e' ), '2', array( 'suffix' => 'h' ) ); // phpcs:ignore
						?>
					</div>
				</fieldset>
				<fieldset><legend><?php esc_html_e( 'Rates', 'a2e' ); ?></legend>
					<div class="tf-grid">
						<?php
						echo $f( 'number', 'myRate', __( 'What an hour of your time is worth', 'a2e' ), '20', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'vaRate', __( 'Typical VA hourly rate', 'a2e' ), '6', array( 'prefix' => '$' ) ); // phpcs:ignore
						echo $f( 'number', 'plan', __( 'Done-for-you plan per month', 'a2e' ), '200', array( 'prefix' => '$' ) ); // phpcs:ignore
						?>
					</div>
				</fieldset>
			</form>
			<div class="tool__results" aria-live="polite">
				<div class="tool__hero-out">
					<?php echo a2e_tout( 'hours', __( 'Hours per month', 'a2e' ), 'big' ); // phpcs:ignore ?>
					<?php echo a2e_tout( 'saving', __( 'Value you get back with done-for-you', 'a2e' ), 'big good' ); // phpcs:ignore ?>
				</div>
				<div class="compare-bars" data-out-html="bars"></div>
				<p class="tool__note"><?php esc_html_e( 'VA cost includes ~20% of your own time spent training and checking their work.', 'a2e' ); ?></p>
			</div>
			<?php
			break;
	}

	echo '</div>';
	return ob_get_clean();
}

/**
 * Tool card (hub and related lists).
 *
 * @param array $tool Tool data.
 * @param int   $i    Index for stagger.
 * @return string
 */
function a2e_tool_card( $tool, $i = 0 ) {
	$cats = a2e_tool_categories();
	return sprintf(
		'<a class="tool-card" href="%1$s" data-cat="%2$s" data-name="%3$s" data-reveal style="--d:%4$d"><span class="tool-card__icon">%5$s</span><span class="tool-card__cat">%6$s</span><h3 class="tool-card__title">%7$s</h3><p class="tool-card__text">%8$s</p><span class="tool-card__go">%9$s %10$s</span></a>',
		esc_url( a2e_tool_url( $tool['slug'] ) ),
		esc_attr( $tool['cat'] ),
		esc_attr( strtolower( $tool['name'] . ' ' . $tool['short'] ) ),
		(int) ( $i % 4 ),
		a2e_icon( $tool['icon'] ),
		esc_html( isset( $cats[ $tool['cat'] ] ) ? $cats[ $tool['cat'] ] : '' ),
		esc_html( $tool['name'] ),
		esc_html( $tool['short'] ),
		esc_html__( 'Open tool', 'a2e' ),
		a2e_icon( 'arrow-right' )
	);
}

/**
 * Tools grid.
 *
 * @param int  $limit   Max tools (0 = all).
 * @param bool $filters Show search + category filters.
 */
function a2e_render_tools_grid( $limit = 0, $filters = false ) {
	$tools = a2e_tools();
	if ( $limit ) {
		$tools = array_slice( $tools, 0, $limit );
	}
	if ( $filters ) {
		echo '<div class="tool-filter" data-tool-filter><label class="tool-filter__search"><span class="screen-reader-text">' . esc_html__( 'Search tools', 'a2e' ) . '</span>' . a2e_icon( 'search' ) . '<input type="search" placeholder="' . esc_attr__( 'Search tools, e.g. fee, title, profit', 'a2e' ) . '" data-tool-search></label><div class="chips" role="group" aria-label="' . esc_attr__( 'Filter by category', 'a2e' ) . '"><button type="button" class="chip is-active" data-cat="all">' . esc_html__( 'All tools', 'a2e' ) . '</button>'; // phpcs:ignore
		foreach ( a2e_tool_categories() as $key => $label ) {
			echo '<button type="button" class="chip" data-cat="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</button>';
		}
		echo '</div></div>';
	}
	echo '<div class="tool-grid" data-tool-grid>';
	$i = 0;
	foreach ( $tools as $tool ) {
		echo a2e_tool_card( $tool, $i++ ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';
	if ( $filters ) {
		echo '<p class="tool-empty" data-tool-empty hidden>' . esc_html__( 'No tool matches that search yet. Tell us what you need and we may build it.', 'a2e' ) . '</p>';
	}
}

/**
 * Explainer HTML with site-relative links resolved.
 *
 * @param string $html HTML.
 * @return string
 */
function a2e_tool_explainer( $html ) {
	return str_replace( 'href="/', 'href="' . esc_url( home_url( '/' ) ), $html );
}

/**
 * Virtual tool route: /free-tools/{slug}/ works even before the page exists.
 *
 * @param bool     $preempt Whether to short-circuit.
 * @param WP_Query $query   Query.
 * @return bool
 */
function a2e_virtual_tool_route( $preempt, $query ) {
	if ( $preempt || ! $query->is_main_query() || ! $query->is_404() ) {
		return $preempt;
	}
	$path = a2e_request_path();
	if ( preg_match( '#^free-tools/([a-z0-9-]+)$#', $path, $m ) && isset( a2e_tools()[ $m[1] ] ) ) {
		$GLOBALS['a2e_virtual_tool'] = $m[1];
		$query->is_404               = false;
		status_header( 200 );
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'a2e_virtual_tool_route', 10, 2 );

/**
 * Use the tool template for tool pages and the virtual route.
 *
 * @param string $template Template.
 * @return string
 */
function a2e_tool_template( $template ) {
	if ( a2e_current_tool() ) {
		$file = locate_template( 'template-parts/tool-page.php' );
		if ( $file ) {
			return $file;
		}
	}
	return $template;
}
add_filter( 'template_include', 'a2e_tool_template', 20 );
