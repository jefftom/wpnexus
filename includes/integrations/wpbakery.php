<?php
/**
 * WPBakery integration.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register WPBakery element.
 *
 * @since 1.0.0
 */
function nexusforms_register_wpbakery_element(): void {
    if (!function_exists('vc_map')) {
        return;
    }

    $forms = NexusForms_Integrations::get_forms_dropdown();

    vc_map([
        'name' => __('NexusForms', 'nexusforms'),
        'base' => 'nexusforms',
        'category' => __('Content', 'nexusforms'),
        'icon' => NEXUSFORMS_PLUGIN_URL . 'assets/images/icon.png',
        'params' => [
            [
                'type' => 'dropdown',
                'heading' => __('Select Form', 'nexusforms'),
                'param_name' => 'id',
                'value' => $forms,
                'std' => '',
            ],
            [
                'type' => 'checkbox',
                'heading' => __('Show Title', 'nexusforms'),
                'param_name' => 'title',
                'value' => ['Yes' => 'yes'],
                'std' => 'yes',
            ],
            [
                'type' => 'checkbox',
                'heading' => __('Show Description', 'nexusforms'),
                'param_name' => 'description',
                'value' => ['Yes' => 'yes'],
                'std' => 'yes',
            ],
            [
                'type' => 'checkbox',
                'heading' => __('AJAX Submission', 'nexusforms'),
                'param_name' => 'ajax',
                'value' => ['Yes' => 'yes'],
                'std' => 'yes',
            ],
        ],
    ]);
}
add_action('vc_before_init', 'nexusforms_register_wpbakery_element');

/**
 * WPBakery shortcode callback.
 *
 * @since 1.0.0
 * @param array $atts Shortcode attributes.
 * @return string Shortcode output.
 */
function nexusforms_wpbakery_shortcode(array $atts): string {
    $atts = shortcode_atts([
        'id' => 0,
        'title' => 'yes',
        'description' => 'yes',
        'ajax' => 'yes',
    ], $atts);

    $form_id = (int) $atts['id'];

    if (!$form_id) {
        return '<p>' . esc_html__('Please select a form.', 'nexusforms') . '</p>';
    }

    $renderer = new NexusForms_Renderer();

    return $renderer->render_form($form_id, [
        'title' => $atts['title'] === 'yes',
        'description' => $atts['description'] === 'yes',
        'ajax' => $atts['ajax'] === 'yes',
    ]);
}
add_shortcode('nexusforms_vc', 'nexusforms_wpbakery_shortcode');
