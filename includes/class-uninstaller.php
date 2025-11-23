<?php
/**
 * Plugin uninstaller class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Uninstaller Class.
 *
 * Handles plugin uninstallation tasks.
 *
 * @since 1.0.0
 */
class NexusForms_Uninstaller {

    /**
     * Uninstall the plugin.
     *
     * @since 1.0.0
     */
    public static function uninstall(): void {
        // Check if user wants to delete data on uninstall.
        $settings = get_option('nexusforms_settings', []);

        if (isset($settings['delete_on_uninstall']) && $settings['delete_on_uninstall']) {
            self::delete_database_tables();
            self::delete_options();
            self::delete_capabilities();
            self::delete_uploads();
        }

        /**
         * Fires after NexusForms is uninstalled.
         *
         * @since 1.0.0
         */
        do_action('nexusforms_uninstalled');
    }

    /**
     * Delete all plugin database tables.
     *
     * @since 1.0.0
     */
    private static function delete_database_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'nexus_forms',
            $wpdb->prefix . 'nexus_fields',
            $wpdb->prefix . 'nexus_entries',
            $wpdb->prefix . 'nexus_notifications',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Delete all plugin options.
     *
     * @since 1.0.0
     */
    private static function delete_options(): void {
        delete_option('nexusforms_version');
        delete_option('nexusforms_db_version');
        delete_option('nexusforms_settings');

        // Delete transients.
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_nexusforms_%'
            OR option_name LIKE '_transient_timeout_nexusforms_%'"
        );
    }

    /**
     * Delete plugin capabilities.
     *
     * @since 1.0.0
     */
    private static function delete_capabilities(): void {
        $admin_role = get_role('administrator');

        if ($admin_role) {
            $admin_role->remove_cap('nexusforms_manage_forms');
            $admin_role->remove_cap('nexusforms_manage_entries');
            $admin_role->remove_cap('nexusforms_manage_settings');
            $admin_role->remove_cap('nexusforms_export_entries');
            $admin_role->remove_cap('nexusforms_delete_entries');
        }
    }

    /**
     * Delete uploaded files.
     *
     * @since 1.0.0
     */
    private static function delete_uploads(): void {
        $upload_dir = wp_upload_dir();
        $nexusforms_dir = $upload_dir['basedir'] . '/nexusforms';

        if (is_dir($nexusforms_dir)) {
            self::delete_directory($nexusforms_dir);
        }
    }

    /**
     * Recursively delete a directory.
     *
     * @since 1.0.0
     * @param string $dir Directory path.
     */
    private static function delete_directory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::delete_directory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
