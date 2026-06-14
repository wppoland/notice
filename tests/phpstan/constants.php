<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Notice
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('NOTICE_DIR')) {
        define('NOTICE_DIR', '/tmp/notice/');
    }
    if (! defined('NOTICE_URL')) {
        define('NOTICE_URL', 'https://example.test/wp-content/plugins/notice/');
    }
}

namespace Notice {
    if (! defined('Notice\\VERSION')) {
        define('Notice\\VERSION', '0.1.0');
    }
    if (! defined('Notice\\PLUGIN_FILE')) {
        define('Notice\\PLUGIN_FILE', '/tmp/notice/notice.php');
    }
}
