<?php

declare(strict_types=1);

namespace Servinder\Subsites\Divi\Divi5;

use WP_Post;

/** Assigns an existing Divi Theme Builder template to a subsite home. */
final class ThemeBuilderTemplates
{
	private const META_KEY = '_servinder_subsites_divi_template_id';

	public function register(): void
	{
		add_action('servinder_subsites_wizard_fields', [$this, 'render_field'], 10, 2);
		add_action('servinder_subsites_wizard_saved', [$this, 'save_field'], 10, 2);
		add_filter('et_theme_builder_template_layouts', [$this, 'filter_template_layouts']);
	}

	public function render_field(int $subsite_id, int $step): void
	{
		if (2 !== $step) {
			return;
		}

		$templates = $this->templates();
		$selected = (int) get_post_meta($subsite_id, self::META_KEY, true);
		?>
		<section class="ss-wizard-card">
			<h2><?php esc_html_e('Layout da home no Divi', 'servinder-subsites-divi'); ?></h2>
			<p><?php esc_html_e('Escolha um modelo do Theme Builder para reutilizar o cabeçalho, corpo e rodapé nesta home. A escolha vale somente para a página inicial deste subsite.', 'servinder-subsites-divi'); ?></p>
			<label for="ss-divi-template"><?php esc_html_e('Modelo do Theme Builder', 'servinder-subsites-divi'); ?></label>
			<select id="ss-divi-template" name="servinder_subsites_divi_template_id">
				<option value="0"><?php esc_html_e('Usar o modelo padrão do site', 'servinder-subsites-divi'); ?></option>
				<?php foreach ($templates as $template) : ?>
					<option value="<?php echo esc_attr((string) $template['id']); ?>" <?php selected($selected, (int) $template['id']); ?>><?php echo esc_html($template['title'] ?: sprintf(__('Modelo #%d', 'servinder-subsites-divi'), (int) $template['id'])); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if (!$templates) : ?>
				<p class="description"><?php esc_html_e('Ainda não há modelos publicados com layouts no Theme Builder. Crie um modelo lá e volte para selecioná-lo.', 'servinder-subsites-divi'); ?></p>
			<?php endif; ?>
			<p><a href="<?php echo esc_url(admin_url('admin.php?page=et_theme_builder')); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Abrir Divi Theme Builder', 'servinder-subsites-divi'); ?> ↗</a></p>
		</section>
		<?php
	}

	public function save_field(int $subsite_id, int $step): void
	{
		if (2 !== $step || !current_user_can('manage_options')) {
			return;
		}

		$requested = absint(wp_unslash($_POST['servinder_subsites_divi_template_id'] ?? 0));
		$valid_ids = array_map(static fn(array $template): int => (int) $template['id'], $this->templates());
		if ($requested && in_array($requested, $valid_ids, true)) {
			update_post_meta($subsite_id, self::META_KEY, $requested);
		} else {
			delete_post_meta($subsite_id, self::META_KEY);
		}
	}

	/** @param array<string, mixed> $layouts @return array<string, mixed> */
	public function filter_template_layouts(array $layouts): array
	{
		if (is_admin() || (function_exists('is_preview') && is_preview())) {
			return $layouts;
		}

		$subsite = function_exists('servinder_subsites_current') ? servinder_subsites_current() : null;
		if (!$subsite instanceof WP_Post || 'publish' !== $subsite->post_status) {
			return $layouts;
		}

		$is_subsite_home = (function_exists('is_front_page') && is_front_page())
			|| (class_exists('Servinder\\Subsites\\Features\\ContextResolver') && \Servinder\Subsites\Features\\ContextResolver::is_root_request());
		if (!$is_subsite_home) {
			return $layouts;
		}

		$template_id = (int) get_post_meta($subsite->ID, self::META_KEY, true);
		$template = $template_id && function_exists('et_theme_builder_get_template') ? et_theme_builder_get_template($template_id) : [];
		if (!$template || empty($template['enabled']) || empty($template['layouts']) || !defined('ET_THEME_BUILDER_TEMPLATE_POST_TYPE')) {
			return $layouts;
		}

		$layouts = [ET_THEME_BUILDER_TEMPLATE_POST_TYPE => is_singular() ? (int) $template['id'] : false];
		foreach (['header' => 'HEADER', 'body' => 'BODY', 'footer' => 'FOOTER'] as $area => $constant_suffix) {
			$constant = 'ET_THEME_BUILDER_' . $constant_suffix . '_LAYOUT_POST_TYPE';
			if (defined($constant) && isset($template['layouts'][$area])) {
				$layouts[constant($constant)] = $template['layouts'][$area];
			}
		}

		return $layouts;
	}

	/** @return array<int, array{id: int, title: string, enabled: bool, layouts: array<string, array<string, mixed>>}> */
	private function templates(): array
	{
		if (!function_exists('et_theme_builder_get_theme_builder_templates')) {
			return [];
		}

		return array_values(array_filter(
			et_theme_builder_get_theme_builder_templates(true, false),
			static fn(array $template): bool => !empty($template['enabled'])
				&& !empty($template['layouts'])
				&& array_filter($template['layouts'], static fn(array $layout): bool => !empty($layout['override']))
		));
	}
}
