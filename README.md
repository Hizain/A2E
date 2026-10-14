# A2E Dropshipping — website rebuild

A full rebuild of [a2edropshipping.com](https://a2edropshipping.com/): a new WordPress theme (**A2E Prime**) and a subscription plugin (**A2E Whop Sync**) that lets you create plans in WordPress and have them created on your Whop account automatically.

| Upload this | What it is |
|---|---|
| [`dist/a2e-prime.zip`](dist/a2e-prime.zip) | The theme: Appearance → Themes → Add New → Upload |
| [`dist/a2e-whop-sync.zip`](dist/a2e-whop-sync.zip) | The plugin: Plugins → Add New → Upload |

Source code lives in `theme/a2e-prime/` and `plugin/a2e-whop-sync/`.

---

## Install (about 10 minutes)

1. **Back up the site** (your host's backup or a plugin such as UpdraftPlus).
2. **Upload and activate `a2e-prime.zip`.** On activation the theme:
   - copies your WhatsApp number, email, Fiverr link, rating, logos, Google codes, plan prices and fee rates from the previous A2E theme;
   - creates any page that is missing (13 tool pages, About, Client area) without touching existing pages, posts, reviews or portfolio items;
   - keeps every existing URL working (see *URLs* below).
3. Check **Settings → Permalinks** is set to **Post name**.
4. **Upload and activate `a2e-whop-sync.zip`.** If the old “A2E Whop Subscriptions” plugin is installed, deactivate it first.
5. Go to **Whop → Connect**, paste a Whop API key (Whop dashboard → Developer → API keys; the Admin role is simplest) and press **Connect**. Your account is detected and the webhook is created for you.
6. **Whop → Plans → Add New Plan** for each plan (name, price, billing period, setup fee, trial, features). Publishing creates it on Whop, and it appears on the homepage, `/pricing/` and `/subscription-plans/` with a secure Whop checkout button.
7. Open **A2E → Setup guide** and tick off the rest (Search Console, Analytics, founder photo).

## Everyday editing

- **A2E → Site Settings** — contact details, review numbers, homepage headline and buttons, stats, FAQ, fallback plans, eBay fee rates, Google codes, supplier logos.
- **Any page or service guide → “A2E — page header & SEO” box** — headline, intro, SEO title and meta description, noindex. Wrap words in `*asterisks*` to get the gradient serif highlight.
- **Posts** — write blog posts as usual. The theme adds a table of contents, reading progress, related posts, tool links and a call to action automatically.
- **A2E → Leads** — every free-audit, consultation, contact and newsletter submission (also emailed to you).
- **A2E → Redirects** — add 301 redirects, bulk-import them, and see 404s visitors hit (one click to fix).
- **Whop → Subscribers** — active clients, MRR estimate, renewals, “cancel at period end”, import from Whop.

## What is new

- **Homepage**: animated example store dashboard (sales/profit chart with hover, supplier sourcing donut, live-style order feed from Amazon/Walmart/AliExpress/Home Depot/Target → eBay), supplier logo marquee, count-up stats, “doing it alone vs with A2E” toggle, bento services grid, 4-step process, interactive **profit simulator** that recommends a plan, reviews wall, founder note, pricing, quick fee checker, latest posts, FAQ.
- **Light and dark mode** — follows the visitor's system setting, with a toggle in the header and footer (no flash on load).
- **13 free tools** for search traffic, each on its own SEO page with an explainer, FAQ and structured data:
  eBay Fee Calculator · Amazon to eBay Profit Calculator · eBay Listing Price Calculator · eBay Title Builder & Checker · eBay Sell-Through Rate Calculator · eBay Defect Rate Calculator · eBay Promoted Listings Calculator · eBay Store Name Generator · eBay Description Template Generator · **new:** Bulk eBay Profit Calculator · eBay Product Research Scorecard · eBay Dropshipping Income Calculator · eBay Store Management Cost Calculator.
- **Designed pages**: services hub, 10 service guides, pricing (with comparison table), monthly plans, free-tools hub with search and filters, multi-step free-audit form, consultation, contact, portfolio, case studies, reviews, interactive checklist (progress saved in the browser), About, Client area, blog, 404.
- **SEO**: titles, meta descriptions, canonical, Open Graph/Twitter cards, JSON-LD (Organization, WebSite, BreadcrumbList, BlogPosting, Service, Offer, WebApplication for tools, FAQPage), clean XML sitemap at `/wp-sitemap.xml`. If Yoast, Rank Math, AIOSEO, SEOPress or The SEO Framework is active, the theme steps back and only adds the tool, service and FAQ data those plugins do not.
- **Speed**: no jQuery or page builder on the front end, self-hosted fonts (≈75 KB), deferred scripts, no block-library CSS on designed pages.

## URLs

Every URL from the current site keeps working. The URL list was taken from the live theme's code (the build environment could not reach the live domain directly), and checked against a local copy of the site:

| URL | Result |
|---|---|
| `/`, `/pricing/`, `/subscription-plans/`, `/amazon-to-ebay-dropshipping-service/`, `/free-tools/` + all 9 tool pages, `/contact/`, `/consultation/`, `/quote-request/`, `/portfolio/`, `/amazon-to-ebay-dropshipping-checklist/`, `/privacy-policy/`, `/terms-of-service/`, `/blog/`, every blog post, `/service-guides/*` (10), `/case-studies/*`, `/portfolio/*`, `/testimonials/` | 200 — same URL, new design |
| `/case-studies-overview/` | 301 → `/case-studies/` (removes a duplicate page) |
| `/testimonials/{name}/` | 301 → `/testimonials/` (as before) |
| `/services/`, `/service-guides/`, `/tools/`, `/blogs/`, `/about-us/`, `/reviews/`, `/plans/`, `/quote/`, `/free-audit/`, `/checklist/`, `/shop/`, `/cart/`, `/checkout/`, `/my-account/`, `/service-guides/pricing-service/`, `/service-guides/consultation/`, `/{tool-slug}/` | 301 → the matching page |

Anything not on that list that still gets traffic appears in **A2E → Redirects → Recent 404s**. Check it a week after launch.

## Things to confirm before launch

- **Numbers**: the homepage stats (528 reviews, 25K+ products, 500+ stores, 8+ years) and the founder note come from the current site. Edit them under **A2E → Site Settings** if anything has changed.
- **Example dashboard**: the hero dashboard is labelled *Example store* and uses illustrative figures; no real-time claims are made.
- **eBay's dropshipping policy** does not allow buying from another retailer to ship straight to your buyer. The copy describes a multi-supplier, policy-aware workflow and the FAQ says so plainly. Keep that wording.
- **Supplier logos** are typeset wordmarks in brand colours (Amazon and Walmart do not allow their logos in open icon sets). If you hold the rights to official logo files, upload them under **Site Settings → Supplier logos**. A trademark/non-affiliation notice is in the footer.
- **eBay fees** default to the US rates for most categories (13.6% + $0.30/$0.40 per order, checked October 2026). Update them under **Site Settings → eBay fees** if eBay changes them.

## How the Whop sync works

- **Connect once** with an account API key (stored encrypted). The plugin calls Whop's `accounts/me` to find your account and creates a webhook for membership and payment events.
- **Plans**: each WordPress plan becomes a Whop product with one plan (Whop is renaming plans to *variants*; the plugin uses `/variants` and falls back to `/plans`). Text edits update Whop in place. A price or billing change creates a new Whop plan and hides the old one, so existing subscribers keep their price. Unpublishing or trashing a plan hides it on Whop.
- **Webhooks** are verified with Whop's Standard Webhooks signature (HMAC-SHA256, 5-minute replay window) and de-duplicated. New subscriptions, renewals, failed payments, refunds and cancellations update **Whop → Subscribers** and can email you.
- **Clients** manage billing on Whop (`whop.com/@me/settings/orders/`). The theme's `/account/` page links there; you can change the link in Whop → Connect → Settings.
- Shortcodes for other themes: `[a2e_whop_plans]`, `[a2e_whop_button plan="123" label="Subscribe"]`.

## Testing done

On a local WordPress 6.5 copy seeded with the current theme's pages and URLs:
- every live URL returns 200 or a 301 to the right page, with no PHP warnings;
- all 13 tools compute in a real browser with no JavaScript errors; lead forms submit inline and save to Leads;
- light/dark mode and phone layout were checked on screenshots;
- the plugin was run against a mock Whop API: connect (bad and good key), webhook creation, creating one-time and recurring plans with setup fee and trial, rename, price change (new plan, old one hidden), signed webhooks (valid, duplicate, tampered, stale, wrong secret, legacy payload), paginated import, cancel at period end, and the pricing page switching to Whop checkout links.

Not yet tested against the live Whop API; do one sandbox purchase (Whop → Connect → “Use Whop sandbox”) before going live.
