import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(
	new URL( '../assets/js/cyber-gallery-mai-comparison.js', import.meta.url ),
	'utf8'
);
const styles = readFileSync(
	new URL( '../assets/css/cyber-gallery-mai.css', import.meta.url ),
	'utf8'
);
const gallerySource = readFileSync(
	new URL( '../classes/class-cyber-gallery-mai.php', import.meta.url ),
	'utf8'
);
const document = {
	querySelectorAll() {
		return [];
	},
};
const window = {
	CyberGalleryMaiComparisonSettings: {
		valueText: '%1$s: %2$d%% visible',
	},
};
window.window = window;

vm.runInNewContext( source, { document, window }, {
	filename: 'cyber-gallery-mai-comparison.js',
} );

const runtime = window.CyberGalleryMaiComparisonRuntime;

test( 'comparison preserves its 1.x runtime compatibility aliases', () => {
	assert.equal(
		window.MaiCyberGalleryComparisonSettings,
		window.CyberGalleryMaiComparisonSettings
	);
	assert.equal(
		window.MaiCyberGalleryComparisonRuntime,
		window.CyberGalleryMaiComparisonRuntime
	);
} );

test( 'comparison positions clamp to the native range', () => {
	assert.equal( runtime.clampPosition( -20 ), 0 );
	assert.equal( runtime.clampPosition( 42 ), 42 );
	assert.equal( runtime.clampPosition( 120 ), 100 );
	assert.equal( runtime.clampPosition( 'bad' ), 50 );
} );

test( 'comparison value text is localized-template compatible', () => {
	assert.equal( runtime.formatValue( 'Original', 64 ), 'Original: 64% visible' );
	assert.equal(
		runtime.formatValue( "Before $& $` $' %2$d %%", 64 ),
		"Before $& $` $' %2$d %%: 64% visible"
	);
} );

test( 'comparison remains a static split without JavaScript and is RTL aware', () => {
	assert.match( styles, /--cyber-comparison-position:\s*50%/ );
	assert.match( styles, /\[dir="rtl"\] \.mai-cyber-gallery-comparison-layer\.is-before/ );
	assert.match( styles, /\.is-cyber-comparison-ready \.mai-cyber-gallery-comparison-range/ );
	assert.match( styles, /@media print[\s\S]*\.mai-cyber-gallery-comparison-range/ );
} );

test( 'comparison uses a strict dedicated source and accessible native range', () => {
	assert.match( gallerySource, /'comparison_before_image'/ );
	assert.match( gallerySource, /'comparison_after_image'/ );
	assert.match( gallerySource, /type="range" min="0" max="100"/ );
	assert.match( gallerySource, /data-cyber-comparison-range/ );
	assert.match(
		gallerySource,
		/data-cyber-comparison-label="%11\$s" hidden disabled/
	);
	assert.match( source, /input\.disabled = false/ );
	assert.match( source, /input\.hidden = false/ );
	assert.match( gallerySource, /get_comparison_items/ );
} );

test( 'landmark labels and Slider title elements are explicitly configurable', () => {
	assert.match( gallerySource, /'aria_label'\s*=>\s*''/ );
	assert.match( gallerySource, /sanitize_text_field\(\s*\(string\) \$args\['aria_label'\]/ );
	assert.match( gallerySource, /'slider_title_tag'\s*=>\s*'h2'/ );
	assert.match(
		gallerySource,
		/array\( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' \)/
	);
} );
