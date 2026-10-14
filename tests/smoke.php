<?php

$assert = static function (bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
};

$assert(function_exists('servinder_subsites'), 'Servinder Subsites core is not active.');
$assert(class_exists('Servinder\\Subsites\\Divi\\Divi5\\ConditionalContentModule'), 'Divi server module did not load after the Divi dependency interface became available.');
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

WP_CLI::success('Published-subsite selector options, core and Divi include/exclude rendering, loop-module visibility and query scoping, shortcode gating, fail-closed behavior, and module loading passed.');
