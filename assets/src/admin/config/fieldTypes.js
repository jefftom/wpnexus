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
    {
        type: 'date',
        label: 'Date Picker',
        icon: '📅',
        description: 'Date selection field',
        category: 'advanced',
    },
    {
        type: 'rating',
        label: 'Rating',
        icon: '⭐',
        description: 'Star rating field',
        category: 'advanced',
    },
    {
        type: 'signature',
        label: 'Signature',
        icon: '✍️',
        description: 'Signature drawing pad',
        category: 'advanced',
    },
    {
        type: 'hidden',
        label: 'Hidden Field',
        icon: '👁️',
        description: 'Hidden value field',
        category: 'advanced',
    },
    {
        type: 'time',
        label: 'Time Picker',
        icon: '🕐',
        description: 'Time selection field',
        category: 'advanced',
    },
    {
        type: 'name',
        label: 'Name',
        icon: '👤',
        description: 'First and last name fields',
        category: 'advanced',
    },
    {
        type: 'address',
        label: 'Address',
        icon: '🏠',
        description: 'Full address with multiple fields',
        category: 'advanced',
    },
    {
        type: 'multiselect',
        label: 'Multi-Select',
        icon: '☰',
        description: 'Select multiple options',
        category: 'choice',
    },
    {
        type: 'consent',
        label: 'Consent',
        icon: '✓',
        description: 'Agreement checkbox with text',
        category: 'advanced',
    },
    {
        type: 'list',
        label: 'List',
        icon: '📋',
        description: 'Repeatable list of items',
        category: 'advanced',
    },
    {
        type: 'html',
        label: 'HTML Content',
        icon: '📰',
        description: 'Display HTML content',
        category: 'layout',
    },
    {
        type: 'section',
        label: 'Section Break',
        icon: '➖',
        description: 'Visual section divider',
        category: 'layout',
    },
];

export const FIELD_CATEGORIES = [
    { id: 'basic', label: 'Basic Fields' },
    { id: 'choice', label: 'Choice Fields' },
    { id: 'advanced', label: 'Advanced Fields' },
    { id: 'layout', label: 'Layout Fields' },
];
