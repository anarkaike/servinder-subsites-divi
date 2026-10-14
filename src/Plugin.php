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
		add_action('after_setup_theme', static function () use ($dependency_interface): void {
			require_once $dependency_interface;
			require_once SERVINDER_SUBSITES_DIVI_PATH . 'src/Divi5/ConditionalContentModule.php';
		}, 20);

		add_action('divi_visual_builder_assets_before_enqueue_scripts', [$this, 'register_visual_builder_assets']);
	}

	public function dependency_notice(): void
	{
		if (!current_user_can('activate_plugins')) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__('Servinder Subsites — Divi requires the Servinder Subsites core plugin and Divi 5.', 'servinder-subsites-divi') . '</p></div>';
	}

	public function register_visual_builder_assets(): void
	{
		if (!function_exists('et_core_is_fb_enabled') || !et_core_is_fb_enabled()) {
			return;
		}
		wp_register_script('servinder-subsites-divi-settings', false, [], SERVINDER_SUBSITES_DIVI_VERSION, true);
		wp_localize_script('servinder-subsites-divi-settings', 'ServinderSubsitesDiviSettings', [
			'subsiteOptions' => \Servinder\Subsites\Divi\Divi5\ConditionalContentModule::available_subsite_options(),
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
