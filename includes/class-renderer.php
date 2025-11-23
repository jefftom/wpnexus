<?php
/**
 * Form renderer class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Renderer Class.
 *
 * Handles frontend form rendering and shortcodes.
 *
 * @since 1.0.0
 */
class NexusForms_Renderer {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_shortcode('nexusforms', [$this, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
        add_action('wp_ajax_nexusforms_submit', [$this, 'handle_ajax_submission']);
        add_action('wp_ajax_nopriv_nexusforms_submit', [$this, 'handle_ajax_submission']);
    }

    /**
     * Render form shortcode.
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes.
     * @return string Form HTML.
     */
    public function render_shortcode(array $atts): string {
        $atts = shortcode_atts([
            'id' => 0,
            'title' => true,
            'description' => true,
            'ajax' => true,
        ], $atts, 'nexusforms');

        $form_id = (int) $atts['id'];

        if (!$form_id) {
            return '<p>' . esc_html__('Please specify a form ID.', 'nexusforms') . '</p>';
        }

        return $this->render_form($form_id, $atts);
    }

    /**
     * Render a form.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $args Rendering arguments.
     * @return string Form HTML.
     */
    public function render_form(int $form_id, array $args = []): string {
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

        if (!$form || $form->status !== 'active') {
            return '<p>' . esc_html__('Form not found.', 'nexusforms') . '</p>';
        }

        $defaults = [
            'title' => true,
            'description' => true,
            'ajax' => true,
        ];

        $args = wp_parse_args($args, $defaults);

        // Enqueue assets.
        $this->enqueue_form_assets($form_id, $args);

        // Build form HTML.
        ob_start();
        include NEXUSFORMS_PLUGIN_DIR . 'templates/form.php';
        $html = ob_get_clean();

        /**
         * Filter the form HTML output.
         *
         * @since 1.0.0
         * @param string $html Form HTML.
         * @param object $form Form object.
         * @param array $args Rendering arguments.
         */
        return apply_filters('nexusforms_form_html', $html, $form, $args);
    }

    /**
     * Conditionally enqueue assets on pages with forms.
     *
     * @since 1.0.0
     */
    public function maybe_enqueue_assets(): void {
        global $post;

        // Check if the page has the shortcode.
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'nexusforms')) {
            // Assets will be enqueued by render_form.
            return;
        }

