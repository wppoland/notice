=== Notice - Announcement Bar for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, announcement bar, notification bar, promo bar, sale banner
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A dismissible store-wide announcement bar for WooCommerce: message, link, colours and an optional schedule.

== Description ==

Notice adds a clean, fast announcement bar to the top of your WooCommerce store.
Use it to promote a sale, a free-shipping threshold, a shipping cut-off or any
store-wide message — with an optional call-to-action button, your own colours and
an optional start/end schedule.

The bar is rendered server-side at the top of the page and ships a tiny,
dependency-free stylesheet. Nothing loads on the front end unless the bar is
actually active, so disabled or out-of-schedule states add zero weight.

= Features =

* A single store-wide announcement bar at the top of every page.
* Message with a small safe-HTML allow-list (**bold**, *italic*, links, line breaks).
* Optional call-to-action button with its own URL and new-tab option.
* Custom background, text and accent colours with a live preview.
* Sticky (pinned) or static (scrolls away) placement.
* Optional schedule: show the bar only between a start and end date/time.
* Dismissible with the choice remembered in the browser (localStorage — no cookies, no personal data).
* Changing the message text re-shows the bar to everyone automatically.
* Accessible: ARIA region, keyboard-operable close button, focus-visible styles, respects reduced motion.
* No layout-shift beyond the bar's own height; assets load only when the bar is active.
* Translation ready (POT included) and a clean uninstall.
* HPOS and cart/checkout blocks compatible.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/notice`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to **WooCommerce → Announcement Bar**, write your message, set colours and (optionally) a schedule, then enable the bar.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes.

= Where does the bar appear? =

At the very top of every front-end page, via the theme's `wp_body_open` hook. Most
modern themes support it.

= Can shoppers close the bar? =

Yes, when "Dismissible" is on. The choice is stored in the visitor's browser using
localStorage — no cookies and no personal data. You can set how many days the
dismissal lasts (0 = forever). Editing the message text re-shows the bar to everyone.

= Does it slow down my store? =

No. The CSS and dismissal script are only enqueued when the bar is actually active,
and the markup is plain HTML. There is no front-end JavaScript framework.

= Can I schedule a promotion? =

Yes. Enable the schedule and set a start and/or end time (in your site's timezone).
Leave either blank for an open-ended window.

== Screenshots ==

1. The announcement bar on a storefront.
2. The settings screen with a live preview.

== Changelog ==

= 0.1.0 =
* Initial release: store-wide announcement bar with message, CTA link, colours, placement, optional schedule and dismissal.
