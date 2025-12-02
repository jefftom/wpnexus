/**
 * Conditional Logic Builder Component
 *
 * Allows users to create conditional logic rules for form fields.
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import './ConditionalLogicBuilder.css';

/**
 * ConditionalLogicBuilder component.
 *
 * @param {Object} props Component props.
 * @param {Object} props.conditionalLogic Current conditional logic configuration.
 * @param {Function} props.onChange Callback when conditional logic changes.
 * @param {Array} props.fields All form fields (to select from).
 * @param {string} props.currentFieldId ID of the current field (to exclude from selection).
 * @return {JSX.Element} ConditionalLogicBuilder component.
 */
export default function ConditionalLogicBuilder({ conditionalLogic, onChange, fields, currentFieldId }) {
	// Default structure
	const defaultLogic = {
		enabled: false,
		action: 'show',
		logic: 'all',
		rules: [],
	};

	const logic = conditionalLogic || defaultLogic;

	// Filter fields that can be used in conditions (exclude current field and layout fields)
	const availableFields = fields.filter(field =>
		field.id !== currentFieldId &&
		!['html', 'section'].includes(field.type)
	);

	/**
	 * Toggle conditional logic enabled state.
	 */
	const handleToggle = () => {
		onChange({
			...logic,
			enabled: !logic.enabled,
			rules: logic.rules.length > 0 ? logic.rules : [createEmptyRule()],
		});
	};

	/**
	 * Create an empty rule.
	 *
	 * @return {Object} Empty rule object.
	 */
	const createEmptyRule = () => ({
		field: availableFields[0]?.id || '',
		operator: 'is',
		value: '',
	});

	/**
	 * Add a new rule.
	 */
	const handleAddRule = () => {
		onChange({
			...logic,
			rules: [...logic.rules, createEmptyRule()],
		});
	};

	/**
	 * Remove a rule.
	 *
	 * @param {number} index Rule index to remove.
	 */
	const handleRemoveRule = (index) => {
		const newRules = logic.rules.filter((_, i) => i !== index);
		onChange({
			...logic,
			rules: newRules,
		});
	};

	/**
	 * Update a rule.
	 *
	 * @param {number} index Rule index to update.
	 * @param {string} key Rule property to update.
	 * @param {*} value New value.
	 */
	const handleRuleChange = (index, key, value) => {
		const newRules = [...logic.rules];
		newRules[index] = {
			...newRules[index],
			[key]: value,
		};
		onChange({
			...logic,
			rules: newRules,
		});
	};

	/**
	 * Update action type (show/hide).
	 *
	 * @param {string} action New action type.
	 */
	const handleActionChange = (action) => {
		onChange({
			...logic,
			action,
		});
	};

	/**
	 * Update logic type (all/any).
	 *
	 * @param {string} logicValue New logic type.
	 */
	const handleLogicChange = (logicValue) => {
		onChange({
			...logic,
			logic: logicValue,
		});
	};

	/**
	 * Get operators for a field type.
	 *
	 * @param {string} fieldId Field ID.
	 * @return {Array} Array of operator options.
	 */
	const getOperatorsForField = (fieldId) => {
		const selectedField = fields.find(f => f.id === fieldId);
		if (!selectedField) {
			return [
				{ value: 'is', label: __('is', 'nexusforms') },
				{ value: 'is_not', label: __('is not', 'nexusforms') },
			];
		}

		// Choice fields (radio, checkbox, select, dropdown)
		if (['radio', 'checkbox', 'select', 'dropdown'].includes(selectedField.type)) {
			return [
				{ value: 'is', label: __('is', 'nexusforms') },
				{ value: 'is_not', label: __('is not', 'nexusforms') },
				{ value: 'empty', label: __('is empty', 'nexusforms') },
				{ value: 'not_empty', label: __('is not empty', 'nexusforms') },
			];
		}

		// Text fields
		if (['text', 'email', 'textarea', 'url', 'phone'].includes(selectedField.type)) {
			return [
				{ value: 'is', label: __('is', 'nexusforms') },
				{ value: 'is_not', label: __('is not', 'nexusforms') },
				{ value: 'contains', label: __('contains', 'nexusforms') },
				{ value: 'starts_with', label: __('starts with', 'nexusforms') },
				{ value: 'ends_with', label: __('ends with', 'nexusforms') },
				{ value: 'empty', label: __('is empty', 'nexusforms') },
				{ value: 'not_empty', label: __('is not empty', 'nexusforms') },
			];
		}

		// Number fields
		if (['number'].includes(selectedField.type)) {
			return [
				{ value: 'is', label: __('is', 'nexusforms') },
				{ value: 'is_not', label: __('is not', 'nexusforms') },
				{ value: 'greater_than', label: __('greater than', 'nexusforms') },
				{ value: 'less_than', label: __('less than', 'nexusforms') },
				{ value: 'empty', label: __('is empty', 'nexusforms') },
				{ value: 'not_empty', label: __('is not empty', 'nexusforms') },
			];
		}

		// Default operators
		return [
			{ value: 'is', label: __('is', 'nexusforms') },
			{ value: 'is_not', label: __('is not', 'nexusforms') },
			{ value: 'empty', label: __('is empty', 'nexusforms') },
			{ value: 'not_empty', label: __('is not empty', 'nexusforms') },
		];
	};

	/**
	 * Get value input for a field.
	 *
	 * @param {Object} rule Current rule.
	 * @param {number} index Rule index.
	 * @return {JSX.Element|null} Value input element or null.
	 */
	const getValueInput = (rule, index) => {
		const selectedField = fields.find(f => f.id === rule.field);

		// No value input for empty/not_empty operators
		if (['empty', 'not_empty'].includes(rule.operator)) {
			return null;
		}

		if (!selectedField) {
			return (
				<input
					type="text"
					className="conditional-logic-value-input"
					value={rule.value}
					onChange={(e) => handleRuleChange(index, 'value', e.target.value)}
					placeholder={__('Enter value...', 'nexusforms')}
				/>
			);
		}

		// Choice fields - dropdown of options
		if (['radio', 'select', 'dropdown'].includes(selectedField.type) && selectedField.options) {
			return (
				<select
					className="conditional-logic-value-select"
					value={rule.value}
					onChange={(e) => handleRuleChange(index, 'value', e.target.value)}
				>
					<option value="">{__('Select value...', 'nexusforms')}</option>
					{selectedField.options.map((option, i) => (
						<option key={i} value={option.value || option.label}>
							{option.label}
						</option>
					))}
				</select>
			);
		}

		// Checkbox - dropdown of checkbox options
		if (selectedField.type === 'checkbox' && selectedField.options) {
			return (
				<select
					className="conditional-logic-value-select"
					value={rule.value}
					onChange={(e) => handleRuleChange(index, 'value', e.target.value)}
				>
					<option value="">{__('Select value...', 'nexusforms')}</option>
					{selectedField.options.map((option, i) => (
						<option key={i} value={option.value || option.label}>
							{option.label}
						</option>
					))}
				</select>
			);
		}

		// Default text input
		return (
			<input
				type="text"
				className="conditional-logic-value-input"
				value={rule.value}
				onChange={(e) => handleRuleChange(index, 'value', e.target.value)}
				placeholder={__('Enter value...', 'nexusforms')}
			/>
		);
	};

	if (availableFields.length === 0) {
		return (
			<div className="conditional-logic-empty">
				<p>{__('Add more fields to your form to use conditional logic.', 'nexusforms')}</p>
			</div>
		);
	}

	return (
		<div className="conditional-logic-builder">
			<div className="conditional-logic-header">
				<label className="conditional-logic-toggle">
					<input
						type="checkbox"
						checked={logic.enabled}
						onChange={handleToggle}
					/>
					<span>{__('Enable Conditional Logic', 'nexusforms')}</span>
				</label>
				<p className="conditional-logic-description">
					{__('Show or hide this field based on other field values.', 'nexusforms')}
				</p>
			</div>

			{logic.enabled && (
				<div className="conditional-logic-rules">
					<div className="conditional-logic-sentence">
						<select
							className="action-type-select"
							value={logic.action}
							onChange={(e) => handleActionChange(e.target.value)}
						>
							<option value="show">{__('Show', 'nexusforms')}</option>
							<option value="hide">{__('Hide', 'nexusforms')}</option>
						</select>
						<span>{__('this field if', 'nexusforms')}</span>
						<select
							className="logic-type-select"
							value={logic.logic}
							onChange={(e) => handleLogicChange(e.target.value)}
						>
							<option value="all">{__('all', 'nexusforms')}</option>
							<option value="any">{__('any', 'nexusforms')}</option>
						</select>
						<span>{__('of the following match:', 'nexusforms')}</span>
					</div>

					<div className="rules-list">
						{logic.rules.map((rule, index) => {
							const selectedField = fields.find(f => f.id === rule.field);
							const operators = getOperatorsForField(rule.field);

							return (
								<div key={index} className="rule-row">
									<select
										className="rule-field-select"
										value={rule.field}
										onChange={(e) => handleRuleChange(index, 'field', e.target.value)}
									>
										{availableFields.map(field => (
											<option key={field.id} value={field.id}>
												{field.label || __('Untitled Field', 'nexusforms')}
											</option>
										))}
									</select>

									<select
										className="rule-operator-select"
										value={rule.operator}
										onChange={(e) => handleRuleChange(index, 'operator', e.target.value)}
									>
										{operators.map(op => (
											<option key={op.value} value={op.value}>
												{op.label}
											</option>
										))}
									</select>

									{getValueInput(rule, index)}

									<button
										type="button"
										className="rule-remove-button"
										onClick={() => handleRemoveRule(index)}
										aria-label={__('Remove rule', 'nexusforms')}
									>
										×
									</button>
								</div>
							);
						})}
					</div>

					<button
						type="button"
						className="add-rule-button"
						onClick={handleAddRule}
					>
						{__('+ Add Rule', 'nexusforms')}
					</button>
				</div>
			)}
		</div>
	);
}
