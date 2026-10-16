=== A2E Header Menu Link ===
Contributors: a2edropshipping
Tags: menu, header, navigation, link, cta
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add one highlighted link to your site header without editing your theme, even when the header menu is not editable from Appearance > Menus.

== Description ==

Some themes build their header menu in code, so Appearance > Menus has no effect on it. This plugin adds a single, highlighted link to the header (and the mobile menu) at runtime, so you can feature a page such as the eBay Account Health Checker without touching your theme.

* Choose the label and the destination (any page on your site, or a custom URL).
* Place it on the main header as a highlighted button, inside the "Free Tools" dropdown as a highlighted item, or both.
* Toggle the highlight, the position (before or after the other items) and open-in-new-tab.
* Colours follow your theme automatically (it reads the theme's brand tokens), so the pill matches your site in light and dark.
* Defaults are set for the A2E Prime theme and point at your Account Health Checker page; CSS selectors are adjustable for other themes.

Nothing is sent anywhere. The link is added in the browser from settings you control.

== Installation ==

1. Upload the `a2e-menu` folder to `/wp-content/plugins/`, or install the zip from Plugins > Add New > Upload Plugin.
2. Activate it.
3. Go to **Settings > Header Link**. By default it already points at your eBay Account Health Checker page; set the label and placement, then save.
4. Open your site: the highlighted link is now in the header and mobile menu.

== Frequently Asked Questions ==

= The link does not appear. =

Your theme uses different markup. In **Settings > Header Link > Advanced**, set the "Desktop menu selector" to the CSS selector of your header's menu list (for most themes something like `.menu` or `#primary-menu`), then save. The default `.nav__list` matches the A2E Prime theme.

= Can I link to something other than the health checker? =

Yes. Pick any page from the dropdown, or choose "a custom URL" and paste any address.

= Does it change my theme? =

No. It adds the link with a small script and stylesheet and never edits theme files.

== Changelog ==

= 1.0.0 =
* First release: one configurable, highlighted header link (main header, Free Tools dropdown, or both), theme-aware styling, and adjustable selectors for any theme.
