import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(
	new URL( '../assets/js/cyber-gallery-mai-editor.js', import.meta.url ),
	'utf8'
);
const targetBlock = 'acf/cyber-gallery-mai';
const legacyBlock = 'acf/mai-cyber-gallery';

/**
 * Load the editor source with small WordPress, ACF, and DOM test doubles.
 *
 * @return {{ actions: Map, createCalls: Object[], document: Object, filter: Function, listeners: Map, window: Object }} Captured editor API.
 */
function loadEditorTransform() {
	let filter = null;
	const createCalls = [];
	const actions = new Map();
	const listeners = new Map();
	const document = {
		addEventListener( name, callback ) {
			listeners.set( name, callback );
		},
		querySelectorAll() {
			return [];
		},
	};
	const window = {
		acf: {
			addAction( name, callback ) {
				actions.set( name, callback );
			},
			enable() {},
			getFields() {
				return [];
			},
		},
		document,
		wp: {
			blocks: {
				createBlock( name, attributes, innerBlocks = [] ) {
					const block = { name, attributes, innerBlocks };
					createCalls.push( block );
					return block;
				},
			},
			data: {},
			hooks: {
				addFilter( hook, namespace, callback ) {
					assert.equal( hook, 'blocks.registerBlockType' );
					assert.equal(
						namespace,
						'cyber-gallery-mai/gallery-transforms'
					);
					filter = callback;
				},
			},
		},
	};
	window.window = window;

	vm.runInNewContext(
		source,
		{
			document,
			window,
		},
		{ filename: 'cyber-gallery-mai-editor.js' }
	);

	assert.equal( typeof filter, 'function' );

	return { actions, createCalls, document, filter, listeners, window };
}

/**
 * Make cross-realm values compare as ordinary Node objects.
 *
 * @param {*} value Value created in the VM context.
 * @return {*} Plain JSON value.
 */
function plain( value ) {
	return JSON.parse( JSON.stringify( value ) );
}

test( 'adds Core and Mai Gallery transforms without replacing existing transforms', () => {
	const { filter } = loadEditorTransform();
	const existing = { type: 'block', blocks: [ 'core/image' ] };
	const settings = {
		transforms: {
			from: [ existing ],
			to: [ { type: 'block', blocks: [ 'core/group' ] } ],
		},
	};
	const unrelated = filter( settings, 'core/paragraph' );
	const result = filter( settings, targetBlock );

	assert.equal( unrelated, settings );
	assert.equal( result.transforms.from.length, 4 );
	assert.equal( result.transforms.from[0], existing );
	assert.equal( result.transforms.to, settings.transforms.to );
	assert.equal( result.transforms.from[1].type, 'block' );
	assert.deepEqual( plain( result.transforms.from[1].blocks ), [ legacyBlock ] );
	assert.equal( result.transforms.from[2].type, 'block' );
	assert.deepEqual(
		plain( result.transforms.from[2].blocks ),
		[ 'core/gallery' ]
	);
	assert.equal( result.transforms.from[3].type, 'block' );
	assert.deepEqual(
		plain( result.transforms.from[3].blocks ),
		[ 'acf/mai-gallery' ]
	);
} );

test( 'converts the 1.x block name without changing saved ACF field data', () => {
	const { filter } = loadEditorTransform();
	const transform = filter( {}, targetBlock ).transforms.from.find( ( item ) =>
		plain( item.blocks ).includes( legacyBlock )
	);
	const data = {
		layout: 'comparison',
		_layout: 'mai_cyber_gallery_layout',
		comparison_before_image: '42',
		_comparison_before_image: 'mai_cyber_gallery_comparison_before_image',
	};
	const innerBlocks = [ { name: 'core/paragraph', attributes: { content: 'Kept' } } ];
	const block = plain( transform.transform( {
		name: legacyBlock,
		mode: 'preview',
		align: 'wide',
		data,
	}, innerBlocks ) );

	assert.equal( block.name, targetBlock );
	assert.equal( block.attributes.name, targetBlock );
	assert.equal( block.attributes.align, 'wide' );
	assert.deepEqual( block.attributes.data, data );
	assert.deepEqual( block.innerBlocks, innerBlocks );
} );

