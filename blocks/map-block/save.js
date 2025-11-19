import { useBlockProps } from '@wordpress/block-editor';

/**
 * Save component for the Interactive Map block.
 * Returns the markup that will be saved to the database.
 */
export default function save({ attributes }) {
    const { mapId, layout } = attributes;

    // Don't render anything if no map is selected
    if (!mapId || mapId === 0) {
        return null;
    }

    const blockProps = useBlockProps.save({
        className: 'wim-map-container',
        'data-map-id': mapId,
        'data-layout': layout
    });

    return (
        <div {...blockProps}>
            {/* The frontend JavaScript will initialize the map here */}
        </div>
    );
}
