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

        // Get form schema for conditional logic.
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

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

        // Expose form schema for conditional logic (as separate variable to avoid conflicts).
        if ($form) {
            wp_add_inline_script(
                'nexusforms-frontend',
                'window.nexusforms_schema_' . $form_id . ' = ' . wp_json_encode([
                    'fields' => json_decode($form->fields ?? '[]', true),
                    'settings' => json_decode($form->settings ?? '{}', true),
                ]) . ';',
                'before'
            );
        }
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

            // Get form for confirmation settings
            $forms = new NexusForms_Forms();
            $form = $forms->get($form_id);
            $settings = $form->settings ?? (object)[];
            $confirmation = $settings->confirmation ?? (object)[];

            // Prepare response based on confirmation type
            $response = [
                'entry_id' => $entry_id,
            ];

            $confirmation_type = $confirmation->type ?? 'message';

            switch ($confirmation_type) {
                case 'redirect':
                    $response['confirmation_type'] = 'redirect';
                    $response['redirect_url'] = $confirmation->redirectUrl ?? home_url();
                    $response['message'] = $confirmation->message ?? __('Form submitted successfully! Redirecting...', 'nexusforms');
                    break;

                case 'page':
                    $response['confirmation_type'] = 'page';
                    $response['page_content'] = $confirmation->pageContent ?? __('Thank you for your submission!', 'nexusforms');
                    break;

                case 'message':
                default:
                    $response['confirmation_type'] = 'message';
                    $response['message'] = $confirmation->message ?? __('Form submitted successfully!', 'nexusforms');
                    break;
            }

            wp_send_json_success($response);
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
                case 'date':
                    $entry_data[$field_id] = sanitize_text_field($value);
                    break;
                case 'rating':
                    $entry_data[$field_id] = intval($value);
                    break;
                case 'signature':
                    // Signature is stored as base64 image data
                    $entry_data[$field_id] = sanitize_textarea_field($value);
                    break;
                case 'hidden':
                    $entry_data[$field_id] = sanitize_text_field($value);
                    break;
                case 'time':
                    $entry_data[$field_id] = sanitize_text_field($value);
                    break;
                case 'name':
                    // Name field has first and last name subfields
                    $entry_data[$field_id] = [
                        'first' => sanitize_text_field($value['first'] ?? ''),
                        'last' => sanitize_text_field($value['last'] ?? ''),
                    ];
                    break;
                case 'address':
                    // Address field has multiple subfields
                    $entry_data[$field_id] = [
                        'street' => sanitize_text_field($value['street'] ?? ''),
                        'street2' => sanitize_text_field($value['street2'] ?? ''),
                        'city' => sanitize_text_field($value['city'] ?? ''),
                        'state' => sanitize_text_field($value['state'] ?? ''),
                        'zip' => sanitize_text_field($value['zip'] ?? ''),
                        'country' => sanitize_text_field($value['country'] ?? ''),
                    ];
                    break;
                case 'multiselect':
                    $entry_data[$field_id] = is_array($value)
                        ? array_map('sanitize_text_field', $value)
                        : sanitize_text_field($value);
                    break;
                case 'consent':
                    $entry_data[$field_id] = $value ? 'yes' : 'no';
                    break;
                case 'list':
                    // List field is an array of rows
                    $entry_data[$field_id] = is_array($value)
                        ? array_map('sanitize_text_field', $value)
                        : [];
                    break;
                case 'html':
                case 'section':
                case 'page':
                case 'calculation':
                    // These fields don't store data
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

            case 'date':
                return sprintf(
                    '<input type="date" %s class="nexusforms-input nexusforms-date">',
                    $attrs
                );

            case 'rating':
                $max_rating = $field['maxRating'] ?? 5;
                $html = '<div class="nexusforms-rating" data-field-id="' . esc_attr($id) . '">';
                for ($i = 1; $i <= $max_rating; $i++) {
                    $html .= sprintf(
                        '<span class="rating-star" data-value="%d">★</span>',
                        $i
                    );
                }
                $html .= sprintf(
                    '<input type="hidden" name="%s" id="%s" value="" %s>',
                    esc_attr($name),
                    esc_attr($id),
                    $required ? 'required' : ''
                );
                $html .= '</div>';
                return $html;

            case 'signature':
                $html = '<div class="nexusforms-signature-wrapper">';
                $html .= sprintf(
                    '<canvas class="nexusforms-signature-canvas" id="%s-canvas" width="600" height="200"></canvas>',
                    esc_attr($id)
                );
                $html .= sprintf(
                    '<input type="hidden" name="%s" id="%s" value="" %s>',
                    esc_attr($name),
                    esc_attr($id),
                    $required ? 'required' : ''
                );
                $html .= '<div class="signature-controls">';
                $html .= sprintf(
                    '<button type="button" class="signature-clear" data-canvas="%s-canvas">%s</button>',
                    esc_attr($id),
                    esc_html__('Clear', 'nexusforms')
                );
                $html .= '</div>';
                $html .= '</div>';
                return $html;

            case 'hidden':
                $default_value = $field['defaultValue'] ?? '';
                return sprintf(
                    '<input type="hidden" name="%s" id="%s" value="%s">',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr($default_value)
                );

            case 'time':
                return sprintf(
                    '<input type="time" %s class="nexusforms-input nexusforms-time">',
                    $attrs
                );

            case 'name':
                $html = '<div class="nexusforms-name-wrapper">';
                $html .= sprintf(
                    '<div class="name-field"><input type="text" name="%s[first]" id="%s-first" placeholder="%s" class="nexusforms-input" %s></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('First Name', 'nexusforms'),
                    $required ? 'required' : ''
                );
                $html .= sprintf(
                    '<div class="name-field"><input type="text" name="%s[last]" id="%s-last" placeholder="%s" class="nexusforms-input" %s></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('Last Name', 'nexusforms'),
                    $required ? 'required' : ''
                );
                $html .= '</div>';
                return $html;

            case 'address':
                $html = '<div class="nexusforms-address-wrapper">';
                $html .= sprintf(
                    '<div class="address-field full"><input type="text" name="%s[street]" id="%s-street" placeholder="%s" class="nexusforms-input" %s></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('Street Address', 'nexusforms'),
                    $required ? 'required' : ''
                );
                $html .= sprintf(
                    '<div class="address-field full"><input type="text" name="%s[street2]" id="%s-street2" placeholder="%s" class="nexusforms-input"></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('Address Line 2', 'nexusforms')
                );
                $html .= sprintf(
                    '<div class="address-field"><input type="text" name="%s[city]" id="%s-city" placeholder="%s" class="nexusforms-input" %s></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('City', 'nexusforms'),
                    $required ? 'required' : ''
                );
                $html .= sprintf(
                    '<div class="address-field"><input type="text" name="%s[state]" id="%s-state" placeholder="%s" class="nexusforms-input" %s></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('State/Province', 'nexusforms'),
                    $required ? 'required' : ''
                );
                $html .= sprintf(
                    '<div class="address-field"><input type="text" name="%s[zip]" id="%s-zip" placeholder="%s" class="nexusforms-input" %s></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('ZIP/Postal Code', 'nexusforms'),
                    $required ? 'required' : ''
                );
                $html .= sprintf(
                    '<div class="address-field"><input type="text" name="%s[country]" id="%s-country" placeholder="%s" class="nexusforms-input"></div>',
                    esc_attr($name),
                    esc_attr($id),
                    esc_attr__('Country', 'nexusforms')
                );
                $html .= '</div>';
                return $html;

            case 'multiselect':
                $options = $field['options'] ?? [];
                $html = sprintf('<select %s class="nexusforms-input nexusforms-multiselect" multiple size="5" name="%s[]">', $attrs, esc_attr($name));
                foreach ($options as $option) {
                    $html .= sprintf(
                        '<option value="%s">%s</option>',
                        esc_attr($option['value']),
                        esc_html($option['label'])
                    );
                }
                $html .= '</select>';
                return $html;

            case 'consent':
                $consent_text = $field['consentText'] ?? __('I agree to the terms and conditions', 'nexusforms');
                return sprintf(
                    '<label class="nexusforms-consent"><input type="checkbox" name="%s" id="%s" value="1" %s> <span class="consent-text">%s</span></label>',
                    esc_attr($name),
                    esc_attr($id),
                    $required ? 'required' : '',
                    wp_kses_post($consent_text)
                );

            case 'list':
                $columns = $field['columns'] ?? [__('Item', 'nexusforms')];
                $html = '<div class="nexusforms-list-wrapper" data-field-id="' . esc_attr($id) . '">';
                $html .= '<div class="list-header">';
                foreach ($columns as $index => $column) {
                    $html .= sprintf('<div class="list-column-header">%s</div>', esc_html($column));
                }
                $html .= '<div class="list-column-header list-actions"></div>';
                $html .= '</div>';
                $html .= '<div class="list-rows">';
                $html .= '<div class="list-row">';
                foreach ($columns as $index => $column) {
                    $html .= sprintf(
                        '<div class="list-column"><input type="text" name="%s[0][%d]" class="nexusforms-input"></div>',
                        esc_attr($name),
                        $index
                    );
                }
                $html .= '<div class="list-column list-actions"><button type="button" class="list-remove" aria-label="' . esc_attr__('Remove', 'nexusforms') . '">×</button></div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<button type="button" class="list-add-row">' . esc_html__('+ Add Row', 'nexusforms') . '</button>';
                $html .= '</div>';
                return $html;

            case 'html':
                $content = $field['htmlContent'] ?? '';
                return '<div class="nexusforms-html-content">' . wp_kses_post($content) . '</div>';

            case 'section':
                $section_title = $field['sectionTitle'] ?? '';
                $section_desc = $field['sectionDescription'] ?? '';
                $html = '<div class="nexusforms-section-break">';
                if ($section_title) {
                    $html .= '<h3 class="section-title">' . esc_html($section_title) . '</h3>';
                }
                if ($section_desc) {
                    $html .= '<p class="section-description">' . esc_html($section_desc) . '</p>';
                }
                $html .= '<hr class="section-divider">';
                $html .= '</div>';
                return $html;

            case 'calculation':
                // Calculation field - read-only, computed by JavaScript
                $formula = $field['formula'] ?? '';
                $format = $field['calculationFormat'] ?? 'number';
                $decimal_places = $field['decimalPlaces'] ?? 2;
                $currency_symbol = $field['currencySymbol'] ?? '$';

                $html = sprintf(
                    '<div class="nexusforms-calculation" data-field-id="%s" data-formula="%s" data-format="%s" data-decimals="%d" data-currency="%s">',
                    esc_attr($id),
                    esc_attr($formula),
                    esc_attr($format),
                    (int) $decimal_places,
                    esc_attr($currency_symbol)
                );

                if ($format === 'currency') {
                    $html .= '<span class="calculation-currency">' . esc_html($currency_symbol) . '</span>';
                }

                $html .= '<span class="calculation-value">0.00</span>';

                if ($format === 'percentage') {
                    $html .= '<span class="calculation-percent">%</span>';
                }

                $html .= '</div>';
                return $html;

            case 'page':
                // Page break - marks the end of a page
                $page_title = $field['pageTitle'] ?? '';
                $page_desc = $field['pageDescription'] ?? '';
                $html = '<div class="nexusforms-page-break" data-page-break="true">';
                if ($page_title) {
                    $html .= '<div class="page-title">' . esc_html($page_title) . '</div>';
                }
                if ($page_desc) {
                    $html .= '<div class="page-description">' . esc_html($page_desc) . '</div>';
                }
                $html .= '</div>';
                return $html;

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
