/**
 * Field Templates Library component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, SearchControl, Icon } from '@wordpress/components';
import { useFormStore } from '../store/formStore';

// Pre-defined field templates
const FIELD_TEMPLATES = [
    {
        id: 'contact_form',
        name: __('Contact Form', 'nexusforms'),
        description: __('Standard contact form with name, email, and message', 'nexusforms'),
        icon: '📧',
        fields: [
            { id: 'name', type: 'text', label: 'Name', required: true, placeholder: 'Enter your name' },
            { id: 'email', type: 'email', label: 'Email Address', required: true, placeholder: 'your@email.com' },
            { id: 'subject', type: 'text', label: 'Subject', required: true },
            { id: 'message', type: 'textarea', label: 'Message', required: true, placeholder: 'Your message...' },
        ],
    },
    {
        id: 'registration',
        name: __('Registration Form', 'nexusforms'),
        description: __('User registration with personal details', 'nexusforms'),
        icon: '👤',
        fields: [
            { id: 'first_name', type: 'text', label: 'First Name', required: true },
            { id: 'last_name', type: 'text', label: 'Last Name', required: true },
            { id: 'email', type: 'email', label: 'Email', required: true },
            { id: 'phone', type: 'tel', label: 'Phone Number', required: false },
            { id: 'password', type: 'text', label: 'Password', required: true },
        ],
    },
    {
        id: 'feedback',
        name: __('Feedback Form', 'nexusforms'),
        description: __('Customer feedback with rating and comments', 'nexusforms'),
        icon: '⭐',
        fields: [
            { id: 'name', type: 'text', label: 'Your Name', required: true },
            { id: 'email', type: 'email', label: 'Email', required: true },
            {
                id: 'rating',
                type: 'radio',
                label: 'Overall Rating',
                required: true,
                options: [
                    { label: 'Excellent', value: '5' },
                    { label: 'Good', value: '4' },
                    { label: 'Average', value: '3' },
                    { label: 'Poor', value: '2' },
                    { label: 'Terrible', value: '1' },
                ],
            },
            { id: 'comments', type: 'textarea', label: 'Additional Comments', required: false },
        ],
    },
    {
        id: 'survey',
        name: __('Survey Form', 'nexusforms'),
        description: __('Multi-question survey template', 'nexusforms'),
        icon: '📊',
        fields: [
            {
                id: 'age_group',
                type: 'select',
                label: 'Age Group',
                required: true,
                options: [
                    { label: 'Under 18', value: 'under_18' },
                    { label: '18-24', value: '18_24' },
                    { label: '25-34', value: '25_34' },
                    { label: '35-44', value: '35_44' },
                    { label: '45-54', value: '45_54' },
                    { label: '55+', value: '55_plus' },
                ],
            },
            {
                id: 'interests',
                type: 'checkbox',
                label: 'Areas of Interest',
                required: false,
                options: [
                    { label: 'Technology', value: 'tech' },
                    { label: 'Sports', value: 'sports' },
                    { label: 'Music', value: 'music' },
                    { label: 'Travel', value: 'travel' },
                    { label: 'Food', value: 'food' },
                ],
            },
            { id: 'suggestions', type: 'textarea', label: 'Suggestions for Improvement', required: false },
        ],
    },
    {
        id: 'appointment',
        name: __('Appointment Booking', 'nexusforms'),
        description: __('Book an appointment with date and time', 'nexusforms'),
        icon: '📅',
        fields: [
            { id: 'full_name', type: 'text', label: 'Full Name', required: true },
            { id: 'email', type: 'email', label: 'Email', required: true },
            { id: 'phone', type: 'tel', label: 'Phone Number', required: true },
            { id: 'preferred_date', type: 'text', label: 'Preferred Date', required: true, placeholder: 'MM/DD/YYYY' },
            {
                id: 'time_slot',
                type: 'select',
                label: 'Time Slot',
                required: true,
                options: [
                    { label: '9:00 AM - 10:00 AM', value: '09:00' },
                    { label: '10:00 AM - 11:00 AM', value: '10:00' },
                    { label: '11:00 AM - 12:00 PM', value: '11:00' },
                    { label: '1:00 PM - 2:00 PM', value: '13:00' },
                    { label: '2:00 PM - 3:00 PM', value: '14:00' },
                    { label: '3:00 PM - 4:00 PM', value: '15:00' },
                ],
            },
            { id: 'notes', type: 'textarea', label: 'Additional Notes', required: false },
        ],
    },
    {
        id: 'newsletter',
        name: __('Newsletter Signup', 'nexusforms'),
        description: __('Simple email subscription form', 'nexusforms'),
        icon: '📰',
        fields: [
            { id: 'email', type: 'email', label: 'Email Address', required: true, placeholder: 'your@email.com' },
            { id: 'name', type: 'text', label: 'Name', required: false, placeholder: 'Optional' },
            {
                id: 'consent',
                type: 'checkbox',
                label: 'Consent',
                required: true,
                options: [
                    { label: 'I agree to receive marketing emails', value: 'yes' },
                ],
            },
        ],
    },
];

export const FieldTemplates = () => {
    const [showModal, setShowModal] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const { setFormData, setFormTitle, fields } = useFormStore();

    const filteredTemplates = FIELD_TEMPLATES.filter((template) =>
        template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        template.description.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const handleApplyTemplate = (template) => {
        if (fields.length > 0) {
            if (!confirm(__('This will replace all existing fields. Continue?', 'nexusforms'))) {
                return;
            }
        }

        // Apply template fields
        const templateFields = template.fields.map((field, index) => ({
            ...field,
            id: field.id || `field_${Date.now()}_${index}`,
            order: index,
        }));

        setFormData({
            id: 0,
            title: template.name,
            description: template.description,
            status: 'draft',
            settings: {},
            fields: templateFields.map((field) => ({
                field_data: field,
            })),
        });

        setShowModal(false);
    };

    return (
        <>
            <Button variant="secondary" onClick={() => setShowModal(true)}>
                <Icon icon="portfolio" />
                {__('Use Template', 'nexusforms')}
            </Button>

            {showModal && (
                <Modal
                    title={__('Choose a Template', 'nexusforms')}
                    onRequestClose={() => setShowModal(false)}
                    className="field-templates-modal"
                >
                    <div className="templates-modal-content">
                        <SearchControl
                            value={searchTerm}
                            onChange={setSearchTerm}
                            placeholder={__('Search templates...', 'nexusforms')}
                        />

                        <div className="templates-grid">
                            {filteredTemplates.map((template) => (
                                <div key={template.id} className="template-card">
                                    <div className="template-icon">{template.icon}</div>
                                    <h3>{template.name}</h3>
                                    <p>{template.description}</p>
                                    <div className="template-meta">
                                        <span className="field-count">
                                            {template.fields.length} {__('fields', 'nexusforms')}
                                        </span>
                                    </div>
                                    <Button
                                        variant="primary"
                                        onClick={() => handleApplyTemplate(template)}
                                    >
                                        {__('Use This Template', 'nexusforms')}
                                    </Button>
                                </div>
                            ))}
                        </div>

                        {filteredTemplates.length === 0 && (
                            <div className="no-templates">
                                <p>{__('No templates found matching your search.', 'nexusforms')}</p>
                            </div>
                        )}
                    </div>
                </Modal>
            )}
        </>
    );
};
