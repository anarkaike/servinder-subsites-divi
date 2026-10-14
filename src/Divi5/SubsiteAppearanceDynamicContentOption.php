<?php

declare(strict_types=1);

namespace Servinder\Subsites\Divi\Divi5;

use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentElements;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionBase;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionInterface;

/** Exposes core subsite appearance values as Divi dynamic content. */
final class SubsiteAppearanceDynamicContentOption extends DynamicContentOptionBase implements DynamicContentOptionInterface
{
	/** @return array<string, array{label: string, key: string, type: 'color'|'image'}> */
	private static function definitions(): array
	{
		return [
			'servinder_subsite_primary_color' => ['label' => esc_html__('Cor principal do subsite', 'servinder-subsites-divi'), 'key' => 'primary_color', 'type' => 'color'],
			'servinder_subsite_accent_color' => ['label' => esc_html__('Cor de destaque do subsite', 'servinder-subsites-divi'), 'key' => 'accent_color', 'type' => 'color'],
			'servinder_subsite_surface_color' => ['label' => esc_html__('Cor de superfície do subsite', 'servinder-subsites-divi'), 'key' => 'surface_color', 'type' => 'color'],
			'servinder_subsite_text_color' => ['label' => esc_html__('Cor do texto do subsite', 'servinder-subsites-divi'), 'key' => 'text_color', 'type' => 'color'],
			'servinder_subsite_logo' => ['label' => esc_html__('Logo do subsite', 'servinder-subsites-divi'), 'key' => 'logo_id', 'type' => 'image'],
			'servinder_subsite_background_image' => ['label' => esc_html__('Imagem de fundo do subsite', 'servinder-subsites-divi'), 'key' => 'background_image_id', 'type' => 'image'],
		];
	}

	public function get_name(): string
	{
		return array_key_first(self::definitions()) ?: 'servinder_subsite_primary_color';
	}

	public function get_label(): string
	{
		return self::definitions()[$this->get_name()]['label'];
	}

	public function register_option_callback(array $options, int $post_id, string $context): array
	{
		foreach (self::definitions() as $name => $definition) {
			if (isset($options[$name])) {
				continue;
			}

			$options[$name] = [
				'id' => $name,
				'label' => $definition['label'],
				'type' => $definition['type'],
				'custom' => false,
				'group' => esc_html__('Servinder Subsites', 'servinder-subsites-divi'),
			];
		}

		return $options;
	}

	/** @param mixed $value @param array<string, mixed> $data_args */
	public function render_callback(mixed $value, array $data_args = []): string
	{
		$name = (string) ($data_args['name'] ?? '');
		$definitions = self::definitions();
		if (!isset($definitions[$name])) {
			return is_string($value) ? $value : '';
		}

		$subsite = SubsiteDynamicContentContext::current();
		if (!$subsite || !function_exists('servinder_subsites_appearance')) {
			return '';
		}

		$appearance = servinder_subsites_appearance((int) $subsite->ID);
		if (is_wp_error($appearance) || !is_array($appearance)) {
			return '';
		}

		$definition = $definitions[$name];
		$raw_value = $appearance[$definition['key']] ?? '';
		if ('image' === $definition['type']) {
			$raw_value = wp_get_attachment_image_url(absint($raw_value), 'full') ?: '';
		}
		$dynamic_value = apply_filters('servinder_subsites_divi_dynamic_content_value', $raw_value, $name, $subsite, $data_args);
		if (!is_scalar($dynamic_value)) {
			return '';
		}

		if ('color' === $definition['type']) {
			return sanitize_hex_color((string) $dynamic_value) ?: '';
		}

		$image_url = esc_url((string) $dynamic_value);
		if ('' === $image_url) {
			return '';
		}

		return DynamicContentElements::get_wrapper_element([
			'post_id' => absint($data_args['post_id'] ?? 0),
			'name' => $name,
			'value' => $image_url,
			'settings' => is_array($data_args['settings'] ?? null) ? $data_args['settings'] : [],
		]);
	}
}
