( function ( blocks, blockEditor, components, element, i18n ) {
	'use strict';

	var registerBlockType = blocks.registerBlockType;
	var useBlockProps = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var ToggleControl = components.ToggleControl;
	var Notice = components.Notice;
	var createElement = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;

	registerBlockType( 'tornevall-tools/statuspage', {
		edit: function ( props ) {
			var history = !! props.attributes.history;
			var blockProps = useBlockProps( {
				className: 'ttfw-statuspage-editor-placeholder'
			} );

			return createElement(
				Fragment,
				null,
				createElement(
					InspectorControls,
					null,
					createElement(
						PanelBody,
						{
							title: __( 'Statuspage settings', 'tornevall-tools-for-wordpress' ),
							initialOpen: true
						},
						createElement( ToggleControl, {
							label: __( 'Show recent resolved incidents', 'tornevall-tools-for-wordpress' ),
							checked: history,
							onChange: function ( value ) {
								props.setAttributes( { history: !! value } );
							}
						} )
					)
				),
				createElement(
					'div',
					blockProps,
					createElement( 'strong', null, __( 'Tornevall Statuspage', 'tornevall-tools-for-wordpress' ) ),
					createElement(
						'p',
						null,
						__( 'The configured Statuspage is rendered on the public page using the same server-side renderer as the shortcode.', 'tornevall-tools-for-wordpress' )
					),
					createElement(
						Notice,
						{ status: 'info', isDismissible: false },
						history
							? __( 'Recent resolved incident history will be included.', 'tornevall-tools-for-wordpress' )
							: __( 'Only current status and active incidents will be shown.', 'tornevall-tools-for-wordpress' )
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n
);
