<?php
/**
 * Elementor widget class.
 *
 * @package NexusForms
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NexusForms Elementor Widget.
 *
 * @since 1.0.0
 */
class NexusForms_Elementor_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * @since 1.0.0
     * @return string Widget name.
     */
    public function get_name(): string {
        return 'nexusforms';
    }

    /**
     * Get widget title.
     *
     * @since 1.0.0
     * @return string Widget title.
     */
    public function get_title(): string {
        return __('NexusForms', 'nexusforms');
    }

    /**
     * Get widget icon.
     *
     * @since 1.0.0
     * @return string Widget icon.
     */
    public function get_icon(): string {
        return 'eicon-form-horizontal';
    }

    /**
     * Get widget categories.
     *
     * @since 1.0.0
     * @return array Widget categories.
     */
    public function get_categories(): array {
        return ['nexusforms', 'general'];
    }

    /**
     * Register widget controls.
     *
     * @since 1.0.0
     */
    protected function register_controls(): void {
        $this->start_controls_section(
            'section_form',
            [
                'label' => __('Form Settings', 'nexusforms'),
            ]
        );

        $forms = NexusForms_Integrations::get_forms_dropdown();

        $this->add_control(
            'form_id',
            [
                'label' => __('Select Form', 'nexusforms'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $forms,
                'default' => '',
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Title', 'nexusforms'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'nexusforms'),
                'label_off' => __('No', 'nexusforms'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label' => __('Show Description', 'nexusforms'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'nexusforms'),
                'label_off' => __('No', 'nexusforms'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'ajax',
            [
                'label' => __('AJAX Submission', 'nexusforms'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'nexusforms'),
                'label_off' => __('No', 'nexusforms'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     *
     * @since 1.0.0
     */
    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $form_id = (int) $settings['form_id'];

        if (!$form_id) {
            echo '<p>' . esc_html__('Please select a form.', 'nexusforms') . '</p>';
            return;
        }

        $renderer = new NexusForms_Renderer();

        echo $renderer->render_form($form_id, [
            'title' => $settings['show_title'] === 'yes',
            'description' => $settings['show_description'] === 'yes',
            'ajax' => $settings['ajax'] === 'yes',
        ]);
    }
}
