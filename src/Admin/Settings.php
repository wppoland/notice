<?php

declare(strict_types=1);

namespace Notice\Admin;

defined('ABSPATH') || exit;

use Notice\Contract\HasHooks;
use Notice\Service\SettingsRepository;

/**
 * Admin settings page registered under the WooCommerce menu.
 *
 * Stores everything in the `notice_settings` option (array): the master switch,
 * the announcement message (limited safe HTML), an optional CTA link, colours
 * and the dismiss behaviour. All output is escaped; all input is sanitised and
 * clamped on save (sanitize_hex_color for colours, wp_kses for the message,
 * sanitize_text_field elsewhere). The save capability is aligned to
 * manage_woocommerce.
 */
final class Settings implements HasHooks
{
    private const PAGE   = 'notice';
    private const PARENT = 'woocommerce';

    /** Incremented to give each inline-help control a unique id/anchor. */
    private int $helpSeq = 0;

    private SettingsRepository $repository;

    public function __construct(SettingsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter(
            'plugin_action_links_' . plugin_basename(\Notice\PLUGIN_FILE),
            [$this, 'actionLinks'],
        );
    }

    /**
     * Add a Settings shortcut on the plugins screen.
     *
     * @param array<int, string> $links
     * @return array<int, string>
     */
    public function actionLinks(array $links): array
    {
        $url = admin_url('admin.php?page=' . self::PAGE);

        array_unshift(
            $links,
            sprintf('<a href="%s">%s</a>', esc_url($url), esc_html__('Settings', 'notice')),
        );

        return $links;
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            self::PARENT,
            __('Announcement Bar', 'notice'),
            __('Announcement Bar', 'notice'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            SettingsRepository::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        // Align the options.php save capability with the menu capability so shop
        // managers (not just admins with manage_options) can save.
        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    /**
     * Load the settings-screen stylesheet and live-preview script, only on this
     * page. Both ship as real files (no inline blobs); the script is deferred.
     */
    public function enqueueAssets(string $hook): void
    {
        if ('woocommerce_page_' . self::PAGE !== $hook) {
            return;
        }

        wp_enqueue_style(
            'notice-admin',
            NOTICE_URL . 'assets/css/admin.css',
            [],
            \Notice\VERSION,
        );

        wp_enqueue_script(
            'notice-admin',
            NOTICE_URL . 'assets/js/admin.js',
            [],
            \Notice\VERSION,
            ['in_footer' => true, 'strategy' => 'defer'],
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->repository->all();
        ?>
        <div class="wrap notice-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="notice-admin__intro">
                <div>
                    <h2><?php esc_html_e('One bar. Store-wide attention.', 'notice'); ?></h2>
                    <p>
                        <?php esc_html_e('Announce a sale, a shipping cut-off or any message across your whole store with a single bar at the top of every page. Add a call-to-action, pick your colours and let shoppers dismiss it. The live preview on the right updates as you type.', 'notice'); ?>
                    </p>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="notice-admin__layout">
                    <div class="notice-admin__main">
                        <?php
                        $this->renderMessageCard($settings);
                        $this->renderLinkCard($settings);
                        $this->renderAppearanceCard($settings);
                        $this->renderBehaviourCard($settings);
                        ?>
                        <?php submit_button(__('Save changes', 'notice')); ?>
                    </div>

                    <?php $this->renderPreviewPanel($settings); ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderMessageCard(array $settings): void
    {
        ?>
        <div class="notice-admin__card">
            <h2><?php esc_html_e('Message', 'notice'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Enable the bar', 'notice'); ?>
                            <?php $this->help(__('The master switch. When off, nothing renders on your storefront and no CSS or JavaScript is loaded — zero front-end impact.', 'notice')); ?>
                        </th>
                        <td>
                            <label for="notice_enabled">
                                <input
                                    type="checkbox"
                                    id="notice_enabled"
                                    name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[enabled]"
                                    value="1"
                                    <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                />
                                <?php esc_html_e('Show the announcement bar on the storefront.', 'notice'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notice_message"><?php esc_html_e('Announcement text', 'notice'); ?></label>
                            <?php $this->help(__('The message shoppers see. A little safe HTML is allowed: <strong>, <em>, <a>, <span> and <br>. Everything else is stripped on save.', 'notice')); ?>
                        </th>
                        <td>
                            <textarea
                                id="notice_message"
                                name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[message]"
                                class="large-text"
                                rows="3"
                                placeholder="<?php esc_attr_e('e.g. Free shipping on orders over $50 — this weekend only!', 'notice'); ?>"
                            ><?php echo esc_textarea((string) ($settings['message'] ?? '')); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Allowed HTML: <strong>, <em>, <a href>, <span>, <br>.', 'notice'); ?>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderLinkCard(array $settings): void
    {
        ?>
        <div class="notice-admin__card">
            <h2><?php esc_html_e('Call to action', 'notice'); ?></h2>
            <p class="description">
                <?php esc_html_e('Optional. Add a button after your message. Leave the label blank to show text only.', 'notice'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="notice_link_label"><?php esc_html_e('Button label', 'notice'); ?></label>
                            <?php $this->help(__('The text on the button, e.g. "Shop the sale". Leave empty to hide the button.', 'notice')); ?>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="notice_link_label"
                                name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[link_label]"
                                value="<?php echo esc_attr((string) ($settings['link_label'] ?? '')); ?>"
                                class="regular-text"
                                placeholder="<?php esc_attr_e('e.g. Shop now', 'notice'); ?>"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notice_link_url"><?php esc_html_e('Button URL', 'notice'); ?></label>
                            <?php $this->help(__('Where the button sends shoppers. Use a full URL including https://.', 'notice')); ?>
                        </th>
                        <td>
                            <input
                                type="url"
                                id="notice_link_url"
                                name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[link_url]"
                                value="<?php echo esc_attr((string) ($settings['link_url'] ?? '')); ?>"
                                class="regular-text"
                                placeholder="https://"
                                inputmode="url"
                            />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Open in new tab', 'notice'); ?>
                            <?php $this->help(__('Opens the link in a new browser tab. The plugin adds rel="noopener" automatically for security.', 'notice')); ?>
                        </th>
                        <td>
                            <label for="notice_link_new_tab">
                                <input
                                    type="checkbox"
                                    id="notice_link_new_tab"
                                    name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[link_new_tab]"
                                    value="1"
                                    <?php checked((bool) ($settings['link_new_tab'] ?? false), true); ?>
                                />
                                <?php esc_html_e('Open the call-to-action link in a new tab.', 'notice'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderAppearanceCard(array $settings): void
    {
        ?>
        <div class="notice-admin__card">
            <h2><?php esc_html_e('Appearance', 'notice'); ?></h2>
            <p class="description">
                <?php esc_html_e('The defaults are a ready-to-use palette: a near-black bar, white text and a warm amber accent — readable on any theme with no tuning. Adjust them only if you want the bar to match your brand.', 'notice'); ?>
            </p>
            <table class="form-table" role="presentation">
                <tbody>
                    <?php
                    $this->colorRow('bg_color', __('Background colour', 'notice'), $settings, '#1e1e1e', __('The bar background. Pick a colour with strong contrast against the text colour for readability.', 'notice'));
                    $this->colorRow('text_color', __('Text colour', 'notice'), $settings, '#ffffff', __('The message text colour.', 'notice'));
                    $this->colorRow('link_color', __('Accent colour', 'notice'), $settings, '#ffd166', __('Used for links inside the message and for the call-to-action button.', 'notice'));
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function renderBehaviourCard(array $settings): void
    {
        ?>
        <div class="notice-admin__card">
            <h2><?php esc_html_e('Dismiss behaviour', 'notice'); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Dismissible', 'notice'); ?>
                            <?php $this->help(__('Adds a close button. The shopper’s choice is stored in their browser (localStorage — no cookies, no personal data) so the bar stays hidden on return visits.', 'notice')); ?>
                        </th>
                        <td>
                            <label for="notice_dismissible">
                                <input
                                    type="checkbox"
                                    id="notice_dismissible"
                                    name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[dismissible]"
                                    value="1"
                                    <?php checked((bool) ($settings['dismissible'] ?? false), true); ?>
                                />
                                <?php esc_html_e('Let shoppers close the bar.', 'notice'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="notice_dismiss_days"><?php esc_html_e('Remember for (days)', 'notice'); ?></label>
                            <?php $this->help(__('How long a dismissal sticks before the bar can reappear. Use 0 to remember forever. Changing the message text resets this for everyone.', 'notice')); ?>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="0"
                                step="1"
                                id="notice_dismiss_days"
                                name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[dismiss_days]"
                                value="<?php echo esc_attr((string) (int) ($settings['dismiss_days'] ?? 7)); ?>"
                                class="small-text"
                            />
                            <p class="description"><?php esc_html_e('0 = remember forever.', 'notice'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Sticky live-preview panel. JS keeps it in sync with the form; without JS
     * it shows the saved state so the panel is never blank.
     *
     * @param array<string, mixed> $settings
     */
    private function renderPreviewPanel(array $settings): void
    {
        $message = (string) ($settings['message'] ?? '');
        $hasLink = '' !== (string) ($settings['link_url'] ?? '') && '' !== (string) ($settings['link_label'] ?? '');
        ?>
        <aside class="notice-admin__card notice-admin__preview" aria-label="<?php esc_attr_e('Announcement bar preview', 'notice'); ?>">
            <h2><?php esc_html_e('Live preview', 'notice'); ?></h2>
            <p class="notice-admin__preview-hint">
                <?php esc_html_e('A sample of how your bar will look. Colours, text and the button update as you edit.', 'notice'); ?>
            </p>
            <div
                class="notice-admin__stage"
                data-notice-preview
                style="
                    --notice-bg:<?php echo esc_attr($this->color($settings['bg_color'] ?? '', '#1e1e1e')); ?>;
                    --notice-fg:<?php echo esc_attr($this->color($settings['text_color'] ?? '', '#ffffff')); ?>;
                    --notice-link:<?php echo esc_attr($this->color($settings['link_color'] ?? '', '#ffd166')); ?>;
                "
            >
                <div class="notice-admin__bar">
                    <span class="notice-admin__bar-message" data-notice-preview-message>
                        <?php
                        echo '' !== trim(wp_strip_all_tags($message))
                            ? wp_kses($message, $this->repository->allowedMessageHtml())
                            : esc_html__('Your announcement will appear here.', 'notice');
                        ?>
                    </span>
                    <a
                        class="notice-admin__bar-cta"
                        data-notice-preview-cta
                        href="#"
                        <?php echo $hasLink ? '' : 'hidden'; ?>
                    ><?php echo esc_html((string) ($settings['link_label'] ?? '')); ?></a>
                </div>
            </div>
        </aside>
        <?php
    }

    /**
     * Render a colour-picker row (native colour input + hex text input).
     *
     * @param array<string, mixed> $settings
     */
    private function colorRow(string $key, string $label, array $settings, string $default, string $tip): void
    {
        $value = $this->color($settings[$key] ?? '', $default);
        $id    = 'notice_' . $key;
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label>
                <?php $this->help($tip); ?>
            </th>
            <td>
                <span class="notice-admin__color">
                    <input
                        type="color"
                        id="<?php echo esc_attr($id); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        data-notice-color-for="<?php echo esc_attr($id . '_text'); ?>"
                        aria-hidden="true"
                        tabindex="-1"
                    />
                    <input
                        type="text"
                        id="<?php echo esc_attr($id . '_text'); ?>"
                        name="<?php echo esc_attr(SettingsRepository::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                        value="<?php echo esc_attr($value); ?>"
                        class="regular-text code"
                        pattern="#?[A-Fa-f0-9]{3,6}"
                        placeholder="<?php echo esc_attr($default); ?>"
                        aria-label="<?php
                        /* translators: %s: colour field name, e.g. Background colour. */
                        echo esc_attr(sprintf(__('%s hex value', 'notice'), $label));
                        ?>"
                    />
                </span>
            </td>
        </tr>
        <?php
    }

    /**
     * Render an accessible inline-help affordance: a "?" button that toggles a
     * popover describing the adjacent setting. Uses the native Popover API and is
     * wired via aria-describedby; the bundled script supplies a fallback.
     */
    private function help(string $text): void
    {
        $id = 'notice-help-' . (++$this->helpSeq);
        ?>
        <button
            type="button"
            class="notice-admin__help"
            aria-label="<?php esc_attr_e('More information', 'notice'); ?>"
            aria-describedby="<?php echo esc_attr($id); ?>"
            aria-expanded="false"
            popovertarget="<?php echo esc_attr($id); ?>"
        >?</button>
        <div id="<?php echo esc_attr($id); ?>" class="notice-admin__tip" role="tooltip" popover hidden>
            <?php echo esc_html($text); ?>
        </div>
        <?php
    }

    /**
     * Validate a stored colour for display, falling back to a safe default.
     */
    private function color(mixed $value, string $fallback): string
    {
        $hex = sanitize_hex_color((string) $value);

        return is_string($hex) && '' !== $hex ? $hex : $fallback;
    }

    /**
     * Sanitises, validates and clamps the submitted settings before save.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $sanitized = [
            'enabled' => ! empty($raw['enabled']),

            'message' => isset($raw['message'])
                ? wp_kses((string) $raw['message'], $this->repository->allowedMessageHtml())
                : '',

            'link_url'     => isset($raw['link_url']) ? esc_url_raw(trim((string) $raw['link_url'])) : '',
            'link_label'   => isset($raw['link_label']) ? sanitize_text_field((string) $raw['link_label']) : '',
            'link_new_tab' => ! empty($raw['link_new_tab']),

            'bg_color'   => $this->color($raw['bg_color'] ?? '', '#1e1e1e'),
            'text_color' => $this->color($raw['text_color'] ?? '', '#ffffff'),
            'link_color' => $this->color($raw['link_color'] ?? '', '#ffd166'),

            'dismissible'  => ! empty($raw['dismissible']),
            'dismiss_days' => max(0, isset($raw['dismiss_days']) ? (int) $raw['dismiss_days'] : 7),
        ];

        return (array) apply_filters('notice_sanitize_settings', $sanitized, $raw);
    }
}
