import { readFileSync } from 'node:fs';

const root = new URL( '../', import.meta.url );

/**
 * Read a UTF-8 project file.
 *
 * @param {string} file Relative path.
 * @return {string} File contents.
 */
function read( file ) {
	return readFileSync( new URL( file, root ), 'utf8' );
}

/**
 * Require one captured version from a project file.
 *
 * @param {string} file    Relative path.
 * @param {RegExp} pattern Version pattern.
 * @param {string} label   Human-readable source.
 * @return {string} Captured version.
 */
function capture( file, pattern, label ) {
	const match = read( file ).match( pattern );

	if ( ! match ) {
		throw new Error( `${ file }: could not find ${ label }.` );
	}

	return match[1];
}

const packageJson = JSON.parse( read( 'package.json' ) );
const packageLock = JSON.parse( read( 'package-lock.json' ) );
const version = packageJson.version;
const releasePattern = /^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$/;

if ( ! releasePattern.test( version ) ) {
	throw new Error( `package.json: version "${ version }" is not a valid release semver.` );
}

const readmeVersions = Array.from(
	read( 'README.md' ).matchAll( /^Version (\d+\.\d+\.\d+)\b/gm )
);
const changeVersions = Array.from(
	read( 'CHANGES.md' ).matchAll( /^## (\d+\.\d+\.\d+)\s*$/gm )
);

if ( ! readmeVersions.length ) {
	throw new Error( 'README.md: could not find a Version x.y.z release entry.' );
}

if ( ! changeVersions.length ) {
	throw new Error( 'CHANGES.md: could not find a release heading.' );
}

const checks = [
	[
		'mai-cyber-gallery.php plugin header',
		capture(
			'mai-cyber-gallery.php',
			/^\s*\*\s+Version:\s+(\d+\.\d+\.\d+)\s*$/m,
			'plugin header version'
		),
	],
	[
		'class-mai-cyber-gallery-plugin.php MAI_CYBER_GALLERY_VERSION',
		capture(
			'classes/class-mai-cyber-gallery-plugin.php',
			/MAI_CYBER_GALLERY_VERSION',\s*'(\d+\.\d+\.\d+)'/,
			'MAI_CYBER_GALLERY_VERSION'
		),
	],
	[ 'package-lock.json top-level version', packageLock.version ],
	[ 'package-lock.json root package version', packageLock.packages?.['']?.version ],
	[
		'readme.txt Stable tag',
		capture( 'readme.txt', /^Stable tag:\s*(\d+\.\d+\.\d+)\s*$/m, 'Stable tag' ),
	],
	[
		'readme.txt first Upgrade Notice',
		capture(
			'readme.txt',
			/^== Upgrade Notice ==\s*\n+\s*= (\d+\.\d+\.\d+) =/m,
			'first Upgrade Notice version'
		),
	],
	[
		'readme.txt first Changelog',
		capture(
			'readme.txt',
			/^== Changelog ==\s*\n+\s*= (\d+\.\d+\.\d+) =/m,
			'first Changelog version'
		),
	],
	[ 'CHANGES.md first release', changeVersions[0][1] ],
	[ 'README.md latest Version entry', readmeVersions.at( -1 )[1] ],
];

for ( const [ label, actual ] of checks ) {
	if ( actual !== version ) {
		throw new Error(
			`${ label }: expected ${ version } from package.json, found ${ String( actual ) }.`
		);
	}
}

console.log( `Verified release version ${ version } across project metadata and documentation.` );
