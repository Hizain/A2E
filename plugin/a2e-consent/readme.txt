=== A2E Consent & Insights ===
Contributors: a2edropshipping
Tags: cookie consent, gdpr, consent mode, analytics, visitor tracking
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A cookie consent banner for A2E Dropshipping that matches the A2E Prime design, plus your own visitor dashboard for the people who allow analytics.

== Description ==

**Cookie consent**

* A banner in the A2E Prime design (light and dark, desktop and phone) with "Accept all", "Reject all" (same size and weight, as UK and EU rules expect) and "Choose".
* A Cookie settings window with Necessary (always on), Analytics and Marketing switches, a plain-English description of each and the list of cookies each one sets. The list is built from the codes you set up.
* A "Cookie settings" link is added to the footer next to Privacy so visitors can change their mind at any time.
* Nothing optional runs until the visitor says yes: Google Analytics 4, Google Tag Manager, the Meta Pixel, Microsoft Clarity, any code you paste, and any script in your pages marked type="text/plain" data-a2ec="analytics" or "marketing".
* Your theme's Google Analytics tag (A2E → Site Settings) used to load for everyone. It now waits for consent. The ID is reused automatically.
* Google Consent Mode v2 built in: basic mode by default (Google tags load only after consent), advanced mode if you choose it.
* When a visitor withdraws consent, the cookies of what they switched off are removed and the page reloads without those codes.
* Respects Global Privacy Control: browsers that ask sites not to share data keep marketing off.
* Consent log: every choice saved with a random consent ID (visitors can see theirs), the choice, banner version, page and a short browser description. No IP addresses. Download as CSV.
* "Ask every visitor again" when you add a new tracking code or change how you use the data.
* Works with full-page caching: the decision is made in the visitor's browser.

**Visitor insights (only visitors who allow analytics)**

* Dashboard: visitors, new visitors, visits, page views, time on page, leads, lead rate, WhatsApp / call / email / Whop clicks and who is on the site now, each compared with the period before. Visitors per day with leads marked.
* How visitors find you: search, social, AI assistants (ChatGPT, Perplexity, Gemini, Copilot, Claude), paid ads (utm tags, Google and Microsoft click IDs), email, other websites. Top sources with their leads and lead rate, campaigns, landing pages, most read pages, devices and countries (when your host or Cloudflare provides the country).
* Visitors list and journeys: every visit, page, click and form, in order. Anonymous until they send you a form; then you see their name and email.
* Leads: each lead from your forms (contact, free store audit, consultation, newsletter, Account Health Checker) gets a "How they found you" box and a "Found you via" column: first source, landing page, the visit that sent the form, visits, pages, time on site and the pages they read.
* Meta Pixel events: PageView, Lead (form sent), Contact (WhatsApp, call or email click), InitiateCheckout (Whop click). Google Analytics events: generate_lead (from the theme), contact_click and begin_checkout.
* Stored in your own WordPress database. IP addresses are never stored. Bots and signed-in staff are not counted. Visit history is deleted automatically after 13 months (you choose), consent records after 24 months.
* WordPress privacy tools: Tools → Export Personal Data and Erase Personal Data include the visit history linked to an email address. Suggested privacy policy text is added to Settings → Privacy → Policy guide.

== Installation ==

1. Plugins → Add New → Upload Plugin, choose a2e-consent.zip, Install, Activate.
2. The banner shows straight away and your Google Analytics ID from the theme is used after consent.
3. Optional: Insights → Settings to add the Meta Pixel, Clarity or Tag Manager, change the wording or the style.
4. Add [a2e_cookie_list] to your privacy policy page.
5. If you use a page cache (LiteSpeed, WP Rocket, Cloudflare APO...), clear it once.

== Frequently Asked Questions ==

= Why are the Insights numbers lower than Google Analytics used to show? =

Only visitors who allow analytics are counted, which is what the law expects. The dashboard shows what share of visitors said yes.

= Do I still need Google Analytics? =

No, Insights covers the everyday questions (which pages, which sources, which leads). Keep GA4 if you run Google Ads or want its reports.

= I see my own visits missing. =

Signed-in staff are not counted and tracking codes do not run for them. Test in a private window.

= Shortcodes =

[a2e_cookie_list] shows a table of every cookie the site uses. [a2e_cookie_settings text="Change cookie settings"] shows a link that opens the Cookie settings window. Any link to #cookie-settings opens it too.

== Changelog ==

= 1.0.0 =
* First release: consent banner, Cookie settings window, consent log, Google Consent Mode v2, consent-gated GA4 / GTM / Meta Pixel / Clarity / custom code, visitor insights dashboard, visitors and journeys, lead source tracking, privacy export and erase.
