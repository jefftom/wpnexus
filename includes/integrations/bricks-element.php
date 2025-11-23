<?php
/**
 * Bricks Builder element class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Bricks Element.
 *
 * @since 1.0.0
 */
class NexusForms_Bricks_Element extends \Bricks\Element {

    /**
     * Element category.
     *
     * @var string
     */
    public $category = 'general';

    /**
     * Element name.
     *
     * @var string
     */
    public $name = 'nexusforms';

    /**
     * Element icon.
     *
     * @var string
     */
    public $icon = 'ti-layout-accordion-merged';

    /**
     * Get element label.
     *
     * @since 1.0.0
     * @return string Element label.
     */
    public function get_label(): string {
        return __('NexusForms', 'nexusforms');
    }

    /**
     * Set element controls.
     *
     * @since 1.0.0
     */
    public function set_controls(): void {
        $forms = NexusForms_Integrations::get_forms_dropdown();

        $this->controls['form_id'] = [
            'tab' => 'content',
            'label' => __('Select Form', 'nexusforms'),
            'type' => 'select',
            'options' => $forms,
            'default' => '',
        ];

        $this->controls['show_title'] = [
            'tab' => 'content',
            'label' => __('Show Title', 'nexusforms'),
            'type' => 'checkbox',
            'default' => true,
        ];

        $this->controls['show_description'] = [
            'tab' => 'content',
            'label' => __('Show Description', 'nexusforms'),
            'type' => 'checkbox',
            'default' => true,
        ];

        $this->controls['ajax'] = [
            'tab' => 'content',
            'label' => __('AJAX Submission', 'nexusforms'),
            'type' => 'checkbox',
            'default' => true,
        ];
    }

    /**
     * Render element.
     *
     * @since 1.0.0
     */
    public function render(): void {
        $settings = $this->settings;
        $form_id = (int) ($settings['form_id'] ?? 0);

        if (!$form_id) {
            echo '<p>' . esc_html__('Please select a form.', 'nexusforms') . '</p>';
            return;
        }

        $renderer = new NexusForms_Renderer();

        echo $renderer->render_form($form_id, [
            'title' => $settings['show_title'] ?? true,
            'description' => $settings['show_description'] ?? true,
            'ajax' => $settings['ajax'] ?? true,
        ]);
    }
}
