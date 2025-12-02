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
            this.initConditionalLogic();
            this.initMultiPageForms();
            this.initCalculations();
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

            // List field events
            $(document).on('click', '.list-add-row', this.handleListAddRow.bind(this));
            $(document).on('click', '.list-remove', this.handleListRemoveRow.bind(this));

            // Multi-page navigation events
            $(document).on('click', '.nexusforms-next-page', this.handleNextPage.bind(this));
            $(document).on('click', '.nexusforms-prev-page', this.handlePrevPage.bind(this));
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

                // Handle different confirmation types
                const confirmationType = response.data.confirmation_type || 'message';

                switch (confirmationType) {
                    case 'redirect':
                        // Show message briefly then redirect
                        if (response.data.message) {
                            $message.html(response.data.message).addClass('success').show();
                        }
                        setTimeout(() => {
                            window.location.href = response.data.redirect_url;
                        }, 1500);
                        break;

                    case 'page':
                        // Replace form with page content
                        $form.html(response.data.page_content);
                        break;

                    case 'message':
                    default:
                        // Show success message
                        $message.html(response.data.message || 'Form submitted successfully!').addClass('success').show();
                        $form[0].reset();
                        break;
                }

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

            // Evaluate conditional logic.
            const $form = $field.closest('.nexusforms-form');
            this.evaluateConditionalLogic($form);

            // Update calculations.
            this.updateCalculations($form);
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

        /**
         * Handle list add row.
         *
         * @param {Event} e Click event.
         */
        handleListAddRow(e) {
            e.preventDefault();
            const $button = $(e.target);
            const $wrapper = $button.closest('.nexusforms-list-wrapper');
            const $rows = $wrapper.find('.list-rows');
            const $firstRow = $rows.find('.list-row').first();
            const fieldId = $wrapper.data('field-id');
            const rowIndex = $rows.find('.list-row').length;

            // Clone the first row
            const $newRow = $firstRow.clone();

            // Clear input values
            $newRow.find('input').val('').each(function(index) {
                const $input = $(this);
                const name = $input.attr('name');
                // Update the row index in the name attribute
                const newName = name.replace(/\[\d+\]/, '[' + rowIndex + ']');
                $input.attr('name', newName);
            });

            // Add the new row
            $rows.append($newRow);
        },

        /**
         * Handle list remove row.
         *
         * @param {Event} e Click event.
         */
        handleListRemoveRow(e) {
            e.preventDefault();
            const $button = $(e.target);
            const $wrapper = $button.closest('.nexusforms-list-wrapper');
            const $rows = $wrapper.find('.list-rows');

            // Don't remove if it's the only row
            if ($rows.find('.list-row').length > 1) {
                $button.closest('.list-row').remove();

                // Reindex remaining rows
                $rows.find('.list-row').each(function(rowIndex) {
                    $(this).find('input').each(function() {
                        const $input = $(this);
                        const name = $input.attr('name');
                        const newName = name.replace(/\[\d+\]/, '[' + rowIndex + ']');
                        $input.attr('name', newName);
                    });
                });
            }
        },

        /**
         * Initialize conditional logic.
         */
        initConditionalLogic() {
            $('.nexusforms-form').each((index, form) => {
                const $form = $(form);
                // Store form schema in data attribute if not already there
                if (!$form.data('schema-loaded')) {
                    $form.data('schema-loaded', true);
                    // Evaluate conditional logic on page load
                    this.evaluateConditionalLogic($form);
                }
            });
        },

        /**
         * Evaluate conditional logic for all fields in a form.
         *
         * @param {jQuery} $form Form element.
         */
        evaluateConditionalLogic($form) {
            // Get form schema from data attribute or window object
            const formId = $form.data('form-id');
            const formSchema = window[`nexusforms_schema_${formId}`] || {};
            const fields = formSchema.fields || [];

            // Evaluate each field's conditional logic
            fields.forEach(field => {
                if (field.conditionalLogic && field.conditionalLogic.enabled) {
                    const shouldShow = this.evaluateRules(field.conditionalLogic, $form);
                    const $fieldWrapper = $form.find(`[data-field-id="${field.id}"]`).closest('.nexusforms-field');

                    if (shouldShow) {
                        this.showField($fieldWrapper);
                    } else {
                        this.hideField($fieldWrapper);
                    }
                }
            });
        },

        /**
         * Evaluate conditional logic rules.
         *
         * @param {Object} conditionalLogic Conditional logic configuration.
         * @param {jQuery} $form Form element.
         * @return {boolean} Should field be shown.
         */
        evaluateRules(conditionalLogic, $form) {
            const { action, logic, rules } = conditionalLogic;

            // Evaluate all rules
            const results = rules.map(rule => this.evaluateRule(rule, $form));

            // Determine if conditions are met
            let conditionsMet;
            if (logic === 'all') {
                conditionsMet = results.every(result => result === true);
            } else { // 'any'
                conditionsMet = results.some(result => result === true);
            }

            // Return based on action type
            if (action === 'show') {
                return conditionsMet;
            } else { // 'hide'
                return !conditionsMet;
            }
        },

        /**
         * Evaluate a single conditional logic rule.
         *
         * @param {Object} rule Rule to evaluate.
         * @param {jQuery} $form Form element.
         * @return {boolean} Does rule match.
         */
        evaluateRule(rule, $form) {
            const { field: fieldId, operator, value } = rule;

            // Get field value
            const $field = $form.find(`[data-field-id="${fieldId}"]`);
            if ($field.length === 0) {
                return false;
            }

            let fieldValue = this.getFieldValue($field);

            // Evaluate based on operator
            switch (operator) {
                case 'is':
                    return fieldValue === value;

                case 'is_not':
                    return fieldValue !== value;

                case 'contains':
                    return String(fieldValue).includes(value);

                case 'starts_with':
                    return String(fieldValue).startsWith(value);

                case 'ends_with':
                    return String(fieldValue).endsWith(value);

                case 'greater_than':
                    return parseFloat(fieldValue) > parseFloat(value);

                case 'less_than':
                    return parseFloat(fieldValue) < parseFloat(value);

                case 'is_empty':
                case 'empty':
                    return !fieldValue || fieldValue.length === 0;

                case 'is_not_empty':
                case 'not_empty':
                    return fieldValue && fieldValue.length > 0;

                default:
                    return false;
            }
        },

        /**
         * Get field value based on field type.
         *
         * @param {jQuery} $field Field element.
         * @return {string|Array} Field value.
         */
        getFieldValue($field) {
            const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();

            // Checkbox
            if (fieldType === 'checkbox') {
                if ($field.is('[name$="[]"]')) {
                    // Multiple checkboxes
                    const name = $field.attr('name');
                    const $checkboxes = $(`[name="${name}"]`);
                    const values = [];
                    $checkboxes.filter(':checked').each(function() {
                        values.push($(this).val());
                    });
                    return values;
                } else {
                    // Single checkbox
                    return $field.is(':checked') ? $field.val() : '';
                }
            }

            // Radio
            if (fieldType === 'radio') {
                const name = $field.attr('name');
                const $checked = $(`[name="${name}"]:checked`);
                return $checked.length > 0 ? $checked.val() : '';
            }

            // Select (including multi-select)
            if (fieldType === 'select') {
                if ($field.prop('multiple')) {
                    return $field.val() || [];
                }
                return $field.val() || '';
            }

            // Default (text, textarea, etc.)
            return $field.val() || '';
        },

        /**
         * Show a field with animation.
         *
         * @param {jQuery} $fieldWrapper Field wrapper element.
         */
        showField($fieldWrapper) {
            if ($fieldWrapper.length === 0) return;

            $fieldWrapper.removeClass('nexusforms-field-hidden').addClass('nexusforms-field-visible');
            $fieldWrapper.find('.nexusforms-input').prop('disabled', false);
        },

        /**
         * Hide a field with animation.
         *
         * @param {jQuery} $fieldWrapper Field wrapper element.
         */
        hideField($fieldWrapper) {
            if ($fieldWrapper.length === 0) return;

            $fieldWrapper.removeClass('nexusforms-field-visible').addClass('nexusforms-field-hidden');
            // Disable hidden fields so they don't get submitted
            $fieldWrapper.find('.nexusforms-input').prop('disabled', true);
        },

        /**
         * Initialize multi-page forms.
         */
        initMultiPageForms() {
            $('.nexusforms-form').each((index, form) => {
                const $form = $(form);
                const $pageBreaks = $form.find('.nexusforms-page-break');

                // Only initialize if form has page breaks
                if ($pageBreaks.length === 0) {
                    return;
                }

                // Split fields into pages
                const pages = this.splitFormIntoPages($form);

                if (pages.length <= 1) {
                    return;
                }

                // Store page data
                $form.data('pages', pages);
                $form.data('currentPage', 0);
                $form.data('totalPages', pages.length);

                // Add progress bar
                this.addProgressBar($form, pages.length);

                // Add navigation buttons to each page
                this.addNavigationButtons($form, pages);

                // Show first page
                this.showPage($form, 0);
            });
        },

        /**
         * Split form fields into pages based on page breaks.
         *
         * @param {jQuery} $form Form element.
         * @return {Array} Array of pages (each page is an array of field elements).
         */
        splitFormIntoPages($form) {
            const pages = [];
            let currentPage = [];

            $form.find('.nexusforms-field').each(function() {
                const $field = $(this);

                // If this is a page break, start a new page
                if ($field.find('.nexusforms-page-break').length > 0) {
                    if (currentPage.length > 0) {
                        pages.push(currentPage);
                        currentPage = [];
                    }
                    // Add the page break info to the previous page
                    if (pages.length > 0) {
                        const $pageBreak = $field.find('.nexusforms-page-break');
                        const pageTitle = $pageBreak.find('.page-title').text();
                        const pageDesc = $pageBreak.find('.page-description').text();
                        pages[pages.length - 1].pageTitle = pageTitle;
                        pages[pages.length - 1].pageDescription = pageDesc;
                    }
                } else {
                    currentPage.push($field);
                }
            });

            // Add the last page if it has fields
            if (currentPage.length > 0) {
                pages.push(currentPage);
            }

            return pages;
        },

        /**
         * Add progress bar to form.
         *
         * @param {jQuery} $form Form element.
         * @param {number} totalPages Total number of pages.
         */
        addProgressBar($form, totalPages) {
            const progressHTML = `
                <div class="nexusforms-progress-bar">
                    <div class="progress-text">
                        <span class="current-page">1</span> / <span class="total-pages">${totalPages}</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: ${(1 / totalPages) * 100}%"></div>
                    </div>
                </div>
            `;

            $form.prepend(progressHTML);
        },

        /**
         * Add navigation buttons to form.
         *
         * @param {jQuery} $form Form element.
         * @param {Array} pages Array of pages.
         */
        addNavigationButtons($form, pages) {
            const $submitButton = $form.find('button[type="submit"]');

            // Replace submit button with navigation container
            const navHTML = `
                <div class="nexusforms-page-navigation">
                    <button type="button" class="nexusforms-prev-page" style="display: none;">
                        ${this.getI18n('previous', 'Previous')}
                    </button>
                    <button type="button" class="nexusforms-next-page">
                        ${this.getI18n('next', 'Next')}
                    </button>
                    <button type="submit" class="nexusforms-submit-button" style="display: none;">
                        ${$submitButton.text() || this.getI18n('submit', 'Submit')}
                    </button>
                </div>
            `;

            $submitButton.replaceWith(navHTML);
        },

        /**
         * Show a specific page.
         *
         * @param {jQuery} $form Form element.
         * @param {number} pageIndex Page index to show.
         */
        showPage($form, pageIndex) {
            const pages = $form.data('pages');
            const totalPages = pages.length;

            // Hide all fields and page breaks
            $form.find('.nexusforms-field').hide();

            // Show fields on current page
            pages[pageIndex].forEach($field => {
                $field.show();
            });

            // Update current page
            $form.data('currentPage', pageIndex);

            // Update progress bar
            this.updateProgressBar($form, pageIndex + 1, totalPages);

            // Update navigation buttons
            this.updateNavigationButtons($form, pageIndex, totalPages);

            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $form.offset().top - 100
            }, 300);
        },

        /**
         * Update progress bar.
         *
         * @param {jQuery} $form Form element.
         * @param {number} currentPage Current page number (1-indexed).
         * @param {number} totalPages Total pages.
         */
        updateProgressBar($form, currentPage, totalPages) {
            const $progressBar = $form.find('.nexusforms-progress-bar');
            const percentage = (currentPage / totalPages) * 100;

            $progressBar.find('.current-page').text(currentPage);
            $progressBar.find('.progress-fill').css('width', `${percentage}%`);
        },

        /**
         * Update navigation buttons visibility.
         *
         * @param {jQuery} $form Form element.
         * @param {number} pageIndex Current page index (0-indexed).
         * @param {number} totalPages Total pages.
         */
        updateNavigationButtons($form, pageIndex, totalPages) {
            const $prevButton = $form.find('.nexusforms-prev-page');
            const $nextButton = $form.find('.nexusforms-next-page');
            const $submitButton = $form.find('.nexusforms-submit-button');

            // Show/hide previous button
            if (pageIndex === 0) {
                $prevButton.hide();
            } else {
                $prevButton.show();
            }

            // Show/hide next vs submit button
            if (pageIndex === totalPages - 1) {
                $nextButton.hide();
                $submitButton.show();
            } else {
                $nextButton.show();
                $submitButton.hide();
            }
        },

        /**
         * Handle next page button click.
         *
         * @param {Event} e Click event.
         */
        handleNextPage(e) {
            e.preventDefault();
            const $button = $(e.target);
            const $form = $button.closest('.nexusforms-form');
            const currentPage = $form.data('currentPage');
            const pages = $form.data('pages');

            // Validate current page before advancing
            if (!this.validatePage($form, currentPage)) {
                return;
            }

            // Go to next page
            if (currentPage < pages.length - 1) {
                this.showPage($form, currentPage + 1);
            }
        },

        /**
         * Handle previous page button click.
         *
         * @param {Event} e Click event.
         */
        handlePrevPage(e) {
            e.preventDefault();
            const $button = $(e.target);
            const $form = $button.closest('.nexusforms-form');
            const currentPage = $form.data('currentPage');

            // Go to previous page
            if (currentPage > 0) {
                this.showPage($form, currentPage - 1);
            }
        },

        /**
         * Validate all fields on a specific page.
         *
         * @param {jQuery} $form Form element.
         * @param {number} pageIndex Page index to validate.
         * @return {boolean} True if page is valid.
         */
        validatePage($form, pageIndex) {
            const pages = $form.data('pages');
            const pageFields = pages[pageIndex];
            let isValid = true;

            pageFields.forEach($field => {
                const $input = $field.find('.nexusforms-input');

                if ($input.length > 0) {
                    // Clear previous errors
                    $input.removeClass('error').attr('aria-invalid', 'false');
                    $field.find('.nexusforms-error').html('');

                    // Validate field
                    if (!this.validateField($input)) {
                        isValid = false;
                    }
                }
            });

            return isValid;
        },

        /**
         * Get internationalized string.
         *
         * @param {string} key I18n key.
         * @param {string} fallback Fallback text.
         * @return {string} Internationalized string.
         */
        getI18n(key, fallback) {
            return (window.nexusformsData && window.nexusformsData.i18n && window.nexusformsData.i18n[key]) || fallback;
        },

        /**
         * Initialize calculations.
         */
        initCalculations() {
            $('.nexusforms-form').each((index, form) => {
                const $form = $(form);
                const $calculations = $form.find('.nexusforms-calculation');

                if ($calculations.length > 0) {
                    // Calculate initial values
                    this.updateCalculations($form);
                }
            });
        },

        /**
         * Update all calculations in a form.
         *
         * @param {jQuery} $form Form element.
         */
        updateCalculations($form) {
            const $calculations = $form.find('.nexusforms-calculation');

            $calculations.each((index, element) => {
                const $calc = $(element);
                const formula = $calc.data('formula');
                const format = $calc.data('format');
                const decimals = $calc.data('decimals');
                const currency = $calc.data('currency');

                if (!formula) {
                    return;
                }

                // Evaluate the formula
                const result = this.evaluateFormula(formula, $form);

                // Format the result
                const formatted = this.formatCalculation(result, format, decimals, currency);

                // Update the display
                $calc.find('.calculation-value').text(formatted);
            });
        },

        /**
         * Evaluate a calculation formula.
         *
         * @param {string} formula Formula with {field_id} placeholders.
         * @param {jQuery} $form Form element.
         * @return {number} Calculated result.
         */
        evaluateFormula(formula, $form) {
            // Replace {field_id} with actual field values
            let expression = formula;

            // Find all {field_id} placeholders
            const fieldRefs = formula.match(/\{([^}]+)\}/g);

            if (fieldRefs) {
                fieldRefs.forEach(ref => {
                    const fieldId = ref.replace(/[{}]/g, '');
                    const $field = $form.find(`[data-field-id="${fieldId}"]`);

                    // Get field value
                    let value = 0;

                    if ($field.length > 0) {
                        // If it's a calculation field, get its calculated value
                        if ($field.hasClass('nexusforms-calculation')) {
                            const calcValue = $field.find('.calculation-value').text();
                            value = parseFloat(calcValue.replace(/[^0-9.-]/g, '')) || 0;
                        } else {
                            // Get input value
                            value = parseFloat(this.getFieldValue($field)) || 0;
                        }
                    }

                    // Replace placeholder with value
                    expression = expression.replace(ref, value);
                });
            }

            // Evaluate the mathematical expression
            try {
                // Use Function constructor for safe evaluation
                const result = new Function('return ' + expression)();
                return isNaN(result) ? 0 : result;
            } catch (e) {
                console.warn('NexusForms: Invalid calculation formula:', formula, e);
                return 0;
            }
        },

        /**
         * Format a calculation result.
         *
         * @param {number} value Value to format.
         * @param {string} format Format type (number, currency, percentage).
         * @param {number} decimals Number of decimal places.
         * @param {string} currency Currency symbol.
         * @return {string} Formatted value.
         */
        formatCalculation(value, format, decimals, currency) {
            // Ensure value is a number
            value = parseFloat(value) || 0;

            // Round to specified decimal places
            const multiplier = Math.pow(10, decimals);
            value = Math.round(value * multiplier) / multiplier;

            // Format with decimal places
            let formatted = value.toFixed(decimals);

            // Add thousands separators
            const parts = formatted.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            formatted = parts.join('.');

            return formatted;
        },
    };

    // Initialize on document ready.
    $(document).ready(() => {
        NexusForms.init();
    });

})(jQuery);
