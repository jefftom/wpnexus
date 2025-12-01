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

            // Rating field events
            $(document).on('click', '.rating-star', this.handleRatingClick.bind(this));
            $(document).on('mouseenter', '.rating-star', this.handleRatingHover.bind(this));
            $(document).on('mouseleave', '.nexusforms-rating', this.handleRatingLeave.bind(this));

            // Signature field events
            this.initSignatureFields();
            $(document).on('click', '.signature-clear', this.handleSignatureClear.bind(this));
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

        /**
         * Handle rating star click.
         *
         * @param {Event} e Click event.
         */
        handleRatingClick(e) {
            const $star = $(e.target);
            const $rating = $star.closest('.nexusforms-rating');
            const value = $star.data('value');
            const fieldId = $rating.data('field-id');

            // Update hidden input
            $rating.find('input[type="hidden"]').val(value);

            // Update visual stars
            $rating.find('.rating-star').each(function (index) {
                if (index < value) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        },

        /**
         * Handle rating star hover.
         *
         * @param {Event} e Hover event.
         */
        handleRatingHover(e) {
            const $star = $(e.target);
            const $rating = $star.closest('.nexusforms-rating');
            const value = $star.data('value');

            // Update visual stars
            $rating.find('.rating-star').each(function (index) {
                if (index < value) {
                    $(this).addClass('hover');
                } else {
                    $(this).removeClass('hover');
                }
            });
        },

        /**
         * Handle rating leave.
         *
         * @param {Event} e Leave event.
         */
        handleRatingLeave(e) {
            const $rating = $(e.target).closest('.nexusforms-rating');
            $rating.find('.rating-star').removeClass('hover');
        },

        /**
         * Initialize signature fields.
         */
        initSignatureFields() {
            $('.nexusforms-signature-canvas').each(function () {
                const canvas = this;
                const ctx = canvas.getContext('2d');
                let isDrawing = false;
                let lastX = 0;
                let lastY = 0;

                // Set up canvas
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                // Mouse events
                $(canvas).on('mousedown', function (e) {
                    isDrawing = true;
                    const rect = canvas.getBoundingClientRect();
                    lastX = e.clientX - rect.left;
                    lastY = e.clientY - rect.top;
                });

                $(canvas).on('mousemove', function (e) {
                    if (!isDrawing) return;

                    const rect = canvas.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(x, y);
                    ctx.stroke();

                    lastX = x;
                    lastY = y;

                    // Update hidden input with base64 data
                    const $input = $(canvas).siblings('input[type="hidden"]');
                    $input.val(canvas.toDataURL());
                });

                $(canvas).on('mouseup mouseleave', function () {
                    isDrawing = false;
                });

                // Touch events
                $(canvas).on('touchstart', function (e) {
                    e.preventDefault();
                    isDrawing = true;
                    const rect = canvas.getBoundingClientRect();
                    const touch = e.touches[0];
                    lastX = touch.clientX - rect.left;
                    lastY = touch.clientY - rect.top;
                });

                $(canvas).on('touchmove', function (e) {
                    e.preventDefault();
                    if (!isDrawing) return;

                    const rect = canvas.getBoundingClientRect();
                    const touch = e.touches[0];
                    const x = touch.clientX - rect.left;
                    const y = touch.clientY - rect.top;

                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(x, y);
                    ctx.stroke();

                    lastX = x;
                    lastY = y;

                    // Update hidden input with base64 data
                    const $input = $(canvas).siblings('input[type="hidden"]');
                    $input.val(canvas.toDataURL());
                });

                $(canvas).on('touchend', function () {
                    isDrawing = false;
                });
            });
        },

        /**
         * Handle signature clear.
         *
         * @param {Event} e Click event.
         */
        handleSignatureClear(e) {
            e.preventDefault();
            const $button = $(e.target);
            const canvasId = $button.data('canvas');
            const canvas = document.getElementById(canvasId);

            if (canvas) {
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // Clear hidden input
                const $input = $(canvas).siblings('input[type="hidden"]');
                $input.val('');
            }
        },
    };

    // Initialize on document ready.
    $(document).ready(() => {
        NexusForms.init();
    });

})(jQuery);
