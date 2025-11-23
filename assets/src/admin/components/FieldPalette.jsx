/**
 * Field palette component.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, PanelBody, SearchControl } from '@wordpress/components';
import { FIELD_TYPES, FIELD_CATEGORIES } from '../config/fieldTypes';
import { useFormStore } from '../store/formStore';

export const FieldPalette = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [activeCategory, setActiveCategory] = useState('all');
    const addField = useFormStore((state) => state.addField);

    const filteredFields = FIELD_TYPES.filter((field) => {
        const matchesSearch = field.label.toLowerCase().includes(searchTerm.toLowerCase()) ||
            field.description.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesCategory = activeCategory === 'all' || field.category === activeCategory;
        return matchesSearch && matchesCategory;
    });

    const handleAddField = (fieldType) => {
        addField(fieldType);
    };

    return (
        <div className="nexusforms-field-palette">
            <div className="palette-header">
                <h3>{__('Add Fields', 'nexusforms')}</h3>
                <SearchControl
                    value={searchTerm}
                    onChange={setSearchTerm}
                    placeholder={__('Search fields...', 'nexusforms')}
                />
            </div>

            <div className="palette-categories">
                <Button
                    variant={activeCategory === 'all' ? 'primary' : 'secondary'}
                    size="small"
                    onClick={() => setActiveCategory('all')}
                >
                    {__('All', 'nexusforms')}
                </Button>
                {FIELD_CATEGORIES.map((category) => (
                    <Button
                        key={category.id}
                        variant={activeCategory === category.id ? 'primary' : 'secondary'}
                        size="small"
                        onClick={() => setActiveCategory(category.id)}
                    >
                        {category.label}
                    </Button>
                ))}
            </div>

            <div className="palette-fields">
                {filteredFields.length === 0 ? (
                    <p className="no-fields">{__('No fields found.', 'nexusforms')}</p>
                ) : (
                    filteredFields.map((field) => (
                        <div
                            key={field.type}
                            className={`palette-field ${field.pro ? 'pro-field' : ''}`}
                            onClick={() => !field.pro && handleAddField(field.type)}
                            onKeyPress={(e) => {
                                if (e.key === 'Enter' && !field.pro) {
                                    handleAddField(field.type);
                                }
                            }}
                            role="button"
                            tabIndex={0}
                        >
                            <span className="field-icon">{field.icon}</span>
                            <div className="field-info">
                                <strong className="field-label">
                                    {field.label}
                                    {field.pro && <span className="pro-badge">PRO</span>}
                                </strong>
                                <span className="field-description">{field.description}</span>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
};
