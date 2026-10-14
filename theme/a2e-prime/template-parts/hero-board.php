<?php
/**
 * Homepage hero board: one example product going from supplier, to an eBay listing,
 * to profit after every fee. Tabs switch products; figures are labelled as examples.
 *
 * @package A2E_Prime
 */

$a2e_ex = a2e_hero_examples();
if ( ! $a2e_ex ) {
	return;
}
$a2e_d = $a2e_ex[0];
?>
<div class="jr" data-search-icon="<?php echo esc_attr( a2e_icon( 'search' ) ); ?>" data-journey="<?php echo a2e_json_attr( $a2e_ex ); // phpcs:ignore ?>">
	<div class="jr__glow" aria-hidden="true"></div>
	<div class="jr__frame">
		<div class="jr__top">
			<div class="jr__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Example products', 'a2e' ); ?>">
				<?php foreach ( $a2e_ex as $a2e_i => $a2e_item ) : ?>
					<button type="button" role="tab" class="jr__tab<?php echo 0 === $a2e_i ? ' is-active' : ''; ?>" id="jr-tab-<?php echo (int) $a2e_i; ?>" aria-controls="jr-panel" aria-selected="<?php echo 0 === $a2e_i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $a2e_i ? '0' : '-1'; ?>" data-jr-tab="<?php echo (int) $a2e_i; ?>">
						<?php echo a2e_brand_mark( $a2e_item['supplier'], 'sm' ); // phpcs:ignore ?>
						<span class="jr__tab-name"><?php echo esc_html( $a2e_item['tab'] ); ?></span>
						<i class="jr__tab-bar" aria-hidden="true"></i>
					</button>
				<?php endforeach; ?>
			</div>
			<span class="jr__badge"><span class="jr__badge-dot" aria-hidden="true"></span><?php esc_html_e( 'Example product', 'a2e' ); ?></span>
		</div>

		<div class="jr__grid" id="jr-panel" role="tabpanel" aria-labelledby="jr-tab-0">
			<article class="jr__card jr__card--source">
				<header class="jr__head">
					<span class="jr__num">1</span>
					<span class="jr__step"><?php esc_html_e( 'Researched & sourced', 'a2e' ); ?></span>
					<span class="jr__mark" data-jr="mark" data-swap><?php echo $a2e_d['mark']; // phpcs:ignore ?></span>
				</header>
				<div class="jr__product" data-swap>
					<span class="jr__art" data-jr="art"><?php echo $a2e_d['artSvg']; // phpcs:ignore ?></span>
					<div>
						<strong class="jr__name" data-jr="name"><?php echo esc_html( $a2e_d['name'] ); ?></strong>
						<span class="jr__sub"><?php esc_html_e( 'Supplier price', 'a2e' ); ?> <b data-jr="cost"><?php echo esc_html( $a2e_d['costFmt'] ); ?></b></span>
					</div>
				</div>
				<div class="jr__demand">
					<div class="jr__demand-head"><span><?php esc_html_e( 'Sold on eBay, last 30 days', 'a2e' ); ?></span><b data-jr="sold"><?php echo esc_html( $a2e_d['soldText'] ); ?></b></div>
					<div class="jr__bars" data-jr="bars" aria-hidden="true"><?php foreach ( $a2e_d['bars'] as $a2e_k => $a2e_h ) : ?><i style="--i:<?php echo (int) $a2e_k; ?>;height:<?php echo (int) $a2e_h; ?>%"></i><?php endforeach; ?></div>
				</div>
				<ul class="jr__checks">
					<li class="is-on" data-jr-check><?php a2e_the_icon( 'check' ); ?><span data-jr="str"><?php echo esc_html( $a2e_d['strText'] ); ?></span></li>
					<li class="is-on" data-jr-check><?php a2e_the_icon( 'check' ); ?><span><?php esc_html_e( 'Supplier ships within 2 days', 'a2e' ); ?></span></li>
					<li class="is-on" data-jr-check><?php a2e_the_icon( 'check' ); ?><span><?php esc_html_e( 'No VeRO or brand risk', 'a2e' ); ?></span></li>
				</ul>
			</article>

			<span class="jr__link" aria-hidden="true"><span class="jr__link-dot"><?php a2e_the_icon( 'arrow-right' ); ?></span></span>

			<article class="jr__card jr__card--listing">
				<header class="jr__head">
					<span class="jr__num">2</span>
					<span class="jr__step"><?php esc_html_e( 'Listed to rank', 'a2e' ); ?></span>
					<span class="jr__mark"><?php echo a2e_brand_mark( 'ebay', 'sm' ); // phpcs:ignore ?></span>
				</header>
				<p class="jr__title" data-jr="title"><?php echo esc_html( $a2e_d['title'] ); ?></p>
				<div class="jr__meter">
					<span class="jr__meter-bar"><i data-jr="meter" style="width:<?php echo esc_attr( round( $a2e_d['chars'] / 80 * 100, 1 ) ); ?>%"></i></span>
					<span><?php /* translators: %s: characters used */ printf( esc_html__( '%s/80 characters', 'a2e' ), '<b data-jr="chars">' . (int) $a2e_d['chars'] . '</b>' ); ?></span>
				</div>
				<ul class="jr__specs" data-jr="specs">
					<?php foreach ( $a2e_d['specs'] as $a2e_spec ) : ?>
						<li><span><?php echo esc_html( $a2e_spec[0] ); ?></span><?php echo esc_html( $a2e_spec[1] ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="jr__seo">
					<span class="jr__seo-label"><?php esc_html_e( 'Matches what buyers search', 'a2e' ); ?></span>
					<ul data-jr="searches">
						<?php foreach ( $a2e_d['searches'] as $a2e_q ) : ?>
							<li><?php a2e_the_icon( 'search' ); ?><?php echo esc_html( $a2e_q ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="jr__price" data-swap>
					<strong data-jr="price"><?php echo esc_html( $a2e_d['priceFmt'] ); ?></strong>
					<span><?php esc_html_e( 'Free shipping', 'a2e' ); ?></span>
				</div>
			</article>

			<span class="jr__link" aria-hidden="true"><span class="jr__link-dot"><?php a2e_the_icon( 'arrow-right' ); ?></span></span>

			<article class="jr__card jr__card--profit">
				<header class="jr__head">
					<span class="jr__num">3</span>
					<span class="jr__step"><?php esc_html_e( 'Profit after every fee', 'a2e' ); ?></span>
					<span class="jr__margin" data-jr="margin" data-swap><?php echo esc_html( $a2e_d['marginFmt'] ); ?></span>
				</header>
				<div class="jr__big">
					<strong data-jr="profit">+<?php echo esc_html( $a2e_d['profitFmt'] ); ?></strong>
					<span class="jr__per"><?php esc_html_e( 'per sale', 'a2e' ); ?></span>
				</div>
				<div class="jr__stack" aria-hidden="true">
					<i class="jr__seg jr__seg--cost" data-jr-seg="0" style="width:<?php echo esc_attr( $a2e_d['split'][0] ); ?>%"></i>
					<i class="jr__seg jr__seg--fees" data-jr-seg="1" style="width:<?php echo esc_attr( $a2e_d['split'][1] ); ?>%"></i>
					<i class="jr__seg jr__seg--profit" data-jr-seg="2" style="width:<?php echo esc_attr( $a2e_d['split'][2] ); ?>%"></i>
				</div>
				<dl class="jr__rows" data-swap>
					<div><dt><i class="jr__key jr__key--price"></i><?php esc_html_e( 'eBay sale price', 'a2e' ); ?></dt><dd data-jr="priceRow"><?php echo esc_html( $a2e_d['priceFmt'] ); ?></dd></div>
					<div><dt><i class="jr__key jr__key--cost"></i><?php esc_html_e( 'Supplier cost', 'a2e' ); ?></dt><dd data-jr="costRow">&minus;<?php echo esc_html( $a2e_d['costFmt'] ); ?></dd></div>
					<div><dt><i class="jr__key jr__key--fees"></i><?php esc_html_e( 'eBay fees', 'a2e' ); ?></dt><dd data-jr="fees">&minus;<?php echo esc_html( $a2e_d['feesFmt'] ); ?></dd></div>
					<div class="is-total"><dt><i class="jr__key jr__key--profit"></i><?php esc_html_e( 'You keep', 'a2e' ); ?></dt><dd data-jr="profitRow"><?php echo esc_html( $a2e_d['profitFmt'] ); ?></dd></div>
				</dl>
				<p class="jr__month" data-swap><?php a2e_the_icon( 'trend' ); ?><span data-jr="month"><?php echo $a2e_d['monthHtml']; // phpcs:ignore ?></span></p>
				<a class="jr__cta" href="<?php echo esc_url( a2e_url( 'quote-request' ) ); ?>"><?php esc_html_e( 'Get products like this researched for you', 'a2e' ); ?><?php a2e_the_icon( 'arrow-right' ); ?></a>
			</article>
		</div>
	</div>
	<p class="jr__note">
		<?php
		/* translators: 1: fee rate 2: order fee */
		printf( esc_html__( 'Example figures. eBay fees: %1$s%% final value fee on the total including 7%% sales tax, plus a %2$s order fee.', 'a2e' ), esc_html( a2e_opt( 'fee_rate' ) ), esc_html( '$' . number_format( (float) a2e_opt( 'fee_order_large' ), 2 ) ) );
		?>
	</p>
</div>
