/**
 * Field types configuration.
 *
 * @package NexusForms
 * @since 1.0.0
 */

export const FIELD_TYPES = [
    {
        type: 'text',
        label: 'Text Input',
        icon: '📝',
        description: 'Single line text input',
        category: 'basic',
    },
    {
        type: 'email',
        label: 'Email',
        icon: '📧',
        description: 'Email address input',
        category: 'basic',
    },
    {
        type: 'textarea',
        label: 'Textarea',
        icon: '📄',
        description: 'Multi-line text input',
        category: 'basic',
    },
    {
        type: 'number',
        label: 'Number',
        icon: '🔢',
        description: 'Numeric input',
        category: 'basic',
    },
    {
        type: 'tel',
        label: 'Phone',
        icon: '📱',
        description: 'Phone number input',
        category: 'basic',
    },
    {
        type: 'url',
        label: 'URL',
        icon: '🔗',
        description: 'Website URL input',
        category: 'basic',
    },
    {
        type: 'select',
        label: 'Dropdown',
        icon: '▼',
        description: 'Select from options',
        category: 'choice',
    },
    {
        type: 'radio',
        label: 'Radio Buttons',
        icon: '◉',
        description: 'Single choice from options',
        category: 'choice',
    },
    {
        type: 'checkbox',
        label: 'Checkboxes',
        icon: '☑',
        description: 'Multiple choice options',
        category: 'choice',
    },
    {
        type: 'file',
        label: 'File Upload',
        icon: '📎',
        description: 'File upload field',
        category: 'advanced',
        pro: true,
    },
];

export const FIELD_CATEGORIES = [
    { id: 'basic', label: 'Basic Fields' },
    { id: 'choice', label: 'Choice Fields' },
    { id: 'advanced', label: 'Advanced Fields' },
];
