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
     * Whether the bar should render on the front end right now: enabled and with
     * a non-empty message.
     */
    public function isActive(): bool
    {
        $settings = $this->all();

        if (empty($settings['enabled'])) {
            return false;
        }

        $message = trim(wp_strip_all_tags((string) ($settings['message'] ?? '')));

        return '' !== $message;
    }
}