test( 'preserves hidden Mai column values inside the Cyber clone wrapper', () => {
	const { actions, window } = loadEditorTransform();
	const enableCalls = [];
	const propertyWrites = [];
	const wrapper = {};
	const fieldElement = {
		closest( selector ) {
			return '.acf-field[data-key="mai_cyber_gallery_columns_clone"]' === selector
				? wrapper
				: null;
		},
	};
	const fieldCollection = {
		0: fieldElement,
		hasClass() {
			return false;
		},
	};
	const field = {
		$el: fieldCollection,
		conditions: { cid: 'columns-layout-condition' },
		get( name ) {
			return 'key' === name ? 'mai_columns' : '';
		},
		prop( name, value ) {
			if ( 1 === arguments.length ) {
				return 'hidden' === name;
			}
			propertyWrites.push( [ name, value ] );
			return value;
		},
	};

	window.acf.enable = function ( element, conditionId ) {
		enableCalls.push( [ element, conditionId ] );
	};

	actions.get( 'hide_field' )( field, 'conditional_logic' );

	assert.deepEqual( enableCalls, [
		[ fieldCollection, 'columns-layout-condition' ],
	] );
	assert.deepEqual( propertyWrites, [ [ 'disabled', false ] ] );
} );

test( 'leaves duplicate navigation selected when a filtered list has no None choice', () => {
	const { listeners } = loadEditorTransform();
	const option = {
		dataset: {},
		disabled: false,
		selected: true,
		value: 'counter',
	};
	const primary = {
		options: [ { ...option, dataset: {} } ],
		querySelectorAll() {
			return [];
		},
		value: 'counter',
	};
	const secondary = {
		closest() {
			return fields;
		},
		matches( selector ) {
			return selector.includes( 'slider_navigation' );
		},
		options: [ { ...option, dataset: {} } ],
		querySelectorAll() {
			return [];
		},
		value: 'counter',
	};
	const primaryField = {
		closest() {
			return fields;
		},
		querySelector() {
			return primary;
		},
	};
	const secondaryField = {
		querySelector() {
			return secondary;
		},
	};
	const fields = {
		querySelector() {
			return secondaryField;
		},
		querySelectorAll() {
			return [ primaryField ];
		},
	};

	listeners.get( 'change' )( { target: secondary } );

	assert.equal( primary.value, 'counter' );
	assert.equal( secondary.value, 'counter' );
	assert.equal( primary.options[0].disabled, false );
	assert.equal( secondary.options[0].disabled, false );
} );

test( 'converts modern Core Gallery image blocks in source order', () => {
	const { filter } = loadEditorTransform();
	const transform = filter( {}, targetBlock ).transforms.from.find( ( item ) =>
		plain( item.blocks ).includes( 'core/gallery' )
	);
	const attributes = {
		align: 'wide',
		anchor: 'portfolio',
		aspectRatio: '1/1',
		className: 'featured-gallery',
		columns: 5,
		linkTo: 'media',
		sizeSlug: 'medium_large',
	};
	const innerBlocks = [
		{ name: 'core/image', attributes: { id: 14 } },
		{ name: 'core/html', attributes: {} },
		{ name: 'core/image', attributes: { id: '7' } },
		{ name: 'core/image', attributes: { id: 0 } },
		{ name: 'core/image', attributes: { id: 14 } },
	];

	assert.equal( transform.isMatch( attributes, innerBlocks ), true );

	const block = plain( transform.transform( attributes, innerBlocks ) );

	assert.equal( block.name, targetBlock );
	assert.equal( block.attributes.name, targetBlock );
	assert.equal( block.attributes.mode, 'preview' );
	assert.equal( block.attributes.align, 'wide' );
	assert.equal( block.attributes.anchor, 'portfolio' );
	assert.equal( block.attributes.className, 'featured-gallery' );
	assert.deepEqual( block.attributes.data, {
		images: [ '14', '7', '14' ],
		_images: 'mai_cyber_gallery_images',
		layout: 'grid',
		_layout: 'mai_cyber_gallery_layout',
		columns: '5',
		_columns: 'mai_columns',
		image_size: 'medium_large',
		_image_size: 'mai_cyber_gallery_image_size',
		image_orientation: 'square',
		_image_orientation: 'mai_cyber_gallery_image_orientation',
		click_action: 'media',
		_click_action: 'mai_cyber_gallery_click_action',
		captions: 'below',
		_captions: 'mai_cyber_gallery_captions',
	} );
} );

