<?php
/**
 * Submissions management class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Submissions Class.
 *
 * Handles form submission (entry) operations.
 *
 * @since 1.0.0
 */
class NexusForms_Submissions {

    /**
     * Create a new submission.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $entry_data Entry data.
     * @param array $meta Additional metadata.
     * @return int|false Entry ID on success, false on failure.
     */
    public function create(int $form_id, array $entry_data, array $meta = []): int|false {
        global $wpdb;

        $table = NexusForms_Database::get_table_name('entries');

        $settings = get_option('nexusforms_settings', []);

        // Get form schema snapshot to preserve structure at time of submission
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

        $schema_snapshot = null;
        if ($form) {
            $schema_snapshot = json_encode([
                'title' => $form->title,
                'description' => $form->description,
                'settings' => $form->settings,
                'fields' => $form->fields,
                'version' => NEXUSFORMS_VERSION,
                'snapshot_time' => current_time('mysql'),
            ]);
        }

        $data = [
            'form_id' => $form_id,
            'entry_data' => json_encode($entry_data),
            'form_schema_snapshot' => $schema_snapshot,
            'user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_ip_address($settings),
            'user_agent' => $this->get_user_agent(),
            'status' => $meta['status'] ?? 'active',
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert(
            $table,
            $data,
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );

        if ($result) {
            $entry_id = $wpdb->insert_id;

            /**
             * Fires after a submission is created.
             *
             * @since 1.0.0
             * @param int $entry_id Entry ID.
             * @param int $form_id Form ID.
             * @param array $entry_data Entry data.
             */
            do_action('nexusforms_submission_created', $entry_id, $form_id, $entry_data);

            return $entry_id;
        }

        return false;
    }

    /**
     * Get a submission by ID.
     *
     * @since 1.0.0
     * @param int $entry_id Entry ID.
     * @return object|null Entry object or null if not found.
     */
    public function get(int $entry_id): ?object {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('entries');

        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $entry_id
        ));

        if ($entry) {
            $entry->entry_data = json_decode($entry->entry_data, true);
            $entry->form_schema_snapshot = $entry->form_schema_snapshot
                ? json_decode($entry->form_schema_snapshot)
                : null;
        }

