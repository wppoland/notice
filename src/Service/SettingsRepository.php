<?php

declare(strict_types=1);

namespace Notice\Service;

defined('ABSPATH') || exit;

/**
 * Reads and normalises the stored Notice settings.
 *
 * The single source of truth for the `notice_settings` option: it merges the
 * packaged defaults over whatever is stored, exposes typed accessors, and
 * decides whether the bar should render right now (enabled, has a message, and
 * within any configured schedule). Keeping this logic here means the renderer,
 * the admin preview and any PRO add-on all agree on what "active" means.
 */
final class SettingsRepository
{
    public const OPTION = 'notice_settings';

    /** Allowed bar positions (mapped to CSS by the template). */
    public const POSITIONS = ['top', 'static'];

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
     * Whether the bar should render on the front end right now: enabled, with a
     * non-empty message, and inside any configured schedule window.
     */
    public function isActive(): bool
    {
        $settings = $this->all();

        if (empty($settings['enabled'])) {
            return false;
        }

        $message = trim(wp_strip_all_tags((string) ($settings['message'] ?? '')));
        if ('' === $message) {
            return false;
        }

        return $this->isWithinSchedule($settings);
    }

    /**
     * Evaluate the optional schedule window against the site's current time.
     *
     * @param array<string, mixed> $settings
     */
    private function isWithinSchedule(array $settings): bool
    {
        if (empty($settings['schedule_enabled'])) {
            return true;
        }

        $now   = (int) current_time('timestamp');
        $start = $this->toTimestamp((string) ($settings['start_datetime'] ?? ''));
        $end   = $this->toTimestamp((string) ($settings['end_datetime'] ?? ''));

        if (null !== $start && $now < $start) {
            return false;
        }

        if (null !== $end && $now > $end) {
            return false;
        }

        return true;
    }

    /**
     * Parse a `Y-m-d\TH:i` datetime-local string into a site-time timestamp.
     */
    private function toTimestamp(string $value): ?int
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $value     = str_replace('T', ' ', $value);
        $timestamp = strtotime($value);

        return false === $timestamp ? null : $timestamp;
    }
}
