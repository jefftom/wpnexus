<?php
/**
 * Gutenberg block integration.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Gutenberg block.
 *
 * @since 1.0.0
 */
function nexusforms_register_gutenberg_block(): void {
    // Register block script.
    wp_register_script(
        'nexusforms-gutenberg-block',
        NEXUSFORMS_PLUGIN_URL . 'assets/build/blocks.js',
        ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'],
        NEXUSFORMS_VERSION
    );

    // Register block.
    register_block_type('nexusforms/form', [
        'editor_script' => 'nexusforms-gutenberg-block',
        'render_callback' => 'nexusforms_render_gutenberg_block',
        'attributes' => [
            'formId' => [
                'type' => 'number',
                'default' => 0,
            ],
            'showTitle' => [
                'type' => 'boolean',
                'default' => true,
            ],
            'showDescription' => [
                'type' => 'boolean',
                'default' => true,
            ],
            'ajax' => [
                'type' => 'boolean',
                'default' => true,
            ],
        ],
    ]);
}
add_action('init', 'nexusforms_register_gutenberg_block');

/**
 * Render Gutenberg block.
 *
 * @since 1.0.0
 * @param array $attributes Block attributes.
 * @return string Block HTML.
 */
function nexusforms_render_gutenberg_block(array $attributes): string {
    $form_id = $attributes['formId'] ?? 0;

    if (!$form_id) {
        return '<p>' . esc_html__('Please select a form.', 'nexusforms') . '</p>';
    }

    $renderer = new NexusForms_Renderer();

    return $renderer->render_form($form_id, [
        'title' => $attributes['showTitle'] ?? true,
        'description' => $attributes['showDescription'] ?? true,
        'ajax' => $attributes['ajax'] ?? true,
    ]);
}

/**
 * Localize block editor data.
 *
 * @since 1.0.0
 */
function nexusforms_localize_gutenberg_block(): void {
    if (!is_admin()) {
        return;
    }

    $forms = NexusForms_Integrations::get_forms_dropdown();

    wp_localize_script('nexusforms-gutenberg-block', 'nexusformsBlock', [
        'forms' => $forms,
    ]);
}
add_action('enqueue_block_editor_assets', 'nexusforms_localize_gutenberg_block');
