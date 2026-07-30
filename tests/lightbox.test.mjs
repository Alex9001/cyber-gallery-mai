import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(
	new URL( '../assets/js/mai-cyber-gallery-lightbox.js', import.meta.url ),
	'utf8'
);
const instrumented = source.replace(
	/\n\}\(\) \);\s*$/,
	'\nwindow.MaiCyberGalleryLightboxTestRuntime = { formatStatus: formatStatus };$&'
);

if ( instrumented === source ) {
	throw new Error( 'Could not expose the private lightbox formatter for testing.' );
}

const document = {
	addEventListener() {},
};
const window = {
	MaiCyberGalleryLightboxSettings: {
		status: '%1$s, %2$s',
	},
};
window.window = window;

vm.runInNewContext( instrumented, { document, window }, {
	filename: 'mai-cyber-gallery-lightbox.js',
} );

test( 'lightbox status preserves dollar sequences in user captions', () => {
	const runtime = window.MaiCyberGalleryLightboxTestRuntime;

	assert.equal(
		runtime.formatStatus( "Cost $5 and token $& $` $' %2$s", '2 of 4' ),
		"Cost $5 and token $& $` $' %2$s, 2 of 4"
	);
	assert.equal( runtime.formatStatus( '', '2 of 4' ), '2 of 4' );
} );
