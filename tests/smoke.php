<?php

$assert = static function (bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
};

$assert(function_exists('servinder_subsites'), 'Servinder Subsites core is not active.');
$assert(class_exists('Servinder\\Subsites\\Divi\\Divi5\\ConditionalContentModule'), 'Divi server module did not load after the Divi dependency interface became available.');
$dynamic_option_class = 'Servinder\\Subsites\\Divi\\Divi5\\SubsiteNameDynamicContentOption';
$assert(class_exists($dynamic_option_class), 'The active-subsite dynamic content option did not load.');
$appearance_option_class = 'Servinder\\Subsites\\Divi\\Divi5\\SubsiteAppearanceDynamicContentOption';
$assert(class_exists($appearance_option_class), 'The subsite appearance dynamic content option did not load.');
$options = [];
$offset = 0;
do {
	$page = servinder_subsites()->list(['status' => 'publish', 'limit' => 100, 'offset' => $offset]);
	foreach ($page as $subsite) {
		$options[] = ['label' => html_entity_decode(get_the_title($subsite), ENT_QUOTES, get_bloginfo('charset')), 'value' => (string) $subsite->ID];
	}
	$offset += count($page);
} while (count($page) === 100);
$active_subsite = get_post((int) ($options[0]['value'] ?? 0));
$assert($active_subsite instanceof WP_Post, 'The smoke site has no published subsite for visibility verification.');
$options_json = wp_json_encode($options);
$assert(is_string($options_json) && false !== json_decode($options_json), 'The Visual Builder selector options are not valid JSON.');
add_filter('servinder_subsites_current_subsite', static fn() => $active_subsite, PHP_INT_MAX);
$dynamic_option = new $dynamic_option_class();
$dynamic_option->load();
$appearance_option = new $appearance_option_class();
$appearance_option->load();
$dynamic_options = apply_filters('divi_module_dynamic_content_options', [], 0, 'edit');
$assert(isset($dynamic_options['servinder_subsite_name']) && 'text' === $dynamic_options['servinder_subsite_name']['type'], 'The Divi dynamic text source was not registered.');
$assert('color' === ($dynamic_options['servinder_subsite_primary_color']['type'] ?? ''), 'The primary color dynamic source was not registered as a color.');
$assert('color' === ($dynamic_options['servinder_subsite_accent_color']['type'] ?? ''), 'The accent color dynamic source was not registered as a color.');
$assert('color' === ($dynamic_options['servinder_subsite_surface_color']['type'] ?? ''), 'The surface color dynamic source was not registered as a color.');
$assert('color' === ($dynamic_options['servinder_subsite_text_color']['type'] ?? ''), 'The text color dynamic source was not registered as a color.');
$assert('image' === ($dynamic_options['servinder_subsite_logo']['type'] ?? ''), 'The logo dynamic source was not registered as an image.');
$assert('image' === ($dynamic_options['servinder_subsite_background_image']['type'] ?? ''), 'The background image dynamic source was not registered as an image.');
$dynamic_data_args = ['name' => 'servinder_subsite_name', 'settings' => []];
$dynamic_output = apply_filters('divi_module_dynamic_content_resolved_value', '', $dynamic_data_args);
$assert(str_contains($dynamic_output, esc_html(get_the_title($active_subsite))), 'The Divi dynamic source did not resolve the active subsite name.');
$unknown_dynamic_output = $dynamic_option->render_callback('original', ['name' => 'another_dynamic_option']);
$assert('original' === $unknown_dynamic_output, 'The dynamic source altered another option value.');
$no_context_filter = static fn() => null;
add_filter('servinder_subsites_current_subsite', $no_context_filter, PHP_INT_MAX);
$no_context_output = apply_filters('divi_module_dynamic_content_resolved_value', '', $dynamic_data_args);
remove_filter('servinder_subsites_current_subsite', $no_context_filter, PHP_INT_MAX);
$assert('' === $no_context_output, 'The dynamic source emitted a value without an active subsite context.');
$dynamic_filter_called = false;
$dynamic_value_filter = static function (string $value, string $option_name, WP_Post $subsite) use (&$dynamic_filter_called, $active_subsite): string {
	$dynamic_filter_called = 'servinder_subsite_name' === $option_name && (int) $subsite->ID === (int) $active_subsite->ID;
	return 'Nome filtrado';
};
add_filter('servinder_subsites_divi_dynamic_content_value', $dynamic_value_filter, 10, 3);
$filtered_dynamic_output = apply_filters('divi_module_dynamic_content_resolved_value', '', $dynamic_data_args);
remove_filter('servinder_subsites_divi_dynamic_content_value', $dynamic_value_filter, 10);
$assert($dynamic_filter_called && str_contains($filtered_dynamic_output, 'Nome filtrado'), 'The dynamic content extension filter did not receive the active subsite value.');
$appearance_filter = static function (array $appearance, int $subsite_id) use ($active_subsite): array {
	if ((int) $active_subsite->ID !== $subsite_id) {
		return $appearance;
	}
	return array_merge($appearance, [
		'primary_color' => '#123abc',
		'accent_color' => '#234bcd',
		'surface_color' => '#345cde',
		'text_color' => '#456def',
		'logo_id' => 0,
		'background_image_id' => 0,
	]);
};
add_filter('servinder_subsites_appearance', $appearance_filter, 99, 2);
$primary_color = apply_filters('divi_module_dynamic_content_resolved_value', '', ['name' => 'servinder_subsite_primary_color', 'settings' => []]);
$surface_color = apply_filters('divi_module_dynamic_content_resolved_value', '', ['name' => 'servinder_subsite_surface_color', 'settings' => []]);
$assert('#123abc' === $primary_color && '#345cde' === $surface_color, 'The Divi color sources did not resolve normalized subsite appearance values.');
$invalid_color_filter = static fn(mixed $value, string $option_name): mixed => 'servinder_subsite_primary_color' === $option_name ? 'red' : $value;
add_filter('servinder_subsites_divi_dynamic_content_value', $invalid_color_filter, 10, 2);
$invalid_color = apply_filters('divi_module_dynamic_content_resolved_value', '', ['name' => 'servinder_subsite_primary_color', 'settings' => []]);
remove_filter('servinder_subsites_divi_dynamic_content_value', $invalid_color_filter, 10);
$assert('' === $invalid_color, 'The Divi color source accepted an invalid extension value.');
$missing_logo = apply_filters('divi_module_dynamic_content_resolved_value', '', ['name' => 'servinder_subsite_logo', 'settings' => []]);
$missing_background = apply_filters('divi_module_dynamic_content_resolved_value', '', ['name' => 'servinder_subsite_background_image', 'settings' => []]);
$assert('' === $missing_logo && '' === $missing_background, 'An unset subsite image source should resolve to an empty value.');
$image_value_filter = static function (mixed $value, string $option_name): mixed {
	return 'servinder_subsite_logo' === $option_name ? 'https://example.com/servinder-subsite-logo.png' : $value;
};
add_filter('servinder_subsites_divi_dynamic_content_value', $image_value_filter, 10, 2);
$filtered_logo = apply_filters('divi_module_dynamic_content_resolved_value', '', ['name' => 'servinder_subsite_logo', 'settings' => []]);
remove_filter('servinder_subsites_divi_dynamic_content_value', $image_value_filter, 10);
$assert(str_contains($filtered_logo, 'https://example.com/servinder-subsite-logo.png'), 'The Divi image source did not format a filtered image URL.');
remove_filter('servinder_subsites_appearance', $appearance_filter, 99);
$rule = ['mode' => 'include', 'subsiteIds' => [(int) $active_subsite->ID]];
$visible = servinder_subsites_is_block_visible($rule);
$hidden = servinder_subsites_is_block_visible(['mode' => 'include', 'subsiteIds' => [PHP_INT_MAX]]);
$excluded = servinder_subsites_is_block_visible(['mode' => 'exclude', 'subsiteIds' => [(int) $active_subsite->ID]]);
$elsewhere = servinder_subsites_is_block_visible(['mode' => 'exclude', 'subsiteIds' => [PHP_INT_MAX]]);
$assert(true === $visible, 'Included content was not visible for the active subsite.');
$assert(false === $hidden && false === $excluded, 'An include or exclude condition returned the wrong visibility.');
$assert(true === $elsewhere, 'Exclude mode hid a subsite that was not selected.');

