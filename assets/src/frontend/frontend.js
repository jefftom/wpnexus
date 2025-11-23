/**
 * Frontend JavaScript for NexusForms.
 *
 * @package NexusForms
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * NexusForms frontend handler.
     */
    const NexusForms = {
        /**
         * Initialize.
         */
        init() {
            this.bindEvents();
        },

        /**
         * Bind events.
         */
        bindEvents() {
            $(document).on('submit', '.nexusforms-form', this.handleSubmit.bind(this));
            $(document).on('change', '.nexusforms-input', this.handleFieldChange.bind(this));
        },

        /**
         * Handle form submission.
         *
         * @param {Event} e Submit event.
         */
        async handleSubmit(e) {
            const $form = $(e.target);
            const ajax = $form.data('ajax') === 'true' || $form.data('ajax') === true;

            // If AJAX is disabled, allow normal form submission.
            if (!ajax) {
                return;
            }

            e.preventDefault();

            const formId = $form.data('form-id');
            const $submitBtn = $form.find('.nexusforms-submit');
            const $message = $form.find('.nexusforms-message');

            // Clear previous messages and errors.
            $message.html('').removeClass('success error');
            $form.find('.nexusforms-error').html('');
            $form.find('.nexusforms-input').removeClass('error');

            // Show loading state.
            $submitBtn.prop('disabled', true);
            $submitBtn.find('.nexusforms-submit-text').hide();
            $submitBtn.find('.nexusforms-submit-spinner').show();

            try {
                const formData = new FormData($form[0]);

                const response = await $.ajax({
                    url: nexusformsData.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                });

                // Success.
                $message.html(response.data.message).addClass('success').show();
                $form[0].reset();

                // Trigger custom event.
                $(document).trigger('nexusforms:submitted', [formId, response.data]);

            } catch (error) {
                const response = error.responseJSON || {};

                // Show general error message.
                const message = response.data?.message || nexusformsData.i18n.error;
                $message.html(message).addClass('error').show();

                // Show field-specific errors.
                if (response.data?.errors) {
                    Object.keys(response.data.errors).forEach((fieldId) => {
                        const $field = $form.find(`#${fieldId}`);
                        const $error = $form.find(`#${fieldId}-error`);

                        $field.addClass('error').attr('aria-invalid', 'true');
                        $error.html(response.data.errors[fieldId]);
                    });
                }

                // Trigger custom event.
                $(document).trigger('nexusforms:error', [formId, error]);
            } finally {
                // Reset button state.
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.nexusforms-submit-text').show();
                $submitBtn.find('.nexusforms-submit-spinner').hide();
            }
        },

        /**
         * Handle field change.
         *
         * @param {Event} e Change event.
         */
        handleFieldChange(e) {
            const $field = $(e.target);
            const $error = $field.siblings('.nexusforms-error');

            // Clear error on field change.
            if ($field.hasClass('error')) {
                $field.removeClass('error').attr('aria-invalid', 'false');
                $error.html('');
            }

            // Client-side validation.
            this.validateField($field);
        },

        /**
         * Validate a field.
         *
         * @param {jQuery} $field Field element.
         * @return {boolean} Is valid.
         */
        validateField($field) {
            const value = $field.val();
            const required = $field.prop('required');
            const type = $field.attr('type');
            const $error = $field.siblings('.nexusforms-error');

            // Required validation.
            if (required && !value) {
                this.showFieldError($field, $error, nexusformsData.i18n.required);
                return false;
            }

            // Email validation.
            if (type === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    this.showFieldError($field, $error, nexusformsData.i18n.invalidEmail);
                    return false;
                }
            }

            return true;
        },

        /**
         * Show field error.
         *
         * @param {jQuery} $field Field element.
         * @param {jQuery} $error Error element.
         * @param {string} message Error message.
         */
        showFieldError($field, $error, message) {
            $field.addClass('error').attr('aria-invalid', 'true');
            $error.html(message);
        },
    };

    // Initialize on document ready.
    $(document).ready(() => {
        NexusForms.init();
    });

})(jQuery);
