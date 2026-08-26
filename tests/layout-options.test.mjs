import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const packingSource = readFileSync(
	new URL( '../assets/js/cyber-gallery-mai-packing.js', import.meta.url ),
	'utf8'
);
const styles = readFileSync(
	new URL( '../assets/css/cyber-gallery-mai.css', import.meta.url ),
	'utf8'
);
const blockSource = readFileSync(
	new URL( '../blocks/cyber-gallery-mai/block.php', import.meta.url ),
	'utf8'
);
const gallerySource = readFileSync(
	new URL( '../classes/class-cyber-gallery-mai.php', import.meta.url ),
	'utf8'
);
const bridgeMarker = '\t// Internal bridge used by the editor bundle';
const instrumentedSource = packingSource.replace(
	bridgeMarker,
	[
		'\twindow.CyberGalleryMaiPackingTest = {',
		'\t\tchooseTiledShape,',
		'\t\tclearImageListeners,',
		'\t\twatchImages,',
		'\t};',
		'',
		bridgeMarker,
	].join( '\n' )
);

if ( instrumentedSource === packingSource ) {
	throw new Error( 'Could not expose packing helpers to the test context.' );
}

const document = {
	querySelectorAll() {
		return [];
	},
};
const window = {};
window.window = window;

vm.runInNewContext(
	instrumentedSource,
	{
		document,
		window,
	},
	{ filename: 'cyber-gallery-mai-packing.js' }
);

const {
	chooseTiledShape,
	clearImageListeners,
	watchImages,
} = window.CyberGalleryMaiPackingTest;

test( 'packing preserves its 1.x runtime compatibility alias', () => {
	assert.equal(
		window.MaiCyberGalleryPackingRuntime,
		window.CyberGalleryMaiPackingRuntime
	);
	assert.equal( typeof window.CyberGalleryMaiPackingRuntime.destroy, 'function' );
} );

/**
 * Build an incomplete image with inspectable event listeners.
 *
 * @return {Object} Image double.
 */
function pendingImage() {
	const listeners = new Map();

	return {
		complete: false,
		listeners,
		addEventListener( type, listener ) {
			listeners.set( type, listener );
		},
		removeEventListener( type, listener ) {
			if ( listeners.get( type ) === listener ) {
				listeners.delete( type );
			}
		},
	};
}

test( 'packing relayouts on image load or error and removes sibling listeners', () => {
	for ( const eventType of [ 'load', 'error' ] ) {
		const image = pendingImage();
		const state = {};
		let settlements = 0;

		watchImages(
			state,
			{ querySelectorAll: () => [ image ] },
			() => settlements++
		);
		assert.deepEqual( Array.from( image.listeners.keys() ), [ 'load', 'error' ] );

		image.listeners.get( eventType )();

		assert.equal( settlements, 1 );
		assert.equal( image.listeners.size, 0 );
		assert.equal( state.imageListeners.size, 0 );
	}
} );

test( 'packing teardown removes pending image listeners', () => {
	const image = pendingImage();
	const state = {};

	watchImages( state, { querySelectorAll: () => [ image ] }, () => {} );
	clearImageListeners( state );

	assert.equal( image.listeners.size, 0 );
	assert.equal( state.imageListeners.size, 0 );
} );

/**
 * Build uniform image-ratio entries for a packing test.
 *
 * @param {number} count Entry count.
 * @return {Array<{ratio: number}>}
 */
function entries( count ) {
	return Array.from( { length: count }, () => ( { ratio: 1 } ) );
}

test( 'Tiled pattern presets make calm rows simple and dense rows intricate', () => {
	const calm = chooseTiledShape( entries( 8 ), 4, 1200, 20, '', 'calm' );
	const dense = chooseTiledShape( entries( 8 ), 4, 1200, 20, '', 'dense' );

	assert.equal( calm.every( ( count ) => 1 === count ), true );
	assert.equal( dense.some( ( count ) => 1 < count ), true );
} );

test( 'Unknown Tiled presets retain the existing organic behavior', () => {
	const organic = chooseTiledShape( entries( 8 ), 4, 1200, 20, '', 'organic' );
	const unknown = chooseTiledShape( entries( 8 ), 4, 1200, 20, '', 'unknown' );

	assert.deepEqual( Array.from( unknown ), Array.from( organic ) );
} );

test( 'Editorial Mosaic uses semantic item classes instead of fixed five-item selectors', () => {
	assert.match( styles, /\.is-mosaic-feature-left/ );
	assert.match( styles, /\.is-mosaic-feature-right/ );
	assert.match( styles, /\.is-mosaic-odd-remainder/ );
	assert.match( styles, /\.has-mosaic-odd-center/ );
	assert.doesNotMatch( styles, /nth-child\(\s*5n/ );
	assert.doesNotMatch( styles, /nth-child\(\s*10n/ );
} );

test( 'Every layout receives independent four-sided block margins', () => {
	for ( const side of [ 'top', 'right', 'bottom', 'left' ] ) {
		assert.match(
			blockSource,
			new RegExp( `'margin_${ side }'\\s*=>\\s*get_field` )
		);
	}

	assert.match(
		gallerySource,
		/foreach \( array\( 'top', 'right', 'bottom', 'left' \) as \$side \)/
	);
} );

test( 'Layout-specific design options are registered in both block and renderer', () => {
	for ( const option of [
		'masonry_flow',
		'justified_last_row',
		'tiled_pattern',
		'mosaic_items_after_feature',
		'mosaic_odd_alignment',
		'mosaic_row_height',
	] ) {
		assert.match( blockSource, new RegExp( `'name'\\s*=>\\s*'${ option }'` ) );
		assert.match( gallerySource, new RegExp( `'${ option }'` ) );
	}
} );
