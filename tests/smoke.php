<?php

declare(strict_types=1);

use Servinder\Subsites\Divi\Divi5\ConditionalContentModule;

$assert = static function (bool $condition, string $message): void {
	if (!$condition) {
		throw new RuntimeException($message);
	}
};

$assert(function_exists('servinder_subsites'), 'Servinder Subsites core is not active.');
$assert(function_exists('et_builder_d5_enabled') && et_builder_d5_enabled(), 'Divi 5 is not enabled.');
$assert(class_exists(ConditionalContentModule::class), 'The Divi 5 conditional module adapter did not load.');
$registered = WP_Block_Type_Registry::get_instance()->get_registered('servinder-subsites-divi/conditional-content');
$assert($registered instanceof WP_Block_Type, 'The conditional module was not registered with Divi 5.');

$options = ConditionalContentModule::available_subsite_options();
$published_subsites = [];
$offset = 0;
do {
	$page = servinder_subsites()->list(['status' => 'publish', 'limit' => 100, 'offset' => $offset]);
	$published_subsites = array_merge($published_subsites, $page);
	$offset += count($page);
} while (count($page) === 100);
$assert(count($options) === count($published_subsites), 'The Visual Builder selector does not match the published subsite records.');

$metadata_settings = [
	'name' => 'servinder-subsites-divi/conditional-content',
	'attributes' => [
		'targetIds' => [
			'settings' => [
				'innerContent' => [
					'item' => [
						'component' => ['props' => ['options' => []]],
					],
				],
			],
		],
	],
];
$metadata_settings = apply_filters('block_type_metadata_settings', $metadata_settings);
$metadata_options = $metadata_settings['attributes']['targetIds']['settings']['innerContent']['item']['component']['props']['options'] ?? [];
$assert(count($metadata_options) === count($published_subsites), 'The registered module metadata does not expose the published subsite choices.');

$active_subsite = $published_subsites[0] ?? null;
$assert($active_subsite instanceof WP_Post, 'The smoke site has no published subsite for visibility verification.');
add_filter('servinder_subsites_current_subsite', static fn() => $active_subsite, PHP_INT_MAX);
$visible = ConditionalContentModule::render_conditional_content('Visible on selected surface', 'include', [(int) $active_subsite->ID]);
$hidden = ConditionalContentModule::render_conditional_content('Must stay hidden', 'include', [PHP_INT_MAX]);
$excluded = ConditionalContentModule::render_conditional_content('Excluded surface', 'exclude', [(int) $active_subsite->ID]);
$elsewhere = ConditionalContentModule::render_conditional_content('Visible everywhere else', 'exclude', [PHP_INT_MAX]);
$assert('Visible on selected surface' === $visible, 'Included content was not rendered for the active subsite.');
$assert('' === $hidden && '' === $excluded, 'Content leaked through an include or exclude condition.');
$assert('Visible everywhere else' === $elsewhere, 'Exclude mode hid content from a subsite that was not selected.');

WP_CLI::success('Divi 5 module registration, published-subsite selector, include/exclude visibility, and fail-closed behavior passed.');