$module = 'Servinder\\Subsites\\Divi\\Divi5\\ConditionalContentModule';
$group_metadata = $module::add_loop_visibility_attributes([
	'name' => 'divi/group-carousel',
	'attributes' => ['module' => ['settings' => []]],
]);
$assert(isset($group_metadata['attributes']['servinderSubsiteTargets'], $group_metadata['attributes']['servinderSubsiteMode']), 'Group Carousel metadata did not receive the subsite visibility controls.');
$assert('all' === $group_metadata['attributes']['servinderSubsiteMode']['default']['innerContent']['desktop']['value'], 'New loop modules do not default to all subsites.');
$unrelated_metadata = $module::add_loop_visibility_attributes(['name' => 'divi/text', 'attributes' => ['module' => ['settings' => []]]]);
$assert(!isset($unrelated_metadata['attributes']['servinderSubsiteMode']), 'A non-loop module received loop visibility controls.');
$visible_loop_attrs = [
	'servinderSubsiteTargets' => ['innerContent' => ['desktop' => ['value' => [(string) $active_subsite->ID]]]],
	'servinderSubsiteMode' => ['innerContent' => ['desktop' => ['value' => 'include']]],
];
$hidden_wrapper = $module::filter_loop_module_wrapper('<div class="et_pb_group">Conteúdo</div>', ['attrs' => [
	'servinderSubsiteTargets' => ['innerContent' => ['desktop' => ['value' => [(string) PHP_INT_MAX]]]],
	'servinderSubsiteMode' => ['innerContent' => ['desktop' => ['value' => 'include']]],
]]);
$assert('' === $hidden_wrapper, 'A loop module outside its selected subsite was not hidden.');
$visible_wrapper = $module::filter_loop_module_wrapper('<div class="et_pb_group">Conteúdo</div>', ['attrs' => $visible_loop_attrs]);
$assert('' !== $visible_wrapper, 'A loop module selected for the active subsite was hidden.');
$numeric_exclude_wrapper = $module::filter_loop_module_wrapper('<div class="et_pb_group">Conteúdo</div>', ['attrs' => [
	'servinderSubsiteTargets' => ['innerContent' => ['desktop' => ['value' => [(string) $active_subsite->ID]]]],
	'servinderSubsiteMode' => ['innerContent' => ['desktop' => ['value' => 2]]],
]]);
$assert('' === $numeric_exclude_wrapper, 'Divi numeric select value 2 did not hide a loop module in exclude mode.');
$visible_query = $module::filter_loop_query_args([], [
	'query_type' => 'post_type',
	'servinder_subsite_id' => (string) $active_subsite->ID,
	'servinder_subsite_mode' => 'include',
	'servinder_subsite_ids' => (string) $active_subsite->ID,
]);
$assert(!isset($visible_query['post__in']) && (int) ($visible_query['servinder_subsites_target_subsite'] ?? 0) === (int) $active_subsite->ID, 'The visible loop query was not scoped to the active subsite.');
$hidden_query = $module::filter_loop_query_args([], [
	'query_type' => 'post_type',
	'servinder_subsite_id' => (string) $active_subsite->ID,
	'servinder_subsite_mode' => 'include',
	'servinder_subsite_ids' => (string) PHP_INT_MAX,
]);
$assert([0] === ($hidden_query['post__in'] ?? []), 'A loop query outside its selected subsite returned content.');
$all_subsites_query = $module::filter_loop_query_args(['posts_per_page' => 4], [
	'query_type' => 'post_type',
	'servinder_subsite_mode' => '0',
]);
$assert(4 === $all_subsites_query['posts_per_page'] && !isset($all_subsites_query['post__in']), 'The all-subsites default changed a legacy loop query.');
$included_numeric_query = $module::filter_loop_query_args([], [
	'query_type' => 'post_type',
	'servinder_subsite_id' => (string) $active_subsite->ID,
	'servinder_subsite_mode' => '1',
	'servinder_subsite_ids' => (string) $active_subsite->ID,
]);
$assert(!isset($included_numeric_query['post__in']), 'Divi numeric select value 1 did not include the selected loop subsite.');
$excluded_numeric_query = $module::filter_loop_query_args([], [
	'query_type' => 'post_type',
	'servinder_subsite_id' => (string) $active_subsite->ID,
	'servinder_subsite_mode' => '2',
	'servinder_subsite_ids' => (string) $active_subsite->ID,
]);
$assert([0] === ($excluded_numeric_query['post__in'] ?? []), 'Divi numeric select value 2 did not exclude the selected loop subsite.');

