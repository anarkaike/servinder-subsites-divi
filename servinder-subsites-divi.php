<?php
/**
 * Plugin Name: Servinder Subsites — Divi
 * Description: Divi 5 modules and loop visibility powered by Servinder Subsites.
 * Version: 0.4.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Requires Plugins: servinder-subsites
 * Author: Servinder
 * License: GPL-2.0-or-later
 * Text Domain: servinder-subsites-divi
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

define('SERVINDER_SUBSITES_DIVI_VERSION', '0.4.0');
define('SERVINDER_SUBSITES_DIVI_FILE', __FILE__);
define('SERVINDER_SUBSITES_DIVI_PATH', plugin_dir_path(__FILE__));
define('SERVINDER_SUBSITES_DIVI_URL', plugin_dir_url(__FILE__));

require_once SERVINDER_SUBSITES_DIVI_PATH . 'src/Plugin.php';

add_action('after_setup_theme', static function (): void {
	(new Servinder\Subsites\Divi\Plugin())->register();
}, 20);
