<?php
/**
 * Form template.
 *
 * @package NexusForms
 * @since 1.0.0
 *
 * @var object $form Form object.
 * @var array $args Rendering arguments.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

$form_classes = apply_filters('nexusforms_form_classes', [
    'nexusforms-form',
    'nexusforms-form-' . $form->id,
], $form);
?>

<div class="nexusforms-wrapper" id="nexusforms-wrapper-<?php echo esc_attr($form->id); ?>">
    <?php if ($args['title'] && $form->title): ?>
        <h2 class="nexusforms-title"><?php echo esc_html($form->title); ?></h2>
    <?php endif; ?>

    <?php if ($args['description'] && $form->description): ?>
        <div class="nexusforms-description"><?php echo wp_kses_post(wpautop($form->description)); ?></div>
    <?php endif; ?>

    <form
        id="nexusforms-form-<?php echo esc_attr($form->id); ?>"
        class="<?php echo esc_attr(implode(' ', $form_classes)); ?>"
        method="post"
        action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
        data-form-id="<?php echo esc_attr($form->id); ?>"
        data-ajax="<?php echo esc_attr($args['ajax'] ? 'true' : 'false'); ?>"
        role="form"
        aria-label="<?php echo esc_attr($form->title); ?>"
        novalidate
    >
        <?php
        /**
         * Fires before form fields are rendered.
         *
         * @since 1.0.0
         * @param object $form Form object.
         */
        do_action('nexusforms_before_fields', $form);
        ?>

        <div class="nexusforms-fields">
            <?php foreach ($form->fields as $field): ?>
                <?php
                $renderer = new NexusForms_Renderer();
                echo $renderer->build_field_html($field->field_data, $form->id);
                ?>
            <?php endforeach; ?>
        </div>

        <?php
        /**
         * Fires after form fields are rendered.
         *
         * @since 1.0.0
         * @param object $form Form object.
         */
        do_action('nexusforms_after_fields', $form);
        ?>

        <!-- Honeypot field for spam protection -->
        <input
            type="text"
            name="nexusforms_hp"
            value=""
            style="position:absolute;left:-9999px;width:1px;height:1px;"
            tabindex="-1"
            autocomplete="off"
            aria-hidden="true"
        >

        <!-- Nonce field -->
        <?php wp_nonce_field('nexusforms_submit_' . $form->id, '_wpnonce'); ?>

        <!-- Hidden fields -->
        <input type="hidden" name="action" value="nexusforms_submit">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">

        <!-- Submit button -->
        <div class="nexusforms-submit-wrapper">
            <button
                type="submit"
                class="nexusforms-submit"
                id="nexusforms-submit-<?php echo esc_attr($form->id); ?>"
            >
                <span class="nexusforms-submit-text">
                    <?php echo esc_html($form->settings['submit_text'] ?? __('Submit', 'nexusforms')); ?>
                </span>
                <span class="nexusforms-submit-spinner" style="display:none;">
                    <?php echo esc_html__('Submitting...', 'nexusforms'); ?>
                </span>
            </button>
        </div>

        <!-- Message container -->
        <div
            class="nexusforms-message"
            id="nexusforms-message-<?php echo esc_attr($form->id); ?>"
            role="alert"
            aria-live="polite"
            aria-atomic="true"
        ></div>
    </form>

    <?php
    /**
     * Fires after the form is rendered.
     *
     * @since 1.0.0
     * @param object $form Form object.
     */
    do_action('nexusforms_after_form', $form);
    ?>
</div>
