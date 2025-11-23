/**
 * Field settings panel component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import {
    PanelBody,
    TextControl,
    TextareaControl,
    ToggleControl,
    Button,
    SelectControl,
} from '@wordpress/components';
import { useFormStore } from '../store/formStore';

const OptionEditor = ({ options, onChange }) => {
    const handleAddOption = () => {
        const newOptions = [
            ...options,
            { label: `Option ${options.length + 1}`, value: `option_${options.length + 1}` },
        ];
        onChange(newOptions);
    };

    const handleUpdateOption = (index, field, value) => {
        const newOptions = [...options];
        newOptions[index] = { ...newOptions[index], [field]: value };
        onChange(newOptions);
    };

    const handleDeleteOption = (index) => {
        const newOptions = options.filter((_, i) => i !== index);
        onChange(newOptions);
    };

    return (
        <div className="option-editor">
            <label className="components-base-control__label">
                {__('Options', 'nexusforms')}
            </label>
            {options.map((option, index) => (
                <div key={index} className="option-row">
                    <TextControl
                        placeholder={__('Label', 'nexusforms')}
                        value={option.label}
                        onChange={(value) => handleUpdateOption(index, 'label', value)}
                    />
                    <TextControl
                        placeholder={__('Value', 'nexusforms')}
                        value={option.value}
                        onChange={(value) => handleUpdateOption(index, 'value', value)}
                    />
                    <Button
                        icon="trash"
                        isDestructive
                        size="small"
                        onClick={() => handleDeleteOption(index)}
                        disabled={options.length === 1}
                    />
                </div>
            ))}
            <Button variant="secondary" onClick={handleAddOption}>
                {__('Add Option', 'nexusforms')}
            </Button>
        </div>
    );
};

export const FieldSettings = () => {
    const { fields, selectedFieldId, updateField, selectField } = useFormStore();

    const selectedField = fields.find((f) => f.id === selectedFieldId);

    if (!selectedField) {
        return (
            <div className="nexusforms-field-settings">
                <div className="settings-empty">
                    <h3>{__('Field Settings', 'nexusforms')}</h3>
                    <p>{__('Select a field to edit its settings.', 'nexusforms')}</p>
                </div>
            </div>
        );
    }

    const handleUpdate = (key, value) => {
        updateField(selectedField.id, { [key]: value });
    };

    const handleClose = () => {
        selectField(null);
    };

    const hasOptions = ['select', 'radio', 'checkbox'].includes(selectedField.type);

    return (
        <div className="nexusforms-field-settings">
            <div className="settings-header">
                <h3>{__('Field Settings', 'nexusforms')}</h3>
                <Button icon="no-alt" label={__('Close', 'nexusforms')} onClick={handleClose} />
            </div>

            <div className="settings-body">
                <PanelBody title={__('General', 'nexusforms')} initialOpen={true}>
                    <TextControl
                        label={__('Field Label', 'nexusforms')}
                        value={selectedField.label}
                        onChange={(value) => handleUpdate('label', value)}
                        help={__('The label displayed above the field', 'nexusforms')}
                    />

                    <TextControl
                        label={__('Field ID', 'nexusforms')}
                        value={selectedField.id}
                        onChange={(value) => handleUpdate('id', value)}
                        help={__('Unique identifier for this field', 'nexusforms')}
                    />

                    <TextControl
                        label={__('Placeholder', 'nexusforms')}
                        value={selectedField.placeholder || ''}
                        onChange={(value) => handleUpdate('placeholder', value)}
                        help={__('Placeholder text shown in the field', 'nexusforms')}
                    />

                    <TextareaControl
                        label={__('Description', 'nexusforms')}
                        value={selectedField.description || ''}
                        onChange={(value) => handleUpdate('description', value)}
                        help={__('Help text displayed below the field', 'nexusforms')}
                    />

                    <ToggleControl
                        label={__('Required', 'nexusforms')}
                        checked={selectedField.required || false}
                        onChange={(value) => handleUpdate('required', value)}
                        help={__('Make this field required', 'nexusforms')}
                    />
                </PanelBody>

                {hasOptions && (
                    <PanelBody title={__('Options', 'nexusforms')} initialOpen={true}>
                        <OptionEditor
                            options={selectedField.options || []}
                            onChange={(options) => handleUpdate('options', options)}
                        />
                    </PanelBody>
                )}

                {selectedField.type === 'number' && (
                    <PanelBody title={__('Validation', 'nexusforms')}>
                        <TextControl
                            label={__('Minimum Value', 'nexusforms')}
                            type="number"
                            value={selectedField.min || ''}
                            onChange={(value) => handleUpdate('min', value)}
                        />

                        <TextControl
                            label={__('Maximum Value', 'nexusforms')}
                            type="number"
                            value={selectedField.max || ''}
                            onChange={(value) => handleUpdate('max', value)}
                        />
                    </PanelBody>
                )}

                {['text', 'email', 'textarea', 'tel', 'url'].includes(selectedField.type) && (
                    <PanelBody title={__('Validation', 'nexusforms')}>
                        <TextControl
                            label={__('Minimum Length', 'nexusforms')}
                            type="number"
                            value={selectedField.min_length || ''}
                            onChange={(value) => handleUpdate('min_length', value)}
                        />

                        <TextControl
                            label={__('Maximum Length', 'nexusforms')}
                            type="number"
                            value={selectedField.max_length || ''}
                            onChange={(value) => handleUpdate('max_length', value)}
                        />

                        {selectedField.type === 'text' && (
                            <>
                                <TextControl
                                    label={__('Pattern (RegEx)', 'nexusforms')}
                                    value={selectedField.pattern || ''}
                                    onChange={(value) => handleUpdate('pattern', value)}
                                    help={__('Regular expression for validation', 'nexusforms')}
                                />

                                <TextControl
                                    label={__('Pattern Error Message', 'nexusforms')}
                                    value={selectedField.pattern_error || ''}
                                    onChange={(value) => handleUpdate('pattern_error', value)}
                                />
                            </>
                        )}
                    </PanelBody>
                )}
            </div>
        </div>
    );
};
