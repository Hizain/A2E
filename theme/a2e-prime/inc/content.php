<?php
/**
 * Built-in copy: services, process, comparisons. Headlines for each service guide can be
 * overridden per post in the editor (A2E page header box); everything here is the default.
 *
 * @package A2E_Prime
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service guide content keyed by slug (/service-guides/{slug}/).
 *
 * @return array
 */
function a2e_services() {
	static $services = null;
	if ( null !== $services ) {
		return $services;
	}
	$services = array(
		'amazon-to-ebay-dropshipping' => array(
			'title'    => 'Amazon to eBay Dropshipping',
			'nav'      => 'Done-for-you dropshipping',
			'icon'     => 'layers',
			'short'    => 'The complete operation — research, listings, store setup and daily management — run by one team.',
			'seo'      => 'Amazon to eBay Dropshipping Service — Done For You | A2E',
			'meta'     => 'Done-for-you Amazon to eBay dropshipping: profit-checked product research, SEO eBay listings, store setup, stock & price monitoring and monthly management.',
			'headline' => 'Your complete eBay dropshipping operation, <em>run for you.</em>',
			'intro'    => 'Finding products, writing listings, watching prices, answering buyers — every day. We run the whole workflow as one connected team, so your store keeps growing even on the days you are busy.',
			'outcomes' => array( 'One team instead of three freelancers', 'Every product profit-checked after fees', 'A clear report every month' ),
			'deliver'  => array(
				array( 'scan', 'Profit-first product research', 'Demand, competition, VeRO and margin after every eBay fee — checked before a single product goes live.' ),
				array( 'pen', 'Listings built to rank', 'Keyword-rich 80-character titles, complete item specifics and clean, mobile-friendly descriptions.' ),
				array( 'store', 'Store foundation', 'Business policies, categories, branding and handling times that earn buyer trust from day one.' ),
				array( 'refresh', 'Stock & price monitoring', 'Supplier changes are caught early, so you never sell at a loss or cancel an order.' ),
				array( 'chat', 'Orders & buyer care', 'Order routines, tracking checks and fast, polite replies that protect your feedback.' ),
				array( 'chart', 'Monthly reporting', 'What sold, what changed and what we are doing next — in plain English.' ),
			),
			'best_for' => array( 'New sellers who want it done properly from the start', 'Busy store owners who want their evenings back', 'Sellers tired of managing separate freelancers' ),
			'faq'      => array(
				array( 'What does your Amazon to eBay dropshipping service include?', 'Product research, SEO listings, store setup, stock and price routines, order and buyer-message workflows and monthly account management — the exact mix depends on the plan you choose.' ),
				array( 'Is Amazon to eBay dropshipping allowed on eBay?', 'eBay allows dropshipping but holds you responsible for delivery, and it does not allow buying from another retailer or marketplace to ship straight to your buyer. We build a compliant multi-supplier sourcing and fulfilment workflow and review it against the current rules during onboarding.' ),
				array( 'Can you run the whole store for me?', 'Yes. Scale Management and custom plans cover the full workflow, from research to daily store care and reporting.' ),
				array( 'Is this suitable for beginners?', 'Yes. Beginners usually start with store setup plus a starter listing batch, or a free audit to plan the first steps.' ),
			),
			'tools'    => array( 'amazon-to-ebay-profit-calculator', 'ebay-fee-calculator', 'ebay-dropshipping-income-calculator' ),
		),
		'product-research'            => array(
			'title'    => 'Product Research',
			'nav'      => 'Product research',
			'icon'     => 'scan',
			'short'    => 'Products chosen on sold data and real margins — not recycled “winning product” lists.',
			'seo'      => 'eBay Product Research Service for Dropshipping | A2E',
			'meta'     => 'eBay product research for dropshipping sellers: sold history, sell-through, competition, VeRO and profit after fees — a shortlist that is ready to list.',
			'headline' => 'Products picked on data, <em>not guesswork.</em>',
			'intro'    => 'Research is where a store gets its edge — or wastes months. Every product we hand you has passed demand, competition, restriction and profit-after-fees checks.',
			'outcomes' => array( 'Shortlists, not giant spreadsheets', 'VeRO & restricted brands filtered out', 'Margin checked after every fee' ),
			'deliver'  => array(
				array( 'chart', 'Sold history & sell-through', 'We look at what actually sold on eBay in the last 30–90 days, not what people list.' ),
				array( 'users', 'Competition gaps', 'Products where existing listings are weak, so a better listing can win the sale.' ),
				array( 'dollar', 'Profit after fees', 'Final value fee, per-order fee, ads and tax are all deducted before a product qualifies.' ),
				array( 'shield', 'VeRO & policy checks', 'Brand restrictions and prohibited items are removed before they can hurt your account.' ),
				array( 'truck', 'Supplier stability', 'Reliable stock, sensible delivery times and low return risk.' ),
				array( 'clipboard', 'Ready-to-list shortlist', 'Each product comes with notes, so listing is fast and accurate.' ),
			),
			'best_for' => array( 'Sellers whose listings get views but no sales', 'New stores choosing their first niches', 'Anyone who wants a repeatable research system' ),
			'faq'      => array(
				array( 'How many products do you research?', 'It depends on your plan — from 100 products on the Starter package to 500 every month on Scale Management. We focus on quality, never padding the numbers.' ),
				array( 'Do you check for restricted brands?', 'Yes. Every product is checked for VeRO brand restrictions and prohibited items before it reaches your list.' ),
				array( 'Can I use the research without your listing service?', 'Yes. Research-only batches are available as a custom plan.' ),
			),
			'tools'    => array( 'ebay-product-research-scorecard', 'ebay-sell-through-rate-calculator', 'amazon-to-ebay-profit-calculator' ),
		),
		'product-listing'             => array(
			'title'    => 'Product Listing & eBay SEO',
			'nav'      => 'Listings & eBay SEO',
			'icon'     => 'pen',
			'short'    => 'Titles, item specifics and descriptions written for eBay search — and for buyers.',
			'seo'      => 'eBay Listing Service & Listing SEO for Dropshippers | A2E',
			'meta'     => 'Professional eBay listing service: keyword-rich titles, complete item specifics, correct categories and clean descriptions that rank in eBay search and convert.',
			'headline' => 'eBay listings built to be found — <em>and bought.</em>',
			'intro'    => 'A listing has two jobs: appear when buyers search, and convince them to buy. We write every listing around how buyers actually search on eBay.',
			'outcomes' => array( 'Keyword-rich 80-character titles', 'Complete item specifics', 'Mobile-first descriptions' ),
			'deliver'  => array(
				array( 'type', 'Titles that use all 80 characters', 'Primary keywords first, then the details buyers filter by.' ),
				array( 'list', 'Complete item specifics', 'Brand, model, size, colour and every filter eBay search relies on.' ),
				array( 'grid', 'Correct category & condition', 'The right category is a ranking factor — we get it right first time.' ),
				array( 'file', 'Clean descriptions', 'Scannable, mobile-friendly and free of policy-breaking links.' ),
				array( 'dollar', 'Competitive pricing', 'Checked against sold comparables and your minimum margin.' ),
				array( 'repeat', 'Rewrites of old listings', 'We re-optimise listings that get views but no sales — or no views at all.' ),
			),
			'best_for' => array( 'Stores with low impressions or clicks', 'Sellers scaling past 100 listings', 'Anyone rewriting a tired catalogue' ),
			'faq'      => array(
				array( 'Do you optimise listings for eBay search?', 'Yes. Titles, item specifics, category and description structure are all written for eBay search (Cassini) and buyer intent.' ),
				array( 'Can you rewrite my existing listings?', 'Yes — clean-ups of existing catalogues are one of our most requested jobs.' ),
				array( 'Do you use templates with external links?', 'No. Descriptions are clean HTML that follows eBay’s active-content and links policy.' ),
			),
			'tools'    => array( 'ebay-title-generator', 'ebay-description-template-generator', 'ebay-price-calculator' ),
		),
		'store-setup'                 => array(
			'title'    => 'eBay Store Setup',
			'nav'      => 'Store setup',
			'icon'     => 'store',
			'short'    => 'Policies, categories, branding and settings done right before you scale.',
			'seo'      => 'eBay Store Setup Service for Dropshipping Sellers | A2E',
			'meta'     => 'Professional eBay store setup: business policies, handling times, store categories, branding and a launch plan — a trustworthy foundation before you list at volume.',
			'headline' => 'An eBay store buyers trust <em>from the first visit.</em>',
			'intro'    => 'Missing policies, random categories and a blank profile make even good listings look risky. We build a clean, professional foundation before you start listing at volume.',
			'outcomes' => array( 'Policies set up correctly', 'Branding that builds trust', 'A plan for your first listings' ),
			'deliver'  => array(
				array( 'file', 'Business policies', 'Shipping, returns and payment policies that are clear and realistic.' ),
				array( 'timer', 'Handling times you can meet', 'Set around your suppliers, so late shipments never become defects.' ),
				array( 'grid', 'Store categories', 'Organised around your niches so buyers can browse.' ),
				array( 'sparkle', 'Name, logo & banner', 'A store identity that looks established — try our free name generator.' ),
				array( 'gauge', 'Selling-limit plan', 'A realistic ramp-up that respects your account’s limits.' ),
				array( 'rocket', 'Launch checklist', 'Everything ticked off before your first listing goes live.' ),
			),
			'best_for' => array( 'Brand-new eBay accounts', 'Stores that grew without a structure', 'Sellers relaunching in a new niche' ),
			'faq'      => array(
				array( 'Do I need an existing eBay account?', 'No. We help with brand-new accounts and improve existing ones. Account age and selling limits affect how fast you can scale, and we plan around them.' ),
				array( 'Can you improve a store that is already live?', 'Yes. We audit and clean up existing stores — categories, policies, branding and listing presentation.' ),
			),
			'tools'    => array( 'ebay-store-name-generator', 'ebay-description-template-generator', 'ebay-title-generator' ),
		),
		'account-management'          => array(
			'title'    => 'eBay Account Management',
			'nav'      => 'Account management',
			'icon'     => 'gauge',
			'short'    => 'Daily store care: listings, stock and prices, orders, buyer messages and account health.',
			'seo'      => 'eBay Account Management Service — Monthly Store Management | A2E',
			'meta'     => 'Monthly eBay account management: new listings, stock and price checks, orders, buyer messages, account health monitoring and a clear monthly report.',
			'headline' => 'Daily store care, <em>without you doing it.</em>',
			'intro'    => 'A store does not improve on its own. It needs new listings, fast reactions to supplier changes, quick buyer replies and someone watching account health. That is what we do — every day.',
			'outcomes' => array( 'Above Standard is the goal', 'Fast buyer replies', 'Monthly report & priorities' ),
			'deliver'  => array(
				array( 'plus', 'New listings every month', 'Fresh, researched products keep your store growing (volume by plan).' ),
				array( 'refresh', 'Stock & price checks', 'Supplier changes are caught before they turn into losses or cancellations.' ),
				array( 'cart', 'Order handling', 'Orders processed within your handling time, with tracking checks.' ),
				array( 'chat', 'Buyer messages', 'Polite, fast answers using approved templates — escalated when needed.' ),
				array( 'shield', 'Account health watch', 'Defect rate, late shipments and cases monitored every week.' ),
				array( 'chart', 'Monthly report', 'Results, wins, risks and next month’s priorities.' ),
			),
			'best_for' => array( 'Active stores that need steady growth', 'Owners who do not want to live in Seller Hub', 'Sellers recovering account health' ),
			'faq'      => array(
				array( 'Is account management billed monthly?', 'Yes. Management works best as an ongoing monthly plan, and you can cancel future renewals at any time.' ),
				array( 'Will I still have full control of my account?', 'Always. Your account, listings and customers stay yours, and you can change or remove our access whenever you like.' ),
				array( 'Which access do you need?', 'We prefer eBay’s own team permissions where possible, so you never have to share your password.' ),
			),
			'tools'    => array( 'ebay-defect-rate-calculator', 'ebay-store-management-cost-calculator', 'ebay-dropshipping-income-calculator' ),
		),
		'order-fulfillment'           => array(
			'title'    => 'Order Fulfillment',
			'nav'      => 'Order fulfillment',
			'icon'     => 'truck',
			'short'    => 'Order routines, tracking, delay and return handling that protect your account.',
			'seo'      => 'eBay Dropshipping Order Fulfillment Service | A2E',
			'meta'     => 'Order fulfillment workflows for eBay dropshipping: order processing, tracking uploads, delay and return templates and escalation rules that protect account health.',
			'headline' => 'Every order handled the same way, <em>every time.</em>',
			'intro'    => 'Fulfilment is where weak stores get exposed: late tracking, silent buyers and slow answers become defects fast. We set up — and on management plans, run — a routine that keeps every order on track.',
			'outcomes' => array( 'Tracking uploaded on time', 'Templates for every problem', 'Clear escalation rules' ),
			'deliver'  => array(
				array( 'clipboard', 'Order checks', 'Every order verified and processed within your handling time.' ),
				array( 'route', 'Tracking routine', 'Valid tracking uploaded on time to protect your metrics.' ),
				array( 'chat', 'Message templates', 'For delays, cancellations, returns and “where is my order?”.' ),
				array( 'repeat', 'Returns & INR process', 'Clear steps for returns and item-not-received cases.' ),
				array( 'alert', 'Risk escalation', 'Rules for high-value or unusual orders.' ),
				array( 'calendar', 'Weekly review', 'Late, cancelled and returned orders reviewed every week.' ),
			),
			'best_for' => array( 'Stores with rising order volume', 'Sellers with late-shipment warnings', 'Teams that need a written SOP' ),
			'faq'      => array(
				array( 'Do you process orders for me?', 'On management plans we handle order processing within the agreed scope. Other plans include the workflow and templates so you or your team can run it.' ),
				array( 'Can this reduce late shipments?', 'It removes avoidable mistakes. Delivery also depends on suppliers and carriers, so we build checks around those too.' ),
			),
			'tools'    => array( 'ebay-defect-rate-calculator', 'ebay-fee-calculator' ),
		),
		'inventory-management'        => array(
			'title'    => 'Inventory & Price Management',
			'nav'      => 'Inventory & pricing',
			'icon'     => 'refresh',
			'short'    => 'Supplier stock and price monitoring that protects margins and account health.',
			'seo'      => 'eBay Dropshipping Inventory & Price Monitoring | A2E',
			'meta'     => 'Inventory management for eBay dropshipping: supplier stock checks, price monitoring, margin alerts and listing updates that prevent losses and cancellations.',
			'headline' => 'Protect your margin <em>and your account.</em>',
			'intro'    => 'When a supplier runs out or raises a price, an unmonitored listing either sells at a loss or forces a cancellation — and cancellations count against you. We catch those changes first.',
			'outcomes' => array( 'Fewer cancellations', 'No selling at a loss', 'Weekly inventory report' ),
			'deliver'  => array(
				array( 'refresh', 'Supplier stock checks', 'Frequency matched to your volume — daily for active stores.' ),
				array( 'trend', 'Price-change monitoring', 'Margin alerts when supplier prices move.' ),
				array( 'gear', 'Revise / end rules', 'Clear rules for when a listing is repriced, paused or ended.' ),
				array( 'badge', 'Supplier reliability notes', 'We track which suppliers are dependable.' ),
				array( 'bolt', 'Automation where it is safe', 'Tools for the routine checks, people for the judgement calls.' ),
				array( 'file', 'Weekly report', 'What changed and what we did about it.' ),
			),
			'best_for' => array( 'Stores with 200+ active listings', 'Sellers who have sold at a loss', 'Anyone with frequent out-of-stock cancellations' ),
			'faq'      => array(
				array( 'How often is stock checked?', 'It depends on your listing volume and how often your suppliers change. Active stores usually need daily checks.' ),
				array( 'Does this include price monitoring?', 'Yes. Price monitoring and margin protection are a core part of the routine.' ),
			),
			'tools'    => array( 'ebay-price-calculator', 'ebay-bulk-profit-calculator' ),
		),
		'automation'                  => array(
			'title'    => 'Dropshipping Automation',
			'nav'      => 'Automation',
			'icon'     => 'workflow',
			'short'    => 'Automate the routine checks — keep humans on anything that affects buyers or profit.',
			'seo'      => 'eBay Dropshipping Automation Setup | A2E',
			'meta'     => 'Practical eBay dropshipping automation: stock and price monitoring, reporting and listing updates set up for your store — with human review where it matters.',
			'headline' => 'Automate the routine. <em>Keep judgement human.</em>',
			'intro'    => 'Good automation saves hours; bad automation scales mistakes. We set up tools for repetitive monitoring and reporting and keep a person on anything that can affect buyers, profit or account health.',
			'outcomes' => array( 'Hours saved every week', 'Tools that fit your store', 'Human review on risky tasks' ),
			'deliver'  => array(
				array( 'refresh', 'Stock & price monitors', 'Configured with sensible margins and safety buffers.' ),
				array( 'bell', 'Listing update reminders', 'Never miss a revise or end action.' ),
				array( 'route', 'Order status tracking', 'Spot stuck or late orders automatically.' ),
				array( 'chat', 'Saved replies', 'Fast, consistent answers to common buyer questions.' ),
				array( 'chart', 'Weekly reporting', 'Key numbers collected without manual work.' ),
				array( 'shield', 'Guardrails', 'Complaints, supplier issues and policy decisions stay manual.' ),
			),
			'best_for' => array( 'Sellers spending hours on repetitive checks', 'Stores scaling past manual limits' ),
			'faq'      => array(
				array( 'Which software do you use?', 'It depends on your store size, budget and suppliers. We recommend and set up tools that fit your workflow rather than forcing one tool on everyone.' ),
				array( 'Can automation replace account management?', 'No. Automation handles repetitive checks; managing a store still needs human judgement.' ),
			),
			'tools'    => array( 'ebay-store-management-cost-calculator' ),
		),
		'virtual-assistant-services'  => array(
			'title'    => 'eBay Virtual Assistant Services',
			'nav'      => 'Virtual assistants',
			'icon'     => 'headset',
			'short'    => 'Trained eBay VAs working from written SOPs — consistent work without babysitting.',
			'seo'      => 'eBay Virtual Assistant Services for Dropshipping | A2E',
			'meta'     => 'eBay virtual assistant services with clear SOPs: research support, listing drafts, stock checks, buyer messages and weekly reporting.',
			'headline' => 'VA support with <em>a clear playbook.</em>',
			'intro'    => 'A virtual assistant only saves you time when the tasks are clearly defined. Our VAs work from written routines (SOPs), so the work is consistent and you are not stuck supervising.',
			'outcomes' => array( 'Trained on eBay', 'Written SOPs', 'Weekly reporting' ),
			'deliver'  => array(
				array( 'scan', 'Research support', 'Help with sourcing and shortlisting under our research rules.' ),
				array( 'pen', 'Listing drafts & updates', 'Accurate listings prepared to your standard.' ),
				array( 'refresh', 'Stock & price checks', 'Routine checks on schedule.' ),
				array( 'chat', 'Buyer messages', 'Answered with approved templates.' ),
				array( 'chart', 'Weekly reporting', 'What was done and what needs your attention.' ),
				array( 'clipboard', 'SOP writing', 'We can document your routines first.' ),
			),
			'best_for' => array( 'Owners who want to delegate safely', 'Stores with a backlog of admin' ),
			'faq'      => array(
				array( 'Is VA support monthly?', 'Usually, yes — VA support works best as monthly help with a defined task list.' ),
				array( 'Can you write the SOPs first?', 'Yes. We can document your routines so the work is clear before ongoing support starts.' ),
			),
			'tools'    => array( 'ebay-store-management-cost-calculator' ),
		),
		'custom-plans'                => array(
			'title'    => 'Custom Plans',
			'nav'      => 'Custom plans',
			'icon'     => 'sparkle',
			'short'    => '500+ listings a month or a specific mix of services — built around your store.',
			'seo'      => 'Custom eBay Dropshipping Management Plans | A2E',
			'meta'     => 'Custom Amazon to eBay dropshipping plans for 500+ listings a month or a mix of research, listings, automation, VA support and management.',
			'headline' => 'A plan built <em>around your store.</em>',
			'intro'    => 'When a standard package does not fit — you need more than 500 listings a month, or a specific mix of services — we build a custom plan with a clear scope and a fixed monthly price.',
			'outcomes' => array( 'Clear written scope', 'Fixed monthly price', 'Billed through Whop' ),
			'deliver'  => array(
				array( 'rocket', 'High-volume listing', 'Research and listing at whatever volume your account supports.' ),
				array( 'layers', 'Mix & match services', 'Research, listings, inventory, VA support and management.' ),
				array( 'users', 'Dedicated team', 'The same people on your store every month.' ),
				array( 'chart', 'Growth roadmap', 'Quarterly priorities and a monthly review call.' ),
				array( 'repeat', 'Store clean-ups', 'Catalogue rewrites and account-health recovery.' ),
				array( 'handshake', 'Flexible terms', 'Scale up or down as your store changes.' ),
			),
			'best_for' => array( 'Sellers with larger listing targets', 'Stores with several problems to fix at once', 'Agencies and multi-store owners' ),
			'faq'      => array(
				array( 'Who needs a custom plan?', 'Sellers with larger listing targets, several problems to fix at once or specific management needs.' ),
				array( 'Can custom plans be billed monthly?', 'Yes. Once the scope is agreed we set up a monthly plan you can manage from your account.' ),
			),
			'tools'    => array( 'ebay-dropshipping-income-calculator', 'ebay-store-management-cost-calculator' ),
		),
	);
	$services = apply_filters( 'a2e_services', $services );
	return $services;
}

