<?php

declare(strict_types=1);

namespace Servinder\Subsites\Divi\Divi5;

use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentElements;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionBase;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionInterface;
/** Provides the active Servinder Subsite name to Divi text fields. */
final class SubsiteNameDynamicContentOption extends DynamicContentOptionBase implements DynamicContentOptionInterface
{
	private const OPTION_NAME = 'servinder_subsite_name';

	public function get_name(): string
	{
		return self::OPTION_NAME;
	}

	public function get_label(): string
	{
		return esc_html__('Nome do subsite atual', 'servinder-subsites-divi');
	}

	public function register_option_callback(array $options, int $post_id, string $context): array
	{
		if (isset($options[$this->get_name()])) {
			return $options;
		}

		$options[$this->get_name()] = [
			'id' => $this->get_name(),
			'label' => $this->get_label(),
			'type' => 'text',
			'custom' => false,
			'group' => esc_html__('Servinder Subsites', 'servinder-subsites-divi'),
			'fields' => [
				'before' => [
					'label' => esc_html__('Antes', 'servinder-subsites-divi'),
					'type' => 'text',
					'default' => '',
				],
				'after' => [
					'label' => esc_html__('Depois', 'servinder-subsites-divi'),
					'type' => 'text',
					'default' => '',
				],
			],
		];

		return $options;
	}

	/** @param mixed $value @param array<string, mixed> $data_args */
	public function render_callback(mixed $value, array $data_args = []): string
	{
		if ($this->get_name() !== ($data_args['name'] ?? '')) {
			return is_string($value) ? $value : '';
		}

		$subsite = SubsiteDynamicContentContext::current();
		if (!$subsite) {
			return '';
		}

		$dynamic_value = get_the_title($subsite);
		$dynamic_value = apply_filters('servinder_subsites_divi_dynamic_content_value', $dynamic_value, $this->get_name(), $subsite, $data_args);
		if (!is_scalar($dynamic_value)) {
			return '';
		}

		return DynamicContentElements::get_wrapper_element([
			'name' => $this->get_name(),
			'value' => esc_html((string) $dynamic_value),
			'settings' => is_array($data_args['settings'] ?? null) ? $data_args['settings'] : [],
		]);
	}
}
