import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import {
	cpSync,
	mkdirSync,
	readFileSync,
	readdirSync,
	rmSync,
	statSync,
	utimesSync,
} from 'node:fs';
import {
	basename,
	dirname,
	join,
	relative,
	resolve,
	sep,
} from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../', import.meta.url ) );
const pluginSlug = 'cyber-gallery-mai';
const buildDirectory = join( root, 'build' );
const stageDirectory = join( buildDirectory, pluginSlug );
const packageJson = JSON.parse(
	readFileSync( join( root, 'package.json' ), 'utf8' )
);
const version = String( packageJson.version || '' );
const zipName = `${ pluginSlug }-${ version }.zip`;
const zipPath = join( buildDirectory, zipName );
const releaseFiles = [
	'cyber-gallery-mai.php',
	'readme.txt',
	'README.md',
	'LICENSE',
	'NOTICE',
	'assets',
	'blocks',
	'classes',
	'includes',
	'languages',
];
const requiredArchiveFiles = [
	`${ pluginSlug }/cyber-gallery-mai.php`,
	`${ pluginSlug }/readme.txt`,
	`${ pluginSlug }/LICENSE`,
	`${ pluginSlug }/NOTICE`,
	`${ pluginSlug }/blocks/cyber-gallery-mai/block.php`,
	`${ pluginSlug }/blocks/cyber-gallery-mai/block.json`,
	`${ pluginSlug }/blocks/cyber-gallery-mai/legacy-block.json`,
	`${ pluginSlug }/classes/class-cyber-gallery-mai-plugin.php`,
	`${ pluginSlug }/classes/class-cyber-gallery-mai.php`,
	`${ pluginSlug }/includes/class-cyber-gallery-mai-cli-command.php`,
	`${ pluginSlug }/includes/compatibility.php`,
	`${ pluginSlug }/includes/functions.php`,
	`${ pluginSlug }/languages/cyber-gallery-mai.pot`,
	`${ pluginSlug }/assets/css/cyber-gallery-mai.min.css`,
	`${ pluginSlug }/assets/css/cyber-gallery-mai-editor.min.css`,
	`${ pluginSlug }/assets/js/cyber-gallery-mai.min.js`,
	`${ pluginSlug }/assets/js/cyber-gallery-mai-packing.min.js`,
	`${ pluginSlug }/assets/js/cyber-gallery-mai-comparison.min.js`,
	`${ pluginSlug }/assets/js/cyber-gallery-mai-lightbox.min.js`,
	`${ pluginSlug }/assets/js/cyber-gallery-mai-editor.min.js`,
];
const forbiddenSegments = new Set( [
	'.git',
	'build',
	'node_modules',
	'scripts',
	'stubs',
	'tests',
	'vendor',
] );

if ( ! /^\d+\.\d+\.\d+$/.test( version ) ) {
	throw new Error( `package.json has invalid release version "${ version }".` );
}

if (
	'build' !== basename( buildDirectory )
	|| resolve( dirname( buildDirectory ) ) !== resolve( root )
) {
	throw new Error( `Refusing to clean unexpected build path: ${ buildDirectory }` );
}

/**
 * Run a release command in the plugin root.
 *
 * @param {string}   command Executable.
 * @param {string[]} args    Arguments.
 * @return {void}
 */
function run( command, args ) {
	execFileSync( command, args, {
		cwd: root,
		stdio: 'inherit',
	} );
}

/**
 * Get command output from the repository.
 *
 * @param {string}   command Executable.
 * @param {string[]} args    Arguments.
 * @return {string} Trimmed output.
 */
function output( command, args ) {
	return execFileSync( command, args, {
		cwd: root,
		encoding: 'utf8',
	} ).trim();
}

/**
 * Recursively collect files in stable lexical order.
 *
 * @param {string} directory Absolute directory.
 * @return {string[]} Absolute file paths.
 */
function collectFiles( directory ) {
	const files = [];

	readdirSync( directory, { withFileTypes: true } )
		.sort( ( left, right ) => left.name.localeCompare( right.name ) )
		.forEach( function ( entry ) {
			const path = join( directory, entry.name );

			if ( entry.isDirectory() ) {
				files.push( ...collectFiles( path ) );
			} else if ( entry.isFile() ) {
				files.push( path );
			}
		} );

	return files;
}

