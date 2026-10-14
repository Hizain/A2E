=== A2E Whop Sync ===
Contributors: a2edropshipping
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Create subscription plans and one-time services in WordPress and they are created on your Whop account automatically, with secure checkout, subscriber sync and cancellations.

== Description ==

1. Connect once: paste a Whop API key under Whop → Connect. The plugin detects your account and creates a signed webhook automatically.
2. Create plans under Whop → Plans (name, price, billing period, setup fee, trial, features). Publishing creates the product and plan on Whop and stores the checkout link.
3. Group plans under Whop → Categories (for example Monthly management, Listing packages, Store setup). Every plan shown on the website appears in the A2E Prime services shop, grouped by category; tick "Also show on the homepage and pricing page" for the 3 or 4 main plans. Other themes can use [a2e_whop_plans] / [a2e_whop_button plan="ID"].
4. New subscribers, renewals, failed payments, refunds and cancellations sync back to Whop → Subscribers. Cancel any subscription at period end from WordPress.

Price changes create a new Whop plan and hide the old one, so existing subscribers keep the price they signed up for. Trashing or unpublishing a plan hides it on Whop; existing subscribers are not affected.

Quote-only services: leave the price empty and set a button link (for example your quote form). They show in the shop but are not created on Whop.

Moving from WooCommerce: while WooCommerce is active, Whop → Plans offers to import your products as draft plans (price, sale price, description, bullet-point features, category, and billing period for WooCommerce Subscriptions products). Check each draft and press Publish to create it on Whop.

The API key and webhook secret are stored encrypted. You can also define A2EWS_API_KEY in wp-config.php.

== Changelog ==

= 1.1.0 =
* Service categories with icon and order, for the A2E Prime services shop.
* New plan fields: full description (editor), icon, "best for", delivery time, regular price, "learn more" link, custom button link for quote-only services.
* Choose per plan: services shop only, or also homepage and pricing page.
* Import WooCommerce products as draft plans.
* Whop sync behaviour is unchanged.

= 1.0.0 =
* First release.
