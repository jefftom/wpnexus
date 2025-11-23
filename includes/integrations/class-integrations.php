<?php
/**
 * Integrations manager class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Integrations Class.
 *
 * Manages page builder integrations.
 *
 * @since 1.0.0
 */
class NexusForms_Integrations {

    /**
     * The single instance of the class.
     *
     * @var NexusForms_Integrations
     */
    protected static ?NexusForms_Integrations $instance = null;

    /**
     * Main instance.
     *
     * @since 1.0.0
     * @return NexusForms_Integrations
     */
    public static function instance(): NexusForms_Integrations {
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
        $this->load_integrations();
    }

    /**
     * Load all integrations.
     *
     * @since 1.0.0
     */
    private function load_integrations(): void {
        // Gutenberg blocks (always load for WordPress 5.0+).
        if (version_compare(get_bloginfo('version'), '5.0', '>=')) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/gutenberg.php';
        }

        // Elementor.
        if (did_action('elementor/loaded')) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/elementor.php';
        }

        // DIVI.
        if (function_exists('et_setup_theme')) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/divi.php';
        }

        // Bricks Builder.
        if (class_exists('Bricks\Theme')) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/bricks.php';
        }

        // WPBakery.
        if (class_exists('Vc_Manager')) {
            require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/wpbakery.php';
        }

        /**
         * Fires after integrations are loaded.
         *
         * @since 1.0.0
         */
        do_action('nexusforms_integrations_loaded');
    }

    /**
     * Get available forms for dropdown.
     *
     * @since 1.0.0
     * @return array Array of forms [id => title].
     */
    public static function get_forms_dropdown(): array {
        $forms_handler = new NexusForms_Forms();
        $forms = $forms_handler->get_all(['status' => 'active', 'limit' => 999]);

        $options = ['' => __('Select a form...', 'nexusforms')];

        foreach ($forms as $form) {
            $options[$form->id] = $form->title;
        }

        return $options;
    }
}
