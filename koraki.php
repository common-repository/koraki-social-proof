<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://koraki.io
 * @since             1.0.0
 * @package           Koraki
 *
 * @wordpress-plugin
 * Plugin Name:       Koraki WordPress Social Proof Plugin
 * Plugin URI:        https://koraki.io
 * Description:       The AI powered social proof plugin for your WordPress and WooCommerce website to convince leads to become customers instantly
 * Version:           1.4.0
 * Author:            Koraki
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       koraki
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'KORAKI_VERSION', '1.4.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-koraki-activator.php
 */
function activate_koraki() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-koraki-activator.php';
	Koraki_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-koraki-deactivator.php
 */
function deactivate_koraki() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-koraki-deactivator.php';
	Koraki_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_koraki' );
register_deactivation_hook( __FILE__, 'deactivate_koraki' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-koraki.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_koraki() {

	$plugin = new Koraki();
	$plugin->run();

}
run_koraki();
