/**
 * Gutenberg block for NexusForms.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Register the NexusForms block.
 */
registerBlockType('nexusforms/form', {
    title: __('NexusForms', 'nexusforms'),
    description: __('Add a NexusForms form to your page', 'nexusforms'),
    category: 'common',
    icon: 'feedback',
    keywords: [__('form', 'nexusforms'), __('contact', 'nexusforms'), __('nexusforms', 'nexusforms')],
    supports: {
        html: false,
    },
    attributes: {
        formId: {
            type: 'number',
            default: 0,
        },
        showTitle: {
            type: 'boolean',
            default: true,
        },
        showDescription: {
            type: 'boolean',
            default: true,
        },
        ajax: {
            type: 'boolean',
            default: true,
        },
    },

    /**
     * Block editor.
     */
    edit({ attributes, setAttributes }) {
        const { formId, showTitle, showDescription, ajax } = attributes;

        // Get forms from localized data.
        const forms = window.nexusformsBlock?.forms || {};
        const formOptions = Object.keys(forms).map((id) => ({
            label: forms[id],
            value: id,
        }));

        return (
            <div className="nexusforms-block-editor">
                <div className="nexusforms-block-controls">
                    <SelectControl
                        label={__('Select Form', 'nexusforms')}
                        value={formId}
                        options={formOptions}
                        onChange={(value) => setAttributes({ formId: parseInt(value) })}
                    />

                    <ToggleControl
                        label={__('Show Title', 'nexusforms')}
                        checked={showTitle}
                        onChange={(value) => setAttributes({ showTitle: value })}
                    />

                    <ToggleControl
                        label={__('Show Description', 'nexusforms')}
                        checked={showDescription}
                        onChange={(value) => setAttributes({ showDescription: value })}
                    />

                    <ToggleControl
                        label={__('AJAX Submission', 'nexusforms')}
                        checked={ajax}
                        onChange={(value) => setAttributes({ ajax: value })}
                    />
                </div>

                <div className="nexusforms-block-preview">
                    {formId ? (
                        <div className="nexusforms-preview-notice">
                            <p>
                                {__('Form Preview:', 'nexusforms')} <strong>{forms[formId]}</strong>
                            </p>
                            <p className="description">
                                {__('The form will be displayed on the frontend.', 'nexusforms')}
                            </p>
                        </div>
                    ) : (
                        <div className="nexusforms-preview-notice">
                            <p>{__('Please select a form to display.', 'nexusforms')}</p>
                        </div>
                    )}
                </div>
            </div>
        );
    },

    /**
     * Block save (dynamic block - returns null).
     */
    save() {
        return null;
    },
});
