<?php
/**
 * Free tool registry: SEO copy, explainers and FAQs for every tool page.
 *
 * Each tool lives at /free-tools/{slug}/ as a child page of /free-tools/. The page is created
 * automatically; text around the tool can be added in the page editor.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All tools, keyed by slug.
 *
 * @return array
 */
function a2e_tools() {
	static $tools = null;
	if ( null !== $tools ) {
		return $tools;
	}
	$checked = a2e_opt( 'fee_checked' );

	$tools = array(
		'ebay-fee-calculator'                  => array(
			'name'      => 'eBay Fee Calculator',
			'icon'      => 'calculator',
			'cat'       => 'fees',
			'short'     => 'See exactly what eBay takes from a sale: final value fee, per-order fee, ads and your real profit.',
			'seo_title' => 'eBay Fee Calculator (2026): Final Value Fees & Profit',
			'meta'      => 'Free eBay fee calculator. Enter your sale price, shipping and costs to see the final value fee, per-order fee, promoted listing cost and your real profit.',
			'intro'     => 'Enter what the buyer pays and what the item costs you. The calculator works out every eBay fee on the order and shows the profit you actually keep.',
			'explainer' => '<h2>How eBay fees are calculated</h2>
<p>For most categories eBay charges a <strong>final value fee</strong> as a percentage of the <em>total amount of the sale</em> — the item price plus shipping, handling and the sales tax the buyer pays — plus a small <strong>per-order fee</strong>. In the US the standard rate for most categories is 13.6% on the first $7,500 and 2.35% on anything above it, and the per-order fee is $0.30 for orders of $10 or less and $0.40 for orders over $10. Some categories, such as books, movies and music, and guitars and basses, use different rates.</p>
<h3>Other fees this calculator includes</h3>
<ul><li><strong>Promoted Listings (general)</strong> – the ad rate you choose, charged only when a buyer clicks your ad and buys within 30 days.</li><li><strong>International fee</strong> – an extra percentage when you ship to a buyer outside your country.</li><li><strong>Seller level</strong> – Top Rated Plus listings get a discount on the final value fee, while Below Standard sellers pay a higher rate.</li></ul>
<h3>The formula</h3>
<p><code>Total sale = price + shipping + sales tax</code><br><code>Final value fee = total sale × category rate (+ above-threshold rate)</code><br><code>Profit = price + shipping − all eBay fees − item cost − your shipping cost</code></p>
<p>Sales tax is collected and paid to the state by eBay, so it is not part of your revenue — but it <em>is</em> part of the amount the final value fee is calculated on. That is why sellers who ignore it usually underestimate their fees.</p>
<h3>Tips to pay less in fees</h3>
<ul><li>Price so that your margin still works <em>after</em> the ad rate — not before.</li><li>Keep your account Above Standard. A Below Standard rating raises your fees on every sale.</li><li>Check your category: a few categories have lower rates or no per-order fee above a price point.</li></ul>',
			'faqs'      => array(
				array( 'q' => 'How much does eBay take from a sale?', 'a' => 'For most US categories eBay takes 13.6% of the total sale (item price, shipping and sales tax) up to $7,500, 2.35% above that, plus $0.30 per order for orders of $10 or less or $0.40 for orders over $10. Ads, international sales and your seller level can add to or reduce this.' ),
				array( 'q' => 'Does eBay charge fees on shipping?', 'a' => 'Yes. The final value fee is calculated on the total amount the buyer pays, which includes the shipping and handling you charge.' ),
				array( 'q' => 'Does eBay charge fees on sales tax?', 'a' => 'Yes. eBay calculates the final value fee on the total sale including the sales tax collected from the buyer, even though the tax itself is passed on to the state.' ),
				array( 'q' => 'Are the rates in this calculator up to date?', 'a' => 'The default rates were last checked in ' . $checked . '. eBay can change its fees at any time, so always confirm the rate for your category on eBay’s official fee page before making big pricing decisions.' ),
			),
		),
		'amazon-to-ebay-profit-calculator'     => array(
			'name'      => 'Amazon to eBay Profit Calculator',
			'icon'      => 'trend',
			'cat'       => 'fees',
			'short'     => 'Check if a product is worth listing: Amazon cost vs eBay price, after every fee.',
			'seo_title' => 'Amazon to eBay Dropshipping Profit Calculator (Free)',
			'meta'      => 'Free Amazon to eBay dropshipping profit calculator. See your real profit, margin, ROI and break-even price after eBay fees and sales tax.',
			'intro'     => 'Put in the Amazon price and the eBay price you plan to sell at. You will instantly see your profit, margin, ROI, break-even price and the price you need for your target margin.',
			'explainer' => '<h2>Why most dropshipping margins are smaller than they look</h2>
<p>The gap between the Amazon price and the eBay price is not your profit. Sales tax on your Amazon purchase, eBay’s final value fee on the full sale (including tax), the per-order fee and any promoted listing spend all come out of that gap. A product that looks like an $8 win can easily end up making $2.</p>
<h3>What each result means</h3>
<ul><li><strong>Profit</strong> – what you keep per sale after all costs and fees.</li><li><strong>Margin</strong> – profit as a percentage of your selling price. Many sellers aim for 15–25% to leave room for returns and price changes.</li><li><strong>ROI</strong> – profit as a percentage of what you spend to buy the item.</li><li><strong>Break-even price</strong> – the lowest eBay price where you lose nothing.</li><li><strong>Target price</strong> – the eBay price you need to hit the margin you choose.</li></ul>
<h3>Before you list any product</h3>
<p>Check that the product actually sells on eBay (look at sold listings, not active ones), that it is not brand-restricted, and that your supplier can deliver within your handling time. Also read eBay’s current dropshipping policy — you remain responsible for delivery, and buying from another retail marketplace to ship directly to your buyer is not allowed. A good margin is worthless if it puts your account at risk.</p>',
			'faqs'      => array(
				array( 'q' => 'What is a good profit margin for Amazon to eBay dropshipping?', 'a' => 'Many sellers aim for at least 15–25% margin after all fees. Thin margins disappear quickly when supplier prices change, items get returned or you add promoted listings.' ),
				array( 'q' => 'Is Amazon to eBay dropshipping allowed?', 'a' => 'eBay allows dropshipping only within its policy: you are responsible for safe and timely delivery, and listing an item and then buying it from another retailer or marketplace to ship straight to your buyer is not allowed. Read eBay’s current dropshipping policy and plan your sourcing accordingly.' ),
				array( 'q' => 'Should I include Amazon sales tax in my cost?', 'a' => 'Yes. Unless you have a tax exemption set up, you pay sales tax on your Amazon purchase, and it reduces your profit on every order.' ),
				array( 'q' => 'How do I find the break-even price?', 'a' => 'This calculator solves it for you: it finds the eBay price where your profit is exactly zero after eBay fees, tax and your costs.' ),
			),
		),
		'ebay-price-calculator'                => array(
			'name'      => 'eBay Listing Price Calculator',
			'icon'      => 'tag',
			'cat'       => 'fees',
			'short'     => 'Work backwards from the profit you want to the exact price to list at.',
			'seo_title' => 'eBay Price Calculator: What to Charge for Your Target Profit',
			'meta'      => 'Free eBay price calculator. Enter your cost and target margin or profit and get the exact listing price that covers eBay fees, ads and shipping.',
			'intro'     => 'Tell us what the item costs you and how much you want to make. We calculate the listing price that covers every eBay fee and still hits your target.',
			'explainer' => '<h2>Pricing backwards from profit</h2>
<p>Most sellers pick a price and hope the profit works. It is faster and safer to start from the profit you need and calculate the price. Because eBay’s fees are a percentage of the final price, you cannot simply add your target to your cost — the fee grows as the price grows. This calculator solves that for you.</p>
<h3>Margin or fixed profit?</h3>
<ul><li><strong>Target margin</strong> keeps your profit proportional to the price, which works well across products with very different prices.</li><li><strong>Fixed profit</strong> is useful for cheap items where a percentage would be too small to be worth the work.</li></ul>
<h3>Check the market before you publish</h3>
<p>The right price also has to be competitive. Compare your result with <em>sold</em> listings for the same item. If the calculated price is well above what buyers have recently paid, the product probably is not worth listing.</p>',
			'faqs'      => array(
				array( 'q' => 'How do I calculate the right price for an eBay listing?', 'a' => 'Start with your total cost, add the profit you want, then account for eBay’s percentage fees, the per-order fee and any ad rate. Because the fees depend on the final price, the easiest way is to use this calculator, which solves it exactly.' ),
				array( 'q' => 'Should I offer free shipping on eBay?', 'a' => 'Often yes — buyers like it and eBay highlights it. Just include the shipping cost in your item price so your margin stays the same.' ),
				array( 'q' => 'What margin should I target?', 'a' => 'It depends on your category and how competitive it is, but many sellers aim for 15–30%. Leave room for returns, price changes and promoted listings.' ),
			),
		),
		'ebay-title-generator'                 => array(
			'name'      => 'eBay Title Builder & Checker',
			'icon'      => 'type',
			'cat'       => 'listing',
			'short'     => 'Build a keyword-rich 80-character title and get an instant quality score.',
			'seo_title' => 'eBay Title Generator & Checker – 80 Character SEO Titles',
			'meta'      => 'Free eBay title generator and checker. Build keyword-rich 80-character listing titles, spot wasted characters and spammy words, and get an instant SEO score.',
			'intro'     => 'Fill in the details of your product and we build an eBay title that uses the 80 characters well. Already have a title? Paste it into the checker to get a score and fixes.',
			'explainer' => '<h2>What makes a good eBay title</h2>
<p>eBay titles can be up to 80 characters, and the words in them are the main thing eBay’s search engine (Cassini) uses to decide whether your listing appears for a search. A strong title reads naturally and puts the words buyers actually search for first.</p>
<h3>A simple formula that works</h3>
<p><code>Brand + Product type + Model + Key features + Size / Colour + Condition</code></p>
<h3>Common title mistakes</h3>
<ul><li><strong>Wasted characters</strong> – words like “L@@K”, “WOW”, “Great” or “!!!” take up space without helping search.</li><li><strong>ALL CAPS</strong> – harder to read and looks spammy.</li><li><strong>Repeating words</strong> – saying the same keyword twice does not rank you higher.</li><li><strong>Leaving characters unused</strong> – a 40-character title misses half its chances to match searches.</li><li><strong>Keyword stuffing with other brands</strong> – adding brands you are not selling breaks eBay’s rules.</li></ul>
<p>Put the details that are not in the title into item specifics. eBay uses those for filters, and buyers use filters a lot.</p>',
			'faqs'      => array(
				array( 'q' => 'How long can an eBay title be?', 'a' => 'eBay listing titles can be up to 80 characters, including spaces. Using most of them with relevant keywords gives your listing more chances to appear in searches.' ),
				array( 'q' => 'Should I use all 80 characters?', 'a' => 'Use as many as you can with words buyers actually search for. Do not pad the title with filler words just to reach 80.' ),
				array( 'q' => 'Does word order matter in eBay titles?', 'a' => 'eBay matches keywords anywhere in the title, but putting the most important words first makes the title easier to read in search results, especially on mobile where titles get cut off.' ),
			),
		),
		'ebay-sell-through-rate-calculator'    => array(
			'name'      => 'eBay Sell-Through Rate Calculator',
			'icon'      => 'refresh',
			'cat'       => 'research',
			'short'     => 'Measure how fast a product sells before you list it.',
			'seo_title' => 'eBay Sell-Through Rate Calculator (STR) – Free Tool',
			'meta'      => 'Free eBay sell-through rate calculator. Enter sold and active listings to get the sell-through rate, demand rating and estimated days to sell.',
			'intro'     => 'Search eBay for the product, count the sold listings over a period and the current active listings, and enter them below. You get the sell-through rate and how quickly the item is likely to sell.',
			'explainer' => '<h2>What is sell-through rate?</h2>
<p>Sell-through rate (STR) shows how much of the available supply of a product actually sells. It is one of the quickest ways to judge demand before you list.</p>
<p><code>Sell-through rate = sold listings ÷ (sold listings + active listings) × 100</code></p>
<h3>How to read the result</h3>
<ul><li><strong>Below 20%</strong> – slow. Lots of competition for few buyers.</li><li><strong>20–50%</strong> – moderate demand. Worth listing if your price and listing are strong.</li><li><strong>50–80%</strong> – strong demand.</li><li><strong>Above 80%</strong> – very hot. Check that supply is reliable before you jump in.</li></ul>
<h3>How to get the numbers</h3>
<p>On eBay, search for the item, then use the “Sold items” filter to count sales (use the same time period every time — 90 days is common). Remove that filter to count the active listings. Tools like eBay’s Terapeak research give the same numbers faster.</p>',
			'faqs'      => array(
				array( 'q' => 'What is a good sell-through rate on eBay?', 'a' => 'Anything above 50% is generally considered strong demand. Between 20% and 50% can still be profitable with a competitive price and a well-optimized listing.' ),
				array( 'q' => 'How do I find sold listings on eBay?', 'a' => 'Search for the product, then tick “Sold items” in the filters (under “Show only” on desktop). Use the same date range each time you compare products.' ),
				array( 'q' => 'Is sell-through rate the same as conversion rate?', 'a' => 'No. Sell-through rate compares sold to available listings across the market. Conversion rate compares your own sales with the number of people who viewed your listing.' ),
			),
		),
		'ebay-defect-rate-calculator'          => array(
			'name'      => 'eBay Defect Rate Calculator',
			'icon'      => 'gauge',
			'cat'       => 'growth',
			'short'     => 'Check your transaction defect rate against eBay’s seller standards.',
			'seo_title' => 'eBay Transaction Defect Rate Calculator – Seller Level Check',
			'meta'      => 'Free eBay defect rate calculator. Enter your transactions and defects to see your transaction defect rate, your seller level band and how many defects you can afford.',
			'intro'     => 'Enter the number of transactions in your evaluation period and how many had a defect. See your defect rate, where it puts you against eBay’s standards and how much room you have left.',
			'explainer' => '<h2>What counts as a defect?</h2>
<p>eBay counts a transaction as defective when, for example, a Money Back Guarantee case is closed without seller resolution or you cancel an order because the item is out of stock. Your <strong>transaction defect rate</strong> is the share of your transactions in the evaluation period that had at least one defect.</p>
<p><code>Defect rate = transactions with a defect ÷ total transactions × 100</code></p>
<h3>The thresholds</h3>
<ul><li><strong>Top Rated</strong> – no more than 0.5% of transactions with a defect.</li><li><strong>Above Standard</strong> – no more than 2%.</li><li><strong>Below Standard</strong> – above 2%, which brings higher fees and lower visibility.</li></ul>
<p>Your rate only affects your level once defects involve a minimum number of different buyers (eBay currently uses 4 for Top Rated and 5 for Above Standard). eBay evaluates sellers monthly — check your Seller Dashboard for your exact numbers and period.</p>
<h3>How dropshippers keep defects low</h3>
<ul><li>Check supplier stock daily so you never have to cancel for “out of stock”.</li><li>Upload valid tracking fast and set a handling time you can always meet.</li><li>Answer buyer messages quickly and solve problems before they become cases.</li></ul>',
			'faqs'      => array(
				array( 'q' => 'What is a good transaction defect rate on eBay?', 'a' => 'To be Top Rated you need 0.5% or less. To stay Above Standard you need 2% or less. Lower is always better for visibility and fees.' ),
				array( 'q' => 'Does an out-of-stock cancellation count as a defect?', 'a' => 'Yes. Cancelling an order because the item is out of stock counts as a seller-initiated cancellation defect, which is why stock monitoring is so important for dropshippers.' ),
				array( 'q' => 'How often does eBay evaluate seller levels?', 'a' => 'eBay evaluates seller performance every month, looking back over your recent sales. Your Seller Dashboard shows your current numbers and the date of the next evaluation.' ),
			),
		),
		'ebay-promoted-listings-calculator'    => array(
			'name'      => 'eBay Promoted Listings Calculator',
			'icon'      => 'percent',
			'cat'       => 'fees',
			'short'     => 'Find the highest ad rate you can afford and still make money.',
			'seo_title' => 'eBay Promoted Listings Calculator – Break-Even Ad Rate',
			'meta'      => 'Free eBay promoted listings calculator. See your ad fee per sale, profit after ads, break-even ad rate and the maximum ad rate for your target margin.',
			'intro'     => 'Enter your sale price, cost and ad rate. We show what the ad costs per sale, what you keep, and the highest ad rate that still leaves you a profit.',
			'explainer' => '<h2>How promoted listing fees work</h2>
<p>With Promoted Listings (general / cost-per-sale), you choose an ad rate — a percentage of the sale. You only pay it when a buyer clicks your promoted listing and buys that item within 30 days. The fee is charged on top of eBay’s normal selling fees.</p>
<h3>Find your break-even ad rate</h3>
<p>Your break-even ad rate is the rate where the ad fee uses up all your profit. Anything above it means you lose money on every promoted sale. A sensible maximum leaves you with the margin you need.</p>
<p><code>Break-even ad rate = profit before ads ÷ total sale × 100</code></p>
<h3>Ad-rate tips</h3>
<ul><li>Start near the suggested rate eBay shows for your category, then adjust based on results.</li><li>Do not promote items with thin margins — fix the price or the product first.</li><li>Review your promoted sales monthly and lower the rate on items that would sell anyway.</li></ul>',
			'faqs'      => array(
				array( 'q' => 'Are eBay promoted listings worth it?', 'a' => 'They can be, as long as your ad rate is below your break-even rate and the extra visibility brings sales you would not otherwise get. Use this calculator to set a ceiling before you start.' ),
				array( 'q' => 'When do I pay the promoted listing fee?', 'a' => 'For general (cost-per-sale) promoted listings, you pay only when a buyer clicks your ad and buys the item within 30 days.' ),
				array( 'q' => 'What ad rate should I use?', 'a' => 'Start around eBay’s suggested rate for your category, but never above the maximum rate this calculator shows for your target margin.' ),
			),
		),
		'ebay-store-name-generator'            => array(
			'name'      => 'eBay Store Name Generator',
			'icon'      => 'sparkle',
			'cat'       => 'listing',
			'short'     => 'Get dozens of catchy, brandable eBay store name ideas in seconds.',
			'seo_title' => 'eBay Store Name Generator – Free Store Name Ideas',
			'meta'      => 'Free eBay store name generator. Enter your niche and get dozens of brandable eBay store name ideas, plus tips for picking a name buyers trust.',
			'intro'     => 'Type a word that describes your niche, pick a style and press Generate. Click any name to copy it.',
			'explainer' => '<h2>How to pick a great eBay store name</h2>
<ul><li><strong>Keep it short</strong> – easier to remember, type and fit on a logo.</li><li><strong>Leave room to grow</strong> – “KitchenCorner” limits you less than “BestGarlicPresses”.</li><li><strong>Avoid trademarks</strong> – never use brand names you do not own, such as “eBay” or “Amazon”.</li><li><strong>Make it trustworthy</strong> – buyers prefer names that sound like a real shop over random strings of keywords.</li><li><strong>Check it is free</strong> – search eBay and social networks before you commit.</li></ul>
<p>Your store name matters less for search ranking than your listings do, but it matters a lot for trust and repeat buyers.</p>',
			'faqs'      => array(
				array( 'q' => 'Can I change my eBay store name later?', 'a' => 'Yes, you can change your store name in your store settings, but changing it often can confuse repeat buyers, so pick one you are happy to keep.' ),
				array( 'q' => 'Should my eBay store name include keywords?', 'a' => 'A keyword can help buyers understand what you sell, but a brandable, trustworthy name is more valuable than stuffing keywords in.' ),
				array( 'q' => 'Is my eBay store name the same as my user ID?', 'a' => 'Not necessarily. Your store name and your eBay user ID can be different, although many sellers keep them similar for consistency.' ),
			),
		),
		'ebay-description-template-generator'  => array(
			'name'      => 'eBay Description Template Generator',
			'icon'      => 'file',
			'cat'       => 'listing',
			'short'     => 'Create a clean, mobile-friendly eBay listing description — copy, paste, done.',
			'seo_title' => 'Free eBay Listing Description Template Generator (HTML)',
			'meta'      => 'Free eBay listing description template generator. Create a clean, mobile-friendly HTML description with features, specs and policies, then copy and paste it into eBay.',
			'intro'     => 'Fill in your product details, preview the result, then copy the HTML and paste it into the description editor on eBay (switch to the HTML tab first).',
			'explainer' => '<h2>Why a clean description sells better</h2>
<p>Most eBay buyers shop on their phones. Heavy templates with big images, tiny text and columns are hard to read on a small screen. A simple, well-structured description with short bullet points answers questions faster and looks more professional.</p>
<h3>What this template does</h3>
<ul><li>Uses simple HTML that works in eBay’s description editor and on mobile.</li><li>Contains no scripts, forms or outside links — eBay does not allow active content.</li><li>Keeps your brand colour subtle so the product stays the focus.</li></ul>
<h3>What to put in your description</h3>
<p>Lead with the main benefit, list the key features, add exact specifications, say what is in the box, and finish with clear shipping and returns information. Keep the important details in item specifics too — eBay uses those for search filters.</p>',
			'faqs'      => array(
				array( 'q' => 'Can I use HTML in eBay descriptions?', 'a' => 'Yes. eBay’s description editor has an HTML option. Basic HTML and inline styles work; scripts, forms and most external content are not allowed.' ),
				array( 'q' => 'How long should an eBay description be?', 'a' => 'Long enough to answer the questions a buyer would ask, and no longer. Short paragraphs and bullet points work best, especially on mobile.' ),
				array( 'q' => 'Do eBay descriptions help with search ranking?', 'a' => 'Your title and item specifics matter most for eBay search, but a clear description improves conversions and reduces returns and questions.' ),
			),
		),
		'ebay-bulk-profit-calculator'          => array(
			'name'      => 'Bulk eBay Profit Calculator',
			'icon'      => 'list',
			'cat'       => 'fees',
			'short'     => 'Paste a product list and see fees, profit and margin for every row at once.',
			'seo_title' => 'Bulk eBay Profit Calculator – Check 100 Products at Once',
			'meta'      => 'Free bulk eBay profit calculator. Paste product costs and eBay prices from a spreadsheet to see fees, profit, margin and a list-or-skip verdict for every item, then export to CSV.',
			'intro'     => 'Paste rows from your sourcing sheet — product, cost and eBay price (shipping optional). Every row gets eBay fees, profit, margin and a verdict. Export the results as CSV.',
			'explainer' => '<h2>Check a whole sourcing list in seconds</h2>
<p>Checking products one by one is how good research sessions turn into late nights. Paste the list straight from Excel or Google Sheets and every row is calculated with the same eBay fee model as our single-product calculators — final value fee on the full sale including tax, the per-order fee and your ad rate.</p>
<h3>How to format your list</h3>
<p>One product per line: <code>Product name, item cost, eBay price, shipping cost</code>. Shipping is optional. Columns copied from a spreadsheet are separated by tabs, which works too. A header row is skipped automatically.</p>
<h3>How the verdict works</h3>
<ul><li><strong>List</strong> – margin at or above your target.</li><li><strong>Test</strong> – profitable, but below your target margin. Worth testing with a better price or lower cost.</li><li><strong>Skip</strong> – loses money or makes almost nothing after fees.</li></ul>
<p>Everything runs in your browser — nothing you paste is uploaded or stored.</p>',
			'faqs'      => array(
				array( 'q' => 'Can I paste directly from Excel or Google Sheets?', 'a' => 'Yes. Copy the cells (name, cost, eBay price and optionally shipping) and paste them in. Tab-separated and comma-separated rows both work.' ),
				array( 'q' => 'Is my product list saved anywhere?', 'a' => 'No. All calculations happen in your browser. Nothing is uploaded to our servers.' ),
				array( 'q' => 'How many products can I check at once?', 'a' => 'Hundreds. The calculator updates instantly as you paste, and you can download the results as a CSV file.' ),
			),
		),
		'ebay-product-research-scorecard'      => array(
			'name'      => 'eBay Product Research Scorecard',
			'icon'      => 'scan',
			'cat'       => 'research',
			'short'     => 'Score any product 0–100 on demand, competition, margin and risk before you list it.',
			'seo_title' => 'eBay Product Research Scorecard – Is This Product Worth Selling?',
			'meta'      => 'Free eBay product research scorecard. Rate demand, sell-through, competition, margin, delivery speed and brand risk to get a 0–100 score and a clear list, test or skip verdict.',
			'intro'     => 'Answer six quick questions about a product you are considering. The scorecard weighs them the way we do when we research for clients and returns a 0–100 score with a verdict.',
			'explainer' => '<h2>The six checks every product should pass</h2>
<p>Winning products are not found by luck — they pass the same handful of checks every time. This scorecard turns our research checklist into a single number so you can compare products quickly and stop listing items that were never going to sell.</p>
<h3>How the score is weighted</h3>
<ul><li><strong>Demand (25 points)</strong> – how many sold on eBay in the last 30 days.</li><li><strong>Margin after fees (25 points)</strong> – your real margin after eBay fees, tax and ads.</li><li><strong>Sell-through rate (15 points)</strong> – sold vs available supply.</li><li><strong>Competition (15 points)</strong> – how many sellers and how good their listings are.</li><li><strong>Delivery speed (10 points)</strong> – whether your supplier can meet your handling time.</li><li><strong>Brand & return risk (10 points)</strong> – VeRO restrictions, fragile or high-return items.</li></ul>
<h3>Reading your score</h3>
<ul><li><strong>75–100: List it.</strong> Strong on the factors that matter most.</li><li><strong>55–74: Test carefully.</strong> List one or two units, improve the weak factor, review in two weeks.</li><li><strong>Below 55: Skip.</strong> Your time is better spent on the next product.</li></ul>
<p>Need the numbers? Use our <a href="/free-tools/ebay-sell-through-rate-calculator/">sell-through rate calculator</a> and <a href="/free-tools/amazon-to-ebay-profit-calculator/">profit calculator</a> first.</p>',
			'faqs'      => array(
				array( 'q' => 'How do I find winning products to sell on eBay?', 'a' => 'Look for products with steady sold history in the last 30–90 days, a sell-through rate above 40–50%, weak or few competing listings and a margin of at least 15–25% after all eBay fees. Then remove anything brand-restricted or slow to deliver.' ),
				array( 'q' => 'What is VeRO on eBay?', 'a' => 'VeRO (Verified Rights Owner) is eBay’s programme that lets brands remove listings that infringe their rights. Listing VeRO-protected brands can get listings removed and put your account at risk, so check before you list.' ),
				array( 'q' => 'How many sales in 30 days means good demand?', 'a' => 'It depends on the niche, but 10 or more sales across the market in 30 days is a reasonable sign of demand for most dropshipping products; 30+ is strong.' ),
			),
		),
		'ebay-dropshipping-income-calculator'  => array(
			'name'      => 'eBay Dropshipping Income Calculator',
			'icon'      => 'coins',
			'cat'       => 'growth',
			'short'     => 'Project monthly orders, revenue and profit — and how many listings you need for your goal.',
			'seo_title' => 'eBay Dropshipping Income Calculator – How Much Can You Make?',
			'meta'      => 'Free eBay dropshipping income calculator. Estimate monthly orders, revenue and profit from your active listings, sales rate and average profit, and see how many listings you need to hit your goal.',
			'intro'     => 'Enter your active listings, how often a listing sells and your average profit per sale. See projected monthly orders, revenue and profit — and how many listings you need to reach your income goal.',
			'explainer' => '<h2>The three levers of dropshipping income</h2>
<p>Every eBay dropshipping store’s profit comes down to three numbers: how many active listings you have, how often each listing sells, and how much you keep per sale. Improve any one of them and income grows; improve all three and it compounds.</p>
<p><code>Monthly orders = active listings × sales per listing per month</code><br><code>Monthly profit = monthly orders × average profit per order</code></p>
<h3>Realistic sales rates</h3>
<p>Most dropshipping listings do not sell every month. Many healthy stores see somewhere between 0.05 and 0.3 sales per listing per month across their catalogue, with a few best sellers doing much more. New accounts usually start lower while eBay builds trust in the account and selling limits are small.</p>
<h3>How to move the numbers</h3>
<ul><li><strong>More listings</strong> – steady, researched additions every month (within your selling limits).</li><li><strong>Higher sales rate</strong> – better products, stronger titles and complete item specifics.</li><li><strong>Higher profit per order</strong> – smarter pricing and cheaper, reliable suppliers.</li></ul>
<p>These are projections, not promises: real results depend on products, competition, account health and seasonality.</p>',
			'faqs'      => array(
				array( 'q' => 'How much can you make dropshipping on eBay?', 'a' => 'It varies widely. A store with 1,000 researched listings selling 0.1 times per listing per month at $6 average profit would make about $600 a month. The calculator lets you model your own numbers.' ),
				array( 'q' => 'How many listings do I need to make $1,000 a month?', 'a' => 'Divide your goal by (sales per listing per month × average profit). At 0.1 sales per listing and $6 profit per sale you would need about 1,667 active listings. Better products and listings reduce that number.' ),
				array( 'q' => 'Why does a new eBay store sell less?', 'a' => 'New accounts have low selling limits, little feedback and less buyer trust, so listings tend to rank and convert lower at first. Consistent, high-quality listings and good service improve this over time.' ),
			),
		),
		'ebay-store-management-cost-calculator' => array(
			'name'      => 'eBay Store Management Cost Calculator',
			'icon'      => 'wallet',
			'cat'       => 'growth',
			'short'     => 'Compare the true cost of running your store yourself vs a VA or a done-for-you plan.',
			'seo_title' => 'eBay Store Management Cost Calculator – DIY vs Done-For-You',
			'meta'      => 'Free calculator: see how many hours your eBay store really takes each month, what that time is worth, and how it compares with hiring a VA or a done-for-you management plan.',
			'intro'     => 'Enter how long the weekly store tasks take you and what an hour of your time is worth. See the true monthly cost of doing it yourself and how it compares with a VA or a managed plan.',
			'explainer' => '<h2>The hidden cost of “free” DIY</h2>
<p>Running a store yourself feels free because no invoice arrives. But research, listing, stock checks, orders and buyer messages add up to hours every week — hours you could spend on a job, a family or another business. Put a fair price on that time and the comparison often surprises people.</p>
<h3>Typical weekly time for a growing store</h3>
<ul><li>Product research: 3–6 hours</li><li>Writing and uploading listings: 4–8 hours</li><li>Stock and price checks: 2–5 hours</li><li>Orders, tracking and returns: 2–4 hours</li><li>Buyer messages and cases: 1–3 hours</li></ul>
<h3>When each option makes sense</h3>
<ul><li><strong>DIY</strong> – you are learning, have spare time and a small catalogue.</li><li><strong>A VA</strong> – you have clear routines and time to train and check their work.</li><li><strong>Done-for-you</strong> – you want results without managing people, with research and listing expertise included.</li></ul>',
			'faqs'      => array(
				array( 'q' => 'How much does eBay account management cost?', 'a' => 'Done-for-you management plans commonly range from a few hundred dollars a month, depending on listing volume and scope. Our plans start at $200 a month including 150 researched listings.' ),
				array( 'q' => 'How many hours a week does an eBay dropshipping store take?', 'a' => 'A store adding listings every week typically takes 10–25 hours a week across research, listing, monitoring, orders and buyer messages.' ),
				array( 'q' => 'Is hiring a VA cheaper than a management service?', 'a' => 'A VA can cost less per hour, but you still have to train, manage and check their work, and research quality depends on their experience. Include your own management time when you compare.' ),
			),
		),
	);

	foreach ( $tools as $slug => &$tool ) {
		$tool['slug'] = $slug;
	}
	unset( $tool );

	$tools = apply_filters( 'a2e_tools', $tools );
	return $tools;
}

