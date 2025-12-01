/**
 * Confirmations settings component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl, TextareaControl, Notice } from '@wordpress/components';
import { useFormStore } from '../store/formStore';

export const Confirmations = () => {
    const { formSettings, updateFormSettings } = useFormStore();

    const confirmation = formSettings?.confirmation || {
        type: 'message',
        message: '',
        redirectUrl: '',
        pageContent: '',
    };

    const updateConfirmation = (field, value) => {
        updateFormSettings({
            confirmation: {
                ...confirmation,
                [field]: value,
            },
        });
    };

    return (
        <div className="nexusforms-confirmations">
            <Notice status="info" isDismissible={false}>
                <p>
                    {__('Choose how to confirm successful form submissions to users.', 'nexusforms')}
                </p>
            </Notice>

            <SelectControl
                label={__('Confirmation Type', 'nexusforms')}
                value={confirmation.type}
                options={[
                    { label: __('Message', 'nexusforms'), value: 'message' },
                    { label: __('Redirect', 'nexusforms'), value: 'redirect' },
                    { label: __('Page Content', 'nexusforms'), value: 'page' },
                ]}
                onChange={(value) => updateConfirmation('type', value)}
                help={__('Select how to display confirmation after form submission', 'nexusforms')}
            />

            {confirmation.type === 'message' && (
                <TextareaControl
                    label={__('Success Message', 'nexusforms')}
                    value={confirmation.message}
                    onChange={(value) => updateConfirmation('message', value)}
                    placeholder={__('Form submitted successfully!', 'nexusforms')}
                    rows={4}
                    help={__('This message will be shown after successful submission', 'nexusforms')}
                />
            )}

            {confirmation.type === 'redirect' && (
                <>
                    <TextControl
                        label={__('Redirect URL', 'nexusforms')}
                        value={confirmation.redirectUrl}
                        onChange={(value) => updateConfirmation('redirectUrl', value)}
                        placeholder="https://example.com/thank-you"
                        type="url"
                        help={__('Users will be redirected to this URL after submission', 'nexusforms')}
                    />
                    <TextControl
                        label={__('Redirect Message (Optional)', 'nexusforms')}
                        value={confirmation.message}
                        onChange={(value) => updateConfirmation('message', value)}
                        placeholder={__('Redirecting...', 'nexusforms')}
                        help={__('Brief message shown before redirect (1.5 seconds)', 'nexusforms')}
                    />
                </>
            )}

            {confirmation.type === 'page' && (
                <TextareaControl
                    label={__('Page Content', 'nexusforms')}
                    value={confirmation.pageContent}
                    onChange={(value) => updateConfirmation('pageContent', value)}
                    placeholder={__('Thank you for your submission!', 'nexusforms')}
                    rows={8}
                    help={__('HTML allowed. This content will replace the form after submission.', 'nexusforms')}
                />
            )}
        </div>
    );
};
