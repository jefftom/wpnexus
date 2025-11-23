<?php
/**
 * Gravity Forms Importer
 *
 * Imports forms from Gravity Forms JSON export format.
 *
 * @package NexusForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class NexusForms_Gravity_Importer {

    /**
     * Import a Gravity Forms JSON export
     *
     * @param string $json_data Gravity Forms JSON data
     * @return array|WP_Error Import result or error
     */
    public function import(string $json_data): array|WP_Error {
        $data = json_decode($json_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON data.', 'nexusforms'));
        }

        // Gravity Forms export can be single form or array of forms
        $gf_forms = isset($data[0]) ? $data : [$data];

        $imported_forms = [];
        $errors = [];

        foreach ($gf_forms as $gf_form) {
            $result = $this->import_single_form($gf_form);

            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $imported_forms[] = $result;
            }
        }

        if (!empty($errors) && empty($imported_forms)) {
            return new WP_Error('import_failed', implode(', ', $errors));
        }

        return [
            'success' => true,
            'imported' => count($imported_forms),
            'forms' => $imported_forms,
            'errors' => $errors,
        ];
    }

    /**
     * Import a single Gravity Form
     *
     * @param array $gf_form Gravity Form data
     * @return int|WP_Error Form ID or error
     */
    private function import_single_form(array $gf_form): int|WP_Error {
        $forms_handler = new NexusForms_Forms();

        // Map Gravity Forms data to our structure
        $form_data = [
            'title' => $gf_form['title'] ?? __('Imported Form', 'nexusforms'),
            'description' => $gf_form['description'] ?? '',
            'status' => ($gf_form['is_active'] ?? true) ? 'active' : 'inactive',
            'settings' => $this->map_form_settings($gf_form),
        ];

        // Create the form
        $form_id = $forms_handler->create($form_data);

        if (!$form_id) {
            return new WP_Error('form_creation_failed', __('Failed to create form.', 'nexusforms'));
        }

        // Import fields
        if (!empty($gf_form['fields'])) {
            $fields = $this->map_fields($gf_form['fields']);

            foreach ($fields as $field) {
                $forms_handler->add_field($form_id, $field);
            }
        }

        // Import notifications (email settings)
        if (!empty($gf_form['notifications'])) {
            $this->import_notifications($form_id, $gf_form['notifications']);
        }

        return $form_id;
    }

    /**
     * Map Gravity Forms settings to our format
     *
     * @param array $gf_form Gravity Form data
     * @return array Mapped settings
     */
    private function map_form_settings(array $gf_form): array {
        return [
            'confirmation_message' => $gf_form['confirmation']['message'] ?? __('Thank you for your submission!', 'nexusforms'),
            'submit_button_text' => $gf_form['button']['text'] ?? __('Submit', 'nexusforms'),
            'enable_ajax' => !($gf_form['enableAnimation'] ?? false),
            'imported_from' => 'gravity_forms',
            'original_id' => $gf_form['id'] ?? null,
        ];
    }

    /**
     * Map Gravity Forms fields to our format
     *
     * @param array $gf_fields Gravity Forms fields
     * @return array Mapped fields
     */
    private function map_fields(array $gf_fields): array {
        $mapped_fields = [];

        foreach ($gf_fields as $gf_field) {
            $field = $this->map_single_field($gf_field);

            if ($field) {
                $mapped_fields[] = $field;
            }
        }

        return $mapped_fields;
    }

    /**
     * Map a single Gravity Forms field
     *
     * @param array $gf_field Gravity Forms field
     * @return array|null Mapped field or null if unsupported
     */
    private function map_single_field(array $gf_field): ?array {
        $type = $gf_field['type'] ?? 'text';
        $input_type = $gf_field['inputType'] ?? null;

        // Map Gravity Forms field type to our type
        $our_type = $this->map_field_type($type, $input_type);

        if (!$our_type) {
            // Unsupported field type
            return null;
        }

        $field = [
            'id' => 'field_' . uniqid(),
            'type' => $our_type,
            'label' => $gf_field['label'] ?? '',
            'description' => $gf_field['description'] ?? '',
            'required' => $gf_field['isRequired'] ?? false,
            'placeholder' => $gf_field['placeholder'] ?? '',
            'defaultValue' => $gf_field['defaultValue'] ?? '',
            'cssClass' => $gf_field['cssClass'] ?? '',
        ];

        // Handle choice fields (select, radio, checkbox)
        if (in_array($our_type, ['select', 'radio', 'checkbox'])) {
            $field['options'] = $this->map_choices($gf_field['choices'] ?? []);
        }

        // Handle file upload
        if ($our_type === 'file') {
            $field['allowedFileTypes'] = $this->map_allowed_file_types($gf_field);
            $field['maxFileSize'] = $this->map_max_file_size($gf_field);
            $field['multiple'] = ($gf_field['multipleFiles'] ?? false);
        }

        // Handle validation
        if (!empty($gf_field['validation'])) {
            $field = array_merge($field, $this->map_validation($gf_field['validation']));
        }

        // Handle conditional logic
        if (!empty($gf_field['conditionalLogic'])) {
            $field['conditionalLogic'] = $this->map_conditional_logic($gf_field['conditionalLogic']);
        }

        return $field;
    }

    /**
     * Map Gravity Forms field type to our type
     *
     * @param string $gf_type Gravity Forms type
     * @param string|null $input_type Input type
     * @return string|null Our field type or null if unsupported
     */
    private function map_field_type(string $gf_type, ?string $input_type): ?string {
        // Direct mappings
        $type_map = [
            'text' => 'text',
            'textarea' => 'textarea',
            'select' => 'select',
            'multiselect' => 'select', // We'll mark as multiple
            'number' => 'number',
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'email' => 'email',
            'website' => 'url',
            'phone' => 'tel',
            'fileupload' => 'file',
        ];

        if (isset($type_map[$gf_type])) {
            return $type_map[$gf_type];
        }

        // Handle special cases
        if ($gf_type === 'text' && $input_type === 'email') {
            return 'email';
        }

        if ($gf_type === 'text' && $input_type === 'tel') {
            return 'tel';
        }

        // Unsupported types: section, page, html, captcha, etc.
        // These will be skipped
        return null;
    }

    /**
     * Map Gravity Forms choices to our options format
     *
     * @param array $gf_choices Gravity Forms choices
     * @return array Mapped options
     */
    private function map_choices(array $gf_choices): array {
        $options = [];

        foreach ($gf_choices as $choice) {
            $options[] = [
                'label' => $choice['text'] ?? $choice['value'] ?? '',
                'value' => $choice['value'] ?? $choice['text'] ?? '',
            ];
        }

        return $options;
    }

    /**
     * Map allowed file types
     *
     * @param array $gf_field Gravity Forms field
     * @return array Allowed file extensions
     */
    private function map_allowed_file_types(array $gf_field): array {
        $allowed = [];

        if (!empty($gf_field['allowedExtensions'])) {
            $extensions = explode(',', $gf_field['allowedExtensions']);
            foreach ($extensions as $ext) {
                $allowed[] = trim($ext);
            }
        }

        return $allowed ?: ['jpg', 'png', 'pdf', 'doc', 'docx'];
    }

    /**
     * Map max file size
     *
     * @param array $gf_field Gravity Forms field
     * @return int Max file size in bytes
     */
    private function map_max_file_size(array $gf_field): int {
        // Gravity Forms stores in MB
        $max_mb = $gf_field['maxFileSize'] ?? 10;
        return $max_mb * 1048576; // Convert to bytes
    }

    /**
     * Map validation rules
     *
     * @param array $validation Gravity Forms validation
     * @return array Mapped validation
     */
    private function map_validation(array $validation): array {
        $rules = [];

        if (!empty($validation['minLength'])) {
            $rules['min_length'] = $validation['minLength'];
        }

        if (!empty($validation['maxLength'])) {
            $rules['max_length'] = $validation['maxLength'];
        }

        return $rules;
    }

    /**
     * Map conditional logic
     *
     * @param array $gf_logic Gravity Forms conditional logic
     * @return array Mapped conditional logic
     */
    private function map_conditional_logic(array $gf_logic): array {
        return [
            'enabled' => true,
            'action' => ($gf_logic['actionType'] ?? 'show') === 'show' ? 'show' : 'hide',
            'logic' => ($gf_logic['logicType'] ?? 'all') === 'all' ? 'all' : 'any',
            'rules' => $this->map_logic_rules($gf_logic['rules'] ?? []),
        ];
    }

    /**
     * Map conditional logic rules
     *
     * @param array $gf_rules Gravity Forms rules
     * @return array Mapped rules
     */
    private function map_logic_rules(array $gf_rules): array {
        $rules = [];

        foreach ($gf_rules as $gf_rule) {
            $rules[] = [
                'field' => 'field_' . ($gf_rule['fieldId'] ?? ''),
                'operator' => $this->map_operator($gf_rule['operator'] ?? 'is'),
                'value' => $gf_rule['value'] ?? '',
            ];
        }

        return $rules;
    }

    /**
     * Map conditional logic operator
     *
     * @param string $gf_operator Gravity Forms operator
     * @return string Our operator
     */
    private function map_operator(string $gf_operator): string {
        $operator_map = [
            'is' => 'is',
            'isnot' => 'is_not',
            'contains' => 'contains',
            '>' => 'greater_than',
            '<' => 'less_than',
        ];

        return $operator_map[$gf_operator] ?? 'is';
    }

    /**
     * Import notifications
     *
     * @param int $form_id Form ID
     * @param array $gf_notifications Gravity Forms notifications
     * @return void
     */
    private function import_notifications(int $form_id, array $gf_notifications): void {
        $forms_handler = new NexusForms_Forms();
        $form = $forms_handler->get($form_id);

        if (!$form) {
            return;
        }

        // Get the first active notification
        foreach ($gf_notifications as $gf_notif) {
            if ($gf_notif['isActive'] ?? true) {
                $email_settings = [
                    'enabled' => true,
                    'to' => $gf_notif['to'] ?? '{admin_email}',
                    'from' => $gf_notif['from'] ?? '{admin_email}',
                    'fromName' => $gf_notif['fromName'] ?? '{site_name}',
                    'replyTo' => $gf_notif['replyTo'] ?? '',
                    'subject' => $gf_notif['subject'] ?? 'New form submission',
                    'body' => $gf_notif['message'] ?? '{all_fields}',
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
