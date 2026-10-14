<?php
/**
 * Homepage hero visual: an animated example store dashboard with supplier sourcing mix,
 * sales/profit chart and an order feed. Clearly labelled as an example.
 *
 * @package A2E_Prime
 */

$demo   = a2e_hero_demo_data();
$weeks  = $demo['weeks'];
$profit = $demo['profit'];
$n      = count( $weeks );
$w      = 560;
$h      = 200;
$max    = max( $weeks ) * 1.08;
$pmax   = max( $profit ) * 1.1;
$pts    = array();
foreach ( $weeks as $i => $v ) {
	$pts[] = array( round( 14 + $i * ( ( $w - 28 ) / ( $n - 1 ) ), 2 ), round( $h - 18 - ( $v / $max ) * ( $h - 40 ), 2 ) );
}

/**
 * Smooth SVG path through points (Catmull-Rom to cubic Bézier).
 *
 * @param array $p Points.
 * @return string
 */
$smooth = function ( $p ) {
	$d     = 'M' . $p[0][0] . ' ' . $p[0][1];
	$count = count( $p );
	for ( $i = 0; $i < $count - 1; $i++ ) {
		$p0 = $p[ max( 0, $i - 1 ) ];
		$p1 = $p[ $i ];
		$p2 = $p[ $i + 1 ];
		$p3 = $p[ min( $count - 1, $i + 2 ) ];
		$d .= sprintf(
			' C%s %s %s %s %s %s',
			round( $p1[0] + ( $p2[0] - $p0[0] ) / 6, 2 ),
			round( $p1[1] + ( $p2[1] - $p0[1] ) / 6, 2 ),
			round( $p2[0] - ( $p3[0] - $p1[0] ) / 6, 2 ),
			round( $p2[1] - ( $p3[1] - $p1[1] ) / 6, 2 ),
			$p2[0],
			$p2[1]
		);
	}
	return $d;
};
$line = $smooth( $pts );
$area = $line . ' L' . $pts[ $n - 1 ][0] . ' ' . ( $h - 18 ) . ' L' . $pts[0][0] . ' ' . ( $h - 18 ) . ' Z';

$rev30  = array_sum( array_slice( $weeks, -4 ) );
$rev_p  = array_sum( array_slice( $weeks, -8, 4 ) );
$pro30  = array_sum( array_slice( $profit, -4 ) );
$pro_p  = array_sum( array_slice( $profit, -8, 4 ) );
$orders = (int) round( $rev30 / 33 );
$ord_p  = (int) round( $rev_p / 33 );
$delta  = function ( $a, $b ) {
	return $b ? round( ( $a - $b ) / $b * 100 ) : 0;
};
$spark = function ( $series ) {
	$min = min( $series );
	$max = max( $series );
	$out = array();
	$c   = count( $series );
	foreach ( $series as $i => $v ) {
		$out[] = round( $i * ( 80 / ( $c - 1 ) ), 1 ) . ',' . round( 26 - ( ( $v - $min ) / max( 1, $max - $min ) ) * 22, 1 );
	}
	return implode( ' ', $out );
};
$chart_data = array();
foreach ( $weeks as $i => $v ) {
	$chart_data[] = array( 'x' => $pts[ $i ][0], 'y' => $pts[ $i ][1], 's' => $v, 'p' => $profit[ $i ] );
}

