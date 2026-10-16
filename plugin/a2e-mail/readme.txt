=== A2E Mail Studio ===
Contributors: a2edropshipping
Requires at least: 6.3
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later

Design and send professional, on-brand emails to the people you already have a relationship with: your clients and everyone who contacted you or subscribed on your website.

== Description ==

A2E Mail Studio lets you write and send polished, theme-styled emails from your own address (for example support@a2edropshipping.com) to contacts you already have a relationship with.

Who you can email:

1. Your website visitors who filled in a form (free audit, consultation, contact) or subscribed to your newsletter. These are read from the theme's Leads.
2. Your clients, read from the A2E Whop Sync subscribers.
3. Anyone you add by hand, where you confirm you already have permission to email them.

What it does NOT do: it never collects addresses from the web, never imports bought or scraped lists, and never emails anyone who unsubscribed or bounced. This keeps your sending domain trusted and keeps you on the right side of anti-spam law.

Features:

* **Branded, email-safe designs** in your theme's colours. Write in a simple format (paragraphs, ## headings, - bullets, [[Label|link]] buttons) with merge tags like {{first_name}}. Long dashes are removed automatically, and your WhatsApp, call number, email and a one-click unsubscribe are added to every message.
* **Ready-made templates** for welcoming clients, following up an audit request, a seasonal promotion, a monthly check-in, and a blank branded message. Edit the wording freely under Templates.
* **Composer** with a live preview and a test send. Send to a group (all subscribers, clients, newsletter, form leads, or contacts you added) or to one person.
* **A safe sending queue** with per-minute, per-hour and per-day limits so your domain is never flooded. Pause and resume any send.
* **Automatic follow-up sequences (new in 1.1)**: set your follow-ups once and they send themselves. A trigger (a new audit request, a new client, a health-check signup, a new subscriber) enrols the person, and timed steps go out on the schedule you choose. Each sequence stops on its own when the person unsubscribes or becomes a client, so nobody is ever over-emailed. Two ready sequences are included (free audit follow-up, new client welcome), switched off until you review them.
* **Automatic contact finding**: new form submissions are added the instant they arrive, and everything is re-checked every few minutes. A "Check for new contacts now" button and a "Search hard (full rescan)" button are on the dashboard.
* **Your own sending server (SMTP)** with a test email, plus an SPF and DMARC check for your domain.
* **One-click unsubscribe** (RFC 8058 List-Unsubscribe headers) and a suppression list that is always honoured.

== Important: how contacts are found ==

This plugin reads contacts from your own WordPress site (form Leads and Whop clients). It does not search the public internet for email addresses, because emailing addresses collected that way is treated as spam by inbox providers and is an aggravated violation under the US CAN-SPAM Act, which quickly gets a sending domain blacklisted. If you want to reach new businesses you have no relationship with yet, use a reputable B2B data provider that handles sourcing and opt-outs, then add those contacts and send careful, personal messages.

== Settings ==

A2E Mail → Settings: the From name and address, your contact details and postal address (required in the footer), sending limits, and optional SMTP. A2E Mail → Templates: edit the ready-made emails or add your own.

== Changelog ==

= 1.1.0 =
* Automatic follow-up sequences (drip automations) with triggers, timed steps, stop-on-goal, and a one-off catch-up enrolment.
* New contact source and segment for people who run the eBay account health checker.
* Leads from the website now enrol into matching sequences automatically.

= 1.0.0 =
* First release.
