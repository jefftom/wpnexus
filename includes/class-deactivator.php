<?php
/**
 * Plugin deactivator class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Deactivator Class.
 *
 * Handles plugin deactivation tasks.
 *
 * @since 1.0.0
 */
class NexusForms_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * @since 1.0.0
     */
    public static function deactivate(): void {
        // Clear scheduled cron jobs.
        wp_clear_scheduled_hook('nexusforms_cleanup_entries');
        wp_clear_scheduled_hook('nexusforms_cleanup_spam');

        // Clear transients.
        self::clear_transients();

        /**
         * Fires after NexusForms is deactivated.
         *
         * @since 1.0.0
         */
        do_action('nexusforms_deactivated');
    }

    /**
     * Clear all plugin transients.
     *
     * @since 1.0.0
     */
    private static function clear_transients(): void {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_nexusforms_%'
            OR option_name LIKE '_transient_timeout_nexusforms_%'"
        );
    }
}
