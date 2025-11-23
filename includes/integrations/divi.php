<?php
/**
 * DIVI module integration.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register DIVI module.
 *
 * @since 1.0.0
 */
function nexusforms_register_divi_module(): void {
    if (class_exists('ET_Builder_Module')) {
        require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/divi-module.php';
    }
}
add_action('et_builder_ready', 'nexusforms_register_divi_module');
