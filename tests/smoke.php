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
$assert('' === $module::render_conditional_content('[servinder_subsites_divi_smoke]', 'include', [PHP_INT_MAX]), 'The Divi adapter rendered content for a non-matching include rule.');
$assert('' === $module::render_conditional_content('[servinder_subsites_divi_smoke]', 'exclude', [(int) $active_subsite->ID]), 'The Divi adapter rendered content excluded from the active subsite.');
$assert(1 === $shortcode_calls, 'The Divi adapter executed a shortcode while conditional content was hidden.');
$excluded_output = $module::render_conditional_content('[servinder_subsites_divi_smoke]', 'exclude', [PHP_INT_MAX]);
$assert('DIVI_CONDITIONAL_SMOKE_OUTPUT' === $excluded_output, 'The Divi adapter did not render content for an allowed exclude rule.');
$assert(2 === $shortcode_calls, 'The shortcode should run once for each allowed Divi render.');

WP_CLI::success('Published-subsite selector options, core and Divi adapter include/exclude rendering, shortcode gating, fail-closed behavior, and module loading passed.');
