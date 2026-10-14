<?php

declare(strict_types=1);

namespace Servinder\Subsites\Divi\Divi5;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if (!interface_exists(DependencyInterface::class)) {
	return;
}

final class ConditionalContentModule implements DependencyInterface
{
	public function load(): void
	{
		add_action('init', [self::class, 'register_module']);
		add_filter('block_type_metadata_settings', [self::class, 'add_subsite_options']);
	}

	public static function register_module(): void
	{
		$metadata_path = SERVINDER_SUBSITES_DIVI_PATH . 'visual-builder/src';
		if (!is_readable($metadata_path . '/module.json')) {
			return;
		}

		ModuleRegistration::register_module($metadata_path, [
			'render_callback' => [self::class, 'render_callback'],
		]);
	}

	public static function add_subsite_options(array $settings): array
	{
		if ('servinder-subsites-divi/conditional-content' !== ($settings['name'] ?? '')) {
			return $settings;
		}

		$targets = self::available_subsite_options();

		if (!isset($settings['attributes']['targetIds']['settings']['innerContent']['item']['component']['props']['options'])) {
			$settings['attributes']['targetIds']['settings']['innerContent']['item']['component']['props']['options'] = [];
		}
		$settings['attributes']['targetIds']['settings']['innerContent']['item']['component']['props']['options'] = $targets;

		return $settings;
	}

	/** Add persisted editor attributes to Divi modules that support the Loop group. */
	public static function add_loop_visibility_attributes(array $settings): array
	{
		$name = (string) ($settings['name'] ?? '');
		if (!str_starts_with($name, 'divi/') || !self::supports_loop($settings)) {
			return $settings;
		}

		$settings['attributes']['servinderSubsiteTargets'] ??= self::loop_attribute('servinderSubsiteTargets.innerContent', __('Subsites', 'servinder-subsites-divi'), __('Escolha onde este módulo com Loop ficará visível.', 'servinder-subsites-divi'), 'divi/checkboxes', self::available_subsite_options(), []);
		$settings['attributes']['servinderSubsiteMode'] ??= self::loop_attribute('servinderSubsiteMode.innerContent', __('Mostrar em', 'servinder-subsites-divi'), __('Controla onde o módulo e os dados do Loop aparecem.', 'servinder-subsites-divi'), 'divi/select', [
			['label' => __('Todos os subsites', 'servinder-subsites-divi'), 'value' => 'all'],
			['label' => __('Somente os selecionados', 'servinder-subsites-divi'), 'value' => 'include'],
			['label' => __('Todos, exceto os selecionados', 'servinder-subsites-divi'), 'value' => 'exclude'],
		], 'all');

		return $settings;
	}

	/** @param array<string, mixed> $settings */
	private static function supports_loop(array $settings): bool
	{
		$name = (string) ($settings['name'] ?? '');
		if (in_array($name, ['divi/group', 'divi/group-carousel'], true)) {
			return true;
		}

		$contains_loop = static function (mixed $value) use (&$contains_loop): bool {
			if (!is_array($value)) {
				return false;
			}
			foreach ($value as $key => $child) {
				if ('loop' === $key || $contains_loop($child)) {
					return true;
				}
			}
			return false;
		};

		return $contains_loop($settings['attributes']['module']['settings'] ?? []);
	}

	/** @param list<array{label:string,value:string}> $options
	 *  @return array<string, mixed>
	 */
	private static function loop_attribute(string $attr_name, string $label, string $description, string $component, array $options, mixed $default): array
	{
		return [
			'type' => 'object',
			'selector' => '{{selector}}',
			'default' => ['innerContent' => ['desktop' => ['value' => $default]]],
			'settings' => [
				'innerContent' => [
					'groupType' => 'group-item',
					'item' => [
						'attrName' => $attr_name,
						'label' => $label,
						'description' => $description,
						'groupSlug' => 'servinderSubsitesLoop',
						'priority' => 'divi/checkboxes' === $component ? 1 : 2,
						'render' => true,
						'features' => ['sticky' => false, 'responsive' => false, 'dynamicContent' => false, 'preset' => 'content'],
						'component' => ['name' => $component, 'type' => 'field', 'props' => ['options' => $options]],
					],
				],
			],
			'attributes' => [],
			'childrenSanitizer' => 'et_core_esc_previously',
		];
	}

	/** Hide a loop module wrapper when its saved subsite rule does not match. */
	public static function filter_loop_module_wrapper(string $wrapper, array $args): string
	{
		$attrs = is_array($args['attrs'] ?? null) ? $args['attrs'] : (is_array($args['module_attrs'] ?? null) ? $args['module_attrs'] : []);
		$mode = self::loop_mode($attrs);
		if ('all' === $mode) {
			return $wrapper;
		}

		$subsite_id = (int) (function_exists('servinder_subsites_current') ? (servinder_subsites_current()?->ID ?? 0) : 0);
		return self::is_loop_visible($mode, self::loop_target_ids($attrs), $subsite_id) ? $wrapper : '';
	}

	/** Scope Divi loop queries to the active subsite and apply module visibility. */
	public static function filter_loop_query_args(array $query_args, array $params): array
	{
		if ('post_type' !== sanitize_key((string) ($params['query_type'] ?? ''))) {
			return $query_args;
		}

		$subsite_id = absint($params['servinder_subsite_id'] ?? 0);
		if (!$subsite_id && function_exists('servinder_subsites_current')) {
			$subsite_id = (int) (servinder_subsites_current()?->ID ?? 0);
		}
		if ($subsite_id) {
			$query_args['servinder_subsites_target_subsite'] = $subsite_id;
		}

		$mode = self::normalize_loop_mode((string) ($params['servinder_subsite_mode'] ?? 'all'));
		if ('all' === $mode) {
			return $query_args;
		}
		$ids = self::parse_loop_ids($params['servinder_subsite_ids'] ?? []);
		if (!self::is_loop_visible($mode, $ids, $subsite_id)) {
			$query_args['post__in'] = [0];
		}
		return $query_args;
	}

