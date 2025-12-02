<?php
/**
 * REST API class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms API Class.
 *
 * Handles REST API endpoints.
 *
 * @since 1.0.0
 */
class NexusForms_API {

    /**
     * API namespace.
     *
     * @var string
     */
    private const NAMESPACE = 'nexusforms/v1';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_routes(): void {
        // Forms endpoints.
        register_rest_route(self::NAMESPACE, '/forms', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_forms'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_form'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/forms/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_form'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_form'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_form'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Duplicate form.
        register_rest_route(self::NAMESPACE, '/forms/(?P<id>\d+)/duplicate', [
            'methods' => 'POST',
            'callback' => [$this, 'duplicate_form'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Export/Import.
        register_rest_route(self::NAMESPACE, '/forms/(?P<id>\d+)/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_form'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/forms/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_form'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Import from Gravity Forms.
        register_rest_route(self::NAMESPACE, '/forms/import/gravity', [
            'methods' => 'POST',
            'callback' => [$this, 'import_from_gravity'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Import from WPForms.
        register_rest_route(self::NAMESPACE, '/forms/import/wpforms', [
            'methods' => 'POST',
            'callback' => [$this, 'import_from_wpforms'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Entries endpoints.
        register_rest_route(self::NAMESPACE, '/entries', [
            'methods' => 'GET',
            'callback' => [$this, 'get_entries'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/entries/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_entry'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_entry'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Public submission endpoint.
        register_rest_route(self::NAMESPACE, '/submit', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_form'],
            'permission_callback' => '__return_true',
        ]);

        // Export entries.
        register_rest_route(self::NAMESPACE, '/forms/(?P<id>\d+)/entries/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_entries'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Export entries (with form_id parameter).
        register_rest_route(self::NAMESPACE, '/entries/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_entries_csv'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    /**
     * Check permission for API requests.
     *
     * @since 1.0.0
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can('nexusforms_manage_forms');
    }

    /**
     * Get all forms.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_forms(WP_REST_Request $request): WP_REST_Response {
        $forms_handler = new NexusForms_Forms();

        $args = [
            'status' => $request->get_param('status') ?: '',
            'orderby' => $request->get_param('orderby') ?: 'created_at',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('limit') ?: 100,
            'offset' => $request->get_param('offset') ?: 0,
        ];

        $forms = $forms_handler->get_all($args);
        $total = $forms_handler->get_count($args['status']);

        return new WP_REST_Response([
            'forms' => $forms,
            'total' => $total,
        ], 200);
    }

    /**
     * Get a single form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_form(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('id');
        $forms_handler = new NexusForms_Forms();
        $form = $forms_handler->get($form_id);

        if (!$form) {
            return new WP_REST_Response([
                'message' => __('Form not found.', 'nexusforms'),
            ], 404);
        }

        return new WP_REST_Response($form, 200);
    }

    /**
     * Create a new form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function create_form(WP_REST_Request $request): WP_REST_Response {
        $forms_handler = new NexusForms_Forms();

        $data = [
            'title' => sanitize_text_field($request->get_param('title')),
            'description' => sanitize_textarea_field($request->get_param('description')),
            'status' => sanitize_text_field($request->get_param('status')) ?: 'active',
            'settings' => $request->get_param('settings') ?: [],
        ];

        $form_id = $forms_handler->create($data);

        if (!$form_id) {
            return new WP_REST_Response([
                'message' => __('Failed to create form.', 'nexusforms'),
            ], 500);
        }

        // Add fields if provided.
        $fields = $request->get_param('fields');
        if ($fields && is_array($fields)) {
            $forms_handler->update_fields($form_id, $fields);
        }

        $form = $forms_handler->get($form_id);

        return new WP_REST_Response($form, 201);
    }

    /**
     * Update a form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function update_form(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('id');
        $forms_handler = new NexusForms_Forms();

        // Check if form exists.
        if (!$forms_handler->get($form_id)) {
            return new WP_REST_Response([
                'message' => __('Form not found.', 'nexusforms'),
            ], 404);
        }

        $data = [];

        if ($request->has_param('title')) {
            $data['title'] = sanitize_text_field($request->get_param('title'));
        }

        if ($request->has_param('description')) {
            $data['description'] = sanitize_textarea_field($request->get_param('description'));
        }

        if ($request->has_param('status')) {
            $data['status'] = sanitize_text_field($request->get_param('status'));
        }

        if ($request->has_param('settings')) {
            $data['settings'] = $request->get_param('settings');
        }

        $result = $forms_handler->update($form_id, $data);

        // Update fields if provided.
        if ($request->has_param('fields')) {
            $fields = $request->get_param('fields');
            if (is_array($fields)) {
                $forms_handler->update_fields($form_id, $fields);
            }
        }

        if (!$result && empty($request->get_param('fields'))) {
            return new WP_REST_Response([
                'message' => __('Failed to update form.', 'nexusforms'),
            ], 500);
        }

        $form = $forms_handler->get($form_id);

        return new WP_REST_Response($form, 200);
    }

    /**
     * Delete a form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function delete_form(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('id');
        $forms_handler = new NexusForms_Forms();

        $result = $forms_handler->delete($form_id);

        if (!$result) {
            return new WP_REST_Response([
                'message' => __('Failed to delete form.', 'nexusforms'),
            ], 500);
        }

        return new WP_REST_Response([
            'message' => __('Form deleted successfully.', 'nexusforms'),
        ], 200);
    }

    /**
     * Duplicate a form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function duplicate_form(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('id');
        $forms_handler = new NexusForms_Forms();

        $new_form_id = $forms_handler->duplicate($form_id);

        if (!$new_form_id) {
            return new WP_REST_Response([
                'message' => __('Failed to duplicate form.', 'nexusforms'),
            ], 500);
        }

        $form = $forms_handler->get($new_form_id);

        return new WP_REST_Response($form, 201);
    }

    /**
     * Export a form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function export_form(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('id');
        $forms_handler = new NexusForms_Forms();
        $form = $forms_handler->get($form_id);

        if (!$form) {
            return new WP_REST_Response([
                'message' => __('Form not found.', 'nexusforms'),
            ], 404);
        }

        return new WP_REST_Response([
            'form' => $form,
            'export_date' => current_time('mysql'),
            'version' => NEXUSFORMS_VERSION,
        ], 200);
    }

    /**
     * Import a form.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function import_form(WP_REST_Request $request): WP_REST_Response {
        $form_data = $request->get_param('form');

        if (!$form_data) {
            return new WP_REST_Response([
                'message' => __('No form data provided.', 'nexusforms'),
            ], 400);
        }

        $forms_handler = new NexusForms_Forms();

        $form_id = $forms_handler->create([
            'title' => $form_data['title'] . ' (Imported)',
            'description' => $form_data['description'],
            'status' => 'draft',
            'settings' => $form_data['settings'],
        ]);

        if ($form_id && !empty($form_data['fields'])) {
            foreach ($form_data['fields'] as $field) {
                $forms_handler->add_field($form_id, $field->field_data, $field->field_order);
            }
        }

        $form = $forms_handler->get($form_id);

        return new WP_REST_Response($form, 201);
    }

    /**
     * Get entries.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_entries(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('form_id');

        if (!$form_id) {
            return new WP_REST_Response([
                'message' => __('Form ID is required.', 'nexusforms'),
            ], 400);
        }

        $submissions = new NexusForms_Submissions();

        $args = [
            'status' => $request->get_param('status') ?: 'active',
            'orderby' => $request->get_param('orderby') ?: 'created_at',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $request->get_param('limit') ?: 100,
            'offset' => $request->get_param('offset') ?: 0,
        ];

        $entries = $submissions->get_by_form($form_id, $args);
        $total = $submissions->get_count($form_id, $args['status']);

        return new WP_REST_Response([
            'entries' => $entries,
            'total' => $total,
        ], 200);
    }

    /**
     * Get a single entry.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_entry(WP_REST_Request $request): WP_REST_Response {
        $entry_id = (int) $request->get_param('id');
        $submissions = new NexusForms_Submissions();
        $entry = $submissions->get($entry_id);

        if (!$entry) {
            return new WP_REST_Response([
                'message' => __('Entry not found.', 'nexusforms'),
            ], 404);
        }

        return new WP_REST_Response($entry, 200);
    }

    /**
     * Delete an entry.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function delete_entry(WP_REST_Request $request): WP_REST_Response {
        $entry_id = (int) $request->get_param('id');
        $submissions = new NexusForms_Submissions();

        $result = $submissions->delete($entry_id);

        if (!$result) {
            return new WP_REST_Response([
                'message' => __('Failed to delete entry.', 'nexusforms'),
            ], 500);
        }

        return new WP_REST_Response([
            'message' => __('Entry deleted successfully.', 'nexusforms'),
        ], 200);
    }

    /**
     * Submit a form (public endpoint).
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function submit_form(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('form_id');

        // Verify nonce.
        $nonce = $request->get_param('_wpnonce');
        if (!wp_verify_nonce($nonce, 'nexusforms_submit_' . $form_id)) {
            return new WP_REST_Response([
                'message' => __('Security check failed.', 'nexusforms'),
            ], 403);
        }

        // Honeypot check.
        if ($request->get_param('nexusforms_hp')) {
            return new WP_REST_Response([
                'message' => __('Spam detected.', 'nexusforms'),
            ], 400);
        }

        // Validate submission.
        $validator = new NexusForms_Validator();
        $errors = $validator->validate_submission($form_id, $request->get_params());

        if (!empty($errors)) {
            return new WP_REST_Response([
                'message' => __('Please fix the errors below.', 'nexusforms'),
                'errors' => $errors,
            ], 400);
        }

        // Create submission.
        $renderer = new NexusForms_Renderer();
        $entry_data = $this->prepare_entry_data($form_id, $request->get_params());

        $submissions = new NexusForms_Submissions();
        $entry_id = $submissions->create($form_id, $entry_data);

        if ($entry_id) {
            // Send notifications.
            $notifications = new NexusForms_Notifications();
            $notifications->send($form_id, $entry_id, $entry_data);

            return new WP_REST_Response([
                'message' => __('Form submitted successfully!', 'nexusforms'),
                'entry_id' => $entry_id,
            ], 200);
        }

        return new WP_REST_Response([
            'message' => __('An error occurred while saving your submission.', 'nexusforms'),
        ], 500);
    }

    /**
     * Export entries to CSV.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function export_entries(WP_REST_Request $request): WP_REST_Response {
        $form_id = (int) $request->get_param('id');
        $submissions = new NexusForms_Submissions();

        $csv = $submissions->export_to_csv($form_id);

        if (empty($csv)) {
            return new WP_REST_Response([
                'message' => __('No entries to export.', 'nexusforms'),
            ], 404);
        }

        return new WP_REST_Response([
            'csv' => $csv,
        ], 200);
    }

    /**
     * Export entries to CSV file download.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function export_entries_csv(WP_REST_Request $request) {
        $form_id = (int) $request->get_param('form_id');

        if (empty($form_id)) {
            return new WP_Error('no_form_id', __('Form ID is required.', 'nexusforms'), ['status' => 400]);
        }

        $submissions = new NexusForms_Submissions();
        $csv = $submissions->export_to_csv($form_id);

        if (empty($csv)) {
            return new WP_Error('no_entries', __('No entries to export.', 'nexusforms'), ['status' => 404]);
        }

        // Get form title for filename
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);
        $form_title = $form ? sanitize_file_name($form->title) : 'form-' . $form_id;
        $filename = $form_title . '-entries-' . date('Y-m-d') . '.csv';

        // Add UTF-8 BOM for Excel compatibility
        $csv_with_bom = "\xEF\xBB\xBF" . $csv;

        // Return response with CSV headers
        $response = new WP_REST_Response($csv_with_bom, 200);
        $response->header('Content-Type', 'text/csv; charset=utf-8');
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->header('Content-Length', strlen($csv_with_bom));
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');

        return $response;
    }

    /**
     * Prepare entry data from request.
     *
     * @since 1.0.0
     * @param int $form_id Form ID.
     * @param array $data Request data.
     * @return array Prepared entry data.
     */
    private function prepare_entry_data(int $form_id, array $data): array {
        $forms = new NexusForms_Forms();
        $form = $forms->get($form_id);

        $entry_data = [];

        foreach ($form->fields as $field) {
            $field_id = $field->field_data['id'];
            $field_type = $field->field_data['type'];
            $value = $data[$field_id] ?? '';

            $validator = new NexusForms_Validator();
            $entry_data[$field_id] = $validator->sanitize_field($field_type, $value);
        }

        return $entry_data;
    }

    /**
     * Import form from Gravity Forms.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function import_from_gravity(WP_REST_Request $request): WP_REST_Response {
        $json_data = $request->get_param('json');

        if (!$json_data) {
            return new WP_REST_Response([
                'message' => __('No Gravity Forms JSON data provided.', 'nexusforms'),
            ], 400);
        }

        $importer = new NexusForms_Gravity_Importer();
        $result = $importer->import($json_data);

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response($result, 201);
    }

    /**
     * Import form from WPForms.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function import_from_wpforms(WP_REST_Request $request): WP_REST_Response {
        $json_data = $request->get_param('json');

        if (!$json_data) {
            return new WP_REST_Response([
                'message' => __('No WPForms JSON data provided.', 'nexusforms'),
            ], 400);
        }

        $importer = new NexusForms_WPForms_Importer();
        $result = $importer->import($json_data);

        if (is_wp_error($result)) {
            return new WP_REST_Response([
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new WP_REST_Response($result, 201);
    }
}
