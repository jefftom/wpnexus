/**
 * Form canvas component with drag-and-drop.
 *
 * @package NexusForms
 * @since 1.0.0
 */

import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { useFormStore } from '../store/formStore';

const FieldPreview = ({ field, index }) => {
    const { selectField, deleteField, duplicateField, selectedFieldId } = useFormStore();

    const isSelected = selectedFieldId === field.id;

    const handleClick = (e) => {
        e.stopPropagation();
        selectField(field.id);
    };

    const handleDelete = (e) => {
        e.stopPropagation();
        if (confirm(__('Are you sure you want to delete this field?', 'nexusforms'))) {
            deleteField(field.id);
        }
    };

    const handleDuplicate = (e) => {
        e.stopPropagation();
        duplicateField(field.id);
    };

    const renderFieldPreview = () => {
        switch (field.type) {
            case 'textarea':
                return (
                    <textarea
                        className="preview-input"
                        placeholder={field.placeholder || __('Enter text...', 'nexusforms')}
                        disabled
                    />
                );

            case 'select':
                return (
                    <select className="preview-input" disabled>
                        <option>{__('Select...', 'nexusforms')}</option>
                        {field.options?.map((opt, i) => (
                            <option key={i} value={opt.value}>
                                {opt.label}
                            </option>
                        ))}
                    </select>
                );

            case 'radio':
            case 'checkbox':
                return (
                    <div className="preview-options">
                        {field.options?.map((opt, i) => (
                            <label key={i} className="preview-option">
                                <input type={field.type} disabled />
                                <span>{opt.label}</span>
                            </label>
                        ))}
                    </div>
                );

            default:
                return (
                    <input
                        type={field.type}
                        className="preview-input"
                        placeholder={field.placeholder || __('Enter value...', 'nexusforms')}
                        disabled
                    />
                );
        }
    };

    return (
        <Draggable draggableId={field.id} index={index}>
            {(provided, snapshot) => (
                <div
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    className={`canvas-field ${isSelected ? 'selected' : ''} ${
                        snapshot.isDragging ? 'dragging' : ''
                    }`}
                    onClick={handleClick}
                >
                    <div className="field-header">
                        <div className="field-drag-handle" {...provided.dragHandleProps}>
                            <Icon icon="move" />
                        </div>
                        <div className="field-label-wrapper">
                            <label className="field-label">
                                {field.label}
                                {field.required && <span className="required">*</span>}
                            </label>
                            {field.description && (
                                <p className="field-description">{field.description}</p>
                            )}
                        </div>
                        <div className="field-actions">
                            <Button
                                icon="admin-page"
                                label={__('Duplicate', 'nexusforms')}
                                size="small"
                                onClick={handleDuplicate}
                            />
                            <Button
                                icon="trash"
                                label={__('Delete', 'nexusforms')}
                                size="small"
                                isDestructive
                                onClick={handleDelete}
                            />
                        </div>
                    </div>
                    <div className="field-preview">{renderFieldPreview()}</div>
                </div>
            )}
        </Draggable>
    );
};

export const FormCanvas = () => {
    const { fields, reorderFields, formTitle, formDescription } = useFormStore();

    const handleDragEnd = (result) => {
        if (!result.destination) return;
        reorderFields(result.source.index, result.destination.index);
    };

    return (
        <div className="nexusforms-form-canvas">
            <div className="canvas-header">
                <div className="form-preview-header">
                    {formTitle && <h2 className="form-title">{formTitle}</h2>}
                    {formDescription && (
                        <p className="form-description">{formDescription}</p>
                    )}
                </div>
            </div>

            <DragDropContext onDragEnd={handleDragEnd}>
                <Droppable droppableId="form-fields">
                    {(provided, snapshot) => (
                        <div
                            ref={provided.innerRef}
                            {...provided.droppableProps}
                            className={`canvas-drop-zone ${
                                snapshot.isDraggingOver ? 'drag-over' : ''
                            }`}
                        >
                            {fields.length === 0 ? (
                                <div className="empty-canvas">
                                    <Icon icon="feedback" size={48} />
                                    <h3>{__('Start Building Your Form', 'nexusforms')}</h3>
                                    <p>
                                        {__(
                                            'Click on a field type from the left panel to add it to your form.',
                                            'nexusforms'
                                        )}
                                    </p>
                                </div>
                            ) : (
                                fields.map((field, index) => (
                                    <FieldPreview key={field.id} field={field} index={index} />
                                ))
                            )}
                            {provided.placeholder}
                        </div>
                    )}
                </Droppable>
            </DragDropContext>

            {fields.length > 0 && (
                <div className="canvas-footer">
                    <Button variant="primary" size="large">
                        {__('Submit', 'nexusforms')}
                    </Button>
                </div>
            )}
        </div>
    );
};