// Donut.
$r      = 34;
$circ   = 2 * M_PI * $r;
$offset = 0;
$colors = array( 'var(--c1)', 'var(--c2)', 'var(--c3)', 'var(--c4)', 'var(--c5)' );
?>
<div class="hv" data-hv>
	<div class="hv__halo" aria-hidden="true"></div>
	<div class="dash" data-tilt aria-label="<?php esc_attr_e( 'Example dashboard of an eBay store managed by A2E', 'a2e' ); ?>" role="img">
		<div class="dash__bar">
			<span class="dash__dots" aria-hidden="true"><i></i><i></i><i></i></span>
			<span class="dash__url"><?php a2e_the_icon( 'lock' ); ?>your-store · <?php esc_html_e( 'Seller overview', 'a2e' ); ?></span>
			<span class="dash__live"><i></i><?php esc_html_e( 'Example store', 'a2e' ); ?></span>
		</div>
		<div class="dash__body">
			<div class="dash__kpis">
				<div class="kpi">
					<span class="kpi__label"><?php esc_html_e( 'Sales · 30 days', 'a2e' ); ?></span>
					<strong class="kpi__val">$<span data-count="<?php echo (int) $rev30; ?>"><?php echo esc_html( number_format( $rev30 ) ); ?></span></strong>
					<span class="kpi__delta">▲ <?php echo (int) $delta( $rev30, $rev_p ); ?>%</span>
					<svg class="kpi__spark" viewBox="0 0 80 28" aria-hidden="true"><polyline points="<?php echo esc_attr( $spark( $weeks ) ); ?>"/></svg>
				</div>
				<div class="kpi kpi--profit">
					<span class="kpi__label"><?php esc_html_e( 'Net profit', 'a2e' ); ?></span>
					<strong class="kpi__val">$<span data-count="<?php echo (int) $pro30; ?>"><?php echo esc_html( number_format( $pro30 ) ); ?></span></strong>
					<span class="kpi__delta">▲ <?php echo (int) $delta( $pro30, $pro_p ); ?>%</span>
					<svg class="kpi__spark" viewBox="0 0 80 28" aria-hidden="true"><polyline points="<?php echo esc_attr( $spark( $profit ) ); ?>"/></svg>
				</div>
				<div class="kpi kpi--orders">
					<span class="kpi__label"><?php esc_html_e( 'Orders', 'a2e' ); ?></span>
					<strong class="kpi__val"><span data-count="<?php echo (int) $orders; ?>"><?php echo (int) $orders; ?></span></strong>
					<span class="kpi__delta">▲ <?php echo (int) $delta( $orders, $ord_p ); ?>%</span>
					<svg class="kpi__spark" viewBox="0 0 80 28" aria-hidden="true"><polyline points="<?php echo esc_attr( $spark( array_map( function ( $v ) { return $v / 33; }, $weeks ) ) ); ?>"/></svg>
				</div>
			</div>

			<div class="dash__main">
				<div class="dash__chart" data-chart='<?php echo esc_attr( wp_json_encode( $chart_data ) ); ?>'>
					<div class="dash__chart-head">
						<span><?php esc_html_e( 'Sales vs profit · 12 weeks', 'a2e' ); ?></span>
						<span class="legend"><i class="legend__sales"></i><?php esc_html_e( 'Sales', 'a2e' ); ?> <i class="legend__profit"></i><?php esc_html_e( 'Profit', 'a2e' ); ?></span>
					</div>
					<div class="chart__wrap">
					<svg class="chart" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" preserveAspectRatio="none" aria-hidden="true">
						<defs>
							<linearGradient id="hvArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0" style="stop-color:var(--c1);stop-opacity:.38"/><stop offset="1" style="stop-color:var(--c1);stop-opacity:0"/></linearGradient>
							<linearGradient id="hvLine" x1="0" x2="1" y1="0" y2="0"><stop offset="0" style="stop-color:var(--c2)"/><stop offset=".55" style="stop-color:var(--c1)"/><stop offset="1" style="stop-color:var(--c3)"/></linearGradient>
						</defs>
						<g class="chart__plot"><g class="chart__grid"><?php for ( $g = 0; $g < 4; $g++ ) : ?><line x1="0" x2="<?php echo (int) $w; ?>" y1="<?php echo (int) ( 22 + $g * 53 ); ?>" y2="<?php echo (int) ( 22 + $g * 53 ); ?>"/><?php endfor; ?></g>
						<g class="chart__bars">
							<?php foreach ( $profit as $i => $p ) : $bh = round( ( $p / $pmax ) * 64, 1 ); ?>
								<rect x="<?php echo esc_attr( $pts[ $i ][0] - 7 ); ?>" y="<?php echo esc_attr( $h - 18 - $bh ); ?>" width="14" height="<?php echo esc_attr( $bh ); ?>" rx="3" style="--i:<?php echo (int) $i; ?>"/>
							<?php endforeach; ?>
						</g>
						<path class="chart__area" d="<?php echo esc_attr( $area ); ?>" fill="url(#hvArea)"/>
						<path class="chart__line" d="<?php echo esc_attr( $line ); ?>" stroke="url(#hvLine)"/>
						<line class="chart__cursor" x1="0" x2="0" y1="10" y2="<?php echo (int) ( $h - 18 ); ?>"/>
					</g></svg>
					<span class="chart__dot" style="left:<?php echo esc_attr( round( $pts[ $n - 1 ][0] / $w * 100, 3 ) ); ?>%;top:<?php echo esc_attr( round( $pts[ $n - 1 ][1] / $h * 100, 3 ) ); ?>%" data-dot></span>
					</div>
					<div class="chart__tip" data-tip hidden></div>
				</div>

				<div class="dash__mix">
					<span class="dash__mini-title"><?php esc_html_e( 'Sourcing mix', 'a2e' ); ?></span>
					<div class="donut">
						<svg viewBox="0 0 90 90" aria-hidden="true">
							<circle cx="45" cy="45" r="<?php echo (int) $r; ?>" class="donut__track"/>
							<?php
							foreach ( $demo['mix'] as $i => $seg ) :
								$len = $seg[1] / 100 * $circ;
								?>
								<circle cx="45" cy="45" r="<?php echo (int) $r; ?>" class="donut__seg" data-seg="<?php echo (int) $i; ?>" stroke-dasharray="<?php echo esc_attr( round( $len - 2, 2 ) . ' ' . round( $circ - $len + 2, 2 ) ); ?>" stroke-dashoffset="<?php echo esc_attr( round( -$offset, 2 ) ); ?>" style="--i:<?php echo (int) $i; ?>;stroke:<?php echo esc_attr( $colors[ $i ] ); ?>"/>
								<?php
								$offset += $len;
							endforeach;
							?>
						</svg>
						<span class="donut__center"><strong><?php echo count( $demo['mix'] ); ?></strong><small><?php esc_html_e( 'suppliers', 'a2e' ); ?></small></span>
					</div>
					<ul class="mix-legend">
						<?php foreach ( $demo['mix'] as $i => $seg ) : ?>
							<li data-seg="<?php echo (int) $i; ?>"><i style="background:<?php echo esc_attr( $colors[ $i ] ); ?>"></i><?php echo a2e_brand_mark( $seg[0], 'sm' ); // phpcs:ignore ?><b><?php echo (int) $seg[1]; ?>%</b></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<div class="dash__feed">
				<div class="dash__mini-title"><?php esc_html_e( 'Latest orders', 'a2e' ); ?> <span class="dash__ping" aria-hidden="true"></span></div>
				<ul class="feed" data-feed>
					<?php foreach ( $demo['orders'] as $i => $o ) : ?>
						<li class="feed__row<?php echo $i < 3 ? ' is-on' : ''; ?>">
							<span class="feed__from"><?php echo a2e_brand_mark( $o[0], 'sm' ); // phpcs:ignore ?></span>
							<span class="feed__arrow" aria-hidden="true"><?php a2e_the_icon( 'arrow-right' ); ?></span>
							<span class="feed__to"><?php echo a2e_brand_mark( 'ebay', 'sm' ); // phpcs:ignore ?></span>
							<span class="feed__name"><?php echo esc_html( $o[1] ); ?></span>
							<span class="feed__profit">+$<?php echo esc_html( number_format( $o[2], 2 ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>

	<div class="fc fc--listing" aria-hidden="true">
		<span class="fc__icon fc__icon--ok"><?php a2e_the_icon( 'check' ); ?></span>
		<span><strong><?php esc_html_e( 'Listing live', 'a2e' ); ?></strong><small><?php esc_html_e( 'Title score 96/100', 'a2e' ); ?></small></span>
	</div>
	<div class="fc fc--vero" aria-hidden="true">
		<span class="fc__icon"><?php a2e_the_icon( 'shield' ); ?></span>
		<span><strong><?php esc_html_e( 'VeRO & policy check', 'a2e' ); ?></strong><small><?php esc_html_e( 'Passed · margin 24% after fees', 'a2e' ); ?></small></span>
	</div>
	<div class="fc fc--sync" aria-hidden="true">
		<span class="fc__icon fc__icon--spin"><?php a2e_the_icon( 'refresh' ); ?></span>
		<span><strong><?php esc_html_e( 'Price & stock synced', 'a2e' ); ?></strong><small><?php esc_html_e( '1,240 listings checked', 'a2e' ); ?></small></span>
	</div>
</div>