/**
 * Services featured in the header and homepage (in order).
 *
 * @return array
 */
function a2e_featured_service_slugs() {
	return array( 'amazon-to-ebay-dropshipping', 'product-research', 'product-listing', 'store-setup', 'account-management', 'order-fulfillment', 'inventory-management', 'virtual-assistant-services' );
}

/**
 * URL of a service guide.
 *
 * @param string $slug Slug.
 * @return string
 */
function a2e_service_url( $slug ) {
	$post = get_page_by_path( $slug, OBJECT, 'a2e_service' );
	if ( $post && 'publish' === $post->post_status ) {
		return get_permalink( $post );
	}
	return home_url( '/service-guides/' . $slug . '/' );
}

/**
 * Homepage "how it works" steps.
 *
 * @return array
 */
function a2e_process_steps() {
	return array(
		array( 'scan', 'Free store audit', 'Tell us where your store is today. We review it and send a clear plan with a fixed price — usually within one business day.' ),
		array( 'search', 'Profit-checked research', 'We shortlist products with proven demand, low competition and healthy margins after every eBay fee.' ),
		array( 'pen', 'Listings go live', 'SEO titles, complete item specifics and clean descriptions — built to rank and convert.' ),
		array( 'gauge', 'We manage & report', 'Daily stock, price, order and buyer care, plus a monthly report on what sold and what is next.' ),
	);
}

