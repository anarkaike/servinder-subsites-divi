<?php

$assert = static function (bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
};

$assert(function_exists('servinder_subsites'), 'Servinder Subsites core is not active.');
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

WP_CLI::success('Published-subsite selector options, shared include/exclude visibility, and fail-closed behavior passed. Divi module loading is verified by frontend requests.');
