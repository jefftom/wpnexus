/**
 * Form Export/Import component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextareaControl, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useQueryClient } from '@tanstack/react-query';

export const FormExportImport = ({ formId }) => {
    const [showExport, setShowExport] = useState(false);
    const [showImport, setShowImport] = useState(false);
    const [exportData, setExportData] = useState('');
    const [importData, setImportData] = useState('');
    const [isExporting, setIsExporting] = useState(false);
    const [isImporting, setIsImporting] = useState(false);
    const queryClient = useQueryClient();

    const handleExport = async () => {
        setIsExporting(true);
        try {
            const form = await apiFetch({
                path: `/nexusforms/v1/forms/${formId}`,
            });

            // Create export object with all form data
            const exportObj = {
                title: form.title,
                description: form.description,
                status: form.status,
                settings: form.settings,
                fields: form.fields,
                version: '1.0',
                exported_at: new Date().toISOString(),
            };

            const json = JSON.stringify(exportObj, null, 2);
            setExportData(json);
            setShowExport(true);
        } catch (error) {
            alert(__('Failed to export form.', 'nexusforms'));
        } finally {
            setIsExporting(false);
        }
    };

    const handleDownload = () => {
        const blob = new Blob([exportData], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `nexusforms-export-${formId}-${Date.now()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    const handleImport = async () => {
        if (!importData.trim()) {
            alert(__('Please paste JSON data to import.', 'nexusforms'));
            return;
        }

        setIsImporting(true);
        try {
            const data = JSON.parse(importData);

            // Update current form with imported data
            await apiFetch({
                path: `/nexusforms/v1/forms/${formId}`,
                method: 'PUT',
                data: {
                    title: data.title,
                    description: data.description,
                    status: data.status,
                    settings: data.settings,
                    fields: data.fields,
                },
            });

            // Invalidate queries to refresh data
            queryClient.invalidateQueries(['form', formId]);

            alert(__('Form imported successfully! Refreshing...', 'nexusforms'));
            window.location.reload();
        } catch (error) {
            alert(__('Failed to import form. Please check the JSON format.', 'nexusforms') + '\n\n' + error.message);
        } finally {
            setIsImporting(false);
        }
    };

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                setImportData(event.target.result);
            };
            reader.readAsText(file);
        }
    };

    return (
        <div className="nexusforms-export-import">
            <div style={{ display: 'flex', gap: '8px' }}>
                <Button variant="secondary" onClick={handleExport} isBusy={isExporting}>
                    {__('Export Form', 'nexusforms')}
                </Button>
                <Button variant="secondary" onClick={() => setShowImport(true)}>
                    {__('Import Form', 'nexusforms')}
                </Button>
            </div>

            {showExport && (
                <Modal
                    title={__('Export Form', 'nexusforms')}
                    onRequestClose={() => setShowExport(false)}
                    className="nexusforms-export-modal"
                >
                    <div className="modal-content" style={{ padding: '20px' }}>
                        <Notice status="success" isDismissible={false}>
                            <p>{__('Form exported successfully! Copy the JSON below or download as a file.', 'nexusforms')}</p>
                        </Notice>

                        <TextareaControl
                            value={exportData}
                            rows={15}
                            readOnly
                            style={{ fontFamily: 'monospace', fontSize: '12px' }}
                        />

                        <div style={{ display: 'flex', gap: '12px', marginTop: '20px' }}>
                            <Button variant="primary" onClick={handleDownload}>
                                {__('Download JSON', 'nexusforms')}
                            </Button>
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    navigator.clipboard.writeText(exportData);
                                    alert(__('Copied to clipboard!', 'nexusforms'));
                                }}
                            >
                                {__('Copy to Clipboard', 'nexusforms')}
                            </Button>
                            <Button variant="secondary" onClick={() => setShowExport(false)}>
                                {__('Close', 'nexusforms')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}

            {showImport && (
                <Modal
                    title={__('Import Form', 'nexusforms')}
                    onRequestClose={() => setShowImport(false)}
                    className="nexusforms-import-modal"
                >
                    <div className="modal-content" style={{ padding: '20px' }}>
                        <Notice status="warning" isDismissible={false}>
                            <p><strong>{__('Warning:', 'nexusforms')}</strong> {__('This will replace the current form configuration.', 'nexusforms')}</p>
                        </Notice>

                        <div style={{ marginBottom: '16px' }}>
                            <label>
                                <strong>{__('Upload JSON File:', 'nexusforms')}</strong>
                                <input
                                    type="file"
                                    accept=".json"
                                    onChange={handleFileUpload}
                                    style={{ display: 'block', marginTop: '8px' }}
                                />
                            </label>
                        </div>

                        <p><strong>{__('Or paste JSON directly:', 'nexusforms')}</strong></p>

                        <TextareaControl
                            value={importData}
                            onChange={setImportData}
                            rows={12}
                            placeholder={__('Paste exported JSON here...', 'nexusforms')}
                            style={{ fontFamily: 'monospace', fontSize: '12px' }}
                        />

                        <div style={{ display: 'flex', gap: '12px', marginTop: '20px' }}>
                            <Button variant="primary" onClick={handleImport} isBusy={isImporting}>
                                {__('Import Form', 'nexusforms')}
                            </Button>
                            <Button variant="secondary" onClick={() => setShowImport(false)}>
                                {__('Cancel', 'nexusforms')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
};
