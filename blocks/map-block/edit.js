/**
 * Interactive Map Block - Edit Component
 * Build-free version using wp.element.createElement
 */

(function() {
    const { __ } = wp.i18n;
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl } = wp.components;
    const { useSelect } = wp.data;
    const { useEffect, useState, createElement: el } = wp.element;
    const apiFetch = wp.apiFetch;

    registerBlockType('wim/interactive-map', {
        edit: function({ attributes, setAttributes }) {
            const { mapId, layout } = attributes;
            const [mapPreview, setMapPreview] = useState(null);
            const [isLoading, setIsLoading] = useState(false);

            // Get all published maps
            const maps = useSelect(function(select) {
                const { getEntityRecords } = select('core');
                return getEntityRecords('postType', 'wim_map', {
                    per_page: -1,
                    status: 'publish',
                    orderby: 'title',
                    order: 'asc'
                });
            }, []);

            // Load map preview when mapId changes
            useEffect(function() {
                if (mapId > 0) {
                    setIsLoading(true);
                    apiFetch({ path: '/wim/v1/maps/' + mapId })
                        .then(function(data) {
                            setMapPreview(data);
                            setIsLoading(false);
                        })
                        .catch(function() {
                            setMapPreview(null);
                            setIsLoading(false);
                        });
                } else {
                    setMapPreview(null);
                }
            }, [mapId]);

            // Prepare map options
            const mapOptions = [
                { label: __('Select a map...', 'wp-interactive-maps'), value: 0 }
            ];

            if (maps) {
                maps.forEach(function(map) {
                    mapOptions.push({
                        label: map.title.rendered,
                        value: map.id
                    });
                });
            }

            const blockProps = useBlockProps({
                className: 'wim-block-editor'
            });

            // Build the editor UI
            const inspectorControls = el(
                InspectorControls,
                {},
                el(
                    PanelBody,
                    { title: __('Map Settings', 'wp-interactive-maps'), initialOpen: true },
                    el(SelectControl, {
                        label: __('Select Map', 'wp-interactive-maps'),
                        value: mapId,
                        options: mapOptions,
                        onChange: function(value) {
                            setAttributes({ mapId: parseInt(value) });
                        },
                        help: __('Choose which map to display', 'wp-interactive-maps')
                    }),
                    el(SelectControl, {
                        label: __('Layout', 'wp-interactive-maps'),
                        value: layout,
                        options: [
                            { label: __('Side Panel', 'wp-interactive-maps'), value: 'side' },
                            { label: __('Popup', 'wp-interactive-maps'), value: 'popup' }
                        ],
                        onChange: function(value) {
                            setAttributes({ layout: value });
                        },
                        help: __('How location content should be displayed', 'wp-interactive-maps')
                    })
                )
            );

            let blockContent;

            if (!mapId) {
                blockContent = el(
                    'div',
                    { className: 'wim-block-placeholder' },
                    el(
                        'div',
                        { className: 'wim-block-placeholder-icon' },
                        el('span', { className: 'dashicons dashicons-location-alt' })
                    ),
                    el('h3', {}, __('Interactive Map', 'wp-interactive-maps')),
                    el('p', {}, __('Select a map from the block settings to display it here.', 'wp-interactive-maps'))
                );
            } else if (isLoading) {
                blockContent = el(
                    'div',
                    { className: 'wim-block-loading' },
                    el('p', {}, __('Loading map preview...', 'wp-interactive-maps'))
                );
            } else if (!mapPreview) {
                blockContent = el(
                    'div',
                    { className: 'wim-block-error' },
                    el('p', {}, __('Map not found or failed to load.', 'wp-interactive-maps'))
                );
            } else {
                blockContent = el(
                    'div',
                    { className: 'wim-block-preview' },
                    el(
                        'div',
                        { className: 'wim-block-preview-header' },
                        el('h4', {}, mapPreview.title),
                        el(
                            'span',
                            { className: 'wim-block-preview-badge' },
                            layout === 'side' ? __('Side Panel', 'wp-interactive-maps') : __('Popup', 'wp-interactive-maps')
                        )
                    ),
                    el(
                        'div',
                        { className: 'wim-block-preview-image' },
                        el('img', { src: mapPreview.image_url, alt: mapPreview.title }),
                        el(
                            'div',
                            { className: 'wim-block-preview-overlay' },
                            el('span', {}, __('Preview', 'wp-interactive-maps'))
                        )
                    ),
                    el(
                        'div',
                        { className: 'wim-block-preview-info' },
                        el('p', {}, mapPreview.locations.length + ' ' + __('location(s)', 'wp-interactive-maps'))
                    )
                );
            }

            return el(
                'div',
                blockProps,
                inspectorControls,
                blockContent
            );
        },

        save: function() {
            // Dynamic block - rendered on server
            return null;
        }
    });
})();
