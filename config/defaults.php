<?php
/**
 * Default settings, merged under the option key `notice_settings`.
 *
 * Notice ships disabled by default: the merchant writes a message and flips the
 * master switch on. Every value here is a safe, sensible default so the bar
 * renders cleanly the moment it is enabled.
 *
 * @package Notice
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    // Master switch — off until the merchant writes a message and opts in.
    'enabled' => false,

    // The announcement. Limited safe HTML is allowed (<strong>, <em>, <a>, <br>, <span>).
    'message' => '',

    // Optional call-to-action link. When a label is set a button links here;
    // otherwise the link is ignored.
    'link_url'     => '',
    'link_label'   => '',
    'link_new_tab' => false,

    // Placement: a fixed bar pinned to the top, or a static bar that sits inline
    // at the very top of the page. 'top' = sticky; 'static' = inline.
    'position' => 'top',

    // Colours. Stored as hex; sanitised with sanitize_hex_color on save.
    'bg_color'   => '#1e1e1e',
    'text_color' => '#ffffff',
    'link_color' => '#ffd166',

    // Dismissible behaviour. When on, a close button appears and the choice is
    // remembered for `dismiss_days` days (localStorage, no cookies/PII).
    'dismissible'  => true,
    'dismiss_days' => 7,

    // Optional schedule. When off the bar is always on (subject to `enabled`).
    // Values are `Y-m-d\TH:i` strings in site time, compared with current_time().
    'schedule_enabled' => false,
    'start_datetime'   => '',
    'end_datetime'     => '',
];
