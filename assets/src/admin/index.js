/**
 * Admin JavaScript entry point.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { render } from '@wordpress/element';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { FormBuilder } from './components/FormBuilder';
import { FormsList } from './components/FormsList';
import { EntriesManager } from './components/EntriesManager';
import './style.css';

// Create QueryClient instance
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
            staleTime: 5 * 60 * 1000, // 5 minutes
        },
    },
});

/**
 * Render forms list.
 */
const formsRoot = document.getElementById('nexusforms-forms-root');
if (formsRoot) {
    render(
        <QueryClientProvider client={queryClient}>
            <FormsList />
        </QueryClientProvider>,
        formsRoot
    );
}

/**
 * Render form editor.
 */
const editorRoot = document.getElementById('nexusforms-editor-root');
if (editorRoot) {
    const formId = parseInt(editorRoot.dataset.formId) || 0;

    render(
        <QueryClientProvider client={queryClient}>
            <FormBuilder formId={formId} />
        </QueryClientProvider>,
        editorRoot
    );
}

/**
 * Render entries manager.
 */
const entriesRoot = document.getElementById('nexusforms-entries-root');
if (entriesRoot) {
    render(
        <QueryClientProvider client={queryClient}>
            <EntriesManager />
        </QueryClientProvider>,
        entriesRoot
    );
}
