/**
 * Notifications Manager component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, TextControl, TextareaControl, ToggleControl, Notice } from '@wordpress/components';
import { useFormStore } from '../store/formStore';

export const NotificationsManager = () => {
    const { formSettings, updateFormSettings } = useFormStore();

    const notifications = formSettings?.notifications || [
        {
            id: 'default',
            name: 'Admin Notification',
            to: '{admin_email}',
            subject: 'New Form Submission',
            message: 'You have a new form submission.',
            enabled: true,
        },
    ];

    const [expandedId, setExpandedId] = useState(notifications[0]?.id || null);

    const updateNotification = (id, field, value) => {
        const updated = notifications.map((notif) =>
            notif.id === id ? { ...notif, [field]: value } : notif
        );
        updateFormSettings({ notifications: updated });
    };

    const addNotification = () => {
        const newNotif = {
            id: `notification_${Date.now()}`,
            name: __('New Notification', 'nexusforms'),
            to: '',
            subject: __('Form Submission', 'nexusforms'),
            message: __('New form submission received.', 'nexusforms'),
            enabled: true,
        };
        updateFormSettings({ notifications: [...notifications, newNotif] });
        setExpandedId(newNotif.id);
    };

    const deleteNotification = (id) => {
        if (!confirm(__('Are you sure you want to delete this notification?', 'nexusforms'))) {
            return;
        }
        const updated = notifications.filter((notif) => notif.id !== id);
        updateFormSettings({ notifications: updated });
        if (expandedId === id) {
            setExpandedId(updated[0]?.id || null);
        }
    };

    return (
        <div className="nexusforms-notifications-manager">
            <Notice status="info" isDismissible={false}>
                <p>
                    {__('Configure multiple email notifications to be sent when this form is submitted.', 'nexusforms')}
                </p>
                <p>
                    <strong>{__('Available Merge Tags:', 'nexusforms')}</strong> {'{admin_email}'}, {'{form_title}'}, {'{entry_id}'}
                </p>
            </Notice>

            <div className="notifications-list">
                {notifications.map((notification) => (
                    <div key={notification.id} className="notification-item">
                        <div
                            className="notification-header"
                            onClick={() => setExpandedId(expandedId === notification.id ? null : notification.id)}
                        >
                            <div className="notification-title">
                                <strong>{notification.name || __('Untitled Notification', 'nexusforms')}</strong>
                                <span className="notification-to">→ {notification.to}</span>
                            </div>
                            <div className="notification-actions">
                                <ToggleControl
                                    checked={notification.enabled}
                                    onChange={(value) => updateNotification(notification.id, 'enabled', value)}
                                    onClick={(e) => e.stopPropagation()}
                                />
                                <span className="dashicons dashicons-arrow-down-alt2" style={{
                                    transform: expandedId === notification.id ? 'rotate(180deg)' : 'none',
                                    transition: 'transform 0.2s'
                                }}></span>
                            </div>
                        </div>

                        {expandedId === notification.id && (
                            <div className="notification-body">
                                <TextControl
                                    label={__('Notification Name', 'nexusforms')}
                                    value={notification.name}
                                    onChange={(value) => updateNotification(notification.id, 'name', value)}
                                />

                                <TextControl
                                    label={__('Send To (Email)', 'nexusforms')}
                                    value={notification.to}
                                    onChange={(value) => updateNotification(notification.id, 'to', value)}
                                    help={__('Use {admin_email} for site admin or enter specific email addresses (comma-separated)', 'nexusforms')}
                                />

                                <TextControl
                                    label={__('Subject', 'nexusforms')}
                                    value={notification.subject}
                                    onChange={(value) => updateNotification(notification.id, 'subject', value)}
                                />

                                <TextareaControl
                                    label={__('Message', 'nexusforms')}
                                    value={notification.message}
                                    onChange={(value) => updateNotification(notification.id, 'message', value)}
                                    rows={6}
                                    help={__('HTML is allowed. Use merge tags to include submission data.', 'nexusforms')}
                                />

                                {notifications.length > 1 && (
                                    <Button
                                        isDestructive
                                        variant="secondary"
                                        onClick={() => deleteNotification(notification.id)}
                                    >
                                        {__('Delete Notification', 'nexusforms')}
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                ))}
            </div>

            <Button variant="secondary" onClick={addNotification}>
                {__('+ Add Notification', 'nexusforms')}
            </Button>
        </div>
    );
};
