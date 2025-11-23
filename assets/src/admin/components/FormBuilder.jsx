/**
 * Main Form Builder component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl, TextareaControl, SelectControl, Spinner, TabPanel } from '@wordpress/components';
import { FieldPalette } from './FieldPalette';
import { FormCanvas } from './FormCanvas';
import { FieldSettings } from './FieldSettings';
import { MultiStepManager } from './MultiStepManager';
import { FieldTemplates } from './FieldTemplates';
import { ImportExport } from './ImportExport';
import { EmailBuilder } from './EmailBuilder';
import { FormPreview } from './FormPreview';
import { useFormStore } from '../store/formStore';
import { useForm, useCreateForm, useUpdateForm } from '../hooks/useFormApi';

export const FormBuilder = ({ formId }) => {
    const [showSettings, setShowSettings] = useState(false);
    const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

    const {
        formTitle,
        formDescription,
        formStatus,
        isDirty,
        setFormData,
        setFormTitle,
        setFormDescription,
        setFormStatus,
        getFormData,
        setIsSaving,
        resetForm,
    } = useFormStore();

    // Fetch existing form data
    const { data: formData, isLoading } = useForm(formId);
    const createForm = useCreateForm();
    const updateForm = useUpdateForm();

    // Load form data when it's fetched
    useEffect(() => {
        if (formData && formId) {
            setFormData(formData);
        } else if (!formId) {
            resetForm();
        }
    }, [formData, formId]);

    // Track unsaved changes
    useEffect(() => {
        setHasUnsavedChanges(isDirty);
    }, [isDirty]);

    // Warn about unsaved changes
    useEffect(() => {
        const handleBeforeUnload = (e) => {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        };

        window.addEventListener('beforeunload', handleBeforeUnload);
        return () => window.removeEventListener('beforeunload', handleBeforeUnload);
    }, [hasUnsavedChanges]);

    const handleSave = async (status = formStatus) => {
        setIsSaving(true);

        try {
            const data = getFormData();
            data.status = status;

            if (formId) {
                await updateForm.mutateAsync({ formId, formData: data });
            } else {
                const result = await createForm.mutateAsync(data);
                // Redirect to edit page with new form ID
                window.location.href = `admin.php?page=nexusforms-new&id=${result.id}`;
            }

            setHasUnsavedChanges(false);
        } catch (error) {
            console.error('Error saving form:', error);
            alert(__('Failed to save form. Please try again.', 'nexusforms'));
        } finally {
            setIsSaving(false);
        }
    };

    const handlePublish = () => {
        handleSave('active');
    };

    const handleSaveDraft = () => {
        handleSave('draft');
    };

    if (isLoading) {
        return (
            <div className="nexusforms-loading">
                <Spinner />
                <p>{__('Loading form...', 'nexusforms')}</p>
            </div>
        );
    }

    return (
        <div className="nexusforms-builder">
            <div className="builder-header">
                <div className="header-left">
                    <TextControl
                        className="form-title-input"
                        value={formTitle}
                        onChange={setFormTitle}
                        placeholder={__('Enter form title...', 'nexusforms')}
                    />
                </div>

                <div className="header-actions">
                    <FieldTemplates />

                    <ImportExport formId={formId} />

                    {formId && (
                        <FormPreview
                            formId={formId}
                            buttonText={__('Preview', 'nexusforms')}
                            buttonVariant="tertiary"
                        />
                    )}

                    <Button
                        variant="tertiary"
                        onClick={() => setShowSettings(true)}
                        icon="admin-settings"
                    >
                        {__('Settings', 'nexusforms')}
                    </Button>

                    <Button variant="secondary" onClick={handleSaveDraft} isBusy={updateForm.isPending}>
                        {__('Save Draft', 'nexusforms')}
                    </Button>

                    <Button
                        variant="primary"
                        onClick={handlePublish}
                        isBusy={createForm.isPending || updateForm.isPending}
                    >
                        {formStatus === 'active' ? __('Update', 'nexusforms') : __('Publish', 'nexusforms')}
                    </Button>
                </div>
            </div>

            <div className="builder-body">
                <div className="builder-sidebar builder-sidebar-left">
                    <FieldPalette />
                </div>

                <div className="builder-main">
                    <FormCanvas />
                </div>

                <div className="builder-sidebar builder-sidebar-right">
                    <FieldSettings />
                </div>
            </div>

            {showSettings && (
                <Modal
                    title={__('Form Settings', 'nexusforms')}
                    onRequestClose={() => setShowSettings(false)}
                    className="nexusforms-settings-modal"
                >
                    <TabPanel
                        tabs={[
                            {
                                name: 'general',
                                title: __('General', 'nexusforms'),
                            },
                            {
                                name: 'email',
                                title: __('Email', 'nexusforms'),
                            },
                            {
                                name: 'multi-step',
                                title: __('Multi-Step', 'nexusforms'),
                            },
                        ]}
                    >
                        {(tab) => (
                            <div className="tab-content">
                                {tab.name === 'general' && (
                                    <div className="modal-content">
                                        <TextControl
                                            label={__('Form Title', 'nexusforms')}
                                            value={formTitle}
                                            onChange={setFormTitle}
                                        />

                                        <TextareaControl
                                            label={__('Form Description', 'nexusforms')}
                                            value={formDescription}
                                            onChange={setFormDescription}
                                            rows={4}
                                        />

                                        <SelectControl
                                            label={__('Form Status', 'nexusforms')}
                                            value={formStatus}
                                            options={[
                                                { label: __('Draft', 'nexusforms'), value: 'draft' },
                                                { label: __('Active', 'nexusforms'), value: 'active' },
                                                { label: __('Inactive', 'nexusforms'), value: 'inactive' },
                                            ]}
                                            onChange={setFormStatus}
                                        />
                                    </div>
                                )}

                                {tab.name === 'email' && (
                                    <div className="modal-content">
                                        <EmailBuilder />
                                    </div>
                                )}

                                {tab.name === 'multi-step' && (
                                    <div className="modal-content">
                                        <MultiStepManager />
                                    </div>
                                )}

                                <div className="modal-actions">
                                    <Button variant="secondary" onClick={() => setShowSettings(false)}>
                                        {__('Close', 'nexusforms')}
                                    </Button>
                                </div>
                            </div>
                        )}
                    </TabPanel>
                </Modal>
            )}
        </div>
    );
};
