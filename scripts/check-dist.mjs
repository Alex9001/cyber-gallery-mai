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
const temporaryDirectory = mkdtempSync( join( tmpdir(), 'cyber-gallery-mai-' ) );
const executableSuffix = process.platform === 'win32' ? '.cmd' : '';

const assets = [
	{
		binary: `cleancss${ executableSuffix }`,
		source: 'assets/css/cyber-gallery-mai.css',
		distribution: 'assets/css/cyber-gallery-mai.min.css',
		args: ( source, output ) => [ '-o', output, source ],
	},
	{
		binary: `cleancss${ executableSuffix }`,
		source: 'assets/css/cyber-gallery-mai-editor.css',
		distribution: 'assets/css/cyber-gallery-mai-editor.min.css',
		args: ( source, output ) => [ '-o', output, source ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/cyber-gallery-mai.js',
		distribution: 'assets/js/cyber-gallery-mai.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/cyber-gallery-mai-packing.js',
		distribution: 'assets/js/cyber-gallery-mai-packing.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/cyber-gallery-mai-comparison.js',
		distribution: 'assets/js/cyber-gallery-mai-comparison.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/cyber-gallery-mai-lightbox.js',
		distribution: 'assets/js/cyber-gallery-mai-lightbox.min.js',
		args: ( source, output ) => [ source, '--compress', '--mangle', '--output', output ],
	},
	{
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/cyber-gallery-mai-editor.js',
		distribution: 'assets/js/cyber-gallery-mai-editor.min.js',
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
		readFileSync( new URL( 'blocks/cyber-gallery-mai/block.json', root ), 'utf8' )
	);
	if (
		'acf/cyber-gallery-mai' !== blockMetadata.name
		|| 'cyber-gallery-mai' !== blockMetadata.textdomain
		|| 'cyber-gallery-mai-editor' !== blockMetadata.editorScript
		|| 'cyber-gallery-mai-editor' !== blockMetadata.editorStyle
	) {
		throw new Error(
			'blocks/cyber-gallery-mai/block.json must use the canonical block identity and dedicated editor asset handles.'
		);
	}
	console.log( 'Verified blocks/cyber-gallery-mai/block.json' );

	const legacyBlockMetadata = JSON.parse(
		readFileSync( new URL( 'blocks/cyber-gallery-mai/legacy-block.json', root ), 'utf8' )
	);
	if (
		'acf/mai-cyber-gallery' !== legacyBlockMetadata.name
		|| 'cyber-gallery-mai' !== legacyBlockMetadata.textdomain
		|| 'cyber-gallery-mai-editor' !== legacyBlockMetadata.editorScript
		|| 'cyber-gallery-mai-editor' !== legacyBlockMetadata.editorStyle
		|| false !== legacyBlockMetadata.supports?.inserter
	) {
		throw new Error(
			'blocks/cyber-gallery-mai/legacy-block.json must retain the hidden 1.x block identity while using canonical assets.'
		);
	}
	console.log( 'Verified blocks/cyber-gallery-mai/legacy-block.json' );

	const translationTemplate = readFileSync(
		new URL( 'languages/cyber-gallery-mai.pot', root ),
		'utf8'
	);
	if (
		! translationTemplate.includes( 'Project-Id-Version: Cyber Gallery for Mai Theme 2.0.0' )
		|| ! translationTemplate.includes( 'X-Domain: cyber-gallery-mai' )
	) {
		throw new Error(
			'languages/cyber-gallery-mai.pot must match the canonical plugin identity and release version.'
		);
	}
	console.log( 'Verified languages/cyber-gallery-mai.pot' );

	const pluginSource = readFileSync(
		new URL( 'cyber-gallery-mai.php', root ),
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
	for ( const [ label, pattern ] of [
		[ 'WordPress minimum', /^[ \t*]*Requires at least:\s+7\.1\s*$/mi ],
		[ 'WordPress tested version', /^[ \t*]*Tested up to:\s+7\.1\s*$/mi ],
		[ 'PHP minimum', /^[ \t*]*Requires PHP:\s+8\.3\s*$/mi ],
		[ 'text domain', /^[ \t*]*Text Domain:\s+cyber-gallery-mai\s*$/mi ],
	] ) {
		if ( ! pattern.test( pluginSource ) ) {
			throw new Error( `cyber-gallery-mai.php has an incorrect ${ label } header.` );
		}
	}
	for ( const [ label, pattern ] of [
		[ 'WordPress minimum', /^Requires at least:\s+7\.1\s*$/mi ],
		[ 'WordPress tested version', /^Tested up to:\s+7\.1\s*$/mi ],
		[ 'PHP minimum', /^Requires PHP:\s+8\.3\s*$/mi ],
	] ) {
		if ( ! pattern.test( pluginReadme ) ) {
			throw new Error( `readme.txt has an incorrect ${ label } header.` );
		}
	}
	if (
		! pluginSource.includes(
			'Update URI:         https://github.com/Alex9001/cyber-gallery-mai'
		)
	) {
		throw new Error( 'cyber-gallery-mai.php must retain its repository-specific Update URI.' );
	}
	console.log( 'Verified Mai Engine dependency and update-channel headers' );

	const rendererSource = readFileSync(
		new URL( 'classes/class-cyber-gallery-mai.php', root ),
		'utf8'
	);
	const rootContextMatches = rendererSource.match(
		/'context'\s*=>\s*'mai-cyber-gallery'/g
	) ?? [];
	if (
		3 !== rootContextMatches.length
		|| ! /array\(\s*'class'\s*=>\s*'mai-cyber-gallery'\s*\)/.test( rendererSource )
	) {
		throw new Error(
			'The renderer must retain the public 1.x gallery class and Genesis root contexts.'
		);
	}
	console.log( 'Verified stable public markup and Genesis contexts' );

	const compatibilitySource = readFileSync(
		new URL( 'includes/compatibility.php', root ),
		'utf8'
	);
	for ( const legacyFunction of [
		'mai_cyber_gallery_add_logo_scale_attachment_field',
		'mai_cyber_gallery_render_linked_image_logo_scale_field',
		'mai_cyber_gallery_render_gallery_logo_scale_support',
		'mai_cyber_gallery_load_slider_navigation_secondary',
		'mai_cyber_gallery_load_logo_padding_axis',
		'mai_cyber_gallery_prepare_slider_navigation_field',
		'mai_cyber_gallery_register_block',
		'mai_cyber_gallery_render_block',
		'mai_cyber_gallery_prepare_columns_clone_field',
		'mai_cyber_gallery_get_validation_block_id',
		'mai_cyber_gallery_get_validation_context_value',
		'mai_cyber_gallery_is_required_field_active',
		'mai_cyber_gallery_validate_conditional_required_value',
		'mai_cyber_gallery_validate_slide_button_link',
		'mai_cyber_gallery_register_field_group',
	] ) {
		if ( ! compatibilitySource.includes( `function ${ legacyFunction }(` ) ) {
			throw new Error( `The 1.x compatibility function ${ legacyFunction } is missing.` );
		}
	}
	console.log( 'Verified the legacy PHP callback surface' );

	const frontendScript = readFileSync(
		new URL( 'assets/js/cyber-gallery-mai.js', root ),
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
				`assets/js/cyber-gallery-mai.js contains editor-only token "${ editorToken }".`
			);
		}
	}

	for ( const lightboxToken of [
		'CyberGalleryMaiLightboxSettings',
		'data-cyber-lightbox',
		'HTMLDialogElement',
	] ) {
		if ( frontendScript.includes( lightboxToken ) ) {
			throw new Error(
				`assets/js/cyber-gallery-mai.js contains lightbox-only token "${ lightboxToken }".`
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
				`assets/js/cyber-gallery-mai.js contains packing-only token "${ packingToken }".`
			);
		}
	}

	const packingScript = readFileSync(
		new URL( 'assets/js/cyber-gallery-mai-packing.js', root ),
		'utf8'
	);
	for ( const requiredToken of [
		'initializeMasonry',
		'initializeJustified',
		'initializeTiled',
		'CyberGalleryMaiPackingRuntime',
	] ) {
		if ( ! packingScript.includes( requiredToken ) ) {
			throw new Error(
				`assets/js/cyber-gallery-mai-packing.js is missing "${ requiredToken }".`
			);
		}
	}
	for ( const carouselToken of [ 'initializeFilmstrip', 'initializeSlider' ] ) {
		if ( packingScript.includes( carouselToken ) ) {
			throw new Error(
				`assets/js/cyber-gallery-mai-packing.js contains carousel-only token "${ carouselToken }".`
			);
		}
	}

	const lightboxScript = readFileSync(
		new URL( 'assets/js/cyber-gallery-mai-lightbox.js', root ),
		'utf8'
	);
	for ( const requiredToken of [
		'CyberGalleryMaiLightboxSettings',
		'data-cyber-lightbox',
		'HTMLDialogElement',
	] ) {
		if ( ! lightboxScript.includes( requiredToken ) ) {
			throw new Error(
				`assets/js/cyber-gallery-mai-lightbox.js is missing "${ requiredToken }".`
			);
		}
	}

	const comparisonScript = readFileSync(
		new URL( 'assets/js/cyber-gallery-mai-comparison.js', root ),
		'utf8'
	);
	for ( const requiredToken of [
		'CyberGalleryMaiComparisonRuntime',
		'data-cyber-comparison-range',
		'aria-valuetext',
	] ) {
		if ( ! comparisonScript.includes( requiredToken ) ) {
			throw new Error(
				`assets/js/cyber-gallery-mai-comparison.js is missing "${ requiredToken }".`
			);
		}
	}
	for ( const unrelatedToken of [ 'initializeFilmstrip', 'initializeSlider', 'HTMLDialogElement' ] ) {
		if ( comparisonScript.includes( unrelatedToken ) ) {
			throw new Error(
				`assets/js/cyber-gallery-mai-comparison.js contains unrelated token "${ unrelatedToken }".`
			);
		}
	}

	const frontendStyles = readFileSync(
		new URL( 'assets/css/cyber-gallery-mai.css', root ),
		'utf8'
	);
	for ( const editorToken of [ '.acf-', '.editor-styles-wrapper' ] ) {
		if ( frontendStyles.includes( editorToken ) ) {
			throw new Error(
				`assets/css/cyber-gallery-mai.css contains editor-only token "${ editorToken }".`
			);
		}
	}
	console.log( 'Verified frontend/editor asset boundaries' );
} finally {
	rmSync( temporaryDirectory, { force: true, recursive: true } );
}
