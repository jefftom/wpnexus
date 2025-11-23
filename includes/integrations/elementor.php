<?php
/**
 * Elementor widget integration.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Elementor widget.
 *
 * @since 1.0.0
 */
function nexusforms_register_elementor_widget(): void {
    require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/elementor-widget.php';
    \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new NexusForms_Elementor_Widget());
}
add_action('elementor/widgets/widgets_registered', 'nexusforms_register_elementor_widget');

/**
 * Add Elementor widget category.
 *
 * @since 1.0.0
 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
 */
function nexusforms_add_elementor_category(\Elementor\Elements_Manager $elements_manager): void {
    $elements_manager->add_category('nexusforms', [
        'title' => __('NexusForms', 'nexusforms'),
        'icon' => 'fa fa-plug',
    ]);
}
add_action('elementor/elements/categories_registered', 'nexusforms_add_elementor_category');
