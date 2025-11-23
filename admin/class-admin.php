<?php
/**
 * Admin functionality.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Admin Class.
 *
 * Handles admin interface and menu.
 *
 * @since 1.0.0
 */
class NexusForms_Admin {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu.
     *
     * @since 1.0.0
     */
    public function add_admin_menu(): void {
        // Main menu.
        add_menu_page(
            __('NexusForms', 'nexusforms'),
            __('NexusForms', 'nexusforms'),
            'nexusforms_manage_forms',
            'nexusforms',
            [$this, 'render_forms_page'],
            'dashicons-feedback',
            30
        );

        // Forms submenu.
        add_submenu_page(
            'nexusforms',
            __('All Forms', 'nexusforms'),
            __('All Forms', 'nexusforms'),
            'nexusforms_manage_forms',
            'nexusforms',
            [$this, 'render_forms_page']
        );

        // Add new form.
        add_submenu_page(
            'nexusforms',
            __('Add New Form', 'nexusforms'),
            __('Add New', 'nexusforms'),
            'nexusforms_manage_forms',
            'nexusforms-new',
            [$this, 'render_editor_page']
        );

        // Entries.
        add_submenu_page(
            'nexusforms',
            __('Entries', 'nexusforms'),
            __('Entries', 'nexusforms'),
            'nexusforms_manage_entries',
            'nexusforms-entries',
            [$this, 'render_entries_page']
        );

        // Settings.
        add_submenu_page(
            'nexusforms',
            __('Settings', 'nexusforms'),
            __('Settings', 'nexusforms'),
            'nexusforms_manage_settings',
            'nexusforms-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets(string $hook): void {
        // Only load on NexusForms pages.
        if (strpos($hook, 'nexusforms') === false) {
            return;
        }

        // Enqueue WordPress components styles.
        wp_enqueue_style('wp-components');

        // Enqueue admin styles.
        wp_enqueue_style(
            'nexusforms-admin',
            NEXUSFORMS_PLUGIN_URL . 'assets/build/style-admin.css',
            ['wp-components'],
            NEXUSFORMS_VERSION
        );

        // Enqueue admin scripts.
        $dependencies = ['wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n'];

        wp_enqueue_script(
            'nexusforms-admin',
            NEXUSFORMS_PLUGIN_URL . 'assets/build/admin.js',
            $dependencies,
            NEXUSFORMS_VERSION,
            true
        );

        // Localize script.
        wp_localize_script('nexusforms-admin', 'nexusformsAdmin', [
            'apiUrl' => rest_url('nexusforms/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'pluginUrl' => NEXUSFORMS_PLUGIN_URL,
            'version' => NEXUSFORMS_VERSION,
            'i18n' => [
                'formBuilder' => __('Form Builder', 'nexusforms'),
                'addField' => __('Add Field', 'nexusforms'),
                'save' => __('Save', 'nexusforms'),
                'saved' => __('Saved', 'nexusforms'),
                'saving' => __('Saving...', 'nexusforms'),
            ],
        ]);
    }

    /**
     * Render forms list page.
     *
     * @since 1.0.0
     */
    public function render_forms_page(): void {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Forms', 'nexusforms'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=nexusforms-new')); ?>" class="page-title-action">
                <?php echo esc_html__('Add New', 'nexusforms'); ?>
            </a>
            <hr class="wp-header-end">
            <div id="nexusforms-forms-root"></div>
        </div>
        <?php
    }

    /**
     * Render form editor page.
     *
     * @since 1.0.0
     */
    public function render_editor_page(): void {
        $form_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        ?>
        <div class="wrap nexusforms-editor-wrap">
            <div id="nexusforms-editor-root" data-form-id="<?php echo esc_attr($form_id); ?>"></div>
        </div>
        <?php
    }

    /**
     * Render entries page.
     *
     * @since 1.0.0
     */
    public function render_entries_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Entries', 'nexusforms'); ?></h1>
            <div id="nexusforms-entries-root"></div>
        </div>
        <?php
    }

    /**
     * Render settings page.
     *
     * @since 1.0.0
     */
    public function render_settings_page(): void {
        // Save settings if form submitted.
        if (isset($_POST['nexusforms_settings_nonce']) &&
            wp_verify_nonce($_POST['nexusforms_settings_nonce'], 'nexusforms_save_settings')) {

            $settings = [
                'recaptcha_enabled' => isset($_POST['recaptcha_enabled']),
                'recaptcha_site_key' => sanitize_text_field($_POST['recaptcha_site_key'] ?? ''),
                'recaptcha_secret_key' => sanitize_text_field($_POST['recaptcha_secret_key'] ?? ''),
                'disable_css' => isset($_POST['disable_css']),
                'ajax_submissions' => isset($_POST['ajax_submissions']),
                'store_ip_addresses' => isset($_POST['store_ip_addresses']),
                'delete_on_uninstall' => isset($_POST['delete_on_uninstall']),
            ];

            update_option('nexusforms_settings', $settings);
            echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'nexusforms') . '</p></div>';
        }

        $settings = get_option('nexusforms_settings', []);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('NexusForms Settings', 'nexusforms'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('nexusforms_save_settings', 'nexusforms_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Disable Plugin CSS', 'nexusforms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="disable_css" value="1" <?php checked($settings['disable_css'] ?? false); ?>>
                                <?php echo esc_html__('Disable default form styles', 'nexusforms'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('AJAX Submissions', 'nexusforms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="ajax_submissions" value="1" <?php checked($settings['ajax_submissions'] ?? true); ?>>
                                <?php echo esc_html__('Enable AJAX form submissions (recommended)', 'nexusforms'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('Store IP Addresses', 'nexusforms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="store_ip_addresses" value="1" <?php checked($settings['store_ip_addresses'] ?? true); ?>>
                                <?php echo esc_html__('Store user IP addresses with submissions', 'nexusforms'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('Google reCAPTCHA', 'nexusforms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="recaptcha_enabled" value="1" <?php checked($settings['recaptcha_enabled'] ?? false); ?>>
                                <?php echo esc_html__('Enable reCAPTCHA', 'nexusforms'); ?>
                            </label>
                            <br><br>
                            <input type="text" name="recaptcha_site_key" class="regular-text"
                                   placeholder="<?php echo esc_attr__('Site Key', 'nexusforms'); ?>"
                                   value="<?php echo esc_attr($settings['recaptcha_site_key'] ?? ''); ?>">
                            <br>
                            <input type="text" name="recaptcha_secret_key" class="regular-text"
                                   placeholder="<?php echo esc_attr__('Secret Key', 'nexusforms'); ?>"
                                   value="<?php echo esc_attr($settings['recaptcha_secret_key'] ?? ''); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php echo esc_html__('Delete Data on Uninstall', 'nexusforms'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="delete_on_uninstall" value="1" <?php checked($settings['delete_on_uninstall'] ?? false); ?>>
                                <?php echo esc_html__('Delete all plugin data when uninstalled', 'nexusforms'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('Warning: This will permanently delete all forms and entries.', 'nexusforms'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize admin.
new NexusForms_Admin();
