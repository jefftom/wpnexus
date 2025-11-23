/**
 * Form builder store using Zustand.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { create } from 'zustand';

/**
 * Form builder store.
 */
export const useFormStore = create((set, get) => ({
    // Form data
    formId: 0,
    formTitle: '',
    formDescription: '',
    formStatus: 'draft',
    formSettings: {},
    fields: [],
    selectedFieldId: null,
    isDirty: false,
    isSaving: false,

    // Actions
    setFormData: (data) => set({
        formId: data.id || 0,
        formTitle: data.title || '',
        formDescription: data.description || '',
        formStatus: data.status || 'draft',
        formSettings: data.settings || {},
        fields: data.fields?.map((field, index) => ({
            id: field.field_data.id || `field_${Date.now()}_${index}`,
            ...field.field_data,
            order: index,
        })) || [],
        isDirty: false,
    }),

    setFormTitle: (title) => set({ formTitle: title, isDirty: true }),

    setFormDescription: (description) => set({ formDescription: description, isDirty: true }),

    setFormStatus: (status) => set({ formStatus: status, isDirty: true }),

    setFormSettings: (settings) => set({ formSettings: settings, isDirty: true }),

    // Field management
    addField: (fieldType, position = null) => {
        const newField = {
            id: `field_${Date.now()}`,
            type: fieldType,
            label: `New ${fieldType} field`,
            placeholder: '',
            required: false,
            description: '',
            options: fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox'
                ? [{ label: 'Option 1', value: 'option_1' }]
                : undefined,
        };

        set((state) => {
            const fields = [...state.fields];
            if (position !== null) {
                fields.splice(position, 0, newField);
            } else {
                fields.push(newField);
            }
            return {
                fields: fields.map((f, i) => ({ ...f, order: i })),
                selectedFieldId: newField.id,
                isDirty: true,
            };
        });
    },

    updateField: (fieldId, updates) => set((state) => ({
        fields: state.fields.map((field) =>
            field.id === fieldId ? { ...field, ...updates } : field
        ),
        isDirty: true,
    })),

    deleteField: (fieldId) => set((state) => ({
        fields: state.fields.filter((field) => field.id !== fieldId).map((f, i) => ({ ...f, order: i })),
        selectedFieldId: state.selectedFieldId === fieldId ? null : state.selectedFieldId,
        isDirty: true,
    })),

    duplicateField: (fieldId) => set((state) => {
        const field = state.fields.find((f) => f.id === fieldId);
        if (!field) return state;

        const newField = {
            ...field,
            id: `field_${Date.now()}`,
            label: `${field.label} (Copy)`,
        };

        const fieldIndex = state.fields.findIndex((f) => f.id === fieldId);
        const fields = [...state.fields];
        fields.splice(fieldIndex + 1, 0, newField);

        return {
            fields: fields.map((f, i) => ({ ...f, order: i })),
            selectedFieldId: newField.id,
            isDirty: true,
        };
    }),

    reorderFields: (startIndex, endIndex) => set((state) => {
        const fields = [...state.fields];
        const [removed] = fields.splice(startIndex, 1);
        fields.splice(endIndex, 0, removed);
        return {
            fields: fields.map((f, i) => ({ ...f, order: i })),
            isDirty: true,
        };
    }),

    selectField: (fieldId) => set({ selectedFieldId: fieldId }),

    // Reset
    resetForm: () => set({
        formId: 0,
        formTitle: '',
        formDescription: '',
        formStatus: 'draft',
        formSettings: {},
        fields: [],
        selectedFieldId: null,
        isDirty: false,
    }),

    setIsSaving: (isSaving) => set({ isSaving }),

    // Get form data for API
    getFormData: () => {
        const state = get();
        return {
            title: state.formTitle,
            description: state.formDescription,
            status: state.formStatus,
            settings: state.formSettings,
            fields: state.fields.map((field) => ({
                id: field.id,
                type: field.type,
                label: field.label,
                placeholder: field.placeholder,
                required: field.required,
                description: field.description,
                options: field.options,
                min: field.min,
                max: field.max,
                min_length: field.min_length,
                max_length: field.max_length,
                pattern: field.pattern,
                pattern_error: field.pattern_error,
            })),
        };
    },
}));
