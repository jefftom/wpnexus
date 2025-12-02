<?php
/**
 * Form validator class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Validator Class.
 *
 * Handles form submission validation.
 *
 * @since 1.0.0
 */
class NexusForms_Validator {

    /**
     * Validate form submission.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $data Submitted data.
     * @return array Array of validation errors.
     */
    public function validate_submission(int $form_id, array $data): array {
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

        if (!$form) {
            return ['form' => __('Invalid form.', 'nexusforms')];
        }

        $errors = [];

        foreach ($form->fields as $field) {
            $field_data = $field->field_data;
            $field_id = $field_data['id'];
            $value = $data[$field_id] ?? '';

            // Skip validation if field is hidden by conditional logic.
            if ($this->is_field_hidden_by_conditional_logic($field_data, $data, $form->fields)) {
                continue;
            }

            // Required validation.
            if (!empty($field_data['required']) && empty($value)) {
                $errors[$field_id] = sprintf(
                    __('%s is required.', 'nexusforms'),
                    $field_data['label'] ?? __('This field', 'nexusforms')
                );
                continue;
            }

            // Skip further validation if field is empty and not required.
            if (empty($value)) {
                continue;
            }

            // Type-specific validation.
            $field_errors = $this->validate_field_type($field_data, $value);
            if (!empty($field_errors)) {
                $errors[$field_id] = $field_errors;
            }

            // Custom validation rules.
            $custom_errors = $this->validate_custom_rules($field_data, $value);
            if (!empty($custom_errors)) {
                $errors[$field_id] = $custom_errors;
            }
        }

        /**
         * Filter validation errors.
         *
         * @since 1.0.0
         * @param array $errors Validation errors.
         * @param int $form_id Form ID.
         * @param array $data Submitted data.
         */
        return apply_filters('nexusforms_validation_errors', $errors, $form_id, $data);
    }

    /**
     * Validate field by type.
     *
     * @since 1.0.0
     * @param array $field Field data.
     * @param mixed $value Field value.
     * @return string Validation error or empty string.
     */
    private function validate_field_type(array $field, mixed $value): string {
        $type = $field['type'] ?? 'text';
        $label = $field['label'] ?? __('This field', 'nexusforms');

        switch ($type) {
            case 'email':
                if (!is_email($value)) {
                    return sprintf(
                        __('%s must be a valid email address.', 'nexusforms'),
                        $label
                    );
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return sprintf(
                        __('%s must be a valid URL.', 'nexusforms'),
                        $label
                    );
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return sprintf(
                        __('%s must be a number.', 'nexusforms'),
                        $label
                    );
                }

                // Min/max validation.
                if (isset($field['min']) && $value < $field['min']) {
                    return sprintf(
                        __('%s must be at least %s.', 'nexusforms'),
                        $label,
                        $field['min']
                    );
                }

                if (isset($field['max']) && $value > $field['max']) {
                    return sprintf(
                        __('%s must be at most %s.', 'nexusforms'),
                        $label,
                        $field['max']
                    );
                }
                break;

            case 'tel':
                // Basic phone validation.
                $cleaned = preg_replace('/[^0-9+]/', '', $value);
                if (strlen($cleaned) < 10) {
                    return sprintf(
                        __('%s must be a valid phone number.', 'nexusforms'),
                        $label
                    );
                }
                break;

            case 'checkbox':
                if (isset($field['min_selections'])) {
                    $selections = is_array($value) ? count($value) : 0;
                    if ($selections < $field['min_selections']) {
                        return sprintf(
                            __('Please select at least %s options for %s.', 'nexusforms'),
                            $field['min_selections'],
                            $label
                        );
                    }
                }

                if (isset($field['max_selections'])) {
                    $selections = is_array($value) ? count($value) : 0;
                    if ($selections > $field['max_selections']) {
                        return sprintf(
                            __('Please select at most %s options for %s.', 'nexusforms'),
                            $field['max_selections'],
                            $label
                        );
                    }
                }
                break;

            case 'file':
                // File upload validation (if implemented).
                if (isset($_FILES[$field['id']])) {
                    $file = $_FILES[$field['id']];

                    // Check file size.
                    if (isset($field['max_size'])) {
                        $max_size = $field['max_size'] * 1024 * 1024; // Convert MB to bytes.
                        if ($file['size'] > $max_size) {
                            return sprintf(
                                __('File size must be less than %s MB.', 'nexusforms'),
                                $field['max_size']
                            );
                        }
                    }

                    // Check file type.
                    if (isset($field['allowed_types'])) {
                        $file_type = wp_check_filetype($file['name']);
                        if (!in_array($file_type['ext'], $field['allowed_types'])) {
                            return sprintf(
                                __('File type must be one of: %s.', 'nexusforms'),
                                implode(', ', $field['allowed_types'])
                            );
                        }
                    }
                }
                break;
        }

        return '';
    }

