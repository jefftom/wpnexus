<?php
/**
 * Forms management class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Forms Class.
 *
 * Handles form CRUD operations.
 *
 * @since 1.0.0
 */
class NexusForms_Forms {

    /**
     * Cache group name.
     *
     * @var string
     */
    private const CACHE_GROUP = 'nexusforms_forms';

    /**
     * Cache expiration time (12 hours).
     *
     * @var int
     */
    private const CACHE_EXPIRATION = 12 * HOUR_IN_SECONDS;

    /**
     * Create a new form.
     *
     * @since 1.0.0
     * @param array $data Form data.
     * @return int|false Form ID on success, false on failure.
     */
    public function create(array $data): int|false {
        global $wpdb;

        $table = NexusForms_Database::get_table_name('forms');

        $defaults = [
            'title' => __('Untitled Form', 'nexusforms'),
            'description' => '',
            'status' => 'active',
            'settings' => json_encode([]),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        // Ensure settings is JSON.
        if (is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        $result = $wpdb->insert($table, $data, ['%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result) {
            $form_id = $wpdb->insert_id;

            /**
             * Fires after a form is created.
             *
             * @since 1.0.0
             * @param int $form_id Form ID.
             * @param array $data Form data.
             */
            do_action('nexusforms_form_created', $form_id, $data);

            return $form_id;
        }

        return false;
    }

    /**
     * Get a form by ID.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @return object|null Form object or null if not found.
     */
    public function get(int $form_id): ?object {
        $cache_key = "form_{$form_id}";
        $form = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $form) {
            global $wpdb;
            $table = NexusForms_Database::get_table_name('forms');

            $form = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $form_id
            ));

            if ($form) {
                // Decode JSON settings.
                $form->settings = json_decode($form->settings, true);

                // Get form fields.
                $form->fields = $this->get_fields($form_id);

                wp_cache_set($cache_key, $form, self::CACHE_GROUP, self::CACHE_EXPIRATION);
            }
        }

        return $form ?: null;
    }

    /**
     * Get all forms.
     *
     * @since 1.0.0
     * @param array $args Query arguments.
     * @return array Array of form objects.
     */
    public function get_all(array $args = []): array {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('forms');

        $defaults = [
            'status' => 'active',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = '';
        if ($args['status']) {
            $where = $wpdb->prepare("WHERE status = %s", $args['status']);
        }

        $query = "SELECT * FROM {$table}
                  {$where}
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT %d OFFSET %d";

        $forms = $wpdb->get_results($wpdb->prepare(
            $query,
            $args['limit'],
            $args['offset']
        ));

        // Decode settings for each form.
        foreach ($forms as $form) {
            $form->settings = json_decode($form->settings, true);
        }

        return $forms;
    }

    /**
     * Update a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $data Form data to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $form_id, array $data): bool {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('forms');

        // Ensure updated_at is set.
        $data['updated_at'] = current_time('mysql');

        // Ensure settings is JSON.
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $form_id],
            ['%s'],
            ['%d']
        );

        if (false !== $result) {
            // Clear cache.
            wp_cache_delete("form_{$form_id}", self::CACHE_GROUP);

            /**
             * Fires after a form is updated.
             *
             * @since 1.0.0
             * @param int $form_id Form ID.
             * @param array $data Form data.
             */
            do_action('nexusforms_form_updated', $form_id, $data);

            return true;
        }

        return false;
    }

    /**
     * Delete a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $form_id): bool {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('forms');

        $result = $wpdb->delete($table, ['id' => $form_id], ['%d']);

        if ($result) {
            // Clear cache.
            wp_cache_delete("form_{$form_id}", self::CACHE_GROUP);

            /**
             * Fires after a form is deleted.
             *
             * @since 1.0.0
             * @param int $form_id Form ID.
             */
            do_action('nexusforms_form_deleted', $form_id);

            return true;
        }

        return false;
    }

    /**
     * Duplicate a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID to duplicate.
     * @return int|false New form ID on success, false on failure.
     */
    public function duplicate(int $form_id): int|false {
        $form = $this->get($form_id);

        if (!$form) {
            return false;
        }

        // Create new form.
        $new_form_id = $this->create([
            'title' => $form->title . ' (Copy)',
            'description' => $form->description,
            'status' => 'draft',
            'settings' => $form->settings,
        ]);

        if ($new_form_id) {
            // Duplicate fields.
            foreach ($form->fields as $field) {
                $this->add_field($new_form_id, $field->field_data, $field->field_order);
            }
        }

        return $new_form_id;
    }

    /**
     * Get form fields.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @return array Array of field objects.
     */
    public function get_fields(int $form_id): array {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('fields');

        $fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE form_id = %d ORDER BY field_order ASC",
            $form_id
        ));

        // Decode field data.
        foreach ($fields as $field) {
            $field->field_data = json_decode($field->field_data, true);
        }

        return $fields;
    }

    /**
     * Add a field to a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $field_data Field data.
     * @param int $field_order Field order.
     * @return int|false Field ID on success, false on failure.
     */
    public function add_field(int $form_id, array $field_data, int $field_order = 0): int|false {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('fields');

        $result = $wpdb->insert(
            $table,
            [
                'form_id' => $form_id,
                'field_data' => json_encode($field_data),
                'field_order' => $field_order,
            ],
            ['%d', '%s', '%d']
        );

        if ($result) {
            // Clear form cache.
            wp_cache_delete("form_{$form_id}", self::CACHE_GROUP);

            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Update form fields.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $fields Array of field data.
     * @return bool True on success, false on failure.
     */
    public function update_fields(int $form_id, array $fields): bool {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('fields');

        // Delete existing fields.
        $wpdb->delete($table, ['form_id' => $form_id], ['%d']);

        // Insert new fields.
        foreach ($fields as $order => $field_data) {
            $this->add_field($form_id, $field_data, $order);
        }

        // Clear form cache.
        wp_cache_delete("form_{$form_id}", self::CACHE_GROUP);

        return true;
    }

    /**
     * Get forms count.
     *
     * @since 1.0.0
     * @param string $status Form status.
     * @return int Number of forms.
     */
    public function get_count(string $status = ''): int {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('forms');

        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE status = %s",
                $status
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
}