/**
 * Tool categories for the hub filters.
 *
 * @return array
 */
function a2e_tool_categories() {
	return array(
		'fees'     => __( 'Fees & profit', 'a2e' ),
		'research' => __( 'Product research', 'a2e' ),
		'listing'  => __( 'Listings & store', 'a2e' ),
		'growth'   => __( 'Growth & account health', 'a2e' ),
	);
}

/**
 * Related tools for a tool page (same category first).
 *
 * @param string $slug  Current tool.
 * @param int    $limit How many.
 * @return array
 */
function a2e_related_tools( $slug, $limit = 3 ) {
	$tools = a2e_tools();
	if ( ! isset( $tools[ $slug ] ) ) {
		return array_slice( $tools, 0, $limit );
	}
	$cat  = $tools[ $slug ]['cat'];
	$same = array();
	$rest = array();
	foreach ( $tools as $key => $tool ) {
		if ( $key === $slug ) {
			continue;
		}
		if ( $tool['cat'] === $cat ) {
			$same[ $key ] = $tool;
		} else {
			$rest[ $key ] = $tool;
		}
	}
	return array_slice( array_merge( $same, $rest ), 0, $limit );
}

/**
 * URL of a tool page.
 *
 * @param string $slug Tool slug.
 * @return string
 */
function a2e_tool_url( $slug ) {
	return a2e_url( 'free-tools/' . $slug );
}

/**
 * Tool slug for a page, if it is a tool page (child of /free-tools/ with a tool slug).
 *
 * @param WP_Post $post Page.
 * @return string
 */
function a2e_tool_slug_for_post( $post ) {
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type || ! $post->post_parent ) {
		return '';
	}
	$tools = a2e_tools();
	if ( ! isset( $tools[ $post->post_name ] ) ) {
		return '';
	}
	$parent = get_post( $post->post_parent );
	return ( $parent && 'free-tools' === $parent->post_name ) ? $post->post_name : '';
}

/**
 * Tool on the current request (a tool page, or the virtual fallback route).
 *
 * @return string
 */
function a2e_current_tool() {
	if ( ! empty( $GLOBALS['a2e_virtual_tool'] ) ) {
		return $GLOBALS['a2e_virtual_tool'];
	}
	if ( ! is_singular( 'page' ) ) {
		return '';
	}
	return a2e_tool_slug_for_post( get_queried_object() );
}
