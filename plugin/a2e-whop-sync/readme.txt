=== A2E Whop Sync ===
Contributors: a2edropshipping
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Create subscription plans in WordPress — they are created on your Whop account automatically, with secure checkout, subscriber sync and cancellations.

== Description ==

1. Connect once: paste a Whop API key under Whop → Connect. The plugin detects your account and creates a signed webhook automatically.
2. Create plans under Whop → Plans (name, price, billing period, setup fee, trial, features). Publishing creates the product and plan on Whop and stores the checkout link.
3. Plans appear on the A2E Prime pricing pages automatically (or anywhere via [a2e_whop_plans] / [a2e_whop_button plan="ID"]).
4. New subscribers, renewals, failed payments, refunds and cancellations sync back to Whop → Subscribers. Cancel any subscription at period end from WordPress.

Price changes create a new Whop plan and hide the old one, so existing subscribers keep the price they signed up for. Trashing or unpublishing a plan hides it on Whop; existing subscribers are not affected.

The API key and webhook secret are stored encrypted. You can also define A2EWS_API_KEY in wp-config.php.

== Changelog ==

= 1.0.0 =
* First release.
