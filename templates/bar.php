<?php
/**
 * Storefront announcement bar.
 *
 * Rendered by Notice\Service\BarRenderer at wp_body_open. All variables are
 * supplied by the renderer's view model and are already validated; output is
 * escaped here at the point of use. The message permits a small safe-HTML
 * allow-list via wp_kses.
 *
 * @package Notice
 *
 * Available view variables:
 *
 * @var string                                              $message
 * @var array<string, array<string, array<string, mixed>>>  $allowed_html
 * @var string                                              $position
 * @var string                                              $bg_color
 * @var string                                              $text_color
 * @var string                                              $link_color
 * @var bool                                                $has_link
 * @var string                                              $link_url
 * @var string                                              $link_label
 * @var bool                                                $link_new_tab
 * @var bool                                                $dismissible
 * @var int                                                 $dismiss_days
 * @var string                                              $storage_key
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template-scope variables provided by the renderer.

defined('ABSPATH') || exit;

$notice_style = sprintf(
    '--notice-bg:%1$s;--notice-fg:%2$s;--notice-link:%3$s;',
    esc_attr($bg_color),
    esc_attr($text_color),
    esc_attr($link_color),
);

$notice_classes = 'notice-bar notice-bar--' . sanitize_html_class($position);
?>
<div
	class="<?php echo esc_attr($notice_classes); ?>"
	role="region"
	aria-label="<?php esc_attr_e('Site announcement', 'notice'); ?>"
	style="<?php echo esc_attr($notice_style); ?>"
	<?php if ($dismissible) : ?>
		data-notice-dismissible="1"
		data-notice-key="<?php echo esc_attr($storage_key); ?>"
		data-notice-days="<?php echo esc_attr((string) $dismiss_days); ?>"
		hidden
	<?php endif; ?>
>
	<div class="notice-bar__inner">
		<p class="notice-bar__message">
			<?php echo wp_kses($message, $allowed_html); ?>
		</p>

		<?php if ($has_link) : ?>
			<a
				class="notice-bar__cta"
				href="<?php echo esc_url($link_url); ?>"
				<?php if ($link_new_tab) : ?>
					target="_blank" rel="noopener noreferrer"
				<?php endif; ?>
			>
				<?php echo esc_html($link_label); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ($dismissible) : ?>
		<button
			type="button"
			class="notice-bar__close"
			aria-label="<?php esc_attr_e('Dismiss announcement', 'notice'); ?>"
		>
			<span aria-hidden="true">&times;</span>
		</button>
	<?php endif; ?>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
