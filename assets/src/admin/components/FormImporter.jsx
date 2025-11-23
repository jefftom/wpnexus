/**
 * Form Importer component.
 *
 * Import forms from Gravity Forms and WPForms.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Button,
    Modal,
    TextareaControl,
    SelectControl,
    Notice,
    Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export const FormImporter = ({ onImportSuccess }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [source, setSource] = useState('gravity');
    const [jsonData, setJsonData] = useState('');
    const [isImporting, setIsImporting] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);

    const handleImport = async () => {
        if (!jsonData.trim()) {
            setError(__('Please paste JSON data to import.', 'nexusforms'));
            return;
        }

        setIsImporting(true);
        setError(null);
        setSuccess(null);

        try {
            const endpoint = source === 'gravity'
                ? '/nexusforms/v1/forms/import/gravity'
                : '/nexusforms/v1/forms/import/wpforms';

            const result = await apiFetch({
                path: endpoint,
                method: 'POST',
                data: { json: jsonData },
            });

            setSuccess(
                result.imported > 1
                    ? __(`Successfully imported ${result.imported} forms!`, 'nexusforms')
                    : __('Successfully imported form!', 'nexusforms')
            );

            setJsonData('');

            // Call success callback
            if (onImportSuccess) {
                onImportSuccess(result);
            }

            // Close modal after 2 seconds
            setTimeout(() => {
                setIsOpen(false);
                setSuccess(null);
            }, 2000);
        } catch (err) {
            setError(err.message || __('Import failed. Please check your JSON data.', 'nexusforms'));
        } finally {
            setIsImporting(false);
        }
    };

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            setJsonData(event.target.result);
            setError(null);
        };
        reader.onerror = () => {
            setError(__('Failed to read file.', 'nexusforms'));
        };
        reader.readAsText(file);
    };

    return (
        <>
            <Button
                variant="secondary"
                onClick={() => setIsOpen(true)}
                icon="download"
            >
                {__('Import', 'nexusforms')}
            </Button>

            {isOpen && (
                <Modal
                    title={__('Import Forms', 'nexusforms')}
                    onRequestClose={() => setIsOpen(false)}
                    className="nexusforms-import-modal"
                >
                    <div className="import-content">
                        <p>
                            {__(
                                'Import forms from Gravity Forms or WPForms by pasting their JSON export data below.',
                                'nexusforms'
                            )}
                        </p>

                        <SelectControl
                            label={__('Import From', 'nexusforms')}
                            value={source}
                            options={[
                                { label: __('Gravity Forms', 'nexusforms'), value: 'gravity' },
                                { label: __('WPForms', 'nexusforms'), value: 'wpforms' },
                            ]}
                            onChange={(value) => setSource(value)}
                            help={
                                source === 'gravity'
                                    ? __(
                                          'Export your Gravity Forms from Forms → Import/Export → Export Forms (JSON)',
                                          'nexusforms'
                                      )
                                    : __(
                                          'Export your WPForms from WPForms → Tools → Export (choose JSON format)',
                                          'nexusforms'
                                      )
                            }
                        />

                        <div className="import-methods">
                            <h3>{__('Method 1: Paste JSON', 'nexusforms')}</h3>

                            <TextareaControl
                                label={__('JSON Data', 'nexusforms')}
                                value={jsonData}
                                onChange={(value) => {
                                    setJsonData(value);
                                    setError(null);
                                }}
                                rows={15}
                                placeholder={__('Paste your JSON export data here...', 'nexusforms')}
                            />

                            <h3>{__('Method 2: Upload JSON File', 'nexusforms')}</h3>

                            <div className="file-upload">
                                <input
                                    type="file"
                                    accept=".json"
                                    onChange={handleFileUpload}
                                    id="import-file-input"
                                />
                                <label htmlFor="import-file-input" className="button">
                                    {__('Choose File', 'nexusforms')}
                                </label>
                            </div>
                        </div>

                        {error && (
                            <Notice status="error" isDismissible={false}>
                                {error}
                            </Notice>
                        )}

                        {success && (
                            <Notice status="success" isDismissible={false}>
                                {success}
                            </Notice>
                        )}

                        <div className="import-actions">
                            <Button variant="secondary" onClick={() => setIsOpen(false)}>
                                {__('Cancel', 'nexusforms')}
                            </Button>

                            <Button
                                variant="primary"
                                onClick={handleImport}
                                disabled={!jsonData.trim() || isImporting}
                            >
                                {isImporting && <Spinner />}
                                {isImporting
                                    ? __('Importing...', 'nexusforms')
                                    : __('Import Form', 'nexusforms')}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </>
    );
};