    /**
     * Validate custom rules.
     *
     * @since 1.0.0
     * @param array $field Field data.
     * @param mixed $value Field value.
     * @return string Validation error or empty string.
     */
    private function validate_custom_rules(array $field, mixed $value): string {
        $label = $field['label'] ?? __('This field', 'nexusforms');

        // Min length.
        if (isset($field['min_length'])) {
            if (strlen($value) < $field['min_length']) {
                return sprintf(
                    __('%s must be at least %s characters.', 'nexusforms'),
                    $label,
                    $field['min_length']
                );
            }
        }

        // Max length.
        if (isset($field['max_length'])) {
            if (strlen($value) > $field['max_length']) {
                return sprintf(
                    __('%s must be at most %s characters.', 'nexusforms'),
                    $label,
                    $field['max_length']
                );
            }
        }

        // Pattern matching.
        if (isset($field['pattern'])) {
            if (!preg_match('/' . $field['pattern'] . '/', $value)) {
                return $field['pattern_error'] ?? sprintf(
                    __('%s format is invalid.', 'nexusforms'),
                    $label
                );
            }
        }

        /**
         * Custom field validation hook.
         *
         * @since 1.0.0
         * @param string $error Error message.
         * @param array $field Field data.
         * @param mixed $value Field value.
         */
        return apply_filters('nexusforms_validate_field', '', $field, $value);
    }

    /**
     * Sanitize field value based on type.
     *
     * @since 1.0.0
     * @param string $type Field type.
     * @param mixed $value Field value.
     * @return mixed Sanitized value.
     */
    public function sanitize_field(string $type, mixed $value): mixed {
        switch ($type) {
            case 'email':
                return sanitize_email($value);

            case 'url':
                return esc_url_raw($value);

            case 'number':
                return floatval($value);

            case 'textarea':
                return sanitize_textarea_field($value);

            case 'checkbox':
                return is_array($value)
                    ? array_map('sanitize_text_field', $value)
                    : sanitize_text_field($value);

            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Check if field is hidden by conditional logic.
     *
     * @since 1.0.0
     * @param array $field_data Field data.
     * @param array $submitted_data All submitted form data.
     * @param array $all_fields All form fields.
     * @return bool True if field is hidden, false otherwise.
     */
    private function is_field_hidden_by_conditional_logic(array $field_data, array $submitted_data, array $all_fields): bool {
        // Check if field has conditional logic enabled.
        if (empty($field_data['conditionalLogic']) || empty($field_data['conditionalLogic']['enabled'])) {
            return false;
        }

        $conditional_logic = $field_data['conditionalLogic'];
        $action = $conditional_logic['action'] ?? 'show';
        $logic = $conditional_logic['logic'] ?? 'all';
        $rules = $conditional_logic['rules'] ?? [];

        if (empty($rules)) {
            return false;
        }

        // Evaluate all rules.
        $results = array_map(function($rule) use ($submitted_data, $all_fields) {
            return $this->evaluate_conditional_rule($rule, $submitted_data, $all_fields);
        }, $rules);

        // Determine if conditions are met.
        $conditions_met = $logic === 'all'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);

        // Determine if field should be shown or hidden.
        $should_show = ($action === 'show' && $conditions_met) || ($action === 'hide' && !$conditions_met);

        return !$should_show; // Return true if field is hidden.
    }

    /**
     * Evaluate a single conditional logic rule.
     *
     * @since 1.0.0
     * @param array $rule Conditional logic rule.
     * @param array $submitted_data All submitted form data.
     * @param array $all_fields All form fields.
     * @return bool True if rule matches, false otherwise.
     */
    private function evaluate_conditional_rule(array $rule, array $submitted_data, array $all_fields): bool {
        $field_id = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? 'is';
        $expected_value = $rule['value'] ?? '';

        // Get the actual value from submitted data.
        $actual_value = $submitted_data[$field_id] ?? '';

        // Evaluate based on operator.
        switch ($operator) {
            case 'is':
                return $actual_value == $expected_value;

            case 'is_not':
                return $actual_value != $expected_value;

            case 'contains':
                return is_string($actual_value) && strpos($actual_value, $expected_value) !== false;

            case 'starts_with':
                return is_string($actual_value) && strpos($actual_value, $expected_value) === 0;

            case 'ends_with':
                return is_string($actual_value) && substr($actual_value, -strlen($expected_value)) === $expected_value;

            case 'greater_than':
                return is_numeric($actual_value) && floatval($actual_value) > floatval($expected_value);

            case 'less_than':
                return is_numeric($actual_value) && floatval($actual_value) < floatval($expected_value);

            case 'is_empty':
            case 'empty':
                return empty($actual_value);

            case 'is_not_empty':
            case 'not_empty':
                return !empty($actual_value);

            default:
                return false;
        }
    }
}
