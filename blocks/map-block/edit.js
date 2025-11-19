import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Edit component for the Interactive Map block.
 */
export default function Edit({ attributes, setAttributes }) {
    const { mapId, layout } = attributes;
    const [mapPreview, setMapPreview] = useState(null);
    const [isLoading, setIsLoading] = useState(false);

    // Get all published maps using useSelect
    const maps = useSelect((select) => {
        const { getEntityRecords } = select('core');
        return getEntityRecords('postType', 'wim_map', {
            per_page: -1,
            status: 'publish',
            orderby: 'title',
            order: 'asc'
        });
    }, []);

    // Load map preview data when mapId changes
    useEffect(() => {
        if (mapId > 0) {
            setIsLoading(true);
            apiFetch({ path: `/wim/v1/maps/${mapId}` })
                .then((data) => {
                    setMapPreview(data);
                    setIsLoading(false);
                })
                .catch(() => {
                    setMapPreview(null);
                    setIsLoading(false);
                });
        } else {
            setMapPreview(null);
        }
    }, [mapId]);

    // Prepare map options for SelectControl
    const mapOptions = [
        { label: __('Select a map...', 'wp-interactive-maps'), value: 0 }
    ];

    if (maps) {
        maps.forEach((map) => {
            mapOptions.push({
                label: map.title.rendered,
                value: map.id
            });
        });
    }

    const blockProps = useBlockProps({
        className: 'wim-block-editor'
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Map Settings', 'wp-interactive-maps')} initialOpen={true}>
                    <SelectControl
                        label={__('Select Map', 'wp-interactive-maps')}
                        value={mapId}
                        options={mapOptions}
                        onChange={(value) => setAttributes({ mapId: parseInt(value) })}
                        help={__('Choose which map to display', 'wp-interactive-maps')}
                    />
                    <SelectControl
                        label={__('Layout', 'wp-interactive-maps')}
                        value={layout}
                        options={[
                            { label: __('Side Panel', 'wp-interactive-maps'), value: 'side' },
                            { label: __('Popup', 'wp-interactive-maps'), value: 'popup' }
                        ]}
                        onChange={(value) => setAttributes({ layout: value })}
                        help={__('How location content should be displayed', 'wp-interactive-maps')}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                {!mapId && (
                    <div className="wim-block-placeholder">
                        <div className="wim-block-placeholder-icon">
                            <span className="dashicons dashicons-location-alt"></span>
                        </div>
                        <h3>{__('Interactive Map', 'wp-interactive-maps')}</h3>
                        <p>{__('Select a map from the block settings to display it here.', 'wp-interactive-maps')}</p>
                    </div>
                )}

                {mapId > 0 && isLoading && (
                    <div className="wim-block-loading">
                        <p>{__('Loading map preview...', 'wp-interactive-maps')}</p>
                    </div>
                )}

                {mapId > 0 && !isLoading && !mapPreview && (
                    <div className="wim-block-error">
                        <p>{__('Map not found or failed to load.', 'wp-interactive-maps')}</p>
                    </div>
                )}

                {mapId > 0 && !isLoading && mapPreview && (
                    <div className="wim-block-preview">
                        <div className="wim-block-preview-header">
                            <h4>{mapPreview.title}</h4>
                            <span className="wim-block-preview-badge">
                                {layout === 'side' ? __('Side Panel', 'wp-interactive-maps') : __('Popup', 'wp-interactive-maps')}
                            </span>
                        </div>
                        <div className="wim-block-preview-image">
                            <img src={mapPreview.image_url} alt={mapPreview.title} />
                            <div className="wim-block-preview-overlay">
                                <span>{__('Preview', 'wp-interactive-maps')}</span>
                            </div>
                        </div>
                        <div className="wim-block-preview-info">
                            <p>
                                {mapPreview.locations.length} {__('location(s)', 'wp-interactive-maps')}
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
