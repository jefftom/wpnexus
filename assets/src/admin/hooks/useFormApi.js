/**
 * API hooks for forms using TanStack Query.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

const API_BASE = '/nexusforms/v1';

/**
 * Fetch all forms.
 */
export const useForms = (params = {}) => {
    return useQuery({
        queryKey: ['forms', params],
        queryFn: async () => {
            const queryString = new URLSearchParams(params).toString();
            const response = await apiFetch({
                path: `${API_BASE}/forms${queryString ? `?${queryString}` : ''}`,
            });
            return response;
        },
    });
};

/**
 * Fetch a single form.
 */
export const useForm = (formId) => {
    return useQuery({
        queryKey: ['form', formId],
        queryFn: async () => {
            const response = await apiFetch({
                path: `${API_BASE}/forms/${formId}`,
            });
            return response;
        },
        enabled: !!formId && formId > 0,
    });
};

/**
 * Create a new form.
 */
export const useCreateForm = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (formData) => {
            const response = await apiFetch({
                path: `${API_BASE}/forms`,
                method: 'POST',
                data: formData,
            });
            return response;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['forms'] });
        },
    });
};

/**
 * Update a form.
 */
export const useUpdateForm = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async ({ formId, formData }) => {
            const response = await apiFetch({
                path: `${API_BASE}/forms/${formId}`,
                method: 'PUT',
                data: formData,
            });
            return response;
        },
        onSuccess: (data, variables) => {
            queryClient.invalidateQueries({ queryKey: ['forms'] });
            queryClient.invalidateQueries({ queryKey: ['form', variables.formId] });
        },
    });
};

/**
 * Delete a form.
 */
export const useDeleteForm = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (formId) => {
            const response = await apiFetch({
                path: `${API_BASE}/forms/${formId}`,
                method: 'DELETE',
            });
            return response;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['forms'] });
        },
    });
};

/**
 * Duplicate a form.
 */
export const useDuplicateForm = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (formId) => {
            const response = await apiFetch({
                path: `${API_BASE}/forms/${formId}/duplicate`,
                method: 'POST',
            });
            return response;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['forms'] });
        },
    });
};

/**
 * Fetch entries for a form.
 */
export const useEntries = (formId, params = {}) => {
    return useQuery({
        queryKey: ['entries', formId, params],
        queryFn: async () => {
            const queryString = new URLSearchParams({ form_id: formId, ...params }).toString();
            const response = await apiFetch({
                path: `${API_BASE}/entries?${queryString}`,
            });
            return response;
        },
        enabled: !!formId && formId > 0,
    });
};

/**
 * Delete an entry.
 */
export const useDeleteEntry = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async (entryId) => {
            const response = await apiFetch({
                path: `${API_BASE}/entries/${entryId}`,
                method: 'DELETE',
            });
            return response;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['entries'] });
        },
    });
};
