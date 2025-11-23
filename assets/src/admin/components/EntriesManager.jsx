/**
 * Entries Management Component
 *
 * Complete interface for viewing and managing form submissions.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    SearchControl,
    SelectControl,
    Spinner,
    Modal,
    Notice,
    CheckboxControl,
} from '@wordpress/components';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { useForms } from '../hooks/useFormApi';

export const EntriesManager = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [formFilter, setFormFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('active');
    const [selectedEntry, setSelectedEntry] = useState(null);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(null);
    const [selectedEntries, setSelectedEntries] = useState([]);

    const queryClient = useQueryClient();

    // Fetch forms for filter
    const { data: formsData } = useForms({});

    // Fetch entries
    const { data: entriesData, isLoading, error } = useQuery({
        queryKey: ['entries', formFilter, statusFilter],
        queryFn: async () => {
            const params = new URLSearchParams();
            if (formFilter) params.append('form_id', formFilter);
            if (statusFilter) params.append('status', statusFilter);

            return await apiFetch({
                path: `/nexusforms/v1/entries?${params.toString()}`,
            });
        },
    });

    // Delete entry mutation
    const deleteMutation = useMutation({
        mutationFn: async (entryId) => {
            return await apiFetch({
                path: `/nexusforms/v1/entries/${entryId}`,
                method: 'DELETE',
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['entries']);
            setShowDeleteConfirm(null);
            setSelectedEntries([]);
        },
    });

    // Bulk delete mutation
    const bulkDeleteMutation = useMutation({
        mutationFn: async (entryIds) => {
            return await Promise.all(
                entryIds.map((id) =>
                    apiFetch({
                        path: `/nexusforms/v1/entries/${id}`,
                        method: 'DELETE',
                    })
                )
            );
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['entries']);
            setSelectedEntries([]);
        },
    });

    // Export to CSV
    const handleExportCSV = async () => {
        try {
            const params = new URLSearchParams();
            if (formFilter) params.append('form_id', formFilter);

            const response = await apiFetch({
                path: `/nexusforms/v1/entries/export?${params.toString()}`,
                parse: false,
            });

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `entries-${formFilter || 'all'}-${Date.now()}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (err) {
            console.error('Export failed:', err);
        }
    };

    // Filter entries by search
    const filteredEntries = useMemo(() => {
        if (!entriesData?.entries) return [];

        return entriesData.entries.filter((entry) => {
            const searchLower = searchTerm.toLowerCase();
            const entryDataString = JSON.stringify(entry.entry_data).toLowerCase();
            return entryDataString.includes(searchLower);
        });
    }, [entriesData, searchTerm]);

    // Get form name
    const getFormName = (formId) => {
        const form = formsData?.forms?.find((f) => f.id === formId);
        return form?.title || `Form #${formId}`;
    };

    // Toggle entry selection
    const toggleEntry = (entryId) => {
        setSelectedEntries((prev) =>
            prev.includes(entryId) ? prev.filter((id) => id !== entryId) : [...prev, entryId]
        );
    };

    // Toggle all entries
    const toggleAll = () => {
        if (selectedEntries.length === filteredEntries.length) {
            setSelectedEntries([]);
        } else {
            setSelectedEntries(filteredEntries.map((e) => e.id));
        }
    };

    if (error) {
        return (
            <Notice status="error" isDismissible={false}>
                {__('Failed to load entries.', 'nexusforms')}
            </Notice>
        );
    }

    return (
        <div className="nexusforms-entries-manager">
            <div className="entries-header">
                <div className="entries-filters">
                    <SearchControl
                        value={searchTerm}
                        onChange={setSearchTerm}
                        placeholder={__('Search entries...', 'nexusforms')}
                    />

                    <SelectControl
                        label={__('Form', 'nexusforms')}
                        value={formFilter}
                        options={[
                            { label: __('All Forms', 'nexusforms'), value: '' },
                            ...(formsData?.forms || []).map((form) => ({
                                label: form.title,
                                value: form.id.toString(),
                            })),
                        ]}
                        onChange={setFormFilter}
                    />

                    <SelectControl
                        label={__('Status', 'nexusforms')}
                        value={statusFilter}
                        options={[
                            { label: __('Active', 'nexusforms'), value: 'active' },
                            { label: __('Spam', 'nexusforms'), value: 'spam' },
                            { label: __('Trash', 'nexusforms'), value: 'trash' },
                        ]}
                        onChange={setStatusFilter}
                    />
                </div>

                <div className="entries-actions">
                    {selectedEntries.length > 0 && (
                        <Button
                            variant="secondary"
                            isDestructive
                            onClick={() => {
                                if (
                                    confirm(
                                        __('Delete %d selected entries?', 'nexusforms').replace(
                                            '%d',
                                            selectedEntries.length
                                        )
                                    )
                                ) {
                                    bulkDeleteMutation.mutate(selectedEntries);
                                }
                            }}
                        >
                            {__('Delete Selected', 'nexusforms')} ({selectedEntries.length})
                        </Button>
                    )}

                    <Button variant="secondary" onClick={handleExportCSV} disabled={!formFilter}>
                        {__('Export CSV', 'nexusforms')}
                    </Button>
                </div>
            </div>

            {isLoading ? (
                <div className="entries-loading">
                    <Spinner />
                    <p>{__('Loading entries...', 'nexusforms')}</p>
                </div>
            ) : filteredEntries.length === 0 ? (
                <div className="entries-empty">
                    <p>{__('No entries found.', 'nexusforms')}</p>
                </div>
            ) : (
                <table className="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td className="check-column">
                                <input
                                    type="checkbox"
                                    checked={
                                        selectedEntries.length === filteredEntries.length &&
                                        filteredEntries.length > 0
                                    }
                                    onChange={toggleAll}
                                />
                            </td>
                            <th>{__('ID', 'nexusforms')}</th>
                            <th>{__('Form', 'nexusforms')}</th>
                            <th>{__('Data Preview', 'nexusforms')}</th>
                            <th>{__('IP Address', 'nexusforms')}</th>
                            <th>{__('Date', 'nexusforms')}</th>
                            <th>{__('Actions', 'nexusforms')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredEntries.map((entry) => (
                            <tr key={entry.id}>
                                <th className="check-column">
                                    <input
                                        type="checkbox"
                                        checked={selectedEntries.includes(entry.id)}
                                        onChange={() => toggleEntry(entry.id)}
                                    />
                                </th>
                                <td>#{entry.id}</td>
                                <td>{getFormName(entry.form_id)}</td>
                                <td>
                                    <div className="entry-preview">
                                        {Object.entries(entry.entry_data || {})
                                            .slice(0, 2)
                                            .map(([key, value]) => (
                                                <span key={key} className="entry-field">
                                                    <strong>{key}:</strong>{' '}
                                                    {Array.isArray(value)
                                                        ? value.join(', ')
                                                        : String(value).substring(0, 50)}
                                                    {String(value).length > 50 ? '...' : ''}
                                                </span>
                                            ))}
                                        {Object.keys(entry.entry_data || {}).length > 2 && (
                                            <span className="more-fields">
                                                +{Object.keys(entry.entry_data).length - 2}{' '}
                                                {__('more', 'nexusforms')}
                                            </span>
                                        )}
                                    </div>
                                </td>
                                <td>{entry.ip_address || '—'}</td>
                                <td>
                                    {new Date(entry.created_at).toLocaleDateString()} <br />
                                    <small>{new Date(entry.created_at).toLocaleTimeString()}</small>
                                </td>
                                <td>
                                    <div className="row-actions">
                                        <Button
                                            variant="link"
                                            onClick={() => setSelectedEntry(entry)}
                                        >
                                            {__('View', 'nexusforms')}
                                        </Button>
                                        <span>|</span>
                                        <Button
                                            variant="link"
                                            isDestructive
                                            onClick={() => setShowDeleteConfirm(entry.id)}
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

            {/* Entry Detail Modal */}
            {selectedEntry && (
                <Modal
                    title={__('Entry Details', 'nexusforms')}
                    onRequestClose={() => setSelectedEntry(null)}
                    className="nexusforms-entry-modal"
                >
                    <div className="entry-details">
                        <div className="entry-meta">
                            <p>
                                <strong>{__('Entry ID:', 'nexusforms')}</strong> #{selectedEntry.id}
                            </p>
                            <p>
                                <strong>{__('Form:', 'nexusforms')}</strong>{' '}
                                {getFormName(selectedEntry.form_id)}
                            </p>
                            <p>
                                <strong>{__('Date:', 'nexusforms')}</strong>{' '}
                                {new Date(selectedEntry.created_at).toLocaleString()}
                            </p>
                            {selectedEntry.ip_address && (
                                <p>
                                    <strong>{__('IP Address:', 'nexusforms')}</strong>{' '}
                                    {selectedEntry.ip_address}
                                </p>
                            )}
                            {selectedEntry.user_agent && (
                                <p>
                                    <strong>{__('User Agent:', 'nexusforms')}</strong>{' '}
                                    {selectedEntry.user_agent}
                                </p>
                            )}
                        </div>

                        <hr />

                        <div className="entry-data">
                            <h3>{__('Submission Data:', 'nexusforms')}</h3>
                            <table className="widefat">
                                <tbody>
                                    {Object.entries(selectedEntry.entry_data || {}).map(
                                        ([key, value]) => (
                                            <tr key={key}>
                                                <th>{key}</th>
                                                <td>
                                                    {Array.isArray(value) ? (
                                                        <ul>
                                                            {value.map((v, i) => (
                                                                <li key={i}>{String(v)}</li>
                                                            ))}
                                                        </ul>
                                                    ) : typeof value === 'object' ? (
                                                        <pre>{JSON.stringify(value, null, 2)}</pre>
                                                    ) : (
                                                        String(value)
                                                    )}
                                                </td>
                                            </tr>
                                        )
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="modal-actions">
                            <Button variant="secondary" onClick={() => setSelectedEntry(null)}>
                                {__('Close', 'nexusforms')}
                            </Button>
                            <Button
                                variant="primary"
                                isDestructive
                                onClick={() => {
                                    setShowDeleteConfirm(selectedEntry.id);
                                    setSelectedEntry(null);
                                }}
                            >
                                {__('Delete Entry', 'nexusforms')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}

            {/* Delete Confirmation Modal */}
            {showDeleteConfirm && (
                <Modal
                    title={__('Confirm Delete', 'nexusforms')}
                    onRequestClose={() => setShowDeleteConfirm(null)}
                    className="nexusforms-confirm-modal"
                >
                    <p>{__('Are you sure you want to delete this entry? This cannot be undone.', 'nexusforms')}</p>
                    <div className="modal-actions">
                        <Button variant="secondary" onClick={() => setShowDeleteConfirm(null)}>
                            {__('Cancel', 'nexusforms')}
                        </Button>
                        <Button
                            variant="primary"
                            isDestructive
                            onClick={() => deleteMutation.mutate(showDeleteConfirm)}
                            disabled={deleteMutation.isPending}
                        >
                            {deleteMutation.isPending
                                ? __('Deleting...', 'nexusforms')
                                : __('Delete', 'nexusforms')}
                        </Button>
                    </div>
                </Modal>
            )}
        </div>
    );
};
