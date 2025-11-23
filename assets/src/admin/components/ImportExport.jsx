/**
 * Import/Export UI component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextareaControl, Icon } from '@wordpress/components';
import { useFormStore } from '../store/formStore';
import apiFetch from '@wordpress/api-fetch';

export const ImportExport = ({ formId }) => {
    const [showExportModal, setShowExportModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [exportData, setExportData] = useState('');
    const [importData, setImportData] = useState('');
    const [isExporting, setIsExporting] = useState(false);
    const [isImporting, setIsImporting] = useState(false);
    const { setFormData, getFormData } = useFormStore();

    const handleExport = async () => {
        if (!formId) {
            alert(__('Please save the form first before exporting.', 'nexusforms'));
            return;
        }

        setIsExporting(true);
        setShowExportModal(true);

        try {
            const response = await apiFetch({
                path: `/nexusforms/v1/forms/${formId}/export`,
            });

            const exportJson = JSON.stringify(response, null, 2);
            setExportData(exportJson);
        } catch (error) {
            console.error('Export error:', error);
            alert(__('Failed to export form. Please try again.', 'nexusforms'));
            setShowExportModal(false);
        } finally {
            setIsExporting(false);
        }
    };

    const handleCopyExport = () => {
        navigator.clipboard.writeText(exportData);
        alert(__('Export data copied to clipboard!', 'nexusforms'));
    };

    const handleDownloadExport = () => {
        const blob = new Blob([exportData], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `nexusforms-export-${formId}-${Date.now()}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    const handleImport = async () => {
        if (!importData.trim()) {
            alert(__('Please paste the import data.', 'nexusforms'));
            return;
        }

        try {
            const parsedData = JSON.parse(importData);

            if (!parsedData.form) {
                alert(__('Invalid import data format.', 'nexusforms'));
                return;
            }

            setIsImporting(true);

            // Apply imported data to current form
            setFormData({
                id: 0, // New form
                title: parsedData.form.title + ' (Imported)',
                description: parsedData.form.description,
                status: 'draft',
                settings: parsedData.form.settings || {},
                fields: parsedData.form.fields || [],
            });

            setShowImportModal(false);
            setImportData('');
            alert(__('Form imported successfully! Please review and save.', 'nexusforms'));
        } catch (error) {
            console.error('Import error:', error);
            alert(__('Failed to import form. Please check the data format.', 'nexusforms'));
        } finally {
            setIsImporting(false);
        }
    };

    const handleFileImport = (event) => {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            setImportData(e.target.result);
        };
        reader.readAsText(file);
    };

    return (
        <div className="import-export-actions">
            <Button variant="secondary" onClick={handleExport} isBusy={isExporting}>
                <Icon icon="download" />
                {__('Export', 'nexusforms')}
            </Button>

            <Button variant="secondary" onClick={() => setShowImportModal(true)}>
                <Icon icon="upload" />
                {__('Import', 'nexusforms')}
            </Button>

            {/* Export Modal */}
            {showExportModal && (
                <Modal
                    title={__('Export Form', 'nexusforms')}
                    onRequestClose={() => setShowExportModal(false)}
                    className="export-modal"
                >
                    <div className="export-modal-content">
                        <p>
                            {__(
                                'Copy the JSON data below or download it as a file. You can import this data into another NexusForms installation.',
                                'nexusforms'
                            )}
                        </p>

                        <TextareaControl
                            value={exportData}
                            readOnly
                            rows={15}
                            className="export-data"
                        />

                        <div className="modal-actions">
                            <Button variant="secondary" onClick={handleCopyExport}>
                                <Icon icon="clipboard" />
                                {__('Copy to Clipboard', 'nexusforms')}
                            </Button>

                            <Button variant="primary" onClick={handleDownloadExport}>
                                <Icon icon="download" />
                                {__('Download JSON File', 'nexusforms')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}

            {/* Import Modal */}
            {showImportModal && (
                <Modal
                    title={__('Import Form', 'nexusforms')}
                    onRequestClose={() => setShowImportModal(false)}
                    className="import-modal"
                >
                    <div className="import-modal-content">
                        <p>
                            {__(
                                'Paste the exported JSON data below or upload a JSON file to import a form.',
                                'nexusforms'
                            )}
                        </p>

                        <div className="import-file-upload">
                            <input
                                type="file"
                                accept=".json,application/json"
                                onChange={handleFileImport}
                                id="import-file"
                            />
                            <label htmlFor="import-file" className="button button-secondary">
                                <Icon icon="media-default" />
                                {__('Choose File', 'nexusforms')}
                            </label>
                        </div>

                        <div className="import-divider">
                            <span>{__('OR', 'nexusforms')}</span>
                        </div>

                        <TextareaControl
                            label={__('Paste JSON Data', 'nexusforms')}
                            value={importData}
                            onChange={setImportData}
                            rows={15}
                            placeholder={__('Paste your exported JSON data here...', 'nexusforms')}
                        />

                        <div className="modal-actions">
                            <Button
                                variant="secondary"
                                onClick={() => setShowImportModal(false)}
                            >
                                {__('Cancel', 'nexusforms')}
                            </Button>

                            <Button
                                variant="primary"
                                onClick={handleImport}
                                isBusy={isImporting}
                                disabled={!importData.trim()}
                            >
                                {__('Import Form', 'nexusforms')}
                            </Button>
                        </div>

                        <div className="import-warning">
                            <Icon icon="warning" />
                            <p>
                                {__(
                                    'Warning: Importing will replace the current form data. Make sure to export your current form first if you want to keep it.',
                                    'nexusforms'
                                )}
                            </p>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
};
