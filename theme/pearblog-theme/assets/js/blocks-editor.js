/**
 * PearBlog PRO Gutenberg Blocks - Editor Scripts
 *
 * @package PearBlog
 * @version 2.0.0
 */

( function ( blocks, element, blockEditor, components, i18n ) {
	var el          = element.createElement;
	var Fragment    = element.Fragment;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps     = blockEditor.useBlockProps;
	var __                = i18n.__;

	// -----------------------------------------------------------------------
	// pearblog/hero
	// -----------------------------------------------------------------------
	blocks.registerBlockType( 'pearblog/hero', {
		title: __( 'PearBlog Hero', 'pearblog-theme' ),
		icon: 'format-image',
		category: 'pearblog',
		attributes: {
			title:     { type: 'string', default: '' },
			subtitle:  { type: 'string', default: '' },
			styleType: { type: 'string', default: 'gradient' },
			imageUrl:  { type: 'string', default: '' },
			videoUrl:  { type: 'string', default: '' },
			ctaText:   { type: 'string', default: '' },
			ctaUrl:    { type: 'string', default: '' },
		},
		edit: function ( props ) {
			var attrs = props.attributes;
			var blockProps = useBlockProps( { className: 'pb-block-hero-editor' } );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( components.PanelBody, { title: __( 'Hero Settings', 'pearblog-theme' ) },
						el( components.SelectControl, {
							label: __( 'Style', 'pearblog-theme' ),
							value: attrs.styleType,
							options: [
								{ label: __( 'Gradient', 'pearblog-theme' ), value: 'gradient' },
								{ label: __( 'Image', 'pearblog-theme' ), value: 'image' },
								{ label: __( 'Video', 'pearblog-theme' ), value: 'video' },
							],
							onChange: function ( val ) { props.setAttributes( { styleType: val } ); },
						} ),
						attrs.styleType === 'image' && el( components.TextControl, {
							label: __( 'Image URL', 'pearblog-theme' ),
							value: attrs.imageUrl,
							onChange: function ( val ) { props.setAttributes( { imageUrl: val } ); },
						} ),
						attrs.styleType === 'video' && el( components.TextControl, {
							label: __( 'Video URL', 'pearblog-theme' ),
							value: attrs.videoUrl,
							onChange: function ( val ) { props.setAttributes( { videoUrl: val } ); },
						} ),
						el( components.TextControl, {
							label: __( 'CTA Button Text', 'pearblog-theme' ),
							value: attrs.ctaText,
							onChange: function ( val ) { props.setAttributes( { ctaText: val } ); },
						} ),
						el( components.TextControl, {
							label: __( 'CTA Button URL', 'pearblog-theme' ),
							value: attrs.ctaUrl,
							onChange: function ( val ) { props.setAttributes( { ctaUrl: val } ); },
						} )
					)
				),
				el( 'div', blockProps,
					el( 'div', { className: 'pb-block-hero-preview pb-hero-' + attrs.styleType },
						el( blockEditor.RichText, {
							tagName: 'h1',
							className: 'pb-hero-title',
							value: attrs.title,
							onChange: function ( val ) { props.setAttributes( { title: val } ); },
							placeholder: __( 'Hero Title…', 'pearblog-theme' ),
						} ),
						el( blockEditor.RichText, {
							tagName: 'p',
							className: 'pb-hero-subtitle',
							value: attrs.subtitle,
							onChange: function ( val ) { props.setAttributes( { subtitle: val } ); },
							placeholder: __( 'Hero Subtitle…', 'pearblog-theme' ),
						} ),
						attrs.ctaText && el( 'span', { className: 'pb-hero-cta-button' }, attrs.ctaText )
					)
				)
			);
		},
		save: function () {
			return null; // Server-side rendered.
		},
	} );

	// -----------------------------------------------------------------------
	// pearblog/faq
	// -----------------------------------------------------------------------
	blocks.registerBlockType( 'pearblog/faq', {
		title: __( 'PearBlog FAQ', 'pearblog-theme' ),
		icon: 'editor-help',
		category: 'pearblog',
		attributes: {
			title: { type: 'string', default: 'Frequently Asked Questions' },
			items: { type: 'string', default: '[]' },
		},
		edit: function ( props ) {
			var attrs      = props.attributes;
			var blockProps = useBlockProps( { className: 'pb-block-faq-editor' } );
			var items      = [];

			try {
				items = JSON.parse( attrs.items );
			} catch ( e ) {
				items = [];
			}

			if ( ! Array.isArray( items ) ) {
				items = [];
			}

			function updateItems( newItems ) {
				props.setAttributes( { items: JSON.stringify( newItems ) } );
			}

			return el( Fragment, {},
				el( InspectorControls, {},
					el( components.PanelBody, { title: __( 'FAQ Settings', 'pearblog-theme' ) },
						el( components.TextControl, {
							label: __( 'Section Title', 'pearblog-theme' ),
							value: attrs.title,
							onChange: function ( val ) { props.setAttributes( { title: val } ); },
						} )
					)
				),
				el( 'div', blockProps,
					el( 'h2', { className: 'pb-faq-title' }, attrs.title ),
					items.map( function ( item, index ) {
						return el( 'div', { key: index, className: 'pb-faq-item-editor' },
							el( components.TextControl, {
								label: __( 'Question', 'pearblog-theme' ) + ' ' + ( index + 1 ),
								value: item.question || '',
								onChange: function ( val ) {
									var updated = items.slice();
									updated[ index ] = Object.assign( {}, updated[ index ], { question: val } );
									updateItems( updated );
								},
							} ),
							el( components.TextareaControl, {
								label: __( 'Answer', 'pearblog-theme' ),
								value: item.answer || '',
								onChange: function ( val ) {
									var updated = items.slice();
									updated[ index ] = Object.assign( {}, updated[ index ], { answer: val } );
									updateItems( updated );
								},
							} ),
							el( components.Button, {
								isDestructive: true,
								isSmall: true,
								onClick: function () {
									var updated = items.filter( function ( _, i ) { return i !== index; } );
									updateItems( updated );
								},
							}, __( 'Remove', 'pearblog-theme' ) )
						);
					} ),
					el( components.Button, {
						isPrimary: true,
						onClick: function () {
							updateItems( items.concat( [ { question: '', answer: '' } ] ) );
						},
					}, __( 'Add FAQ Item', 'pearblog-theme' ) )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// -----------------------------------------------------------------------
	// pearblog/cta
	// -----------------------------------------------------------------------
	blocks.registerBlockType( 'pearblog/cta', {
		title: __( 'PearBlog CTA', 'pearblog-theme' ),
		icon: 'megaphone',
		category: 'pearblog',
		attributes: {
			title:      { type: 'string', default: 'Ready to Get Started?' },
			subtitle:   { type: 'string', default: '' },
			buttonText: { type: 'string', default: 'Learn More' },
			buttonUrl:  { type: 'string', default: '' },
			style:      { type: 'string', default: 'gradient' },
			type:       { type: 'string', default: 'default' },
		},
		edit: function ( props ) {
			var attrs      = props.attributes;
			var blockProps = useBlockProps( { className: 'pb-block-cta-editor pb-cta-style-' + attrs.style } );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( components.PanelBody, { title: __( 'CTA Settings', 'pearblog-theme' ) },
						el( components.SelectControl, {
							label: __( 'Style', 'pearblog-theme' ),
							value: attrs.style,
							options: [
								{ label: __( 'Gradient', 'pearblog-theme' ), value: 'gradient' },
								{ label: __( 'Solid', 'pearblog-theme' ), value: 'solid' },
								{ label: __( 'Outline', 'pearblog-theme' ), value: 'outline' },
								{ label: __( 'Minimal', 'pearblog-theme' ), value: 'minimal' },
							],
							onChange: function ( val ) { props.setAttributes( { style: val } ); },
						} ),
						el( components.SelectControl, {
							label: __( 'Type', 'pearblog-theme' ),
							value: attrs.type,
							options: [
								{ label: __( 'Default', 'pearblog-theme' ), value: 'default' },
								{ label: __( 'Affiliate', 'pearblog-theme' ), value: 'affiliate' },
								{ label: __( 'Lead Capture', 'pearblog-theme' ), value: 'lead' },
							],
							onChange: function ( val ) { props.setAttributes( { type: val } ); },
						} ),
						el( components.TextControl, {
							label: __( 'Button URL', 'pearblog-theme' ),
							value: attrs.buttonUrl,
							onChange: function ( val ) { props.setAttributes( { buttonUrl: val } ); },
						} )
					)
				),
				el( 'div', blockProps,
					el( blockEditor.RichText, {
						tagName: 'h2',
						className: 'pb-cta-title',
						value: attrs.title,
						onChange: function ( val ) { props.setAttributes( { title: val } ); },
						placeholder: __( 'CTA Title…', 'pearblog-theme' ),
					} ),
					el( blockEditor.RichText, {
						tagName: 'p',
						className: 'pb-cta-subtitle',
						value: attrs.subtitle,
						onChange: function ( val ) { props.setAttributes( { subtitle: val } ); },
						placeholder: __( 'CTA Subtitle…', 'pearblog-theme' ),
					} ),
					el( blockEditor.RichText, {
						tagName: 'span',
						className: 'pb-cta-button pb-cta-button-primary',
						value: attrs.buttonText,
						onChange: function ( val ) { props.setAttributes( { buttonText: val } ); },
						placeholder: __( 'Button Text', 'pearblog-theme' ),
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// -----------------------------------------------------------------------
	// pearblog/related-posts
	// -----------------------------------------------------------------------
	blocks.registerBlockType( 'pearblog/related-posts', {
		title: __( 'PearBlog Related Posts', 'pearblog-theme' ),
		icon: 'admin-links',
		category: 'pearblog',
		attributes: {
			title: { type: 'string', default: 'Related Articles' },
			count: { type: 'number', default: 3 },
		},
		edit: function ( props ) {
			var attrs      = props.attributes;
			var blockProps = useBlockProps( { className: 'pb-block-related-editor' } );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( components.PanelBody, { title: __( 'Related Posts Settings', 'pearblog-theme' ) },
						el( components.TextControl, {
							label: __( 'Section Title', 'pearblog-theme' ),
							value: attrs.title,
							onChange: function ( val ) { props.setAttributes( { title: val } ); },
						} ),
						el( components.RangeControl, {
							label: __( 'Number of Posts', 'pearblog-theme' ),
							value: attrs.count,
							min: 1,
							max: 12,
							onChange: function ( val ) { props.setAttributes( { count: val } ); },
						} )
					)
				),
				el( 'div', blockProps,
					el( 'h2', { className: 'pb-related-title' }, attrs.title ),
					el( 'p', { className: 'pb-block-placeholder' },
						__( 'Related posts will appear here based on category.', 'pearblog-theme' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// -----------------------------------------------------------------------
	// pearblog/toc
	// -----------------------------------------------------------------------
	blocks.registerBlockType( 'pearblog/toc', {
		title: __( 'PearBlog Table of Contents', 'pearblog-theme' ),
		icon: 'list-view',
		category: 'pearblog',
		attributes: {
			title:  { type: 'string', default: 'Table of Contents' },
			sticky: { type: 'boolean', default: true },
		},
		edit: function ( props ) {
			var attrs      = props.attributes;
			var blockProps = useBlockProps( { className: 'pb-block-toc-editor' } );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( components.PanelBody, { title: __( 'TOC Settings', 'pearblog-theme' ) },
						el( components.TextControl, {
							label: __( 'Title', 'pearblog-theme' ),
							value: attrs.title,
							onChange: function ( val ) { props.setAttributes( { title: val } ); },
						} ),
						el( components.ToggleControl, {
							label: __( 'Sticky Sidebar', 'pearblog-theme' ),
							checked: attrs.sticky,
							onChange: function ( val ) { props.setAttributes( { sticky: val } ); },
						} )
					)
				),
				el( 'div', blockProps,
					el( 'h2', { className: 'pb-toc-title' }, attrs.title ),
					el( 'p', { className: 'pb-block-placeholder' },
						__( 'Table of contents will be auto-generated from headings.', 'pearblog-theme' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	// -----------------------------------------------------------------------
	// pearblog/ad-slot
	// -----------------------------------------------------------------------
	blocks.registerBlockType( 'pearblog/ad-slot', {
		title: __( 'PearBlog Ad Slot', 'pearblog-theme' ),
		icon: 'money-alt',
		category: 'pearblog',
		attributes: {
			position: { type: 'string', default: 'content' },
			label:    { type: 'string', default: 'Advertisement' },
		},
		edit: function ( props ) {
			var attrs      = props.attributes;
			var blockProps = useBlockProps( { className: 'pb-block-ad-editor' } );

			return el( Fragment, {},
				el( InspectorControls, {},
					el( components.PanelBody, { title: __( 'Ad Settings', 'pearblog-theme' ) },
						el( components.SelectControl, {
							label: __( 'Position', 'pearblog-theme' ),
							value: attrs.position,
							options: [
								{ label: __( 'Top', 'pearblog-theme' ), value: 'top' },
								{ label: __( 'Content', 'pearblog-theme' ), value: 'content' },
								{ label: __( 'Middle', 'pearblog-theme' ), value: 'middle' },
								{ label: __( 'Bottom', 'pearblog-theme' ), value: 'bottom' },
							],
							onChange: function ( val ) { props.setAttributes( { position: val } ); },
						} ),
						el( components.TextControl, {
							label: __( 'Label', 'pearblog-theme' ),
							value: attrs.label,
							onChange: function ( val ) { props.setAttributes( { label: val } ); },
						} )
					)
				),
				el( 'div', blockProps,
					el( 'div', { className: 'pb-block-ad-placeholder' },
						el( 'span', { className: 'pb-block-ad-label' }, attrs.label ),
						el( 'span', { className: 'pb-block-ad-position' }, attrs.position )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
