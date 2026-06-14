<?php

declare(strict_types=1);

namespace Notice\Service;

defined('ABSPATH') || exit;

use Notice\Contract\HasHooks;

/**
 * Renders the storefront announcement bar.
 *
 * Prints the bar at the very top of <body> (wp_body_open) and enqueues a tiny,
 * dependency-free stylesheet and dismissal script — only when the bar is
 * actually active, so disabled or out-of-schedule states add zero front-end
 * weight. All output is escaped; the message is run through wp_kses with the
 * repository's allow-list.
 */
final class BarRenderer implements HasHooks
{
    private SettingsRepository $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function registerHooks(): void
    {
        // Print as early in the body as themes allow.
        add_action('wp_body_open', [$this, 'render'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue the front-end assets only when the bar will render.
     */
    public function enqueueAssets(): void
    {
        if (! $this->settings->isActive()) {
            return;
        }

        wp_enqueue_style(
            'notice-bar',
            NOTICE_URL . 'assets/css/notice-bar.css',
            [],
            \Notice\VERSION,
        );

        $dismissible = ! empty($this->settings->all()['dismissible']);

        if ($dismissible) {
            wp_enqueue_script(
                'notice-bar',
                NOTICE_URL . 'assets/js/notice-bar.js',
                [],
                \Notice\VERSION,
                ['in_footer' => true, 'strategy' => 'defer'],
            );
        }
    }

    /**
     * Render the bar. Guards every state so it hides rather than render broken.
     */
    public function render(): void
    {
        if (! $this->settings->isActive()) {
            return;
        }

        $settings = $this->settings->all();
        $template = NOTICE_DIR . 'templates/bar.php';

        if (! is_readable($template)) {
            return;
        }

        // Data passed to the template, pre-computed and escaped at the edge.
        $view = $this->buildView($settings);

        (static function (array $view) use ($template): void {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope local.
            extract($view, EXTR_SKIP);
            require $template;
        })($view);
    }

    /**
     * Build the template view model from raw settings.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function buildView(array $settings): array
    {
        $position = in_array($settings['position'] ?? 'top', SettingsRepository::POSITIONS, true)
            ? (string) $settings['position']
            : 'top';

        $linkUrl   = (string) ($settings['link_url'] ?? '');
        $linkLabel = (string) ($settings['link_label'] ?? '');
        $hasLink   = '' !== $linkUrl && '' !== $linkLabel;

        $dismissDays = max(0, (int) ($settings['dismiss_days'] ?? 0));

        return [
            'message'        => (string) ($settings['message'] ?? ''),
            'allowed_html'   => $this->settings->allowedMessageHtml(),
            'position'       => $position,
            'bg_color'       => $this->color($settings['bg_color'] ?? '', '#1e1e1e'),
            'text_color'     => $this->color($settings['text_color'] ?? '', '#ffffff'),
            'link_color'     => $this->color($settings['link_color'] ?? '', '#ffd166'),
            'has_link'       => $hasLink,
            'link_url'       => $linkUrl,
            'link_label'     => $linkLabel,
            'link_new_tab'   => ! empty($settings['link_new_tab']),
            'dismissible'    => ! empty($settings['dismissible']),
            'dismiss_days'   => $dismissDays,
            // A version-stamped storage key invalidates a remembered dismissal
            // whenever the message changes, so a new announcement always shows.
            'storage_key'    => 'notice_dismissed_' . substr(md5((string) ($settings['message'] ?? '')), 0, 8),
        ];
    }

    /**
     * Validate a stored colour, falling back to a safe default.
     */
    private function color(mixed $value, string $fallback): string
    {
        $hex = sanitize_hex_color((string) $value);

        return is_string($hex) && '' !== $hex ? $hex : $fallback;
    }
}
