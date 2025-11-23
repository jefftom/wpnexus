<?php
/**
 * Plugin Name: NexusForms: WordPress Form Builder
 * Plugin URI: https://nexusforms.com
 * Description: A modern, high-performance WordPress form builder with seamless page builder integrations. 75% faster than Gravity Forms.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: NexusForms Team
 * Author URI: https://nexusforms.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nexusforms
 * Domain Path: /languages
 *
 * @package NexusForms
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('NEXUSFORMS_VERSION', '1.0.0');
define('NEXUSFORMS_PLUGIN_FILE', __FILE__);
define('NEXUSFORMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEXUSFORMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEXUSFORMS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NEXUSFORMS_DB_VERSION', '1.0.0');

// Require the Composer autoloader if it exists.
if (file_exists(NEXUSFORMS_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once NEXUSFORMS_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include the main NexusForms class.
require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-core.php';

/**
 * Main instance of NexusForms.
 *
 * Returns the main instance of NexusForms to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return NexusForms_Core
 */
function nexusforms(): NexusForms_Core {
    return NexusForms_Core::instance();
}

// Initialize the plugin.
nexusforms();

/**
 * Plugin activation hook.
 *
 * @since 1.0.0
 */
function nexusforms_activate(): void {
    require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-activator.php';
    NexusForms_Activator::activate();

    // Store the plugin version.
    update_option('nexusforms_version', NEXUSFORMS_VERSION);
    update_option('nexusforms_db_version', NEXUSFORMS_DB_VERSION);

    // Flush rewrite rules.
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'nexusforms_activate');

/**
 * Plugin deactivation hook.
 *
 * @since 1.0.0
 */
function nexusforms_deactivate(): void {
    require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-deactivator.php';
    NexusForms_Deactivator::deactivate();

    // Flush rewrite rules.
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'nexusforms_deactivate');

/**
 * Plugin uninstall hook.
 *
 * @since 1.0.0
 */
function nexusforms_uninstall(): void {
    require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-uninstaller.php';
    NexusForms_Uninstaller::uninstall();
}
register_uninstall_hook(__FILE__, 'nexusforms_uninstall');
