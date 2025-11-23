<?php
/**
 * Database management class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Database Class.
 *
 * Handles database table creation and migrations.
 *
 * @since 1.0.0
 */
class NexusForms_Database {

    /**
     * Create all plugin database tables.
     *
     * @since 1.0.0
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Forms table.
        $forms_table = $wpdb->prefix . 'nexus_forms';
        $forms_sql = "CREATE TABLE {$forms_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'active',
            settings JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status_idx (status),
            KEY created_idx (created_at)
        ) {$charset_collate};";

        // Fields table.
        $fields_table = $wpdb->prefix . 'nexus_fields';
        $fields_sql = "CREATE TABLE {$fields_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            field_data JSON NOT NULL,
            field_order INT DEFAULT 0,
            PRIMARY KEY (id),
            KEY form_order_idx (form_id, field_order),
            CONSTRAINT fk_fields_form_id FOREIGN KEY (form_id)
                REFERENCES {$forms_table}(id) ON DELETE CASCADE
        ) {$charset_collate};";

        // Entries table.
        $entries_table = $wpdb->prefix . 'nexus_entries';
        $entries_sql = "CREATE TABLE {$entries_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            entry_data JSON NOT NULL,
            form_schema_snapshot JSON,
            user_id BIGINT UNSIGNED,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY form_created_idx (form_id, created_at),
            KEY user_idx (user_id),
            KEY status_idx (status),
            CONSTRAINT fk_entries_form_id FOREIGN KEY (form_id)
                REFERENCES {$forms_table}(id) ON DELETE CASCADE
        ) {$charset_collate};";

        // Notifications table.
        $notifications_table = $wpdb->prefix . 'nexus_notifications';
        $notifications_sql = "CREATE TABLE {$notifications_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            notification_data JSON NOT NULL,
            type VARCHAR(50) DEFAULT 'email',
            PRIMARY KEY (id),
            KEY form_idx (form_id),
            CONSTRAINT fk_notifications_form_id FOREIGN KEY (form_id)
                REFERENCES {$forms_table}(id) ON DELETE CASCADE
        ) {$charset_collate};";

        // Execute table creation.
        dbDelta($forms_sql);
        dbDelta($fields_sql);
        dbDelta($entries_sql);
        dbDelta($notifications_sql);

        // Update database version.
        update_option('nexusforms_db_version', NEXUSFORMS_DB_VERSION);
    }

    /**
     * Get table name with prefix.
     *
     * @since 1.0.0
     * @param string $table Table name without prefix.
     * @return string Full table name with prefix.
     */
    public static function get_table_name(string $table): string {
        global $wpdb;
        return $wpdb->prefix . 'nexus_' . $table;
    }
}
