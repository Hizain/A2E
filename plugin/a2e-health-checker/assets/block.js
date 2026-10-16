/**
 * Editor block for the eBay Account Health Checker.
 *
 * Renders server-side so the editor preview matches the front end exactly, with a
 * single toggle for the highlighted standout container.
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'a2e/health-checker', {
		title: __( 'eBay Account Health Checker', 'a2e-health' ),
		description: __( 'A free, interactive eBay account health checker with recovery estimates, suggestions and a WhatsApp hire button.', 'a2e-health' ),
		icon: 'heart',
		category: 'widgets',
		keywords: [ 'ebay', 'health', 'seller', 'a2e' ],
		attributes: {
			highlight: { type: 'boolean', default: true }
		},
		supports: { html: false, align: [ 'wide', 'full' ] },

		edit: function ( props ) {
			var atts = props.attributes;
			return el(
				element.Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Display', 'a2e-health' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Highlight (standout container)', 'a2e-health' ),
							help: __( 'Wrap the tool in a highlighted border so it stands out wherever it appears.', 'a2e-health' ),
							checked: !! atts.highlight,
							onChange: function ( value ) {
								props.setAttributes( { highlight: value } );
							}
						} )
					)
				),
				el( ServerSideRender, {
					block: 'a2e/health-checker',
					attributes: atts
				} )
			);
		},

		save: function () {
			return null; // Rendered by PHP.
		}
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
);
