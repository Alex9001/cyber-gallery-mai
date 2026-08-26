import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import {
	chmodSync,
	copyFileSync,
	lstatSync,
	mkdirSync,
	readFileSync,
	readdirSync,
	readlinkSync,
	rmSync,
	utimesSync,
	writeFileSync,
} from 'node:fs';
import {
	basename,
	dirname,
	isAbsolute,
	join,
	relative,
	resolve,
	sep,
} from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../', import.meta.url ) );
const pluginName = 'Cyber Gallery for Mai Theme';
const pluginSlug = 'cyber-gallery-mai';
const embeddedProvenanceFile = 'release-provenance.json';
const buildDirectory = join( root, 'build' );
const stageDirectory = join( buildDirectory, pluginSlug );
const packageJson = JSON.parse(
	readFileSync( join( root, 'package.json' ), 'utf8' )
);
const releaseManifestSource = readFileSync(
	join( root, 'scripts/release-manifest.json' ),
	'utf8'
);
const releaseManifest = JSON.parse( releaseManifestSource );
const version = String( packageJson.version || '' );
const distributionDirectories = [
	'assets',
	'blocks',
	'classes',
	'includes',
	'languages',
];
const topLevelReleaseFiles = [
	'LICENSE',
	'NOTICE',
	'README.md',
	'cyber-gallery-mai.php',
	'readme.txt',
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

/**
 * Sort paths without depending on the process locale.
 *
 * @param {string[]} paths Paths to sort.
 * @return {string[]} A sorted copy.
 */
function sortPaths( paths ) {
	return [ ...paths ].sort( function ( left, right ) {
		return left < right ? -1 : left > right ? 1 : 0;
	} );
}

/**
 * Calculate a SHA-256 digest.
 *
 * @param {string|Buffer} value Digest input.
 * @return {string} Lowercase hexadecimal digest.
 */
export function sha256( value ) {
	return createHash( 'sha256' ).update( value ).digest( 'hex' );
}

/**
 * Serialize deterministic, human-readable JSON.
 *
 * @param {object} value JSON value with keys in canonical construction order.
 * @return {string} Serialized JSON with a final newline.
 */
export function serializeJson( value ) {
	return `${ JSON.stringify( value, null, '\t' ) }\n`;
}

/**
 * Create a canonical or unmistakably noncanonical artifact basename.
 *
 * @param {string} releaseSlug      Plugin slug.
 * @param {string} releaseVersion   Plugin version.
 * @param {string} sourceRevision   Full Git revision.
 * @param {string} dirtyFingerprint Empty for a clean tree.
 * @return {string} Artifact basename without extension.
 */
export function createArtifactBase(
	releaseSlug,
	releaseVersion,
	sourceRevision,
	dirtyFingerprint = ''
) {
	const canonical = `${ releaseSlug }-${ releaseVersion }`;

	if ( ! dirtyFingerprint ) {
		return canonical;
	}

	return `${ canonical }-dirty-${ sourceRevision.slice( 0, 12 ) }-${ dirtyFingerprint }`;
}

/**
 * Create the provenance embedded in the plugin archive.
 *
 * The archive digest is intentionally absent because embedding it would create
 * a checksum cycle. The external provenance sidecar binds this exact embedded
 * document to the finished ZIP digest.
 *
 * @param {object} values Provenance values.
 * @return {object} Canonically ordered provenance object.
 */
export function createEmbeddedProvenance( values ) {
	return {
		schemaVersion: 1,
		artifact: values.artifact,
		plugin: {
			name: values.pluginName,
			slug: values.pluginSlug,
			version: values.version,
		},
		source: {
			revision: values.sourceRevision,
			tree: values.sourceTree,
			dirty: Boolean( values.dirtyFingerprint ),
			dirtyFingerprint: values.dirtyFingerprint || null,
			sourceDateEpoch: values.sourceDateEpoch,
			manifestSha256: values.manifestSha256,
		},
	};
}

/**
 * Bind embedded provenance to the completed archive.
 *
 * @param {object} embeddedProvenance Embedded document.
 * @param {number} archiveBytes       ZIP byte length.
 * @param {string} archiveSha256      ZIP digest.
 * @return {object} External provenance document.
 */
export function createExternalProvenance(
	embeddedProvenance,
	archiveBytes,
	archiveSha256
) {
	return {
		schemaVersion: 1,
		artifact: {
			file: embeddedProvenance.artifact,
			bytes: archiveBytes,
			sha256: archiveSha256,
		},
		embeddedProvenanceSha256: sha256(
			serializeJson( embeddedProvenance )
		),
		plugin: embeddedProvenance.plugin,
		source: embeddedProvenance.source,
	};
}

/**
 * Create a conventional checksum sidecar.
 *
 * @param {string} archiveName   Archive filename, without directories.
 * @param {string} archiveSha256 ZIP digest.
 * @return {string} sha256sum-compatible sidecar contents.
 */
export function createChecksumSidecar( archiveName, archiveSha256 ) {
	return `${ archiveSha256 }  ${ archiveName }\n`;
}

/**
 * Validate one manifest path.
 *
 * @param {string} path Manifest path.
 * @return {void}
 */
function validateManifestPath( path ) {
	const segments = path.split( '/' );

	if (
		! path
		|| isAbsolute( path )
		|| path.includes( '\\' )
		|| segments.some( ( segment ) => ! segment || '.' === segment || '..' === segment )
		|| segments.some( ( segment ) => segment.startsWith( '.' ) )
		|| segments.some( ( segment ) => forbiddenSegments.has( segment ) )
	) {
		throw new Error( `Release manifest contains an unsafe path: ${ path }` );
	}
}

/**
 * Validate manifest structure, ordering, and generated-file policy.
 *
 * @param {object} manifest Parsed manifest.
 * @return {void}
 */
export function validateManifestDefinition( manifest ) {
	if (
		1 !== manifest.schemaVersion
		|| ! Array.isArray( manifest.sourceFiles )
		|| ! Array.isArray( manifest.generatedFiles )
	) {
		throw new Error( 'Release manifest schema is invalid.' );
	}

	for ( const collection of [ manifest.sourceFiles, manifest.generatedFiles ] ) {
		collection.forEach( validateManifestPath );

		if ( new Set( collection ).size !== collection.length ) {
			throw new Error( 'Release manifest paths must be unique.' );
		}

		if ( JSON.stringify( collection ) !== JSON.stringify( sortPaths( collection ) ) ) {
			throw new Error( 'Release manifest paths must use stable lexical order.' );
		}
	}

	if (
		1 !== manifest.generatedFiles.length
		|| embeddedProvenanceFile !== manifest.generatedFiles[ 0 ]
	) {
		throw new Error(
			`The only generated archive member must be ${ embeddedProvenanceFile }.`
		);
	}

	const overlap = manifest.sourceFiles.filter( ( path ) =>
		manifest.generatedFiles.includes( path )
	);
	if ( overlap.length ) {
		throw new Error( `Source and generated manifest paths overlap: ${ overlap.join( ', ' ) }` );
	}
}

/**
 * Get command output from the repository.
 *
 * @param {string}   command Executable.
 * @param {string[]} args    Arguments.
 * @param {boolean}  trim    Whether to trim output.
 * @return {string} Command output.
 */
function output( command, args, trim = true ) {
	const result = execFileSync( command, args, {
		cwd: root,
		encoding: 'utf8',
	} );

	return trim ? result.trim() : result;
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
 * Find all directories implied by manifest files.
 *
 * @param {string[]} files Manifest files.
 * @return {Set<string>} Expected directory paths.
 */
function getExpectedDirectories( files ) {
	const directories = new Set();

	files.forEach( function ( file ) {
		let directory = dirname( file ).split( sep ).join( '/' );

		while ( '.' !== directory && ! directories.has( directory ) ) {
			directories.add( directory );
			directory = dirname( directory ).split( sep ).join( '/' );
		}
	} );

	return directories;
}

/**
 * Reject unexpected and non-regular paths in distribution directories.
 *
 * @param {string}   repositoryRoot Absolute repository root.
 * @param {string[]} expectedFiles  Expected runtime source paths.
 * @param {string[]} directories    Distribution directories to inspect.
 * @return {void}
 */
export function inspectDistributionTree(
	repositoryRoot,
	expectedFiles,
	directories
) {
	const expectedFileSet = new Set( expectedFiles );
	const expectedDirectories = getExpectedDirectories( expectedFiles );

	function inspect( relativePath ) {
		const absolutePath = join( repositoryRoot, relativePath );
		const metadata = lstatSync( absolutePath );

		if ( metadata.isSymbolicLink() ) {
			throw new Error( `Release distribution contains a symlink: ${ relativePath }` );
		}

		if ( metadata.isDirectory() ) {
			if ( ! expectedDirectories.has( relativePath ) ) {
				throw new Error(
					`Release distribution contains an untracked, ignored, or unexpected directory: ${ relativePath }`
				);
			}

			readdirSync( absolutePath )
				.sort()
				.forEach( ( child ) => inspect( `${ relativePath }/${ child }` ) );
			return;
		}

		if ( ! metadata.isFile() ) {
			throw new Error( `Release distribution contains a special path: ${ relativePath }` );
		}

		if ( ! expectedFileSet.has( relativePath ) ) {
			throw new Error(
				`Release distribution contains an untracked, ignored, or unexpected file: ${ relativePath }`
			);
		}
	}

	directories.forEach( inspect );

	expectedFiles.forEach( function ( path ) {
		const metadata = lstatSync( join( repositoryRoot, path ) );

		if ( metadata.isSymbolicLink() || ! metadata.isFile() ) {
			throw new Error( `Release source is not a regular file: ${ path }` );
		}
	} );
}

/**
 * Verify that the manifest is exactly the tracked runtime file set.
 *
 * @return {void}
 */
export function validateReleaseSources() {
	validateManifestDefinition( releaseManifest );
	inspectDistributionTree(
		root,
		releaseManifest.sourceFiles,
		distributionDirectories
	);

	const releaseScope = [ ...topLevelReleaseFiles, ...distributionDirectories ];
	const trackedFiles = output(
		'git',
		[ 'ls-files', '-z', '--', ...releaseScope ],
		false
	)
		.split( '\0' )
		.filter( Boolean );
	const expectedFiles = releaseManifest.sourceFiles;
	const missingFromManifest = trackedFiles.filter(
		( path ) => ! expectedFiles.includes( path )
	);
	const notTracked = expectedFiles.filter( ( path ) => ! trackedFiles.includes( path ) );

	if ( missingFromManifest.length || notTracked.length ) {
		throw new Error(
			'Release manifest does not exactly match tracked runtime files.'
			+ ( missingFromManifest.length
				? ` Missing from manifest: ${ missingFromManifest.join( ', ' ) }.`
				: '' )
			+ ( notTracked.length
				? ` Not tracked: ${ notTracked.join( ', ' ) }.`
				: '' )
		);
	}
}

/**
 * Fingerprint every reported source change, including untracked file bytes.
 *
 * @param {string} sourceStatus NUL-delimited porcelain status.
 * @return {string} SHA-256 dirty-tree fingerprint.
 */
function createDirtyFingerprint( sourceStatus ) {
	const hash = createHash( 'sha256' );
	hash.update( sourceStatus );
	hash.update( output( 'git', [ 'diff', '--binary', 'HEAD', '--' ], false ) );

	const untrackedFiles = sortPaths(
		output(
			'git',
			[ 'ls-files', '--others', '--exclude-standard', '-z' ],
			false
		)
			.split( '\0' )
			.filter( Boolean )
	);

	untrackedFiles.forEach( function ( path ) {
		const absolutePath = join( root, path );
		const metadata = lstatSync( absolutePath );
		hash.update( `\0${ path }\0` );

		if ( metadata.isSymbolicLink() ) {
			hash.update( readlinkSync( absolutePath ) );
		} else if ( metadata.isFile() ) {
			hash.update( readFileSync( absolutePath ) );
		}
	} );

	return hash.digest( 'hex' );
}

/**
 * Recursively collect regular files in stable lexical order.
 *
 * @param {string} directory Absolute directory.
 * @return {string[]} Absolute file paths.
 */
function collectFiles( directory ) {
	const files = [];

	readdirSync( directory, { withFileTypes: true } )
		.sort( ( left, right ) => left.name < right.name ? -1 : left.name > right.name ? 1 : 0 )
		.forEach( function ( entry ) {
			const path = join( directory, entry.name );

			if ( entry.isDirectory() ) {
				files.push( ...collectFiles( path ) );
			} else if ( entry.isFile() ) {
				files.push( path );
			} else {
				throw new Error( `Staged release contains a non-regular path: ${ path }` );
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
	if ( lstatSync( path ).isDirectory() ) {
		readdirSync( path ).forEach( function ( child ) {
			normalizeTimestamps( join( path, child ), date );
		} );
	}

	utimesSync( path, date, date );
}

/**
 * Build the release and its verification sidecars.
 *
 * @return {void}
 */
function main() {
	if ( ! /^\d+\.\d+\.\d+$/.test( version ) ) {
		throw new Error( `package.json has invalid release version "${ version }".` );
	}

	if (
		'build' !== basename( buildDirectory )
		|| resolve( dirname( buildDirectory ) ) !== resolve( root )
	) {
		throw new Error( `Refusing to clean unexpected build path: ${ buildDirectory }` );
	}

	validateReleaseSources();

	const allowDirtyBuild = '1' === process.env.CYBER_GALLERY_MAI_ALLOW_DIRTY_BUILD;
	const initialSourceStatus = output(
		'git',
		[ 'status', '--porcelain=v1', '-z', '--untracked-files=all' ],
		false
	);

	if ( initialSourceStatus && ! allowDirtyBuild ) {
		throw new Error(
			'Refusing to create a release from uncommitted source. '
			+ 'Commit all source and generated assets first. '
			+ 'For local staging only, set CYBER_GALLERY_MAI_ALLOW_DIRTY_BUILD=1.'
		);
	}

	// Start from an empty output tree so ignored artifacts from a prior build
	// cannot affect validation or be mistaken for current release output.
	rmSync( buildDirectory, { force: true, recursive: true } );

	console.log( `Building ${ pluginSlug } ${ version } release...` );
	run( 'npm', [ 'run', 'build' ] );
	run( 'npm', [ 'run', 'check' ] );
	run( 'composer', [ 'test' ] );
	validateReleaseSources();

	const sourceRevision = output( 'git', [ 'rev-parse', '--verify', 'HEAD' ] );
	const sourceTree = output( 'git', [ 'rev-parse', '--verify', 'HEAD^{tree}' ] );
	const sourceStatus = output(
		'git',
		[ 'status', '--porcelain=v1', '-z', '--untracked-files=all' ],
		false
	);

	if ( sourceStatus && ! allowDirtyBuild ) {
		throw new Error(
			'Release checks changed tracked source or generated assets. '
			+ 'Commit the reproducible output before packaging.'
		);
	}

	const dirtyFingerprint = sourceStatus
		? createDirtyFingerprint( sourceStatus )
		: '';
	const artifactBase = createArtifactBase(
		pluginSlug,
		version,
		sourceRevision,
		dirtyFingerprint
	);
	const zipName = `${ artifactBase }.zip`;
	const zipPath = join( buildDirectory, zipName );
	const checksumPath = `${ zipPath }.sha256`;
	const provenancePath = `${ zipPath }.provenance.json`;
	const epochSource = process.env.SOURCE_DATE_EPOCH
		|| output( 'git', [ 'show', '-s', '--format=%ct', 'HEAD' ] );
	const sourceDateEpoch = Number.parseInt( epochSource, 10 );

	if (
		! /^\d+$/.test( epochSource )
		|| ! Number.isSafeInteger( sourceDateEpoch )
		|| sourceDateEpoch < 315532800
		|| sourceDateEpoch > 4354819199
	) {
		throw new Error( `SOURCE_DATE_EPOCH is invalid for ZIP metadata: ${ epochSource }` );
	}

	const archiveDate = new Date( sourceDateEpoch * 1000 );
	const embeddedProvenance = createEmbeddedProvenance( {
		artifact: zipName,
		pluginName,
		pluginSlug,
		version,
		sourceRevision,
		sourceTree,
		dirtyFingerprint,
		sourceDateEpoch,
		manifestSha256: sha256( releaseManifestSource ),
	} );

	if ( sourceStatus ) {
		console.warn(
			`Warning: creating noncanonical local artifact ${ zipName } from uncommitted source.`
		);
	}

	mkdirSync( stageDirectory, { recursive: true } );

	releaseManifest.sourceFiles.forEach( function ( source ) {
		const destinationPath = join( stageDirectory, source );
		mkdirSync( dirname( destinationPath ), { recursive: true } );
		copyFileSync( join( root, source ), destinationPath );
		chmodSync( destinationPath, 0o644 );
	} );

	const embeddedProvenancePath = join(
		stageDirectory,
		embeddedProvenanceFile
	);
	writeFileSync(
		embeddedProvenancePath,
		serializeJson( embeddedProvenance ),
		{ mode: 0o644 }
	);
	normalizeTimestamps( stageDirectory, archiveDate );

	const expectedStageFiles = sortPaths( [
		...releaseManifest.sourceFiles,
		...releaseManifest.generatedFiles,
	] );
	const actualStageFiles = collectFiles( stageDirectory ).map( ( path ) =>
		relative( stageDirectory, path ).split( sep ).join( '/' )
	);

	if ( JSON.stringify( actualStageFiles ) !== JSON.stringify( expectedStageFiles ) ) {
		throw new Error( 'Staged files do not exactly match the release manifest.' );
	}

	const archiveFiles = expectedStageFiles.map( ( path ) => `${ pluginSlug }/${ path }` );
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

	if ( JSON.stringify( archivedFiles ) !== JSON.stringify( archiveFiles ) ) {
		throw new Error( 'ZIP contents do not exactly match the release manifest.' );
	}

	const zipBuffer = readFileSync( zipPath );
	const checksum = sha256( zipBuffer );
	const externalProvenance = createExternalProvenance(
		embeddedProvenance,
		zipBuffer.length,
		checksum
	);
	writeFileSync(
		checksumPath,
		createChecksumSidecar( zipName, checksum ),
		{ mode: 0o644 }
	);
	writeFileSync(
		provenancePath,
		serializeJson( externalProvenance ),
		{ mode: 0o644 }
	);

	console.log( `Staged ${ archiveFiles.length } files in ${ stageDirectory }` );
	console.log( `Created ${ zipPath } (${ zipBuffer.length } bytes)` );
	console.log( `Created ${ checksumPath }` );
	console.log( `Created ${ provenancePath }` );
	console.log(
		`Source revision ${ sourceRevision }${ sourceStatus ? ' (dirty local staging)' : '' }`
	);
	console.log( `SHA-256 ${ checksum }` );
}

if (
	process.argv[ 1 ]
	&& resolve( process.argv[ 1 ] ) === fileURLToPath( import.meta.url )
) {
	main();
}
