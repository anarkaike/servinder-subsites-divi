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
