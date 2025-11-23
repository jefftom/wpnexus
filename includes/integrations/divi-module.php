<?php
/**
 * DIVI module class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms DIVI Module.
 *
 * @since 1.0.0
 */
class NexusForms_Divi_Module extends ET_Builder_Module {

    /**
     * Module slug.
     *
     * @var string
     */
    public $slug = 'nexusforms_form';

    /**
     * Visual Builder support.
     *
     * @var string
     */
    public $vb_support = 'on';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function init(): void {
        $this->name = __('NexusForms', 'nexusforms');
        $this->icon_path = NEXUSFORMS_PLUGIN_DIR . 'assets/images/icon.svg';
    }

    /**
     * Get module fields.
     *
     * @since 1.0.0
     * @return array Module fields.
     */
    public function get_fields(): array {
        $forms = NexusForms_Integrations::get_forms_dropdown();

        return [
            'form_id' => [
                'label' => __('Select Form', 'nexusforms'),
                'type' => 'select',
                'options' => $forms,
                'default' => '',
            ],
            'show_title' => [
                'label' => __('Show Title', 'nexusforms'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Yes', 'nexusforms'),
                    'off' => __('No', 'nexusforms'),
                ],
                'default' => 'on',
            ],
            'show_description' => [
                'label' => __('Show Description', 'nexusforms'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Yes', 'nexusforms'),
                    'off' => __('No', 'nexusforms'),
                ],
                'default' => 'on',
            ],
            'ajax' => [
                'label' => __('AJAX Submission', 'nexusforms'),
                'type' => 'yes_no_button',
                'options' => [
                    'on' => __('Yes', 'nexusforms'),
                    'off' => __('No', 'nexusforms'),
                ],
                'default' => 'on',
            ],
        ];
    }

    /**
     * Render module.
     *
     * @since 1.0.0
     * @param array $attrs Module attributes.
     * @param string $content Module content.
     * @param string $render_slug Render slug.
     * @return string Module HTML.
     */
    public function render($attrs, $content, $render_slug): string {
        $form_id = (int) $this->props['form_id'];

        if (!$form_id) {
            return '<p>' . esc_html__('Please select a form.', 'nexusforms') . '</p>';
        }

        $renderer = new NexusForms_Renderer();

        return $renderer->render_form($form_id, [
            'title' => $this->props['show_title'] === 'on',
            'description' => $this->props['show_description'] === 'on',
            'ajax' => $this->props['ajax'] === 'on',
        ]);
    }
}

new NexusForms_Divi_Module();