test( 'supports legacy ids and images while applying safe defaults', () => {
	const { filter } = loadEditorTransform();
	const transform = filter( {}, targetBlock ).transforms.from.find( ( item ) =>
		plain( item.blocks ).includes( 'core/gallery' )
	);
	const fromIds = plain(
		transform.transform(
			{
				aspectRatio: '3/4',
				columns: 99,
				ids: [ 9, '4', null, -2 ],
				linkTo: 'post',
				sizeSlug: 'Large<script>',
			},
			[]
		)
	);
	const fromImages = plain(
		transform.transform(
			{
				imageCrop: false,
				images: [ { id: '12' }, {}, { id: 3 } ],
			},
			[]
		)
	);

	assert.deepEqual( fromIds.attributes.data.images, [ '9', '4' ] );
	assert.equal( fromIds.attributes.data.columns, '8' );
	assert.equal( fromIds.attributes.data.image_orientation, 'portrait' );
	assert.equal( fromIds.attributes.data.click_action, 'attachment' );
	assert.equal( fromIds.attributes.data.image_size, 'largescript' );

	assert.deepEqual( fromImages.attributes.data.images, [ '12', '3' ] );
	assert.equal( fromImages.attributes.data.columns, '2' );
	assert.equal( fromImages.attributes.data.image_orientation, 'custom' );
	assert.equal( fromImages.attributes.data.click_action, 'none' );
} );

test( 'maps Core image lightboxes and rejects galleries without attachment IDs', () => {
	const { filter } = loadEditorTransform();
	const transform = filter( {}, targetBlock ).transforms.from.find( ( item ) =>
		plain( item.blocks ).includes( 'core/gallery' )
	);
	const innerBlocks = [
		{
			name: 'core/image',
			attributes: {
				id: 21,
				lightbox: { enabled: true },
			},
		},
	];

	assert.equal(
		plain( transform.transform( {}, innerBlocks ) ).attributes.data.click_action,
		'lightbox'
	);
	assert.equal(
		transform.isMatch(
			{ ids: [], images: [ { id: 0 } ] },
			[ { name: 'core/image', attributes: { url: 'https://example.com/image.jpg' } } ]
		),
		false
	);
} );

