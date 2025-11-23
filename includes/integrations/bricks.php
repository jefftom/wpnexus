<?php
/**
 * Bricks Builder integration.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Bricks element.
 *
 * @since 1.0.0
 * @param array $elements Elements array.
 * @return array Modified elements array.
 */
function nexusforms_register_bricks_element(array $elements): array {
    require_once NEXUSFORMS_PLUGIN_DIR . 'includes/integrations/bricks-element.php';
    $elements['nexusforms'] = NexusForms_Bricks_Element::class;
    return $elements;
}
add_filter('bricks/elements/register', 'nexusforms_register_bricks_element');
