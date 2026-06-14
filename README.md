# Notice — Announcement Bar for WooCommerce

A dismissible, store-wide announcement bar for WooCommerce: a message (with a
small safe-HTML allow-list), an optional call-to-action link, custom colours,
sticky or static placement, an optional start/end schedule, and a dismiss option
remembered in the visitor's browser (localStorage — no cookies, no PII).

Notice is **fully self-contained** — no runtime Composer dependencies.

## Architecture

- `notice.php` — bootstrap. Declares HPOS/blocks compatibility, requires WooCommerce,
  and boots on `init:0`, firing `do_action('notice/booted', Plugin::instance())`
  from inside `Plugin::boot()`.
- `src/Plugin.php` — singleton + DI container wiring (`config/services.php`),
  hook registration (`config/hooks.php`), migrations.
- `src/Container.php` — minimal lazy-singleton container with `has()`.
- `src/Service/SettingsRepository.php` — single source of truth for the
  `notice_settings` option, schedule evaluation, and the message HTML allow-list.
- `src/Service/BarRenderer.php` — front-end rendering (at `wp_body_open`) and
  conditional asset enqueue (only when the bar is active).
- `src/Admin/Settings.php` — the **WooCommerce → Announcement Bar** settings page,
  with a live preview, inline help and full input sanitisation.
- `templates/bar.php` — the storefront bar markup.
- `assets/` — front-end CSS + dismissal JS, and admin CSS + preview JS.

## Development

```bash
composer install
composer cs        # PHPCS (WordPress security ruleset)
composer analyse   # PHPStan level 6
```

CI runs PHPCS, PHPStan and the WordPress.org Plugin Check on every push.

## PRO

The premium companion, **Notice Pro**, lives in a separate repository and boots
via `add_action('notice/booted', ...)`.