	/** @param array<string, mixed> $attrs @return list<int> */
	private static function loop_target_ids(array $attrs): array
	{
		$value = $attrs['servinderSubsiteTargets']['innerContent']['desktop']['value'] ?? [];
		return self::parse_loop_ids($value);
	}

	/** @param mixed $value @return list<int> */
	private static function parse_loop_ids(mixed $value): array
	{
		if (is_string($value)) {
			$value = '' === trim($value) ? [] : explode(',', $value);
		}
		if (!is_array($value)) {
			return [];
		}
		return array_values(array_unique(array_filter(array_map('absint', $value))));
	}

	/** @param array<string, mixed> $attrs */
	private static function loop_mode(array $attrs): string
	{
		$value = $attrs['servinderSubsiteMode']['innerContent']['desktop']['value'] ?? 'all';
		return self::normalize_loop_mode(is_string($value) ? $value : 'all');
	}

	private static function normalize_loop_mode(string $mode): string
	{
		return in_array($mode, ['all', 'include', 'exclude'], true) ? $mode : 'all';
	}

	/** @param list<int> $ids */
	private static function is_loop_visible(string $mode, array $ids, int $subsite_id): bool
	{
		if ('all' === $mode) {
			return true;
		}
		if (!$subsite_id || !function_exists('servinder_subsites_is_block_visible')) {
			return false;
		}
		return servinder_subsites_is_block_visible(['mode' => $mode, 'subsiteIds' => $ids], $subsite_id);
	}

	/** @return list<array{label: string, value: string}> */
	public static function available_subsite_options(): array
	{
		if (!function_exists('servinder_subsites')) {
			return [];
		}

		$targets = [];
		$offset = 0;
		do {
			$subsites = servinder_subsites()->list(['status' => 'publish', 'limit' => 100, 'offset' => $offset]);
			foreach ($subsites as $subsite) {
				$targets[] = [
					'label' => html_entity_decode(get_the_title($subsite), ENT_QUOTES, get_bloginfo('charset')),
					'value' => (string) $subsite->ID,
				];
			}
			$offset += count($subsites);
		} while (count($subsites) === 100);

		return $targets;
	}

	public static function render_callback(array $attrs, string $content, \WP_Block $block, $elements): string
	{
		$targets = $attrs['targetIds']['innerContent']['desktop']['value'] ?? [];
		$targets = is_array($targets) ? array_values(array_filter(array_map('absint', $targets))) : [];
		$mode = (string) ($attrs['visibilityMode']['innerContent']['desktop']['value'] ?? 'include');
		$body = $elements->render(['attrName' => 'content']);

		$body = self::render_conditional_content($body, $mode, $targets, $block);
		if ('' === $body) {
			return '';
		}

		$module_inner = HTMLUtility::render([
			'tag' => 'div',
			'attributes' => ['class' => 'et_pb_module_inner'],
			'childrenSanitizer' => 'et_core_esc_previously',
			'children' => $body,
		]);
		$module_children = $elements->style_components(['attrName' => 'module']) . $module_inner;

		return Module::render([
			'orderIndex' => $block->parsed_block['orderIndex'] ?? 0,
			'storeInstance' => $block->parsed_block['storeInstance'] ?? '',
			'attrs' => $attrs,
			'elements' => $elements,
			'id' => $block->parsed_block['id'] ?? '',
			'moduleClassName' => 'servinder_subsites_divi_conditional_content',
			'name' => $block->block_type->name,
			'classnamesFunction' => [self::class, 'module_classnames'],
			'moduleCategory' => $block->block_type->category,
			'stylesComponent' => [self::class, 'module_styles'],
			'children' => $module_children,
		]);
	}

	public static function module_styles(array $args): void
	{
		$elements = $args['elements'];
		Style::add([
			'id' => $args['id'],
			'name' => $args['name'],
			'orderIndex' => $args['orderIndex'],
			'storeInstance' => $args['storeInstance'],
			'styles' => [$elements->style(['attrName' => 'module'])],
		]);
	}

	public static function module_classnames(array $args): void
	{
		$args['classnamesInstance']->add(ElementClassnames::classnames([
			'attrs' => $args['attrs']['module']['decoration'] ?? [],
		]));
	}

	/** @param list<int> $target_ids */
	public static function render_conditional_content(string $content, string $mode, array $target_ids, ?\WP_Block $block = null): string
	{
		if (!function_exists('servinder_subsites_is_block_visible')) {
			return '';
		}

		$rule = apply_filters('servinder_subsites_divi_visibility_rule', [
			'mode' => 'exclude' === $mode ? 'exclude' : 'include',
			'subsiteIds' => $target_ids,
		], $block);
		if (!is_array($rule)) {
			return '';
		}
		$visible = servinder_subsites_is_block_visible($rule);
		$visible = (bool) apply_filters('servinder_subsites_divi_is_visible', $visible, $rule, $block);
		if (!$visible) {
			return '';
		}

		$rendered = do_shortcode($content);
		return (string) apply_filters('servinder_subsites_divi_rendered_content', $rendered, $rule, $block);
	}

	public static function register_dependency($dependency_tree): void
	{
		$dependency_tree->add_dependency(new self());
	}
}

add_action('divi_module_library_modules_dependency_tree', [ConditionalContentModule::class, 'register_dependency']);
