<?php
/**
 * Plugin Name:		Mystery Themes Demo Importer
 * Plugin URI: 		https://wordpress.org/plugins/mysterythemes-demo-importer/
 * Description:		One Click Demo Importer For Mystery Themes official themes demo content, customization options, widgets and theme settings.
 * Version:			1.0.6
 * Author:			Mystery Themes
 * Author URI:		https://mysterythemes.com/
 * License:			GPLv3 or later
 * License URI:		http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:		mysterythemes-demo-importer
 * Domain Path:		/languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
if ( ! defined( 'MTDI_VERSION' ) ) {
	define( 'MTDI_VERSION', '1.0.5' );
}

if ( ! defined( 'MTDI_TD' ) ) {
	define( 'MTDI_TD', 'mysterythemes-demo-importer' );
}

if ( ! defined( 'MTDI_PLUGIN_DIR' ) ) {
	define( 'MTDI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'MTDI_PLUGIN_URL' ) ) {
	define( 'MTDI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'MTDI_PLUGIN_FILE_DIR' ) ) {
	define( 'MTDI_PLUGIN_FILE_DIR', MTDI_PLUGIN_DIR .'mysterythemes-demo-importer.php' );
}

if ( ! defined( 'MTDI_ADMIN_DIR' ) ) {
	define( 'MTDI_ADMIN_DIR', WP_PLUGIN_DIR . '/mysterythemes-demo-importer/admin/' );
}
if ( ! defined( 'MTDI_PLUGIN_FILE' ) ) {
	define( 'MTDI_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'MTDI_PLUGIN_BASENAME' ) ) {
	define( 'MTDI_PLUGIN_BASENAME', plugin_basename( MTDI_PLUGIN_FILE ) );
}

/**
 * The core plugin class that is used to define the demo library list.
 */
require plugin_dir_path(__FILE__) . 'includes/class-mtdi-library.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mtdi-activator.php
 */
function activate_mtdi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-mtdi-activator.php';
	MTDI_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_mtdi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mtdi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function mtdi_run() {
	$plugin = new MTDI();
	$plugin->run();
}
mtdi_run();
