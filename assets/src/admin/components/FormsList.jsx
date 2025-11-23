/**
 * Forms list component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, SearchControl, Spinner, SelectControl } from '@wordpress/components';
import { useForms, useDeleteForm, useDuplicateForm } from '../hooks/useFormApi';
import { FormImporter } from './FormImporter';

export const FormsList = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('');

    const { data, isLoading, error } = useForms({ status: statusFilter });
    const deleteForm = useDeleteForm();
    const duplicateForm = useDuplicateForm();

    const handleDelete = async (formId, formTitle) => {
        if (!confirm(__('Are you sure you want to delete "%s"?', 'nexusforms').replace('%s', formTitle))) {
            return;
        }

        try {
            await deleteForm.mutateAsync(formId);
        } catch (error) {
            alert(__('Failed to delete form.', 'nexusforms'));
        }
    };

    const handleDuplicate = async (formId) => {
        try {
            await duplicateForm.mutateAsync(formId);
        } catch (error) {
            alert(__('Failed to duplicate form.', 'nexusforms'));
        }
    };

    const filteredForms = data?.forms?.filter((form) =>
        form.title.toLowerCase().includes(searchTerm.toLowerCase())
    ) || [];

    if (error) {
        return (
            <div className="nexusforms-error">
                <p>{__('Error loading forms. Please try again.', 'nexusforms')}</p>
            </div>
        );
    }

    return (
        <div className="nexusforms-forms-list">
            <div className="list-header">
                <div className="list-filters">
                    <SearchControl
                        value={searchTerm}
                        onChange={setSearchTerm}
                        placeholder={__('Search forms...', 'nexusforms')}
                    />

                    <SelectControl
                        value={statusFilter}
                        options={[
                            { label: __('All Statuses', 'nexusforms'), value: '' },
                            { label: __('Active', 'nexusforms'), value: 'active' },
                            { label: __('Draft', 'nexusforms'), value: 'draft' },
                            { label: __('Inactive', 'nexusforms'), value: 'inactive' },
                        ]}
                        onChange={setStatusFilter}
                    />
                </div>

                <div className="list-stats">
                    <span className="total-forms">
                        {__('Total:', 'nexusforms')} <strong>{data?.total || 0}</strong>
                    </span>
                    <FormImporter onImportSuccess={() => window.location.reload()} />
                </div>
            </div>

            {isLoading ? (
                <div className="list-loading">
                    <Spinner />
                    <p>{__('Loading forms...', 'nexusforms')}</p>
                </div>
            ) : filteredForms.length === 0 ? (
                <div className="list-empty">
                    <div className="empty-state">
                        <h3>{__('No forms found', 'nexusforms')}</h3>
                        <p>{__('Get started by creating your first form.', 'nexusforms')}</p>
                        <Button
                            variant="primary"
                            href="admin.php?page=nexusforms-new"
                        >
                            {__('Create New Form', 'nexusforms')}
                        </Button>
                    </div>
                </div>
            ) : (
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th className="column-title">{__('Title', 'nexusforms')}</th>
                            <th className="column-shortcode">{__('Shortcode', 'nexusforms')}</th>
                            <th className="column-entries">{__('Entries', 'nexusforms')}</th>
                            <th className="column-status">{__('Status', 'nexusforms')}</th>
                            <th className="column-date">{__('Created', 'nexusforms')}</th>
                            <th className="column-actions">{__('Actions', 'nexusforms')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredForms.map((form) => (
                            <tr key={form.id}>
                                <td className="column-title">
                                    <strong>
                                        <a href={`admin.php?page=nexusforms-new&id=${form.id}`}>
                                            {form.title}
                                        </a>
                                    </strong>
                                </td>
                                <td className="column-shortcode">
                                    <code
                                        className="shortcode-snippet"
                                        onClick={(e) => {
                                            navigator.clipboard.writeText(`[nexusforms id="${form.id}"]`);
                                            e.target.classList.add('copied');
                                            setTimeout(() => e.target.classList.remove('copied'), 2000);
                                        }}
                                        title={__('Click to copy', 'nexusforms')}
                                    >
                                        [nexusforms id="{form.id}"]
                                    </code>
                                </td>
                                <td className="column-entries">
                                    <a href={`admin.php?page=nexusforms-entries&form_id=${form.id}`}>
                                        {__('View', 'nexusforms')}
                                    </a>
                                </td>
                                <td className="column-status">
                                    <span className={`status-badge status-${form.status}`}>
                                        {form.status}
                                    </span>
                                </td>
                                <td className="column-date">
                                    {new Date(form.created_at).toLocaleDateString()}
                                </td>
                                <td className="column-actions">
                                    <div className="row-actions">
                                        <Button
                                            variant="link"
                                            href={`admin.php?page=nexusforms-new&id=${form.id}`}
                                        >
                                            {__('Edit', 'nexusforms')}
                                        </Button>
                                        <span className="separator">|</span>
                                        <Button
                                            variant="link"
                                            onClick={() => handleDuplicate(form.id)}
                                            isBusy={duplicateForm.isPending}
                                        >
                                            {__('Duplicate', 'nexusforms')}
                                        </Button>
                                        <span className="separator">|</span>
                                        <Button
                                            variant="link"
                                            className="delete-link"
                                            onClick={() => handleDelete(form.id, form.title)}
                                            isBusy={deleteForm.isPending}
                                        >
                                            {__('Delete', 'nexusforms')}
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
};
