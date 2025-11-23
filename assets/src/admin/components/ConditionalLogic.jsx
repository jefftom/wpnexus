/**
 * Conditional Logic component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { Button, SelectControl, ToggleControl, Icon } from '@wordpress/components';
import { useFormStore } from '../store/formStore';

export const ConditionalLogic = ({ fieldId }) => {
    const { fields, updateField } = useFormStore();
    const field = fields.find((f) => f.id === fieldId);

    if (!field) return null;

    const conditionalLogic = field.conditionalLogic || {
        enabled: false,
        action: 'show',
        logic: 'all',
        rules: [],
    };

    const availableFields = fields.filter((f) => f.id !== fieldId);

    const handleToggle = (enabled) => {
        updateField(fieldId, {
            conditionalLogic: {
                ...conditionalLogic,
                enabled,
                rules: enabled && conditionalLogic.rules.length === 0
                    ? [{ field: '', operator: 'is', value: '' }]
                    : conditionalLogic.rules,
            },
        });
    };

    const handleUpdate = (key, value) => {
        updateField(fieldId, {
            conditionalLogic: {
                ...conditionalLogic,
                [key]: value,
            },
        });
    };

    const handleRuleUpdate = (index, key, value) => {
        const newRules = [...conditionalLogic.rules];
        newRules[index] = { ...newRules[index], [key]: value };
        updateField(fieldId, {
            conditionalLogic: {
                ...conditionalLogic,
                rules: newRules,
            },
        });
    };

    const handleAddRule = () => {
        updateField(fieldId, {
            conditionalLogic: {
                ...conditionalLogic,
                rules: [...conditionalLogic.rules, { field: '', operator: 'is', value: '' }],
            },
        });
    };

    const handleRemoveRule = (index) => {
        const newRules = conditionalLogic.rules.filter((_, i) => i !== index);
        updateField(fieldId, {
            conditionalLogic: {
                ...conditionalLogic,
                rules: newRules.length > 0 ? newRules : [{ field: '', operator: 'is', value: '' }],
            },
        });
    };

    return (
        <div className="conditional-logic">
            <ToggleControl
                label={__('Enable Conditional Logic', 'nexusforms')}
                help={__('Show or hide this field based on other field values', 'nexusforms')}
                checked={conditionalLogic.enabled}
                onChange={handleToggle}
            />

            {conditionalLogic.enabled && (
                <div className="conditional-logic-rules">
                    <div className="logic-header">
                        <SelectControl
                            value={conditionalLogic.action}
                            options={[
                                { label: __('Show', 'nexusforms'), value: 'show' },
                                { label: __('Hide', 'nexusforms'), value: 'hide' },
                            ]}
                            onChange={(value) => handleUpdate('action', value)}
                        />
                        <span className="logic-text">{__('this field if', 'nexusforms')}</span>
                        <SelectControl
                            value={conditionalLogic.logic}
                            options={[
                                { label: __('All', 'nexusforms'), value: 'all' },
                                { label: __('Any', 'nexusforms'), value: 'any' },
                            ]}
                            onChange={(value) => handleUpdate('logic', value)}
                        />
                        <span className="logic-text">{__('of these rules match:', 'nexusforms')}</span>
                    </div>

                    {conditionalLogic.rules.map((rule, index) => (
                        <div key={index} className="logic-rule">
                            <SelectControl
                                value={rule.field}
                                options={[
                                    { label: __('Select field...', 'nexusforms'), value: '' },
                                    ...availableFields.map((f) => ({
                                        label: f.label,
                                        value: f.id,
                                    })),
                                ]}
                                onChange={(value) => handleRuleUpdate(index, 'field', value)}
                            />

                            <SelectControl
                                value={rule.operator}
                                options={[
                                    { label: __('is', 'nexusforms'), value: 'is' },
                                    { label: __('is not', 'nexusforms'), value: 'is_not' },
                                    { label: __('contains', 'nexusforms'), value: 'contains' },
                                    { label: __('starts with', 'nexusforms'), value: 'starts_with' },
                                    { label: __('ends with', 'nexusforms'), value: 'ends_with' },
                                    { label: __('greater than', 'nexusforms'), value: 'greater_than' },
                                    { label: __('less than', 'nexusforms'), value: 'less_than' },
                                    { label: __('is empty', 'nexusforms'), value: 'is_empty' },
                                    { label: __('is not empty', 'nexusforms'), value: 'is_not_empty' },
                                ]}
                                onChange={(value) => handleRuleUpdate(index, 'operator', value)}
                            />

                            {!['is_empty', 'is_not_empty'].includes(rule.operator) && (
                                <input
                                    type="text"
                                    className="components-text-control__input"
                                    value={rule.value}
                                    onChange={(e) => handleRuleUpdate(index, 'value', e.target.value)}
                                    placeholder={__('Value', 'nexusforms')}
                                />
                            )}

                            <Button
                                icon="trash"
                                isDestructive
                                onClick={() => handleRemoveRule(index)}
                                label={__('Remove rule', 'nexusforms')}
                            />
                        </div>
                    ))}

                    <Button
                        variant="secondary"
                        onClick={handleAddRule}
                        className="add-rule-button"
                    >
                        <Icon icon="plus" />
                        {__('Add Rule', 'nexusforms')}
                    </Button>
                </div>
            )}
        </div>
    );
};
