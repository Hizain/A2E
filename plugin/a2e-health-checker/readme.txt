=== A2E eBay Account Health Checker ===
Contributors: a2edropshipping
Tags: ebay, seller, account health, dropshipping, lead generation
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A free, interactive eBay account health checker you can place on any page. It scores an account, shows how to recover Top Rated, gives suggestions, and offers a WhatsApp hire button.

== Description ==

Drop a polished, free lead-magnet onto your site in one click. Visitors enter a few numbers from their eBay Seller Dashboard and instantly see:

* A health score out of 100 and where they stand against eBay US seller standards (Top Rated, Above Standard, or Below Standard risk).
* A per-metric breakdown with coloured bars: defect rate, late shipment rate, cases without resolution, and valid tracking on time.
* **Recovery maths** - how many clean transactions (with no new defects) are needed to climb back to Above Standard and Top Rated, which metric is holding them back, and roughly when they will get there based on their order velocity and their next evaluation date.
* **Tailored suggestions** to improve store health, matched to the weak metrics.
* A **"Hire us on WhatsApp"** button so they can ask your team to maintain their account health, plus an optional link to your plans.
* An optional "email me my report" capture that stores the lead and, when the A2E Mail Studio plugin is active, adds the contact and can start an automatic follow-up sequence.

Everything the visitor types stays in their browser. Nothing is uploaded unless they choose to email themselves the report.

**It does not change your theme.** The plugin creates a ready-made page you can assign anywhere, and also provides a shortcode and a block. Styles are fully self-contained and adapt to any theme, matching the A2E Prime theme automatically when it is active. Wherever the tool appears it is wrapped in a highlighted, standout container (you can turn this off).

== Installation ==

1. Upload the `a2e-health-checker` folder to `/wp-content/plugins/`, or install the zip from Plugins > Add New > Upload Plugin.
2. Activate it. A page titled "eBay Account Health Checker" is created for you automatically.
3. Go to **Health Checker** in the admin menu to set your WhatsApp number, business name, where report requests are emailed, and the hire call to action.
4. Add the ready page to your menu, or place `[a2e_health_checker]` on any page or post, or insert the "eBay Account Health Checker" block.

== Usage ==

**Shortcode**

`[a2e_health_checker]`

Turn the highlight off for one placement:

`[a2e_health_checker highlight="0"]`

**Block**

Add the "eBay Account Health Checker" block and toggle the highlight in the sidebar.

== Frequently Asked Questions ==

= Does this change or require a specific theme? =

No. It works with any theme and does not touch your theme files. When the A2E Prime theme is active it automatically matches its colours and style.

= Where does the lead go? =

If the A2E Mail Studio plugin is active, the lead is added there (and can enter a follow-up sequence) through the shared `a2e_lead_received` hook. Otherwise it is stored in a private "Health leads" list under the Health Checker menu. You also get an email for each request.

= Is anything the visitor types sent to a server? =

No. Scoring, recovery estimates and suggestions all run in the browser. Numbers are only transmitted if the visitor chooses to email themselves the report.

== Changelog ==

= 2.1.0 =
* New: with the A2E Prime theme, the checker is one of the theme's free tools. It appears in the header Free Tools mega menu, the phone menu, the footer, the Free Tools page, the homepage tools section, the 404 and search pages, related tools, account-health service guides and the blog sidebar, listed first as the newest tool.
* New: its page moves under Free Tools (/free-tools/ebay-account-health-checker/) and uses the theme's tool page, like the other tools. The old address redirects there.
* New: every "13 free tools" heading, the announcement bar, the Free Tools badge and the Free Tools page description now show the live tool count (14).
* New: settings to turn the theme integration on or off and to list the tool first.
* Fix: the "Data from" and "Data to" date fields now line up.

= 2.0.0 =
* Accuracy rebuild. The tool now reports your exact eBay US seller level (Below Standard, Above Standard or Top Rated) using eBay's real rules, so it matches your Seller Dashboard.
* Fixed: an account with valid tracking under 95% is no longer shown as Top Rated. The 95% tracking requirement, the case and late-shipment count rules (max 2 / max 5, or 0.3% / 3%), the 4-different-buyers rule for defects, and Top Rated eligibility (90+ days, 100+ sales, $1,000+) are all enforced.
* New: custom data-window start and end dates, and a custom next-evaluation date.
* New: a clear seller-level badge and tier meter, a per-metric breakdown showing each metric against its Top Rated target with a pass / blocks / below chip, the exact clean sales needed to reach Top Rated (and to clear Below Standard first), and a note on Top Rated Plus.
* Redesign: animated meter, bars and counters, tidy aligned headings and values, and perfect light / dark that follows the A2E Prime theme.

= 1.0.1 =
* Fix: the tool now follows your theme's own light / dark toggle instead of the visitor's operating-system setting, so it no longer stays dark when the site is switched to light. It reads your theme's design tokens (colours, radius, shadow, font), so it matches the site in both modes.
* Fix: rebalanced the layout. The form and score sit side by side, with the recovery estimate and suggestions as full-width panels below, so nothing looks stretched.

= 1.0.0 =
* First release: scoring against eBay US seller standards, recovery maths to Above Standard and Top Rated with a timeline, tailored suggestions, WhatsApp hire button, assignable page, shortcode and block, highlighted container, and A2E Mail Studio integration.
