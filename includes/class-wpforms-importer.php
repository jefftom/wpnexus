<?php
/**
 * WPForms Importer
 *
 * Imports forms from WPForms JSON export format.
 *
 * @package NexusForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class NexusForms_WPForms_Importer {

    /**
     * Import a WPForms JSON export
     *
     * @param string $json_data WPForms JSON data
     * @return array|WP_Error Import result or error
     */
    public function import(string $json_data): array|WP_Error {
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON data.', 'nexusforms'));
        }

        $result = $this->import_single_form($data);

        if (is_wp_error($result)) {
            return $result;
        }

        return [
            'success' => true,
            'imported' => 1,
            'forms' => [$result],
            'errors' => [],
        ];
    }

    /**
     * Import a single WPForms form
     *
     * @param array $wpf_form WPForms form data
     * @return int|WP_Error Form ID or error
     */
    private function import_single_form(array $wpf_form): int|WP_Error {
        $forms_handler = new NexusForms_Forms();

        // Map WPForms data to our structure
        $form_data = [
            'title' => $wpf_form['settings']['form_title'] ?? __('Imported Form', 'nexusforms'),
            'description' => $wpf_form['settings']['form_desc'] ?? '',
            'status' => 'active',
            'settings' => $this->map_form_settings($wpf_form['settings'] ?? []),
        ];

        // Create the form
        $form_id = $forms_handler->create($form_data);

        if (!$form_id) {
            return new WP_Error('form_creation_failed', __('Failed to create form.', 'nexusforms'));
        }

        // Import fields
        if (!empty($wpf_form['fields'])) {
            $fields = $this->map_fields($wpf_form['fields']);

            foreach ($fields as $field) {
                $forms_handler->add_field($form_id, $field);
            }
        }

        // Import notification settings
        if (!empty($wpf_form['settings'])) {
            $this->import_notifications($form_id, $wpf_form['settings']);
        }

        return $form_id;
    }

    /**
     * Map WPForms settings to our format
     *
     * @param array $wpf_settings WPForms settings
     * @return array Mapped settings
     */
    private function map_form_settings(array $wpf_settings): array {
        return [
            'confirmation_message' => $wpf_settings['confirmation_message'] ?? __('Thank you for your submission!', 'nexusforms'),
            'submit_button_text' => $wpf_settings['submit_text'] ?? __('Submit', 'nexusforms'),
            'enable_ajax' => ($wpf_settings['ajax_submit'] ?? '1') === '1',
            'imported_from' => 'wpforms',
        ];
    }

    /**
     * Map WPForms fields to our format
     *
     * @param array $wpf_fields WPForms fields
     * @return array Mapped fields
     */
    private function map_fields(array $wpf_fields): array {
        $mapped_fields = [];

        foreach ($wpf_fields as $wpf_field) {
            $field = $this->map_single_field($wpf_field);

            if ($field) {
                $mapped_fields[] = $field;
            }
        }

        return $mapped_fields;
    }

    /**
     * Map a single WPForms field
     *
     * @param array $wpf_field WPForms field
     * @return array|null Mapped field or null if unsupported
     */
    private function map_single_field(array $wpf_field): ?array {
        $type = $wpf_field['type'] ?? 'text';

        // Map WPForms field type to our type
        $our_type = $this->map_field_type($type);

        if (!$our_type) {
            // Unsupported field type
            return null;
        }

        $field = [
            'id' => 'field_' . uniqid(),
            'type' => $our_type,
            'label' => $wpf_field['label'] ?? '',
            'description' => $wpf_field['description'] ?? '',
            'required' => ($wpf_field['required'] ?? '0') === '1',
            'placeholder' => $wpf_field['placeholder'] ?? '',
            'defaultValue' => $wpf_field['default_value'] ?? '',
            'cssClass' => $wpf_field['css'] ?? '',
        ];

        // Handle choice fields
        if (in_array($our_type, ['select', 'radio', 'checkbox'])) {
            $field['options'] = $this->map_choices($wpf_field['choices'] ?? []);
        }

        // Handle file upload
        if ($our_type === 'file') {
            $field['allowedFileTypes'] = $this->map_allowed_file_types($wpf_field);
            $field['maxFileSize'] = $this->map_max_file_size($wpf_field);
            $field['multiple'] = !empty($wpf_field['max_file_number']) && $wpf_field['max_file_number'] > 1;
        }

        // Handle validation
        if (!empty($wpf_field['size'])) {
            $field['size'] = $wpf_field['size'];
        }

        return $field;
    }

    /**
     * Map WPForms field type to our type
     *
     * @param string $wpf_type WPForms type
     * @return string|null Our field type or null if unsupported
     */
    private function map_field_type(string $wpf_type): ?string {
        $type_map = [
            'text' => 'text',
            'textarea' => 'textarea',
            'select' => 'select',
            'radio' => 'radio',
            'checkbox' => 'checkbox',
            'number' => 'number',
            'email' => 'email',
            'url' => 'url',
            'phone' => 'tel',
            'file-upload' => 'file',
            'name' => 'text', // Simple name field maps to text
            'address' => 'textarea', // Address maps to textarea
        ];

        return $type_map[$wpf_type] ?? null;
    }

    /**
     * Map WPForms choices to our options format
     *
     * @param array $wpf_choices WPForms choices
     * @return array Mapped options
     */
    private function map_choices(array $wpf_choices): array {
        $options = [];

        foreach ($wpf_choices as $choice) {
            $options[] = [
                'label' => $choice['label'] ?? $choice['value'] ?? '',
                'value' => $choice['value'] ?? $choice['label'] ?? '',
            ];
        }

        return $options;
    }

    /**
     * Map allowed file types
     *
     * @param array $wpf_field WPForms field
     * @return array Allowed file extensions
     */
    private function map_allowed_file_types(array $wpf_field): array {
        $allowed = [];

        if (!empty($wpf_field['extensions'])) {
            $extensions = explode(',', $wpf_field['extensions']);
            foreach ($extensions as $ext) {
                $allowed[] = trim($ext);
            }
        }

        return $allowed ?: ['jpg', 'png', 'pdf', 'doc', 'docx'];
    }

    /**
     * Map max file size
     *
     * @param array $wpf_field WPForms field
     * @return int Max file size in bytes
     */
    private function map_max_file_size(array $wpf_field): int {
        // WPForms stores in MB
        $max_mb = $wpf_field['max_size'] ?? 10;
        return $max_mb * 1048576; // Convert to bytes
    }

    /**
     * Import notifications
     *
     * @param int $form_id Form ID
     * @param array $wpf_settings WPForms settings
     * @return void
     */
    private function import_notifications(int $form_id, array $wpf_settings): void {
        $forms_handler = new NexusForms_Forms();
        $form = $forms_handler->get($form_id);

        if (!$form) {
            return;
        }

        // WPForms stores notifications in settings
        $notifications = $wpf_settings['notifications'] ?? [];

        foreach ($notifications as $wpf_notif) {
            if (!empty($wpf_notif['email'])) {
                $email_settings = [
                    'enabled' => true,
                    'to' => $wpf_notif['email'] ?? '{admin_email}',
                    'from' => $wpf_notif['from_email'] ?? '{admin_email}',
                    'fromName' => $wpf_notif['from_name'] ?? '{site_name}',
                    'replyTo' => $wpf_notif['reply_to'] ?? '',
                    'subject' => $wpf_notif['subject'] ?? 'New form submission',
                    'body' => $wpf_notif['message'] ?? '{all_fields}',
                    'format' => 'html',
                ];

                // Update form settings with email
                $settings = $form->settings;
                $settings['email'] = $email_settings;

                $forms_handler->update($form_id, ['settings' => $settings]);
                break; // Only import first notification for now
            }
        }
    }
}