        // Check if any page builder is rendering forms.
        if (did_action('elementor/frontend/after_enqueue_scripts')) {
            // Elementor is active, enqueue conditionally.
            return;
        }
    }

    /**
     * Enqueue form assets.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $args Rendering arguments.
     */
    private function enqueue_form_assets(int $form_id, array $args): void {
        $settings = get_option('nexusforms_settings', []);

        // Enqueue styles.
        if (!isset($settings['disable_css']) || !$settings['disable_css']) {
            wp_enqueue_style(
                'nexusforms-styles',
                NEXUSFORMS_PLUGIN_URL . 'assets/build/frontend.css',
                [],
                NEXUSFORMS_VERSION
            );
        }

        // Enqueue scripts.
        wp_enqueue_script(
            'nexusforms-frontend',
            NEXUSFORMS_PLUGIN_URL . 'assets/build/frontend.js',
            ['jquery'],
            NEXUSFORMS_VERSION,
            true
        );

        // Localize script.
        wp_localize_script('nexusforms-frontend', 'nexusformsData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nexusforms_submit_' . $form_id),
            'formId' => $form_id,
            'ajax' => $args['ajax'],
            'i18n' => [
                'submitting' => __('Submitting...', 'nexusforms'),
                'success' => __('Form submitted successfully!', 'nexusforms'),
                'error' => __('An error occurred. Please try again.', 'nexusforms'),
                'required' => __('This field is required.', 'nexusforms'),
                'invalidEmail' => __('Please enter a valid email address.', 'nexusforms'),
            ],
        ]);
    }

    /**
     * Handle AJAX form submission.
     *
     * @since 1.0.0
     */
    public function handle_ajax_submission(): void {
        // Verify nonce.
        $form_id = (int) ($_POST['form_id'] ?? 0);
        $nonce = $_POST['_wpnonce'] ?? '';

        if (!wp_verify_nonce($nonce, 'nexusforms_submit_' . $form_id)) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'nexusforms'),
            ], 403);
        }

        // Honeypot check (spam protection).
        if (!empty($_POST['nexusforms_hp'])) {
            wp_send_json_error([
                'message' => __('Spam detected.', 'nexusforms'),
            ], 400);
        }

        // Validate submission.
        $validator = new NexusForms_Validator();
        $errors = $validator->validate_submission($form_id, $_POST);

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => __('Please fix the errors below.', 'nexusforms'),
                'errors' => $errors,
            ], 400);
        }

        // Sanitize and prepare data.
        $entry_data = $this->prepare_entry_data($form_id, $_POST);

        // Create submission.
        $submissions = new NexusForms_Submissions();
        $entry_id = $submissions->create($form_id, $entry_data);

        if ($entry_id) {
            // Send notifications.
            $notifications = new NexusForms_Notifications();
            $notifications->send($form_id, $entry_id, $entry_data);

            /**
             * Fires after a form is successfully submitted.
             *
             * @since 1.0.0
             * @param int $entry_id Entry ID.
             * @param int $form_id Form ID.
             * @param array $entry_data Entry data.
             */
            do_action('nexusforms_after_submission', $entry_id, $form_id, $entry_data);

            wp_send_json_success([
                'message' => __('Form submitted successfully!', 'nexusforms'),
                'entry_id' => $entry_id,
            ]);
        } else {
            wp_send_json_error([
                'message' => __('An error occurred while saving your submission.', 'nexusforms'),
            ], 500);
        }
    }

    /**
     * Prepare entry data from POST.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $post_data POST data.
     * @return array Sanitized entry data.
     */
    private function prepare_entry_data(int $form_id, array $post_data): array {
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

        $entry_data = [];
        $file_handler = nexusforms()->file_handler;

        // First pass: Create entry ID for file uploads
        // We'll use a temporary ID of 0 and update files later if needed
        $entry_id = 0;

        foreach ($form->fields as $field) {
            $field_id = $field->field_data['id'];
            $field_type = $field->field_data['type'];
            $value = $post_data[$field_id] ?? '';

            // Sanitize based on field type.
            switch ($field_type) {
                case 'email':
                    $entry_data[$field_id] = sanitize_email($value);
                    break;
                case 'url':
                    $entry_data[$field_id] = esc_url_raw($value);
                    break;
                case 'number':
                    $entry_data[$field_id] = floatval($value);
                    break;
                case 'textarea':
                    $entry_data[$field_id] = sanitize_textarea_field($value);
                    break;
                case 'checkbox':
                    $entry_data[$field_id] = is_array($value)
                        ? array_map('sanitize_text_field', $value)
                        : sanitize_text_field($value);
                    break;
                case 'file':
                    // Handle file upload
                    if (isset($_FILES[$field_id]) && !empty($_FILES[$field_id]['name'])) {
                        $field_settings = $field->field_data;

                        // Check if multiple files
                        if (is_array($_FILES[$field_id]['name'])) {
                            $upload_results = $file_handler->handle_multiple_uploads(
                                $_FILES[$field_id],
                                $form_id,
                                $entry_id,
                                $field_settings
                            );
                        } else {
                            $upload_result = $file_handler->handle_upload(
                                $_FILES[$field_id],
                                $form_id,
                                $entry_id,
                                $field_settings
                            );
                            $upload_results = $upload_result ? [$upload_result] : [];
                        }

                        // Store file information
                        $entry_data[$field_id] = [];
                        foreach ($upload_results as $result) {
                            if (isset($result['success']) && $result['success']) {
                                $entry_data[$field_id][] = [
                                    'filename' => $result['filename'],
                                    'original_name' => $result['original_name'],
                                    'url' => $result['url'],
                                    'size' => $result['size'],
                                    'type' => $result['type'],
                                ];
                            }
                        }
                    } else {
                        $entry_data[$field_id] = [];
                    }
                    break;
                default:
                    $entry_data[$field_id] = sanitize_text_field($value);
            }
        }

        return $entry_data;
    }

    /**
     * Build field HTML.
     *
     * @since 1.0.0
     * @param array $field Field data.
     * @param int $form_id Form ID.
     * @return string Field HTML.
     */
    public function build_field_html(array $field, int $form_id): string {
        $field_type = $field['type'] ?? 'text';
        $field_id = $field['id'] ?? '';
        $field_label = $field['label'] ?? '';
        $field_placeholder = $field['placeholder'] ?? '';
        $field_required = $field['required'] ?? false;
        $field_description = $field['description'] ?? '';

        $html = '<div class="nexusforms-field nexusforms-field-' . esc_attr($field_type) . '">';

        // Label.
        if ($field_label) {
            $html .= sprintf(
                '<label for="%s" id="%s-label" class="nexusforms-label">%s%s</label>',
                esc_attr($field_id),
                esc_attr($field_id),
                esc_html($field_label),
                $field_required ? ' <span class="required" aria-label="required">*</span>' : ''
            );
        }

        // Field input.
        $html .= $this->get_field_input($field, $form_id);

        // Description.
        if ($field_description) {
            $html .= sprintf(
                '<p class="nexusforms-description" id="%s-description">%s</p>',
                esc_attr($field_id),
                esc_html($field_description)
            );
        }

        // Error container.
        $html .= sprintf(
            '<span id="%s-error" class="nexusforms-error" role="alert" aria-live="polite"></span>',
            esc_attr($field_id)
        );

        $html .= '</div>';

        return $html;
    }

    /**
     * Get field input HTML.
     *
     * @since 1.0.0
     * @param array $field Field data.
     * @param int $form_id Form ID.
     * @return string Input HTML.
     */
    private function get_field_input(array $field, int $form_id): string {
        $type = $field['type'] ?? 'text';
        $id = $field['id'] ?? '';
        $name = $id;
        $required = $field['required'] ?? false;
        $placeholder = $field['placeholder'] ?? '';
        $description = $field['description'] ?? '';

        $attrs = sprintf(
            'id="%s" name="%s" aria-labelledby="%s-label" %s %s %s',
            esc_attr($id),
            esc_attr($name),
            esc_attr($id),
            $required ? 'required aria-required="true"' : '',
            $placeholder ? 'placeholder="' . esc_attr($placeholder) . '"' : '',
            $description ? 'aria-describedby="' . esc_attr($id) . '-description"' : ''
        );

        switch ($type) {
            case 'textarea':
                return sprintf(
                    '<textarea %s class="nexusforms-input nexusforms-textarea"></textarea>',
                    $attrs
                );

            case 'select':
                $options = $field['options'] ?? [];
                $html = sprintf('<select %s class="nexusforms-input nexusforms-select">', $attrs);
                $html .= '<option value="">' . esc_html__('Select...', 'nexusforms') . '</option>';
                foreach ($options as $option) {
                    $html .= sprintf(
                        '<option value="%s">%s</option>',
                        esc_attr($option['value']),
                        esc_html($option['label'])
                    );
                }
                $html .= '</select>';
                return $html;

            case 'radio':
            case 'checkbox':
                $options = $field['options'] ?? [];
                $html = '<div class="nexusforms-options">';
                foreach ($options as $index => $option) {
                    $option_id = $id . '_' . $index;
                    $html .= sprintf(
                        '<label class="nexusforms-option"><input type="%s" name="%s" value="%s" id="%s"> %s</label>',
                        esc_attr($type),
                        esc_attr($type === 'checkbox' ? $name . '[]' : $name),
                        esc_attr($option['value']),
                        esc_attr($option_id),
                        esc_html($option['label'])
                    );
                }
                $html .= '</div>';
                return $html;

            case 'file':
                $multiple = $field['multiple'] ?? false;
                $accept = '';

                // Build accept attribute from allowed file types
                if (!empty($field['allowedFileTypes'])) {
                    $extensions = [];
                    foreach ($field['allowedFileTypes'] as $ext) {
                        $extensions[] = '.' . $ext;
                    }
                    $accept = 'accept="' . esc_attr(implode(',', $extensions)) . '"';
                }

                return sprintf(
                    '<input type="file" %s %s %s class="nexusforms-input nexusforms-file">',
                    $attrs,
                    $multiple ? 'multiple' : '',
                    $accept
                );

            default:
                return sprintf(
                    '<input type="%s" %s class="nexusforms-input nexusforms-%s">',
                    esc_attr($type),
                    $attrs,
                    esc_attr($type)
                );
        }
    }
}
