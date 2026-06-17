=== Notice - Announcement Bar for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, announcement bar, notification bar, promo bar, sale banner
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.2
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A dismissible store-wide announcement bar for WooCommerce: message, link and colours.

== Description ==

Notice adds one announcement bar to the top of your WooCommerce store. Use it to
promote a sale, a free-shipping threshold, a shipping cut-off or any store-wide
message, with an optional call-to-action button and your own colours.

The bar is rendered server-side at `wp_body_open` and ships a small,
dependency-free stylesheet. The CSS loads only when the bar is enabled and has a
message, and the dismissal script only when the bar is also dismissible, so a
disabled bar adds nothing to your pages.

The plugin is developed in the open. Source and bug reports:
https://github.com/wppoland/notice

= Features =

* A single store-wide announcement bar pinned to the top of every page.
* Message with a small safe-HTML allow-list (**bold**, *italic*, links, line breaks).
* Optional call-to-action button with its own URL and new-tab option.
* Custom background, text and accent colours with a live preview.
* Dismissible with the choice remembered in the browser (localStorage — no cookies, no personal data).
* Changing the message text re-shows the bar to everyone automatically.
* Accessible: ARIA region, keyboard-operable close button, focus-visible styles, respects reduced motion.
* No layout-shift beyond the bar's own height; assets load only when the bar is active.
* Translation ready (POT included) and a clean uninstall.
* HPOS and cart/checkout blocks compatible.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/notice`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to **WooCommerce → Announcement Bar**, write your message, set colours, then enable the bar.

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

= Does the bar work on mobile? =

Yes. The bar spans the viewport width and the dismiss control stays reachable on small screens.

== Screenshots ==

1. The announcement bar on a storefront.
2. The settings screen with a live preview.

== Changelog ==

= 0.1.2 =
* Multi-bar support via `notice/bars` filter; `notice/bar_active` now receives a bar id. Front-end dismissal handles stacked bars.

= 0.1.1 =
* Add `notice/bar_active` filter so PRO and custom code can narrow bar visibility.

= 0.1.0 =
* Initial release: store-wide announcement bar with message, CTA link, colours and dismissal.