/**
 * "Doing it alone vs with A2E" comparison rows.
 *
 * @return array list of [ alone, with_a2e ]
 */
function a2e_compare_rows() {
	return array(
		array( 'Guessing which products will sell', 'Products chosen on sold data and margin after fees' ),
		array( 'Listings buried on page 9 of search', 'Keyword-rich titles and complete item specifics' ),
		array( 'Selling at a loss when supplier prices jump', 'Daily stock & price checks with margin alerts' ),
		array( 'Evenings lost to buyer messages', 'Fast, polite replies handled for you' ),
		array( 'Defects and late shipments creeping up', 'Account health watched every week' ),
		array( 'Three freelancers who never talk', 'One team, one plan, one monthly report' ),
	);
}

/**
 * Example dashboard data for the hero visual (clearly labelled as an example on the page).
 *
 * @return array
 */
function a2e_hero_demo_data() {
	return array(
		'weeks'   => array( 1820, 2140, 2010, 2690, 2950, 3320, 3180, 3870, 4210, 4630, 4480, 5240 ),
		'profit'  => array( 260, 310, 290, 400, 455, 520, 500, 610, 670, 745, 720, 850 ),
		'mix'     => array(
			array( 'amazon', 34 ),
			array( 'walmart', 22 ),
			array( 'aliexpress', 17 ),
			array( 'homedepot', 15 ),
			array( 'target', 12 ),
		),
		'orders'  => array(
			array( 'amazon', 'Cordless stick vacuum filter (2-pack)', 6.42 ),
			array( 'walmart', 'Stainless steel air fryer liner set', 4.85 ),
			array( 'homedepot', 'LED motion sensor stair lights', 9.10 ),
			array( 'aliexpress', 'Magnetic phone car mount', 5.36 ),
			array( 'target', 'Ceramic plant pot, 3-piece', 7.80 ),
			array( 'amazon', 'Resistance band set with handles', 8.25 ),
		),
	);
}
