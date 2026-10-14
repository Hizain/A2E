<?php
/**
 * Homepage profit simulator: sliders → projected orders/profit, a 12-month growth chart and
 * a recommended plan. All maths run in assets/js/app.js.
 *
 * @package A2E_Prime
 */

?>
<div class="sim" data-sim>
	<div class="sim__controls">
		<div class="sim__field">
			<label for="sim-listings"><?php esc_html_e( 'Active listings today', 'a2e' ); ?> <output data-sim-out="listings">300</output></label>
			<input type="range" id="sim-listings" data-sim-in="listings" min="0" max="3000" step="50" value="300">
			<span class="sim__scale"><span>0</span><span>3,000</span></span>
		</div>
		<div class="sim__field">
			<label for="sim-rate"><?php esc_html_e( 'Sales per listing / month', 'a2e' ); ?> <output data-sim-out="rate">0.12</output></label>
			<input type="range" id="sim-rate" data-sim-in="rate" min="0.03" max="0.5" step="0.01" value="0.12">
			<span class="sim__scale"><span><?php esc_html_e( 'slow', 'a2e' ); ?></span><span><?php esc_html_e( 'hot niche', 'a2e' ); ?></span></span>
		</div>
		<div class="sim__field">
			<label for="sim-profit"><?php esc_html_e( 'Average profit per order', 'a2e' ); ?> <output data-sim-out="profit">$6.50</output></label>
			<input type="range" id="sim-profit" data-sim-in="profit" min="2" max="25" step="0.5" value="6.5">
			<span class="sim__scale"><span>$2</span><span>$25</span></span>
		</div>
		<div class="sim__field">
			<span class="sim__label"><?php esc_html_e( 'New researched listings we add each month', 'a2e' ); ?></span>
			<div class="seg" role="radiogroup" aria-label="<?php esc_attr_e( 'Listings added per month', 'a2e' ); ?>">
				<label><input type="radio" name="sim-add" value="0" data-sim-in="add"><span><?php esc_html_e( 'None', 'a2e' ); ?></span></label>
				<label><input type="radio" name="sim-add" value="150" data-sim-in="add" checked><span>150</span></label>
				<label><input type="radio" name="sim-add" value="500" data-sim-in="add"><span>500</span></label>
			</div>
		</div>
		<p class="sim__fine"><?php a2e_the_icon( 'info' ); ?><?php esc_html_e( 'An estimate to help you plan — not a promise. Real results depend on products, competition, account limits and season.', 'a2e' ); ?></p>
	</div>

	<div class="sim__panel">
		<div class="sim__kpis">
			<div><span><?php esc_html_e( 'Monthly profit today', 'a2e' ); ?></span><strong data-sim-res="now">$0</strong></div>
			<div class="is-hl"><span><?php esc_html_e( 'Monthly profit in 12 months', 'a2e' ); ?></span><strong data-sim-res="later">$0</strong></div>
			<div><span><?php esc_html_e( 'Orders / month in 12 months', 'a2e' ); ?></span><strong data-sim-res="orders">0</strong></div>
			<div><span><?php esc_html_e( 'Profit over the next 12 months', 'a2e' ); ?></span><strong data-sim-res="year">$0</strong></div>
		</div>
		<div class="sim__chart" data-sim-chart aria-hidden="true"></div>
		<div class="sim__months" aria-hidden="true"><span><?php esc_html_e( 'Month 1', 'a2e' ); ?></span><span><?php esc_html_e( 'Month 12', 'a2e' ); ?></span></div>
		<div class="sim__plan" data-sim-plan>
			<div>
				<span class="sim__plan-kicker"><?php esc_html_e( 'Recommended for you', 'a2e' ); ?></span>
				<strong data-sim-plan-name><?php esc_html_e( 'Growth Management', 'a2e' ); ?></strong>
				<small data-sim-plan-price></small>
			</div>
			<a class="btn btn--primary btn--sm" href="<?php echo esc_url( a2e_url( 'pricing' ) ); ?>" data-sim-plan-link><span><?php esc_html_e( 'See this plan', 'a2e' ); ?></span><?php a2e_the_icon( 'arrow-right', 'btn__icon' ); ?></a>
		</div>
	</div>
</div>