/**
 * Normalize staged timestamps for stable ZIP metadata.
 *
 * @param {string} path Absolute staged path.
 * @param {Date}   date Timestamp.
 * @return {void}
 */
function normalizeTimestamps( path, date ) {
	if ( statSync( path ).isDirectory() ) {
		readdirSync( path ).forEach( function ( child ) {
			normalizeTimestamps( join( path, child ), date );
		} );
	}

	utimesSync( path, date, date );
}

console.log( `Building ${ pluginSlug } ${ version } release...` );
run( 'npm', [ 'run', 'build' ] );
run( 'npm', [ 'run', 'check' ] );
run( 'composer', [ 'test' ] );

const sourceRevision = output( 'git', [ 'rev-parse', '--verify', 'HEAD' ] );
const sourceStatus = output( 'git', [
	'status',
	'--porcelain',
	'--untracked-files=all',
] );
const allowDirtyBuild = '1' === process.env.CYBER_GALLERY_MAI_ALLOW_DIRTY_BUILD;

if ( sourceStatus && ! allowDirtyBuild ) {
	throw new Error(
		'Refusing to create a release from uncommitted source. '
		+ 'Commit all source and generated assets first. '
		+ 'For local staging only, set CYBER_GALLERY_MAI_ALLOW_DIRTY_BUILD=1.'
	);
}

if ( sourceStatus ) {
	console.warn( 'Warning: creating a local-only artifact from uncommitted source.' );
}

rmSync( buildDirectory, { force: true, recursive: true } );
mkdirSync( stageDirectory, { recursive: true } );

releaseFiles.forEach( function ( source ) {
	const sourcePath = join( root, source );
	const destinationPath = join( stageDirectory, source );

	cpSync( sourcePath, destinationPath, { recursive: true } );
} );

const sourceDateEpoch = Number.parseInt(
	process.env.SOURCE_DATE_EPOCH || '946684800',
	10
);
const archiveDate = new Date(
	( Number.isFinite( sourceDateEpoch ) ? sourceDateEpoch : 946684800 ) * 1000
);

normalizeTimestamps( stageDirectory, archiveDate );

const stagedFiles = collectFiles( stageDirectory );
const archiveFiles = stagedFiles.map( function ( path ) {
	return relative( buildDirectory, path ).split( sep ).join( '/' );
} );

archiveFiles.forEach( function ( path ) {
	const segments = path.split( '/' );

	if (
		segments[0] !== pluginSlug
		|| segments.some( ( segment ) => forbiddenSegments.has( segment ) )
		|| segments.some( ( segment ) => segment.startsWith( '.' ) )
	) {
		throw new Error( `Refusing to package unexpected path: ${ path }` );
	}
} );

requiredArchiveFiles.forEach( function ( path ) {
	if ( ! archiveFiles.includes( path ) ) {
		throw new Error( `Release is missing required file: ${ path }` );
	}
} );

execFileSync(
	'zip',
	[ '-X', '-q', zipPath, ...archiveFiles ],
	{
		cwd: buildDirectory,
		env: {
			...process.env,
			TZ: 'UTC',
		},
	}
);

const archivedFiles = execFileSync( 'unzip', [ '-Z1', zipPath ], {
	encoding: 'utf8',
} )
	.trim()
	.split( '\n' )
	.filter( Boolean );

if (
	archivedFiles.length !== archiveFiles.length
	|| archivedFiles.some( ( path, index ) => path !== archiveFiles[ index ] )
) {
	throw new Error( 'ZIP contents do not match the staged release manifest.' );
}

const zipBuffer = readFileSync( zipPath );
const checksum = createHash( 'sha256' ).update( zipBuffer ).digest( 'hex' );

console.log( `Staged ${ archiveFiles.length } files in ${ stageDirectory }` );
console.log( `Created ${ zipPath } (${ zipBuffer.length } bytes)` );
console.log( `Source revision ${ sourceRevision }${ sourceStatus ? ' (dirty local staging)' : '' }` );
console.log( `SHA-256 ${ checksum }` );
