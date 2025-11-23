/**
 * Form Preview Component
 *
 * Preview form as users will see it on the frontend.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, Spinner, Notice } from '@wordpress/components';
import { useQuery } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

export const FormPreview = ({ formId, buttonText, buttonVariant = 'secondary' }) => {
    const [isOpen, setIsOpen] = useState(false);

    // Fetch form data
    const { data: form, isLoading, error } = useQuery({
        queryKey: ['form', formId],
        queryFn: async () => {
            return await apiFetch({
                path: `/nexusforms/v1/forms/${formId}`,
            });
        },
        enabled: isOpen, // Only fetch when modal is open
    });

    const renderFieldPreview = (field) => {
        const fieldData = field.field_data || field;
        const { type, label, placeholder, required, options, description } = fieldData;

        const fieldClasses = `nexusforms-field nexusforms-field-${type}`;

        return (
            <div key={fieldData.id} className={fieldClasses}>
                {label && (
                    <label className="nexusforms-label">
                        {label}
                        {required && <span className="required">*</span>}
                    </label>
                )}

                {type === 'text' && (
                    <input
                        type="text"
                        className="nexusforms-input"
                        placeholder={placeholder}
                        disabled
                    />
                )}

                {type === 'email' && (
                    <input
                        type="email"
                        className="nexusforms-input"
                        placeholder={placeholder}
                        disabled
                    />
                )}

                {type === 'tel' && (
                    <input
                        type="tel"
                        className="nexusforms-input"
                        placeholder={placeholder}
                        disabled
                    />
                )}

                {type === 'url' && (
                    <input
                        type="url"
                        className="nexusforms-input"
                        placeholder={placeholder}
                        disabled
                    />
                )}

                {type === 'number' && (
                    <input
                        type="number"
                        className="nexusforms-input"
                        placeholder={placeholder}
                        disabled
                    />
                )}

                {type === 'textarea' && (
                    <textarea
                        className="nexusforms-input nexusforms-textarea"
                        placeholder={placeholder}
                        rows={4}
                        disabled
                    />
                )}

                {type === 'select' && (
                    <select className="nexusforms-input nexusforms-select" disabled>
                        <option value="">{__('Select...', 'nexusforms')}</option>
                        {(options || []).map((option, index) => (
                            <option key={index} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                )}

                {type === 'radio' && (
                    <div className="nexusforms-options">
                        {(options || []).map((option, index) => (
                            <label key={index} className="nexusforms-option">
                                <input type="radio" name={fieldData.id} value={option.value} disabled />
                                {option.label}
                            </label>
                        ))}
                    </div>
                )}

                {type === 'checkbox' && (
                    <div className="nexusforms-options">
                        {(options || []).map((option, index) => (
                            <label key={index} className="nexusforms-option">
                                <input type="checkbox" value={option.value} disabled />
                                {option.label}
                            </label>
                        ))}
                    </div>
                )}

                {type === 'file' && (
                    <input type="file" className="nexusforms-input nexusforms-file" disabled />
                )}

                {description && <p className="nexusforms-description">{description}</p>}
            </div>
        );
    };

    return (
        <>
            <Button variant={buttonVariant} onClick={() => setIsOpen(true)}>
                {buttonText || __('Preview', 'nexusforms')}
            </Button>

            {isOpen && (
                <Modal
                    title={__('Form Preview', 'nexusforms')}
                    onRequestClose={() => setIsOpen(false)}
                    className="nexusforms-preview-modal"
                    style={{ maxWidth: '800px' }}
                >
                    {isLoading ? (
                        <div className="preview-loading">
                            <Spinner />
                            <p>{__('Loading preview...', 'nexusforms')}</p>
                        </div>
                    ) : error ? (
                        <Notice status="error" isDismissible={false}>
                            {__('Failed to load form preview.', 'nexusforms')}
                        </Notice>
                    ) : (
                        <div className="form-preview-container">
                            <Notice status="info" isDismissible={false}>
                                {__(
                                    'This is a preview. The form is not functional in this view.',
                                    'nexusforms'
                                )}
                            </Notice>

                            <div className="nexusforms-wrapper preview">
                                {form.title && <h2 className="nexusforms-title">{form.title}</h2>}
                                {form.description && (
                                    <div
                                        className="nexusforms-description"
                                        dangerouslySetInnerHTML={{ __html: form.description }}
                                    />
                                )}

                                <div className="nexusforms-form-preview">
                                    <div className="nexusforms-fields">
                                        {form.fields && form.fields.length > 0 ? (
                                            form.fields.map((field) => renderFieldPreview(field))
                                        ) : (
                                            <p className="no-fields">
                                                {__(
                                                    'No fields added yet. Add fields to see them in the preview.',
                                                    'nexusforms'
                                                )}
                                            </p>
                                        )}
                                    </div>

                                    <div className="nexusforms-submit-wrapper">
                                        <button
                                            type="button"
                                            className="nexusforms-submit"
                                            disabled
                                        >
                                            {form.settings?.submit_button_text || __('Submit', 'nexusforms')}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="preview-info">
                                <p>
                                    <strong>{__('Shortcode:', 'nexusforms')}</strong>
                                    <code
                                        style={{ marginLeft: '8px', cursor: 'pointer' }}
                                        onClick={(e) => {
                                            navigator.clipboard.writeText(`[nexusforms id="${formId}"]`);
                                            e.target.textContent = __('Copied!', 'nexusforms');
                                            setTimeout(() => {
                                                e.target.textContent = `[nexusforms id="${formId}"]`;
                                            }, 2000);
                                        }}
                                    >
                                        [nexusforms id="{formId}"]
                                    </code>
                                </p>
                            </div>

                            <div className="modal-actions">
                                <Button variant="secondary" onClick={() => setIsOpen(false)}>
                                    {__('Close', 'nexusforms')}
                                </Button>
                                <Button
                                    variant="primary"
                                    href={`admin.php?page=nexusforms-new&id=${formId}`}
                                >
                                    {__('Edit Form', 'nexusforms')}
                                </Button>
                            </div>
                        </div>
                    )}
                </Modal>
            )}
        </>
    );
};
