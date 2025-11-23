/**
 * Admin JavaScript entry point.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { render } from '@wordpress/element';
import './style.css';

/**
 * Simple placeholder component for admin interface.
 * This will be replaced with a full React form builder in future iterations.
 */
const AdminApp = () => {
    return (
        <div className="nexusforms-admin">
            <div className="nexusforms-notice">
                <h2>NexusForms Admin Interface</h2>
                <p>The React-based form builder interface will be rendered here.</p>
                <p>For now, you can create forms using the REST API endpoints at: <code>/wp-json/nexusforms/v1/forms</code></p>
            </div>
        </div>
    );
};

// Render admin interface.
const rootElement = document.getElementById('nexusforms-forms-root');
if (rootElement) {
    render(<AdminApp />, rootElement);
}

// Render form editor.
const editorElement = document.getElementById('nexusforms-editor-root');
if (editorElement) {
    const formId = editorElement.dataset.formId;
    render(
        <div className="nexusforms-editor">
            <h2>Form Editor</h2>
            <p>Form ID: {formId || 'New Form'}</p>
            <p>React form builder will be implemented here with drag-and-drop functionality.</p>
        </div>,
        editorElement
    );
}

// Render entries list.
const entriesElement = document.getElementById('nexusforms-entries-root');
if (entriesElement) {
    render(
        <div className="nexusforms-entries">
            <h2>Entries Management</h2>
            <p>Entries list and management interface will be rendered here.</p>
        </div>,
        entriesElement
    );
}