$shortcode_calls = 0;
add_shortcode('servinder_subsites_divi_smoke', static function () use (&$shortcode_calls): string {
	++$shortcode_calls;
	return 'DIVI_CONDITIONAL_SMOKE_OUTPUT';
});
$included_output = $module::render_conditional_content(
	'[servinder_subsites_divi_smoke]',
	'include',
	[(int) $active_subsite->ID]
);
$assert('DIVI_CONDITIONAL_SMOKE_OUTPUT' === $included_output, 'The Divi adapter did not render shortcode content for a matching include rule.');
$assert(1 === $shortcode_calls, 'The shortcode should run once after an allowed include rule.');
$numeric_include_output = $module::render_conditional_content('NUMERIC_INCLUDE_VISIBLE', 0, [(int) $active_subsite->ID]);
$assert('NUMERIC_INCLUDE_VISIBLE' === $numeric_include_output, 'Divi numeric select value 0 did not include the selected content subsite.');
$numeric_exclude_output = $module::render_conditional_content('NUMERIC_EXCLUDE_HIDDEN', 1, [(int) $active_subsite->ID]);
$assert('' === $numeric_exclude_output, 'Divi numeric select value 1 did not exclude the selected content subsite.');
$assert('' === $module::render_conditional_content('[servinder_subsites_divi_smoke]', 'include', [PHP_INT_MAX]), 'The Divi adapter rendered content for a non-matching include rule.');
$assert('' === $module::render_conditional_content('[servinder_subsites_divi_smoke]', 'exclude', [(int) $active_subsite->ID]), 'The Divi adapter rendered content excluded from the active subsite.');
$assert(1 === $shortcode_calls, 'The Divi adapter executed a shortcode while conditional content was hidden.');
$excluded_output = $module::render_conditional_content('[servinder_subsites_divi_smoke]', 'exclude', [PHP_INT_MAX]);
$assert('DIVI_CONDITIONAL_SMOKE_OUTPUT' === $excluded_output, 'The Divi adapter did not render content for an allowed exclude rule.');
$assert(2 === $shortcode_calls, 'The shortcode should run once for each allowed Divi render.');

WP_CLI::success('Published-subsite selector options, active-subsite dynamic content, core and Divi include/exclude rendering, loop-module visibility and query scoping, shortcode gating, fail-closed behavior, and module loading passed.');