test( 'converts serialized Mai Gallery data while preserving compatible settings', () => {
	const { filter } = loadEditorTransform();
	const transforms = filter( {}, targetBlock ).transforms.from;
	const transform = transforms.find( ( item ) =>
		plain( item.blocks ).includes( 'acf/mai-gallery' )
	);
	const attributes = {
		name: 'acf/mai-gallery',
		data: {
			image_orientation: 'landscape',
			_image_orientation: 'mai_gallery_image_orientation',
			links: '0',
			_links: 'mai_gallery_links',
			shadow: '1',
			_shadow: 'mai_gallery_shadow',
			lightbox: '1',
			_lightbox: 'mai_gallery_lightbox',
			images: [ '4248', '3965', '3967' ],
			_images: 'mai_gallery_images',
			columns: '5',
			_columns: 'mai_columns',
			columns_responsive: '0',
			_columns_responsive: 'mai_columns_responsive',
			align_columns: 'center',
			_align_columns: 'mai_align_columns',
			align_columns_vertical: 'full',
			_align_columns_vertical: 'mai_align_columns_vertical',
			column_gap: 'xl',
			_column_gap: 'mai_column_gap',
			row_gap: 'xl',
			_row_gap: 'mai_row_gap',
			margin_top: '',
			_margin_top: 'mai_margin_top',
			margin_bottom: '',
			_margin_bottom: 'mai_margin_bottom',
			columns_clone: '',
			_columns_clone: 'mai_gallery_columns_clone',
		},
		align: 'wide',
		anchor: 'client-gallery',
		className: 'legacy-client-gallery',
		mode: 'preview',
	};

	assert.equal( transform.isMatch( attributes ), true );

	const block = plain( transform.transform( attributes ) );

	assert.equal( block.name, targetBlock );
	assert.equal( block.attributes.name, targetBlock );
	assert.equal( block.attributes.mode, 'preview' );
	assert.equal( block.attributes.align, 'wide' );
	assert.equal( block.attributes.anchor, 'client-gallery' );
	assert.equal( block.attributes.className, 'legacy-client-gallery' );
	assert.deepEqual( block.attributes.data, {
		layout: 'grid',
		_layout: 'mai_cyber_gallery_layout',
		click_action: 'lightbox',
		_click_action: 'mai_cyber_gallery_click_action',
		images: [ '4248', '3965', '3967' ],
		_images: 'mai_cyber_gallery_images',
		image_orientation: 'landscape',
		_image_orientation: 'mai_cyber_gallery_image_orientation',
		image_size: 'large',
		_image_size: 'mai_cyber_gallery_image_size',
		image_radius: 'rounded',
		_image_radius: 'mai_cyber_gallery_image_radius',
		captions: 'below',
		_captions: 'mai_cyber_gallery_captions',
		shadow: '1',
		_shadow: 'mai_cyber_gallery_shadow',
		hover: 'zoom',
		_hover: 'mai_cyber_gallery_hover',
		columns: '5',
		_columns: 'mai_columns',
		columns_responsive: '0',
		_columns_responsive: 'mai_columns_responsive',
		columns_md: '1',
		_columns_md: 'mai_columns_md',
		columns_sm: '1',
		_columns_sm: 'mai_columns_sm',
		columns_xs: '1',
		_columns_xs: 'mai_columns_xs',
		align_columns: 'center',
		_align_columns: 'mai_align_columns',
		align_columns_vertical: 'full',
		_align_columns_vertical: 'mai_align_columns_vertical',
		column_gap: 'xl',
		_column_gap: 'mai_column_gap',
		row_gap: 'xl',
		_row_gap: 'mai_row_gap',
		margin_top: '',
		_margin_top: 'mai_margin_top',
		margin_bottom: '',
		_margin_bottom: 'mai_margin_bottom',
	} );
} );

test( 'converts Mai Gallery custom-link repeaters and rejects empty active sources', () => {
	const { filter } = loadEditorTransform();
	const transforms = filter( {}, targetBlock ).transforms.from;
	const transform = transforms.find( ( item ) =>
		plain( item.blocks ).includes( 'acf/mai-gallery' )
	);
	const linkedAttributes = {
		data: {
			links: '1',
			lightbox: '1',
			image_orientation: 'custom',
			images: [ '999' ],
			images_links: '3',
			_images_links: 'mai_gallery_images_links',
			images_links_0_image: '21',
			_images_links_0_image: 'mai_gallery_image',
			images_links_0_url: 'https://example.com/one',
			_images_links_0_url: 'mai_gallery_url',
			images_links_1_image: '0',
			images_links_1_url: 'https://example.com/invalid',
			images_links_2_image: '14',
			images_links_2_url: '',
			columns: 'auto',
			columns_responsive: '1',
			columns_md: '4',
			columns_sm: '2',
			columns_xs: '1',
			align_columns: 'unsafe',
			align_columns_vertical: 'middle',
			column_gap: 'xxxxl',
			row_gap: 'bad',
			margin_top: 'md',
			margin_bottom: 'xxl',
		},
		align: 'left',
	};

	assert.equal( transform.isMatch( linkedAttributes ), true );

	const block = plain( transform.transform( linkedAttributes ) );
	const data = block.attributes.data;

	assert.equal( data.click_action, 'custom' );
	assert.equal( data.images, undefined );
	assert.equal( data.image_orientation, 'custom' );
	assert.equal( data.image_size, 'landscape-md' );
	assert.equal( data.images_links, '2' );
	assert.equal( data._images_links, 'mai_cyber_gallery_images_links' );
	assert.equal( data.images_links_0_image, '21' );
	assert.equal(
		data._images_links_0_image,
		'mai_cyber_gallery_image'
	);
	assert.deepEqual( data.images_links_0_link, {
		url: 'https://example.com/one',
		title: '',
		target: '',
	} );
	assert.equal( data._images_links_0_link, 'mai_cyber_gallery_link' );
	assert.equal( data.images_links_1_image, '14' );
	assert.deepEqual( data.images_links_1_link, {
		url: '',
		title: '',
		target: '',
	} );
	assert.equal( data.columns, '3' );
	assert.equal( data.columns_responsive, '1' );
	assert.equal( data.columns_md, '4' );
	assert.equal( data.columns_sm, '2' );
	assert.equal( data.columns_xs, '1' );
	assert.equal( data.align_columns, '' );
	assert.equal( data.align_columns_vertical, 'middle' );
	assert.equal( data.column_gap, 'xxxxl' );
	assert.equal( data.row_gap, 'xl' );
	assert.equal( data.margin_top, 'md' );
	assert.equal( data.margin_bottom, 'xxl' );
	assert.equal( block.attributes.align, undefined );

	assert.equal(
		transform.isMatch( {
			data: {
				links: '1',
				images: [ '999' ],
				images_links: '1',
				images_links_0_image: '0',
			},
		} ),
		false
	);
	assert.equal(
		transform.isMatch( {
			data: {
				links: '0',
				images: [ 0, null, -2 ],
			},
		} ),
		false
	);
} );

