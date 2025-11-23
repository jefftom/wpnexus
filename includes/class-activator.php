<?php
/**
 * Plugin activator class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Activator Class.
 *
 * Handles plugin activation tasks.
 *
 * @since 1.0.0
 */
class NexusForms_Activator {

    /**
     * Activate the plugin.
     *
     * @since 1.0.0
     */
    public static function activate(): void {
        // Check PHP version.
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('NexusForms requires PHP 8.0 or higher.', 'nexusforms'),
                esc_html__('Plugin Activation Error', 'nexusforms'),
                ['back_link' => true]
            );
        }

        // Check WordPress version.
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('NexusForms requires WordPress 6.0 or higher.', 'nexusforms'),
                esc_html__('Plugin Activation Error', 'nexusforms'),
                ['back_link' => true]
            );
        }

        // Create database tables.
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-database.php';
        NexusForms_Database::create_tables();

        // Create default capabilities.
        self::create_capabilities();

        // Set default options.
        self::set_default_options();

        /**
         * Fires after NexusForms is activated.
         *
         * @since 1.0.0
         */
        do_action('nexusforms_activated');
    }

    /**
     * Create default capabilities.
     *
     * @since 1.0.0
     */
    private static function create_capabilities(): void {
        $admin_role = get_role('administrator');

        if ($admin_role) {
            $admin_role->add_cap('nexusforms_manage_forms');
            $admin_role->add_cap('nexusforms_manage_entries');
            $admin_role->add_cap('nexusforms_manage_settings');
            $admin_role->add_cap('nexusforms_export_entries');
            $admin_role->add_cap('nexusforms_delete_entries');
        }
    }

    /**
     * Set default plugin options.
     *
     * @since 1.0.0
     */
    private static function set_default_options(): void {
        $defaults = [
            'recaptcha_enabled' => false,
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'disable_css' => false,
            'ajax_submissions' => true,
            'store_ip_addresses' => true,
            'delete_on_uninstall' => false,
        ];

        add_option('nexusforms_settings', $defaults);
    }
}
