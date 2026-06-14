<?php
/**
 * Uninstall cleanup for Notice.
 *
 * Runs when the plugin is deleted from wp-admin. Removes the options Notice
 * creates. There is no per-post or per-user data to remove.
 *
 * @package Notice
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('notice_settings');
delete_option('notice_db_version');
