<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Notice\Contract\HasHooks.
 *
 * @package Notice
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Notice\Admin\Settings;
use Notice\Service\BarRenderer;

defined('ABSPATH') || exit;

return is_admin()
    ? [
        Settings::class,
    ]
    : [
        BarRenderer::class,
    ];
