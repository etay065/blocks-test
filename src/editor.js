/**
 * JS for the Gutenberg editor logic and view
 * */

import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InspectorControls,
	InnerBlocks,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

function uid( prefix ) {
	return prefix + '-' + Math.random().toString( 36 ).slice( 2, 8 );
}

const ALLOWED_BLOCKS = [ 'blocks-test/posts-pagination' ];
const TEMPLATE       = [ [ 'blocks-test/posts-pagination' ] ];

registerBlockType( 'blocks-test/posts-grid', {
	title:    __( 'Blocks Test Posts Grid', 'blocks-test' ),
	category: 'widgets',
	icon:     'grid-view',
	supports: { html: false, reusable: false },
	attributes: {
		columns:      { type: 'integer', default: 3 },
		postsPerPage: { type: 'integer', default: 6 },
		blockId:      { type: 'string',  default: '' },
	},

	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'bt-posts-grid-editor-wrapper' } );
		if ( ! attributes.blockId ) {
			setAttributes( { blockId: uid( 'bt-grid' ) } );
		}
		return (
			<div { ...blockProps }>
				<InspectorControls>
					<PanelBody title={ __( 'Grid Settings', 'blocks-test' ) } initialOpen={ true }>
						<SelectControl
							label={ __( 'Columns', 'blocks-test' ) }
							value={ attributes.columns }
							options={ [
								{ label: '2', value: 2 },
								{ label: '3', value: 3 },
								{ label: '4', value: 4 },
							] }
							onChange={ ( val ) => setAttributes( { columns: parseInt( val, 10 ) } ) }
						/>
						<RangeControl
							label={ __( 'Posts per page', 'blocks-test' ) }
							value={ attributes.postsPerPage }
							min={ 2 } max={ 24 } step={ 1 }
							onChange={ ( val ) => setAttributes( { postsPerPage: val } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender block="blocks-test/posts-grid" attributes={ attributes } />
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					templateLock="all"
				/>
			</div>
		);
	},

	save() {
		return <InnerBlocks.Content />;
	},

	deprecated: [
		{
			attributes: {
				columns:      { type: 'integer', default: 3 },
				postsPerPage: { type: 'integer', default: 6 },
				blockId:      { type: 'string',  default: '' },
			},
			save( { attributes } ) {
				const blockId = attributes.blockId || 'bt-grid-default';
				const cols    = attributes.columns || 3;
				return (
					<div
						className="bt-posts-grid-wrapper"
						id={ blockId }
						data-block-id={ blockId }
						data-columns={ cols }
						data-posts-per-page={ attributes.postsPerPage || 6 }
					>
						<div className={ `bt-posts-grid bt-posts-grid--cols-${ cols }` } />
						<InnerBlocks.Content />
					</div>
				);
			},
		},
	],
} );

registerBlockType( 'blocks-test/posts-filter', {
	title:    __( 'Blocks Test Posts Filter', 'blocks-test' ),
	category: 'widgets',
	icon:     'filter',
	supports: { html: false, reusable: false },
	attributes: {
		blockId: { type: 'string', default: '' },
	},

	edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'bt-posts-filter-editor-wrapper' } );
		if ( ! attributes.blockId ) {
			setAttributes( { blockId: uid( 'bt-filter' ) } );
		}
		return (
			<div { ...blockProps }>
				<ServerSideRender block="blocks-test/posts-filter" attributes={ attributes } />
			</div>
		);
	},

	save() { return null; },
} );

registerBlockType( 'blocks-test/posts-pagination', {
	title:    __( 'Blocks Test Posts Pagination', 'blocks-test' ),
	category: 'widgets',
	icon:     'controls-forward',
	parent:   [ 'blocks-test/posts-grid' ],
	supports: { html: false, reusable: false, inserter: false },
	attributes: {
		blockId: { type: 'string', default: '' },
	},

	edit() {
		const blockProps = useBlockProps( { className: 'bt-pagination bt-pagination--editor' } );
		return (
			<nav { ...blockProps }>
				<button className="bt-pagination__btn" disabled>
					← { __( 'Previous', 'blocks-test' ) }
				</button>
				<span className="bt-pagination__info">
                    { __( 'Page 1 of …', 'blocks-test' ) }
                </span>
				<button className="bt-pagination__btn">
					{ __( 'Next', 'blocks-test' ) } →
				</button>
			</nav>
		);
	},

	save() { return null; },
} );