        return $entry ?: null;
    }

    /**
     * Get all submissions for a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $args Query arguments.
     * @return array Array of entry objects.
     */
    public function get_by_form(int $form_id, array $args = []): array {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('entries');

        $defaults = [
            'status' => 'active',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = $wpdb->prepare("WHERE form_id = %d", $form_id);

        if ($args['status']) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }

        $query = "SELECT * FROM {$table}
                  {$where}
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT %d OFFSET %d";

        $entries = $wpdb->get_results($wpdb->prepare(
            $query,
            $args['limit'],
            $args['offset']
        ));

        // Decode entry data.
        foreach ($entries as $entry) {
            $entry->entry_data = json_decode($entry->entry_data, true);
        }

        return $entries;
    }

    /**
     * Update a submission.
     *
     * @since 1.0.0
     * @param int $entry_id Entry ID.
     * @param array $data Data to update.
     * @return bool True on success, false on failure.
     */
    public function update(int $entry_id, array $data): bool {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('entries');

        // Ensure entry_data is JSON.
        if (isset($data['entry_data']) && is_array($data['entry_data'])) {
            $data['entry_data'] = json_encode($data['entry_data']);
        }

        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $entry_id],
            null,
            ['%d']
        );

        if (false !== $result) {
            /**
             * Fires after a submission is updated.
             *
             * @since 1.0.0
             * @param int $entry_id Entry ID.
             * @param array $data Entry data.
             */
            do_action('nexusforms_submission_updated', $entry_id, $data);

            return true;
        }

        return false;
    }

    /**
     * Delete a submission.
     *
     * @since 1.0.0
     * @param int $entry_id Entry ID.
     * @return bool True on success, false on failure.
     */
    public function delete(int $entry_id): bool {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('entries');

        $result = $wpdb->delete($table, ['id' => $entry_id], ['%d']);

        if ($result) {
            /**
             * Fires after a submission is deleted.
             *
             * @since 1.0.0
             * @param int $entry_id Entry ID.
             */
            do_action('nexusforms_submission_deleted', $entry_id);

            return true;
        }

        return false;
    }

    /**
     * Get submissions count for a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param string $status Entry status.
     * @return int Number of entries.
     */
    public function get_count(int $form_id, string $status = ''): int {
        global $wpdb;
        $table = NexusForms_Database::get_table_name('entries');

        if ($status) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE form_id = %d AND status = %s",
                $form_id,
                $status
            ));
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE form_id = %d",
            $form_id
        ));
    }

    /**
     * Export submissions to CSV.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @return string CSV content.
     */
    public function export_to_csv(int $form_id): string {
        $entries = $this->get_by_form($form_id, ['limit' => 9999]);

        if (empty($entries)) {
            return '';
        }

        // Get form to get field labels.
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

        // Build CSV header with composite field support.
        $headers = ['Entry ID', 'Date'];
        $field_mapping = []; // Track field structure for data extraction

        foreach ($form->fields as $field) {
            $field_data = $field->field_data;
            $field_id = $field_data['id'];
            $field_type = $field_data['type'] ?? 'text';
            $label = $field_data['label'] ?? $field_id;

            // Handle composite fields with multiple columns
            if ($field_type === 'name') {
                $headers[] = $label . ' (First)';
                $headers[] = $label . ' (Last)';
                $field_mapping[] = ['id' => $field_id, 'type' => 'name'];
            } elseif ($field_type === 'address') {
                $headers[] = $label . ' (Street)';
                $headers[] = $label . ' (Street 2)';
                $headers[] = $label . ' (City)';
                $headers[] = $label . ' (State)';
                $headers[] = $label . ' (ZIP)';
                $headers[] = $label . ' (Country)';
                $field_mapping[] = ['id' => $field_id, 'type' => 'address'];
            } else {
                $headers[] = $label;
                $field_mapping[] = ['id' => $field_id, 'type' => $field_type];
            }
        }

        $headers[] = 'IP Address';
        $headers[] = 'User Agent';

        // Build CSV rows.
        $csv = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');
        fputcsv($csv, $headers);

        foreach ($entries as $entry) {
            $row = [$entry->id, $entry->created_at];

            foreach ($field_mapping as $field_info) {
                $field_id = $field_info['id'];
                $field_type = $field_info['type'];
                $value = $entry->entry_data[$field_id] ?? '';

                // Handle different field types
                if ($field_type === 'name' && is_array($value)) {
                    $row[] = $value['first'] ?? '';
                    $row[] = $value['last'] ?? '';
                } elseif ($field_type === 'address' && is_array($value)) {
                    $row[] = $value['street'] ?? '';
                    $row[] = $value['street2'] ?? '';
                    $row[] = $value['city'] ?? '';
                    $row[] = $value['state'] ?? '';
                    $row[] = $value['zip'] ?? '';
                    $row[] = $value['country'] ?? '';
                } elseif (is_array($value)) {
                    // Multi-value fields (checkbox, multi-select, list)
                    $row[] = $this->flatten_array_value($value);
                } else {
                    $row[] = $value;
                }
            }

            $row[] = $entry->ip_address;
            $row[] = $entry->user_agent;

            fputcsv($csv, $row);
        }

        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);

        return $output;
    }

    /**
     * Get user IP address.
     *
     * @since 1.0.0
     * @param array $settings Plugin settings.
     * @return string|null IP address or null.
     */
    private function get_ip_address(array $settings): ?string {
        if (isset($settings['store_ip_addresses']) && !$settings['store_ip_addresses']) {
            return null;
        }

        $ip_keys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs.
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }

    /**
     * Get user agent.
     *
     * @since 1.0.0
     * @return string|null User agent or null.
     */
    private function get_user_agent(): ?string {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Flatten array value for CSV export.
     *
     * Handles multi-dimensional arrays (like list fields) and simple arrays.
     *
     * @since 1.0.0
     * @param mixed $value Value to flatten.
     * @return string Flattened string.
     */
    private function flatten_array_value($value): string {
        if (!is_array($value)) {
            return (string) $value;
        }

        // Handle list field (array of arrays)
        if (is_array(reset($value))) {
            $rows = [];
            foreach ($value as $row) {
                if (is_array($row)) {
                    $rows[] = implode(' | ', array_map('strval', $row));
                } else {
                    $rows[] = (string) $row;
                }
            }
            return implode('; ', $rows);
        }

        // Handle simple array (checkbox, multi-select)
        return implode(', ', array_map('strval', $value));
    }
}
