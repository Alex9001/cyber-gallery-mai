import { execFileSync } from 'node:child_process';
import {
	mkdtempSync,
	readFileSync,
	rmSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { basename, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = new URL( '../', import.meta.url );
const temporaryDirectory = mkdtempSync( join( tmpdir(), 'mai-cyber-gallery-' ) );
const executableSuffix = process.platform === 'win32' ? '.cmd' : '';

const assets = [
	{
		binary: `cleancss${ executableSuffix }`,
		source: 'assets/css/mai-cyber-gallery.css',
		distribution: 'assets/css/mai-cyber-gallery.min.css',
		args: ( source, output ) => [ '-o', output, source ],
	},
	{
		binary: `cleancss${ executableSuffix }`,
		source: 'assets/css/mai-cyber-gallery-editor.css',
		distribution: 'assets/css/mai-cyber-gallery-editor.min.css',
		args: ( source, output ) => [ '-o', output, source ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/mai-cyber-gallery.js',
		distribution: 'assets/js/mai-cyber-gallery.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/mai-cyber-gallery-packing.js',
		distribution: 'assets/js/mai-cyber-gallery-packing.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/mai-cyber-gallery-comparison.js',
		distribution: 'assets/js/mai-cyber-gallery-comparison.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/mai-cyber-gallery-lightbox.js',
		distribution: 'assets/js/mai-cyber-gallery-lightbox.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/mai-cyber-gallery-editor.js',
		distribution: 'assets/js/mai-cyber-gallery-editor.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
];

try {
	for ( const asset of assets ) {
		const source = fileURLToPath( new URL( asset.source, root ) );
		const distribution = new URL( asset.distribution, root );
		const output = join( temporaryDirectory, basename( asset.distribution ) );
		const binary = fileURLToPath( new URL( `node_modules/.bin/${ asset.binary }`, root ) );

		execFileSync( binary, asset.args( source, output ), { stdio: 'inherit' } );

		if ( ! readFileSync( distribution ).equals( readFileSync( output ) ) ) {
			throw new Error( `${ asset.distribution } is not reproducible; run npm run build.` );
		}

		console.log( `Verified ${ asset.distribution }` );
	}

	const blockMetadata = JSON.parse(
		readFileSync( new URL( 'blocks/mai-cyber-gallery/block.json', root ), 'utf8' )
	);
	if (
		'mai-cyber-gallery-editor' !== blockMetadata.editorScript
		|| 'mai-cyber-gallery-editor' !== blockMetadata.editorStyle
	) {
		throw new Error(
			'blocks/mai-cyber-gallery/block.json must use the dedicated editor asset handles.'
		);
	}
	console.log( 'Verified blocks/mai-cyber-gallery/block.json' );

	const pluginSource = readFileSync(
		new URL( 'mai-cyber-gallery.php', root ),
		'utf8'
	);
	const pluginReadme = readFileSync(
		new URL( 'readme.txt', root ),
		'utf8'
	);
	if (
		! /^[ \t*]*Requires Plugins:\s+mai-engine\s*$/mi.test( pluginSource )
		|| ! /^Requires Plugins:\s+mai-engine\s*$/mi.test( pluginReadme )
	) {
		throw new Error( 'Plugin headers must retain the installed Mai Engine dependency slug.' );
	}
	if (
		! pluginSource.includes(
			'Update URI:         https://github.com/Alex9001/mai-cyber-gallery'
		)
	) {
		throw new Error( 'mai-cyber-gallery.php must retain its repository-specific Update URI.' );
	}
	console.log( 'Verified Mai Engine dependency and update-channel headers' );

	const frontendScript = readFileSync(
		new URL( 'assets/js/mai-cyber-gallery.js', root ),
		'utf8'
	);
	for ( const editorToken of [
		'window.acf',
		'initializeAcf',
		'syncSliderNavigationSelects',
		'initializeLogoScaleEditors',
	] ) {
		if ( frontendScript.includes( editorToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery.js contains editor-only token "${ editorToken }".`
			);
		}
	}

	for ( const lightboxToken of [
		'MaiCyberGalleryLightboxSettings',
		'data-cyber-lightbox',
		'HTMLDialogElement',
	] ) {
		if ( frontendScript.includes( lightboxToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery.js contains lightbox-only token "${ lightboxToken }".`
			);
		}
	}

	for ( const packingToken of [
		'initializeMasonry',
		'initializeJustified',
		'initializeTiled',
	] ) {
		if ( frontendScript.includes( packingToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery.js contains packing-only token "${ packingToken }".`
			);
		}
	}

	const packingScript = readFileSync(
		new URL( 'assets/js/mai-cyber-gallery-packing.js', root ),
		'utf8'
	);
	for ( const requiredToken of [
		'initializeMasonry',
		'initializeJustified',
		'initializeTiled',
		'MaiCyberGalleryPackingRuntime',
	] ) {
		if ( ! packingScript.includes( requiredToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery-packing.js is missing "${ requiredToken }".`
			);
		}
	}
	for ( const carouselToken of [ 'initializeFilmstrip', 'initializeSlider' ] ) {
		if ( packingScript.includes( carouselToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery-packing.js contains carousel-only token "${ carouselToken }".`
			);
		}
	}

	const lightboxScript = readFileSync(
		new URL( 'assets/js/mai-cyber-gallery-lightbox.js', root ),
		'utf8'
	);
	for ( const requiredToken of [
		'MaiCyberGalleryLightboxSettings',
		'data-cyber-lightbox',
		'HTMLDialogElement',
	] ) {
		if ( ! lightboxScript.includes( requiredToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery-lightbox.js is missing "${ requiredToken }".`
			);
		}
	}

	const comparisonScript = readFileSync(
		new URL( 'assets/js/mai-cyber-gallery-comparison.js', root ),
		'utf8'
	);
	for ( const requiredToken of [
		'MaiCyberGalleryComparisonRuntime',
		'data-cyber-comparison-range',
		'aria-valuetext',
	] ) {
		if ( ! comparisonScript.includes( requiredToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery-comparison.js is missing "${ requiredToken }".`
			);
		}
	}
	for ( const unrelatedToken of [ 'initializeFilmstrip', 'initializeSlider', 'HTMLDialogElement' ] ) {
		if ( comparisonScript.includes( unrelatedToken ) ) {
			throw new Error(
				`assets/js/mai-cyber-gallery-comparison.js contains unrelated token "${ unrelatedToken }".`
			);
		}
	}

	const frontendStyles = readFileSync(
		new URL( 'assets/css/mai-cyber-gallery.css', root ),
		'utf8'
	);
	for ( const editorToken of [ '.acf-', '.editor-styles-wrapper' ] ) {
		if ( frontendStyles.includes( editorToken ) ) {
			throw new Error(
				`assets/css/mai-cyber-gallery.css contains editor-only token "${ editorToken }".`
			);
		}
	}
	console.log( 'Verified frontend/editor asset boundaries' );
} finally {
	rmSync( temporaryDirectory, { force: true, recursive: true } );
}
