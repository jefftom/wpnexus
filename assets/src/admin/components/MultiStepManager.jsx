/**
 * Multi-Step Form Manager component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, TextControl, Modal, Icon, ToggleControl } from '@wordpress/components';
import { useFormStore } from '../store/formStore';

export const MultiStepManager = () => {
    const { formSettings, setFormSettings } = useFormStore();
    const [showModal, setShowModal] = useState(false);
    const [editingStep, setEditingStep] = useState(null);

    const multiStep = formSettings.multiStep || {
        enabled: false,
        steps: [{ id: 'step_1', title: 'Step 1', description: '' }],
        showProgressBar: true,
        showStepNumbers: true,
    };

    const handleToggle = (enabled) => {
        setFormSettings({
            ...formSettings,
            multiStep: {
                ...multiStep,
                enabled,
                steps: enabled && multiStep.steps.length === 0
                    ? [{ id: 'step_1', title: 'Step 1', description: '' }]
                    : multiStep.steps,
            },
        });
    };

    const handleUpdate = (key, value) => {
        setFormSettings({
            ...formSettings,
            multiStep: {
                ...multiStep,
                [key]: value,
            },
        });
    };

    const handleAddStep = () => {
        const newStepNumber = multiStep.steps.length + 1;
        const newStep = {
            id: `step_${Date.now()}`,
            title: `Step ${newStepNumber}`,
            description: '',
        };

        setFormSettings({
            ...formSettings,
            multiStep: {
                ...multiStep,
                steps: [...multiStep.steps, newStep],
            },
        });
    };

    const handleUpdateStep = (index, updates) => {
        const newSteps = [...multiStep.steps];
        newSteps[index] = { ...newSteps[index], ...updates };

        setFormSettings({
            ...formSettings,
            multiStep: {
                ...multiStep,
                steps: newSteps,
            },
        });
    };

    const handleDeleteStep = (index) => {
        if (multiStep.steps.length <= 1) {
            alert(__('You must have at least one step.', 'nexusforms'));
            return;
        }

        if (!confirm(__('Are you sure you want to delete this step?', 'nexusforms'))) {
            return;
        }

        const newSteps = multiStep.steps.filter((_, i) => i !== index);
        setFormSettings({
            ...formSettings,
            multiStep: {
                ...multiStep,
                steps: newSteps,
            },
        });
    };

    const handleReorderStep = (index, direction) => {
        const newSteps = [...multiStep.steps];
        const targetIndex = direction === 'up' ? index - 1 : index + 1;

        if (targetIndex < 0 || targetIndex >= newSteps.length) return;

        [newSteps[index], newSteps[targetIndex]] = [newSteps[targetIndex], newSteps[index]];

        setFormSettings({
            ...formSettings,
            multiStep: {
                ...multiStep,
                steps: newSteps,
            },
        });
    };

    return (
        <div className="multi-step-manager">
            <ToggleControl
                label={__('Enable Multi-Step Form', 'nexusforms')}
                help={__('Split your form into multiple pages/steps', 'nexusforms')}
                checked={multiStep.enabled}
                onChange={handleToggle}
            />

            {multiStep.enabled && (
                <div className="multi-step-settings">
                    <div className="step-options">
                        <ToggleControl
                            label={__('Show Progress Bar', 'nexusforms')}
                            checked={multiStep.showProgressBar}
                            onChange={(value) => handleUpdate('showProgressBar', value)}
                        />

                        <ToggleControl
                            label={__('Show Step Numbers', 'nexusforms')}
                            checked={multiStep.showStepNumbers}
                            onChange={(value) => handleUpdate('showStepNumbers', value)}
                        />
                    </div>

                    <div className="steps-list">
                        <div className="steps-header">
                            <h4>{__('Form Steps', 'nexusforms')}</h4>
                            <Button variant="secondary" onClick={handleAddStep} size="small">
                                <Icon icon="plus" />
                                {__('Add Step', 'nexusforms')}
                            </Button>
                        </div>

                        {multiStep.steps.map((step, index) => (
                            <div key={step.id} className="step-item">
                                <div className="step-header">
                                    <div className="step-number">{index + 1}</div>
                                    <div className="step-info">
                                        <strong>{step.title}</strong>
                                        {step.description && (
                                            <p className="step-description">{step.description}</p>
                                        )}
                                    </div>
                                    <div className="step-actions">
                                        <Button
                                            icon="arrow-up-alt2"
                                            size="small"
                                            onClick={() => handleReorderStep(index, 'up')}
                                            disabled={index === 0}
                                            label={__('Move up', 'nexusforms')}
                                        />
                                        <Button
                                            icon="arrow-down-alt2"
                                            size="small"
                                            onClick={() => handleReorderStep(index, 'down')}
                                            disabled={index === multiStep.steps.length - 1}
                                            label={__('Move down', 'nexusforms')}
                                        />
                                        <Button
                                            icon="edit"
                                            size="small"
                                            onClick={() => {
                                                setEditingStep(index);
                                                setShowModal(true);
                                            }}
                                            label={__('Edit', 'nexusforms')}
                                        />
                                        <Button
                                            icon="trash"
                                            size="small"
                                            isDestructive
                                            onClick={() => handleDeleteStep(index)}
                                            disabled={multiStep.steps.length <= 1}
                                            label={__('Delete', 'nexusforms')}
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="step-assignment-notice">
                        <Icon icon="info" />
                        <p>
                            {__(
                                'Assign fields to steps by editing each field and selecting its step in the field settings.',
                                'nexusforms'
                            )}
                        </p>
                    </div>
                </div>
            )}

            {showModal && editingStep !== null && (
                <Modal
                    title={__('Edit Step', 'nexusforms')}
                    onRequestClose={() => {
                        setShowModal(false);
                        setEditingStep(null);
                    }}
                >
                    <div className="step-edit-modal">
                        <TextControl
                            label={__('Step Title', 'nexusforms')}
                            value={multiStep.steps[editingStep].title}
                            onChange={(value) => handleUpdateStep(editingStep, { title: value })}
                        />

                        <TextControl
                            label={__('Step Description', 'nexusforms')}
                            value={multiStep.steps[editingStep].description}
                            onChange={(value) => handleUpdateStep(editingStep, { description: value })}
                            help={__('Optional description shown at the top of this step', 'nexusforms')}
                        />

                        <div className="modal-actions">
                            <Button
                                variant="primary"
                                onClick={() => {
                                    setShowModal(false);
                                    setEditingStep(null);
                                }}
                            >
                                {__('Done', 'nexusforms')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
};
