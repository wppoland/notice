<?php

declare(strict_types=1);

namespace Notice\Service;

defined('ABSPATH') || exit;

/**
 * Reads and normalises the stored Notice settings.
 *
 * The single source of truth for the `notice_settings` option: it merges the
 * packaged defaults over whatever is stored, exposes typed accessors, and
 * decides whether the bar should render right now (enabled and has a message).
 * Keeping this logic here means the renderer and the admin preview always agree
 * on what "active" means.
 */
final class SettingsRepository
{
    public const OPTION = 'notice_settings';

    /** Safe HTML allowed inside the announcement message. */
    private const ALLOWED_MESSAGE_HTML = [
        'strong' => [],
        'b'      => [],
        'em'     => [],
        'i'      => [],
        'br'     => [],
        'span'   => [],
        'a'      => [
            'href'   => [],
            'title'  => [],
            'target' => [],
            'rel'    => [],
        ],
    ];

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * Stored settings merged over the packaged defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        $stored = get_option(self::OPTION, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return $this->cache = array_merge($this->defaults(), $stored);
    }

    /**
     * Packaged defaults.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = require NOTICE_DIR . 'config/defaults.php';

        return $defaults;
    }

    /**
     * The allowed-HTML map for the message field, exposed so the sanitiser and
     * the renderer use exactly the same allow-list.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function allowedMessageHtml(): array
    {
        return self::ALLOWED_MESSAGE_HTML;
    }

    /**
     * Whether any announcement bar should render on the front end right now.
     */
    public function isActive(): bool
    {
        return [] !== $this->activeBars();
    }

    /**
     * Bars that should render on this request.
     *
     * @return list<array{id: string, settings: array<string, mixed>}>
     */
    public function activeBars(): array
    {
        $active = [];

        foreach ($this->resolveBars() as $bar) {
            if (! is_array($bar)) {
                continue;
            }

            $id       = sanitize_key((string) ($bar['id'] ?? 'default'));
            $settings = is_array($bar['settings'] ?? null) ? $bar['settings'] : [];

            if ('' === $id || ! $this->isBarActive($settings, $id)) {
                continue;
            }

            $active[] = [
                'id'       => $id,
                'settings' => $settings,
            ];
        }

        return $active;
    }

    /**
     * Candidate bars before per-request visibility checks.
     *
     * @return list<array{id: string, settings: array<string, mixed>}>
     */
    public function resolveBars(): array
    {
        $bars = [
            [
                'id'       => 'default',
                'settings' => $this->all(),
            ],
        ];

        /**
         * Filter the announcement bars Notice will consider rendering.
         *
         * @param list<array{id: string, settings: array<string, mixed>}> $bars
         */
        $filtered = apply_filters('notice/bars', $bars);

        if (! is_array($filtered)) {
            return $bars;
        }

        $normalised = [];

        foreach ($filtered as $bar) {
            if (! is_array($bar)) {
                continue;
            }

            $id = sanitize_key((string) ($bar['id'] ?? ''));

            if ('' === $id) {
                continue;
            }

            $settings = is_array($bar['settings'] ?? null) ? $bar['settings'] : [];

            $normalised[] = [
                'id'       => $id,
                'settings' => $settings,
            ];
        }

        return [] !== $normalised ? $normalised : $bars;
    }

    /**
     * Whether a single bar should render from its settings.
     *
     * @param array<string, mixed> $settings
     */
    private function isBarActive(array $settings, string $barId): bool
    {
        if (empty($settings['enabled'])) {
            return false;
        }

        $message = trim(wp_strip_all_tags((string) ($settings['message'] ?? '')));

        if ('' === $message) {
            return false;
        }

        /**
         * Filter whether an announcement bar should render on this request.
         *
         * PRO and custom code can narrow visibility by page, role or segment.
         *
         * @param bool                 $active   Whether base settings allow the bar.
         * @param array<string, mixed> $settings Resolved bar settings.
         * @param string               $barId    Stable bar identifier.
         */
        return (bool) apply_filters('notice/bar_active', true, $settings, $barId);
    }
}
