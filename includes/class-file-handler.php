<?php
/**
 * File Upload Handler
 *
 * Handles file uploads with validation, storage, and retrieval.
 *
 * @package NexusForms
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class NexusForms_File_Handler {

    /**
     * Allowed file types with MIME types
     */
    private const ALLOWED_TYPES = [
        // Images
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',

        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',

        // Archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',

        // Text
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'rtf' => 'application/rtf',

        // Audio/Video
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'wav' => 'audio/wav',
        'avi' => 'video/x-msvideo',
    ];

    /**
     * Default max file size (in bytes) - 10MB
     */
    private const DEFAULT_MAX_SIZE = 10485760;

    /**
     * Upload directory name
     */
    private const UPLOAD_DIR = 'nexusforms';

    /**
     * Get upload directory path
     *
     * @param int $form_id Form ID for subdirectory
     * @param int $entry_id Entry ID for subdirectory
     * @return array Upload directory info
     */
    public function get_upload_dir(int $form_id, int $entry_id = 0): array {
        $upload_dir = wp_upload_dir();

        $subdir = '/' . self::UPLOAD_DIR . '/' . $form_id;
        if ($entry_id > 0) {
            $subdir .= '/' . $entry_id;
        }

        return [
            'path' => $upload_dir['basedir'] . $subdir,
            'url' => $upload_dir['baseurl'] . $subdir,
            'subdir' => $subdir,
            'basedir' => $upload_dir['basedir'],
            'baseurl' => $upload_dir['baseurl'],
        ];
    }

    /**
     * Create upload directory with security
     *
     * @param string $path Directory path
     * @return bool Success status
     */
    private function create_upload_dir(string $path): bool {
        if (file_exists($path)) {
            return true;
        }

        // Create directory with proper permissions
        if (!wp_mkdir_p($path)) {
            return false;
        }

        // Add .htaccess for security
        $htaccess_file = $path . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Disable directory listing\nOptions -Indexes\n\n# Disable script execution\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|htm|shtml|sh|cgi)$\">\nOrder allow,deny\nDeny from all\n</FilesMatch>\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Add index.php for additional security
        $index_file = $path . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }

        return true;
    }

    /**
     * Validate file upload
     *
     * @param array $file $_FILES array entry
     * @param array $field_settings Field configuration
     * @return array|true True if valid, error array if invalid
     */
    public function validate_file(array $file, array $field_settings = []): array|true {
        // Check for upload errors
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['error' => __('Invalid file upload.', 'nexusforms')];
        }

        // Check error code
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['error' => __('File size exceeds maximum allowed.', 'nexusforms')];
            case UPLOAD_ERR_NO_FILE:
                return ['error' => __('No file was uploaded.', 'nexusforms')];
            default:
                return ['error' => __('Unknown upload error.', 'nexusforms')];
        }

        // Validate file size
        $max_size = $field_settings['maxFileSize'] ?? self::DEFAULT_MAX_SIZE;
        if ($file['size'] > $max_size) {
            $max_size_mb = round($max_size / 1048576, 2);
            return ['error' => sprintf(__('File size exceeds maximum of %s MB.', 'nexusforms'), $max_size_mb)];
        }

        // Validate file type
        $allowed_extensions = $field_settings['allowedFileTypes'] ?? array_keys(self::ALLOWED_TYPES);
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension'] ?? '');

        $is_allowed = false;
        foreach ($allowed_extensions as $allowed_ext) {
            // Handle multiple extensions like 'jpg|jpeg|jpe'
            $exts = explode('|', $allowed_ext);
            if (in_array($extension, $exts, true)) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            return ['error' => sprintf(__('File type .%s is not allowed.', 'nexusforms'), $extension)];
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [];
        foreach ($allowed_extensions as $ext) {
            if (isset(self::ALLOWED_TYPES[$ext])) {
                $allowed_mimes[] = self::ALLOWED_TYPES[$ext];
            }
        }

        if (!in_array($mime_type, $allowed_mimes, true)) {
            return ['error' => __('File type verification failed.', 'nexusforms')];
        }

        return true;
    }

    /**
     * Handle file upload
     *
     * @param array $file $_FILES array entry
     * @param int $form_id Form ID
     * @param int $entry_id Entry ID (0 for temporary uploads)
     * @param array $field_settings Field configuration
     * @return array|false Upload result or false on failure
     */
    public function handle_upload(array $file, int $form_id, int $entry_id = 0, array $field_settings = []): array|false {
        // Validate file
        $validation = $this->validate_file($file, $field_settings);
        if (is_array($validation)) {
            return $validation; // Return error
        }

        // Get upload directory
        $upload_dir = $this->get_upload_dir($form_id, $entry_id);

        // Create directory
        if (!$this->create_upload_dir($upload_dir['path'])) {
            return ['error' => __('Failed to create upload directory.', 'nexusforms')];
        }

        // Generate unique filename
        $file_info = pathinfo($file['name']);
        $filename = sanitize_file_name($file_info['filename']);
        $extension = strtolower($file_info['extension'] ?? '');
        $unique_filename = wp_unique_filename($upload_dir['path'], $filename . '.' . $extension);

        // Full path
        $target_path = $upload_dir['path'] . '/' . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return ['error' => __('Failed to move uploaded file.', 'nexusforms')];
        }

        // Set proper permissions
        chmod($target_path, 0644);

        // Return file information
        return [
            'success' => true,
            'filename' => $unique_filename,
            'original_name' => $file['name'],
            'path' => $target_path,
            'url' => $upload_dir['url'] . '/' . $unique_filename,
            'size' => $file['size'],
            'type' => $file['type'],
            'form_id' => $form_id,
            'entry_id' => $entry_id,
        ];
    }

    /**
     * Handle multiple file uploads
     *
     * @param array $files $_FILES array with multiple files
     * @param int $form_id Form ID
     * @param int $entry_id Entry ID
     * @param array $field_settings Field configuration
     * @return array Upload results
     */
    public function handle_multiple_uploads(array $files, int $form_id, int $entry_id = 0, array $field_settings = []): array {
        $results = [];

        // Check if multiple files
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $results;
        }

        $file_count = count($files['name']);

        // Validate max files
        $max_files = $field_settings['maxFiles'] ?? 5;
        if ($file_count > $max_files) {
            return [
                'error' => sprintf(__('Maximum %d files allowed.', 'nexusforms'), $max_files)
            ];
        }

        // Process each file
        for ($i = 0; $i < $file_count; $i++) {
            // Skip empty files
            if (empty($files['name'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];

            $result = $this->handle_upload($file, $form_id, $entry_id, $field_settings);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Delete uploaded file
     *
     * @param string $file_path File path
     * @return bool Success status
     */
    public function delete_file(string $file_path): bool {
        if (!file_exists($file_path)) {
            return false;
        }

        // Ensure file is within uploads directory
        $upload_dir = wp_upload_dir();
        if (strpos($file_path, $upload_dir['basedir'] . '/' . self::UPLOAD_DIR) !== 0) {
            return false;
        }

        return unlink($file_path);
    }

    /**
     * Delete all files for an entry
     *
     * @param int $form_id Form ID
     * @param int $entry_id Entry ID
     * @return bool Success status
     */
    public function delete_entry_files(int $form_id, int $entry_id): bool {
        $upload_dir = $this->get_upload_dir($form_id, $entry_id);

        if (!file_exists($upload_dir['path'])) {
            return true;
        }

        // Delete all files in directory
        $files = glob($upload_dir['path'] . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Remove directory
        return rmdir($upload_dir['path']);
    }

    /**
     * Get file download URL with security token
     *
     * @param int $form_id Form ID
     * @param int $entry_id Entry ID
     * @param string $filename Filename
     * @return string Download URL
     */
    public function get_download_url(int $form_id, int $entry_id, string $filename): string {
        $token = wp_create_nonce("nexusforms_download_{$form_id}_{$entry_id}_{$filename}");

        return add_query_arg([
            'nexusforms_download' => 1,
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'file' => urlencode($filename),
            'token' => $token,
        ], home_url());
    }

    /**
     * Handle file download request
     *
     * @return void
     */
    public function handle_download(): void {
        if (!isset($_GET['nexusforms_download'])) {
            return;
        }

        $form_id = absint($_GET['form_id'] ?? 0);
        $entry_id = absint($_GET['entry_id'] ?? 0);
        $filename = sanitize_file_name($_GET['file'] ?? '');
        $token = sanitize_text_field($_GET['token'] ?? '');

        // Verify nonce
        if (!wp_verify_nonce($token, "nexusforms_download_{$form_id}_{$entry_id}_{$filename}")) {
            wp_die(__('Security check failed.', 'nexusforms'));
        }

        // Check permissions
        if (!current_user_can('nexusforms_view_entries')) {
            wp_die(__('You do not have permission to download this file.', 'nexusforms'));
        }

        // Get file path
        $upload_dir = $this->get_upload_dir($form_id, $entry_id);
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Verify file exists
        if (!file_exists($file_path)) {
            wp_die(__('File not found.', 'nexusforms'));
        }

        // Verify file is within allowed directory
        $real_path = realpath($file_path);
        $allowed_path = realpath($upload_dir['path']);

        if (strpos($real_path, $allowed_path) !== 0) {
            wp_die(__('Invalid file path.', 'nexusforms'));
        }

        // Send file
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        readfile($file_path);
        exit;
    }

    /**
     * Initialize file handler
     *
     * @return void
     */
    public function init(): void {
        add_action('init', [$this, 'handle_download']);
    }
}
