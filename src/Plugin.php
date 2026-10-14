<?php

declare(strict_types=1);

namespace Servinder\Subsites\Divi;

final class Plugin
{
	public function register(): void
	{
		if (!function_exists('servinder_subsites') || !function_exists('et_builder_d5_enabled') || !et_builder_d5_enabled()) {
			add_action('admin_notices', [$this, 'dependency_notice']);
			return;
		}

		$dependency_interface = trailingslashit(get_template_directory()) . 'includes/builder-5/server/Framework/DependencyManagement/Interfaces/DependencyInterface.php';
		if (!is_readable($dependency_interface)) {
			add_action('admin_notices', [$this, 'dependency_notice']);
			return;
		}
		require_once $dependency_interface;
		require_once SERVINDER_SUBSITES_DIVI_PATH . 'src/Divi5/ConditionalContentModule.php';

		add_action('init', [$this, 'register_dynamic_content'], 20);
		add_action('divi_visual_builder_assets_before_enqueue_scripts', [$this, 'register_visual_builder_assets']);
		add_filter('block_type_metadata_settings', [\Servinder\Subsites\Divi\Divi5\ConditionalContentModule::class, 'add_loop_visibility_attributes']);
		add_filter('divi_module_wrapper_render', [\Servinder\Subsites\Divi\Divi5\ConditionalContentModule::class, 'filter_loop_module_wrapper'], 10, 2);
		add_filter('divi_module_options_loop_post_type_results_query_args', [\Servinder\Subsites\Divi\Divi5\ConditionalContentModule::class, 'filter_loop_query_args'], 10, 2);
	}

	public function register_dynamic_content(): void
	{
		$base_class = '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentOptionBase';
		$option_interface = '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentOptionInterface';
		$elements_class = '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentElements';
		if (!class_exists($base_class) || !interface_exists($option_interface) || !class_exists($elements_class)) {
			return;
		}

		require_once SERVINDER_SUBSITES_DIVI_PATH . 'src/Divi5/SubsiteNameDynamicContentOption.php';
		$option_class = \Servinder\Subsites\Divi\Divi5\SubsiteNameDynamicContentOption::class;
		if (class_exists($option_class)) {
			(new $option_class())->load();
		}
	}

	public function dependency_notice(): void
	{
		if (!current_user_can('activate_plugins')) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__('Servinder Subsites — Divi is a separate feature plugin and requires Servinder Subsites and Divi 5. The free Servinder WordPress integration plugin is separate.', 'servinder-subsites-divi') . '</p></div>';
	}

	public function register_visual_builder_assets(): void
	{
		if (!function_exists('et_core_is_fb_enabled') || !et_core_is_fb_enabled()) {
			return;
		}
		wp_register_script('servinder-subsites-divi-settings', false, [], SERVINDER_SUBSITES_DIVI_VERSION, true);
		wp_localize_script('servinder-subsites-divi-settings', 'ServinderSubsitesDiviSettings', [
			'subsiteOptions' => \Servinder\Subsites\Divi\Divi5\ConditionalContentModule::available_subsite_options(),
			'currentSubsiteId' => function_exists('servinder_subsites_current') ? (int) (servinder_subsites_current()?->ID ?? 0) : 0,
		]);

		\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
			'name' => 'servinder-subsites-divi-visual-builder',
			'version' => SERVINDER_SUBSITES_DIVI_VERSION,
			'script' => [
				'src' => SERVINDER_SUBSITES_DIVI_URL . 'visual-builder/build/servinder-subsites-divi.js',
				'deps' => ['react', 'jquery', 'divi-module-library', 'divi-vendor-wp-hooks', 'divi-rest', 'servinder-subsites-divi-settings'],
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			],
		]);
	}

}