test( 'opens ACF expanded editing from an empty preview action', () => {
	const { actions, document, window } = loadEditorTransform();
	const status = { hidden: true };
	const block = {
		getAttribute( attribute ) {
			return 'data-block' === attribute ? 'cyber-client-id' : '';
		},
	};
	let clickHandler = null;
	let launcherClicks = 0;
	let selectedClientId = '';
	let openedSidebar = '';
	const launcher = {
		click() {
			launcherClicks++;
		},
		disabled: false,
		getClientRects() {
			return [ {} ];
		},
	};
	const button = {
		addEventListener( event, callback ) {
			assert.equal( event, 'click' );
			clickHandler = callback;
		},
		closest( selector ) {
			return '[data-block]' === selector ? block : null;
		},
		parentElement: {
			querySelector( selector ) {
				return '[data-cyber-manage-gallery-status]' === selector ? status : null;
			},
		},
	};
	const preview = {
		querySelectorAll( selector ) {
			return '[data-cyber-manage-gallery]' === selector ? [ button ] : [];
		},
	};

	document.querySelectorAll = function ( selector ) {
		return '.acf-blocks-open-expanded-editor-btn' === selector ? [ launcher ] : [];
	};
	window.setTimeout = function ( callback ) {
		callback();
	};
	window.wp.data.select = function ( store ) {
		if ( 'core/block-editor' === store ) {
			return {
				getSelectedBlockClientId() {
					return selectedClientId;
				},
			};
		}
		if ( 'core/edit-post' === store ) {
			return {};
		}
		return null;
	};
	window.wp.data.dispatch = function ( store ) {
		if ( 'core/block-editor' === store ) {
			return {
				selectBlock( clientId ) {
					selectedClientId = clientId;
				},
			};
		}
		if ( 'core/edit-post' === store ) {
			return {
				openGeneralSidebar( sidebar ) {
					openedSidebar = sidebar;
				},
			};
		}
		return null;
	};

	const canonicalPreview = actions.get( 'render_block_preview/type=cyber-gallery-mai' );
	const legacyPreview = actions.get( 'render_block_preview/type=mai-cyber-gallery' );

	assert.equal( canonicalPreview, legacyPreview );
	canonicalPreview( preview );
	assert.equal( typeof clickHandler, 'function' );

	clickHandler();

	assert.equal( selectedClientId, 'cyber-client-id' );
	assert.equal( openedSidebar, 'edit-post/block' );
	assert.equal( launcherClicks, 1 );
	assert.equal( status.hidden, true );
} );
