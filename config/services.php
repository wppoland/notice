<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Services are thin; the announcement-bar logic lives in
 * Notice\Service\BarRenderer and the admin screen in Notice\Admin\Settings.
 *
 * @package Notice
 */

declare(strict_types=1);

use Notice\Admin\Settings;
use Notice\Container;
use Notice\Migrator;
use Notice\Service\BarRenderer;
use Notice\Service\SettingsRepository;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    $c->singleton(
        SettingsRepository::class,
        static fn (): SettingsRepository => new SettingsRepository(),
    );

    $c->singleton(
        BarRenderer::class,
        static fn (Container $c): BarRenderer => new BarRenderer(
            $c->get(SettingsRepository::class),
        ),
    );

    // Admin screen — only needed in wp-admin context.
    if (is_admin()) {
        $c->singleton(
            Settings::class,
            static fn (Container $c): Settings => new Settings(
                $c->get(SettingsRepository::class),
            ),
        );
    }
};
