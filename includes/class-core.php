<?php
/**
 * Core plugin class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main NexusForms Core Class.
 *
 * @since 1.0.0
 */
final class NexusForms_Core {

    /**
     * The single instance of the class.
     *
     * @var NexusForms_Core
     */
    protected static ?NexusForms_Core $instance = null;

    /**
     * Forms handler instance.
     *
     * @var NexusForms_Forms
     */
    public NexusForms_Forms $forms;

    /**
     * Submissions handler instance.
     *
     * @var NexusForms_Submissions
     */
    public NexusForms_Submissions $submissions;

    /**
     * Renderer instance.
     *
     * @var NexusForms_Renderer
     */
    public NexusForms_Renderer $renderer;

    /**
     * Validator instance.
     *
     * @var NexusForms_Validator
     */
    public NexusForms_Validator $validator;

    /**
     * API handler instance.
     *
     * @var NexusForms_API
     */
    public NexusForms_API $api;

    /**
     * File handler instance.
     *
     * @var NexusForms_File_Handler
     */
    public NexusForms_File_Handler $file_handler;

    /**
     * Main NexusForms Instance.
     *
     * Ensures only one instance of NexusForms is loaded or can be loaded.
     *
     * @since 1.0.0
     * @return NexusForms_Core Main instance.
     */
    public static function instance(): NexusForms_Core {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Define additional constants.
     *
     * @since 1.0.0
     */
    private function define_constants(): void {
        // Define any additional constants here.
    }

    /**
     * Include required core files.
     *
     * @since 1.0.0
     */
    private function includes(): void {
        // Core functionality.
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-database.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-forms.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-submissions.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-renderer.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-validator.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-api.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-notifications.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-file-handler.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-gravity-importer.php';
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-wpforms-importer.php';

        // Admin functionality.
        if (is_admin()) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'admin/class-admin.php';
        }

        // Integrations.
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/class-integrations.php';
    }

    /**
     * Initialize WordPress hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks(): void {
        // Load plugin text domain.
        add_action('init', [$this, 'load_textdomain']);

        // Initialize components.
        add_action('plugins_loaded', [$this, 'init'], 0);

        // Check for database updates.
        add_action('plugins_loaded', [$this, 'check_version'], 5);
    }

    /**
     * Initialize components.
     *
     * @since 1.0.0
     */
    private function init_components(): void {
        // Initialize core components.
        $this->forms = new NexusForms_Forms();
        $this->submissions = new NexusForms_Submissions();
        $this->renderer = new NexusForms_Renderer();
        $this->validator = new NexusForms_Validator();
        $this->api = new NexusForms_API();
        $this->file_handler = new NexusForms_File_Handler();

        // Initialize file handler hooks
        $this->file_handler->init();

        // Initialize integrations.
        NexusForms_Integrations::instance();
    }

    /**
     * Load plugin text domain for translations.
     *
     * @since 1.0.0
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'nexusforms',
            false,
            dirname(NEXUSFORMS_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Initialize the plugin.
     *
     * @since 1.0.0
     */
    public function init(): void {
        /**
         * Fires after NexusForms is fully loaded.
         *
         * @since 1.0.0
         */
        do_action('nexusforms_loaded');
    }

    /**
     * Check plugin version and run updates if needed.
     *
     * @since 1.0.0
     */
    public function check_version(): void {
        $current_version = get_option('nexusforms_version', '0.0.0');
        $current_db_version = get_option('nexusforms_db_version', '0.0.0');

        // Check if we need to update the database.
        if (version_compare($current_db_version, NEXUSFORMS_DB_VERSION, '<')) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'includes/class-database.php';
            NexusForms_Database::create_tables();
            update_option('nexusforms_db_version', NEXUSFORMS_DB_VERSION);
        }

        // Check if we need to update the plugin version.
        if (version_compare($current_version, NEXUSFORMS_VERSION, '<')) {
            update_option('nexusforms_version', NEXUSFORMS_VERSION);

            /**
             * Fires after NexusForms is updated.
             *
             * @since 1.0.0
             * @param string $current_version The previous version.
             * @param string $new_version The new version.
             */
            do_action('nexusforms_updated', $current_version, NEXUSFORMS_VERSION);
        }
    }

    /**
     * Get the plugin URL.
     *
     * @since 1.0.0
     * @return string
     */
    public function plugin_url(): string {
        return untrailingslashit(plugins_url('/', NEXUSFORMS_PLUGIN_FILE));
    }

    /**
     * Get the plugin path.
     *
     * @since 1.0.0
     * @return string
     */
    public function plugin_path(): string {
        return untrailingslashit(plugin_dir_path(NEXUSFORMS_PLUGIN_FILE));
    }
}
