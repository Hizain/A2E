=== A2E Mail Studio ===
Contributors: a2edropshipping
Requires at least: 6.3
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
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
* **Welcome email for every new contact (new in 1.3)**: the moment someone is added (by you, or through a form or newsletter sign-up) they get "who we are and what we offer": Amazon to eBay dropshipping stores, branded eBay stores, Shopify stores and Claude automation, with a free audit button. It is sent once per person and is fully editable under Automations.
* **Weekly newsletter (new in 1.3)**: on the day and time you choose, an issue is built for you from your new blog posts, a tip of the week, a free tool of the week and your offer, then sent to your weekly list. Preview it, send yourself a test, add a note for the next issue, send it now or skip a week. People join through your newsletter form, the "Yes, send me the weekly tips" button in the welcome email, or when you add them and tick that they asked for it. They can leave the weekly newsletter alone or unsubscribe from everything.
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

= 1.3.0 =
* New: welcome email. Every new contact gets "what we offer" straight away: added by hand, contact, audit and consultation forms, newsletter sign-ups and the health checker. Sent once per person, switched on, and editable (A2E Mail > Automations > Welcome: what we offer). Existing contacts are not emailed unless you press "Send it to my existing matching contacts".
* New: weekly newsletter (A2E Mail > Newsletter). Choose the day, time and audience; each issue is built from new blog posts, a rotating tip of the week (13 included, editable), a free tool of the week from A2E Prime and your offer section. Preview, test, send now, skip a week, a one-off note for the next issue, and a list of past issues with delivery progress. Off until you switch it on.
* New: weekly list. Newsletter form sign-ups join it automatically, the welcome email offers a join button (with a confirm step so link scanners never sign anyone up), and the add-contact form has "They asked for the weekly newsletter too". The unsubscribe page offers "Only stop the weekly newsletter".
* New: automations can start from several triggers, including "You add a contact by hand", can send only once per person, send a "wait 0" first step the moment the person is added, and have a "Send me a test of every step" button.
* New: email format supports [text](https://link) links, ![alt](https://image) images, ### small headings and --- dividers, plus merge tags {{audit_url}}, {{consultation_url}}, {{pricing_url}}, {{tools_url}}, {{blog_url}} and {{weekly_invite}}.
* New: "What we offer (A2E Dropshipping)" template for one-off sends from Compose.
* Fix: the "Yes, unsubscribe me" button on the unsubscribe page now works when clicked (the page's form was being stripped). One-click unsubscribe from the inbox always worked.
* Fix: subjects like "{{first_name}}, ..." no longer read "there, ..." when the contact has no name.
* Fix: admin screens fit on a phone.
* Improvement: an SMTP server that does not answer now fails after 20 seconds instead of 5 minutes.

= 1.2.0 =
* Fix: contact, quote and consultation form notifications (and Health Checker reports and WordPress emails) now use your From address and, when it is on, your SMTP server. Before, only A2E Mail's own emails did, so notifications went out from "wordpress@" your domain through the server's basic mail function and were often rejected or sent to spam without anyone being told. New setting "Use these settings for every email this site sends", on by default.
* New: a log of recent website emails on the Settings screen, showing who each one went to and whether it was sent, with the exact error when one fails.
* New: a warning on the Dashboard and A2E Mail screens when a website email fails.
* New: "Send a test notification" sends a message exactly the way your contact form does, to the same address.
* Campaign headers (List-Unsubscribe, bulk precedence) stay on A2E Mail's own emails only.

= 1.1.0 =
* Automatic follow-up sequences (drip automations) with triggers, timed steps, stop-on-goal, and a one-off catch-up enrolment.
* New contact source and segment for people who run the eBay account health checker.
* Leads from the website now enrol into matching sequences automatically.

= 1.0.0 =
* First release.
