import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(
	new URL( '../assets/js/mai-cyber-gallery.js', import.meta.url ),
	'utf8'
);
const styles = readFileSync(
	new URL( '../assets/css/mai-cyber-gallery.css', import.meta.url ),
	'utf8'
);
const blockSource = readFileSync(
	new URL( '../blocks/mai-cyber-gallery/block.php', import.meta.url ),
	'utf8'
);
const gallerySource = readFileSync(
	new URL( '../classes/class-gallery.php', import.meta.url ),
	'utf8'
);
const blockMetadata = JSON.parse(
	readFileSync(
		new URL( '../blocks/mai-cyber-gallery/block.json', import.meta.url ),
		'utf8'
	)
);
const bridgeMarker = '\t// Internal bridge used by the editor bundle';
const instrumentedSource = source.replace(
	bridgeMarker,
	[
		'\twindow.MaiCyberGalleryTest = {',
		'\t\tgetFilmstripMoveTargetIndex,',
		'\t\trenderFilmstripMotion,',
		'\t};',
		'',
		bridgeMarker,
	].join( '\n' )
);

if ( instrumentedSource === source ) {
	throw new Error( 'Could not expose Filmstrip helpers to the test context.' );
}

const document = {
	querySelectorAll() {
		return [];
	},
};
const window = {
	cancelAnimationFrame() {},
	getComputedStyle() {
		return { direction: 'ltr' };
	},
	requestAnimationFrame() {
		return 1;
	},
};
window.window = window;

vm.runInNewContext(
	instrumentedSource,
	{
		document,
		window,
	},
	{ filename: 'mai-cyber-gallery.js' }
);

const {
	getFilmstripMoveTargetIndex,
	renderFilmstripMotion,
} = window.MaiCyberGalleryTest;

test( 'Filmstrip autoplay advances a fixed item count instead of a visible group', () => {
	assert.equal( getFilmstripMoveTargetIndex( 8, [ 0, 1, 2 ], 1, true, 1 ), 1 );
	assert.equal( getFilmstripMoveTargetIndex( 8, [ 1, 2, 3 ], 1, true, 3 ), 4 );
	assert.equal( getFilmstripMoveTargetIndex( 8, [ 0, 1, 2 ], 1, true, 0 ), 3 );
	assert.equal( getFilmstripMoveTargetIndex( 8, [ 5, 6, 7 ], 1, true, 2 ), 0 );
} );

test( 'Filmstrip drag presentation keeps edge stretch and tilt restrained', () => {
	const properties = new Map();
	const state = {
		motionPosition: null,
		motionQuery: { matches: false },
		track: {
			clientWidth: 500,
			scrollLeft: 0,
			scrollWidth: 1000,
			style: {
				setProperty( property, value ) {
					properties.set( property, value );
				},
			},
		},
	};

	renderFilmstripMotion( state, -500, 10 );

	assert.equal( state.track.scrollLeft, 0 );
	assert.equal( properties.get( '--cyber-filmstrip-rubber-band' ), '16.000px' );
	assert.equal( properties.get( '--cyber-filmstrip-motion-tilt' ), '-0.450deg' );
} );

test( 'Filmstrip logos use intrinsic dimensions constrained by the explicit rail height', () => {
	const selector = '.mai-cyber-gallery-layout-filmstrip.has-filmstrip-image-logo-contain .mai-cyber-gallery-image {';
	const start = styles.lastIndexOf( selector );
	const end = styles.indexOf( '\n}', start );
	const rule = styles.slice( start, end );

	assert.notEqual( start, -1 );
	assert.match( rule, /\bwidth: auto;/ );
	assert.match( rule, /\bheight: auto;/ );
	assert.match(
		rule,
		/max-height: max\([\s\S]*var\(--cyber-filmstrip-current-height\)/
	);
	assert.doesNotMatch( rule, /\bmax-height: 100%;/ );
} );

test( 'Filmstrip arrows are opt-in and the sidebar action names the expanded editor', () => {
	assert.equal(
		blockMetadata.acf.expandedEditorButtonText,
		'Open Expanded Editor'
	);
	assert.match(
		blockSource,
		/array_key_exists\( 'filmstrip_arrows', \$block_data \)[\s\S]*?: false;/
	);
	assert.match(
		blockSource,
		/'name'\s*=> 'filmstrip_arrows',[\s\S]*?'default_value'\s*=> 0,/
	);
	assert.match(
		gallerySource,
		/'filmstrip_arrows'\s*=> false,/
	);
} );

test( 'Filmstrip logo padding supports separate axes with a legacy fallback', () => {
	assert.match( blockSource, /filmstrip_logo_padding_horizontal/ );
	assert.match( blockSource, /filmstrip_logo_padding_vertical/ );
	assert.match( blockSource, /mai_cyber_gallery_load_logo_padding_axis/ );
	assert.match( styles, /--cyber-filmstrip-logo-padding-horizontal/ );
	assert.match( styles, /--cyber-filmstrip-logo-padding-vertical/ );
	assert.match(
		gallerySource,
		/--cyber-filmstrip-logo-padding-horizontal/
	);
	assert.match(
		gallerySource,
		/--cyber-filmstrip-logo-padding-vertical/
	);
} );
