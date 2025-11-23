/**
 * Email Notification Builder component.
 *
 * Visual email template builder with smart tags and templates.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    TextControl,
    TextareaControl,
    SelectControl,
    ToggleControl,
    PanelBody,
    Icon,
    Popover,
} from '@wordpress/components';
import { useFormStore } from '../store/formStore';

// Email templates
const EMAIL_TEMPLATES = [
    {
        id: 'default',
        name: __('Default', 'nexusforms'),
        subject: __('New form submission: {form_title}', 'nexusforms'),
        body: `<h2>{form_title}</h2>
<p>${__('A new form submission has been received.', 'nexusforms')}</p>

<h3>${__('Submission Details:', 'nexusforms')}</h3>
{all_fields}

<hr>
<p><small>${__('Submitted on {date} at {time}', 'nexusforms')}</small></p>`,
    },
    {
        id: 'simple',
        name: __('Simple', 'nexusforms'),
        subject: __('New submission from {form_title}', 'nexusforms'),
        body: `{all_fields}

---
${__('Submitted on {date} at {time}', 'nexusforms')}`,
    },
    {
        id: 'detailed',
        name: __('Detailed', 'nexusforms'),
        subject: __('[{site_name}] New submission: {form_title}', 'nexusforms'),
        body: `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #0073aa; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .field { margin-bottom: 15px; padding: 10px; background: white; border-left: 3px solid #0073aa; }
        .field-label { font-weight: bold; color: #0073aa; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{form_title}</h1>
        </div>
        <div class="content">
            <p>${__('A new form submission has been received from your website.', 'nexusforms')}</p>

            <h3>${__('Submission Details:', 'nexusforms')}</h3>
            {all_fields}
        </div>
        <div class="footer">
            <p>${__('Submitted on {date} at {time} from {ip_address}', 'nexusforms')}</p>
            <p>{site_name} - {site_url}</p>
        </div>
    </div>
</body>
</html>`,
    },
];

// Available smart tags
const SMART_TAGS = [
    { tag: '{form_title}', label: __('Form Title', 'nexusforms') },
    { tag: '{form_id}', label: __('Form ID', 'nexusforms') },
    { tag: '{all_fields}', label: __('All Fields', 'nexusforms') },
    { tag: '{entry_id}', label: __('Entry ID', 'nexusforms') },
    { tag: '{date}', label: __('Submission Date', 'nexusforms') },
    { tag: '{time}', label: __('Submission Time', 'nexusforms') },
    { tag: '{ip_address}', label: __('IP Address', 'nexusforms') },
    { tag: '{user_agent}', label: __('User Agent', 'nexusforms') },
    { tag: '{site_name}', label: __('Site Name', 'nexusforms') },
    { tag: '{site_url}', label: __('Site URL', 'nexusforms') },
    { tag: '{admin_email}', label: __('Admin Email', 'nexusforms') },
];

export const EmailBuilder = () => {
    const { formSettings, setFormSettings, fields } = useFormStore();
    const [showSmartTags, setShowSmartTags] = useState(false);
    const [cursorPosition, setCursorPosition] = useState(null);
    const [activeField, setActiveField] = useState('body');

    const emailSettings = formSettings.email || {
        enabled: true,
        to: '{admin_email}',
        from: '{admin_email}',
        fromName: '{site_name}',
        replyTo: '',
        subject: __('New form submission: {form_title}', 'nexusforms'),
        body: EMAIL_TEMPLATES[0].body,
        format: 'html',
    };

    const handleUpdate = (key, value) => {
        setFormSettings({
            ...formSettings,
            email: {
                ...emailSettings,
                [key]: value,
            },
        });
    };

    const handleApplyTemplate = (template) => {
        handleUpdate('subject', template.subject);
        handleUpdate('body', template.body);
    };

    const insertSmartTag = (tag) => {
        const field = activeField;
        const currentValue = emailSettings[field] || '';

        if (cursorPosition !== null) {
            const before = currentValue.substring(0, cursorPosition);
            const after = currentValue.substring(cursorPosition);
            handleUpdate(field, before + tag + after);
        } else {
            handleUpdate(field, currentValue + tag);
        }

        setShowSmartTags(false);
    };

    const handleFieldFocus = (field, e) => {
        setActiveField(field);
        if (e.target.selectionStart !== undefined) {
            setCursorPosition(e.target.selectionStart);
        }
    };

    // Add field-specific smart tags
    const fieldSmartTags = fields.map((field) => ({
        tag: `{${field.id}}`,
        label: field.label || field.id,
    }));

    const allSmartTags = [...SMART_TAGS, ...fieldSmartTags];

    return (
        <div className="email-builder">
            <PanelBody title={__('Email Notifications', 'nexusforms')} initialOpen={true}>
                <ToggleControl
                    label={__('Enable Email Notifications', 'nexusforms')}
                    checked={emailSettings.enabled}
                    onChange={(value) => handleUpdate('enabled', value)}
                    help={__('Send email notifications when forms are submitted', 'nexusforms')}
                />

                {emailSettings.enabled && (
                    <>
                        <div className="email-templates">
                            <label className="components-base-control__label">
                                {__('Email Templates', 'nexusforms')}
                            </label>
                            <div className="template-buttons">
                                {EMAIL_TEMPLATES.map((template) => (
                                    <Button
                                        key={template.id}
                                        variant="secondary"
                                        size="small"
                                        onClick={() => handleApplyTemplate(template)}
                                    >
                                        {template.name}
                                    </Button>
                                ))}
                            </div>
                        </div>

                        <hr />

                        <TextControl
                            label={__('To Email', 'nexusforms')}
                            value={emailSettings.to}
                            onChange={(value) => handleUpdate('to', value)}
                            help={__('Recipient email address (use {admin_email} or field ID)', 'nexusforms')}
                        />

                        <TextControl
                            label={__('From Email', 'nexusforms')}
                            value={emailSettings.from}
                            onChange={(value) => handleUpdate('from', value)}
                        />

                        <TextControl
                            label={__('From Name', 'nexusforms')}
                            value={emailSettings.fromName}
                            onChange={(value) => handleUpdate('fromName', value)}
                        />

                        <TextControl
                            label={__('Reply To', 'nexusforms')}
                            value={emailSettings.replyTo}
                            onChange={(value) => handleUpdate('replyTo', value)}
                            help={__('Optional reply-to email address', 'nexusforms')}
                        />

                        <SelectControl
                            label={__('Email Format', 'nexusforms')}
                            value={emailSettings.format}
                            options={[
                                { label: __('HTML', 'nexusforms'), value: 'html' },
                                { label: __('Plain Text', 'nexusforms'), value: 'text' },
                            ]}
                            onChange={(value) => handleUpdate('format', value)}
                        />

                        <div className="smart-tag-controls">
                            <Button
                                variant="secondary"
                                size="small"
                                icon="tag"
                                onClick={() => setShowSmartTags(!showSmartTags)}
                            >
                                {__('Insert Smart Tag', 'nexusforms')}
                            </Button>

                            {showSmartTags && (
                                <div className="smart-tags-popover">
                                    <div className="smart-tags-header">
                                        <strong>{__('Smart Tags', 'nexusforms')}</strong>
                                        <Button
                                            icon="no-alt"
                                            size="small"
                                            onClick={() => setShowSmartTags(false)}
                                            label={__('Close', 'nexusforms')}
                                        />
                                    </div>
                                    <div className="smart-tags-list">
                                        <div className="smart-tags-section">
                                            <h4>{__('Form Tags', 'nexusforms')}</h4>
                                            {SMART_TAGS.map((item) => (
                                                <Button
                                                    key={item.tag}
                                                    variant="tertiary"
                                                    size="small"
                                                    onClick={() => insertSmartTag(item.tag)}
                                                    className="smart-tag-button"
                                                >
                                                    <code>{item.tag}</code>
                                                    <span>{item.label}</span>
                                                </Button>
                                            ))}
                                        </div>

                                        {fieldSmartTags.length > 0 && (
                                            <div className="smart-tags-section">
                                                <h4>{__('Field Tags', 'nexusforms')}</h4>
                                                {fieldSmartTags.map((item) => (
                                                    <Button
                                                        key={item.tag}
                                                        variant="tertiary"
                                                        size="small"
                                                        onClick={() => insertSmartTag(item.tag)}
                                                        className="smart-tag-button"
                                                    >
                                                        <code>{item.tag}</code>
                                                        <span>{item.label}</span>
                                                    </Button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>

                        <TextControl
                            label={__('Email Subject', 'nexusforms')}
                            value={emailSettings.subject}
                            onChange={(value) => handleUpdate('subject', value)}
                            onFocus={(e) => handleFieldFocus('subject', e)}
                            onClick={(e) => setCursorPosition(e.target.selectionStart)}
                        />

                        <TextareaControl
                            label={__('Email Body', 'nexusforms')}
                            value={emailSettings.body}
                            onChange={(value) => handleUpdate('body', value)}
                            onFocus={(e) => handleFieldFocus('body', e)}
                            onClick={(e) => setCursorPosition(e.target.selectionStart)}
                            rows={15}
                            help={
                                emailSettings.format === 'html'
                                    ? __('Use HTML for formatting', 'nexusforms')
                                    : __('Plain text only', 'nexusforms')
                            }
                        />

                        <div className="email-preview-notice">
                            <Icon icon="info" />
                            <p>
                                {__(
                                    'Smart tags will be replaced with actual values when the form is submitted.',
                                    'nexusforms'
                                )}
                            </p>
                        </div>
                    </>
                )}
            </PanelBody>
        </div>
    );
};
