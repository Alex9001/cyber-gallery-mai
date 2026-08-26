import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import {
	mkdirSync,
	mkdtempSync,
	readFileSync,
	rmSync,
	symlinkSync,
	writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import {
	createArtifactBase,
	createChecksumSidecar,
	createEmbeddedProvenance,
	createExternalProvenance,
	inspectDistributionTree,
	serializeJson,
	sha256,
	validateManifestDefinition,
} from '../scripts/build-release.mjs';

const root = fileURLToPath( new URL( '../', import.meta.url ) );
const manifest = JSON.parse(
	readFileSync( new URL( '../scripts/release-manifest.json', import.meta.url ), 'utf8' )
);

test( 'release manifest exactly covers prospective tracked runtime files', () => {
	validateManifestDefinition( manifest );

	const releaseScope = [
		'LICENSE',
		'NOTICE',
		'README.md',
		'cyber-gallery-mai.php',
		'readme.txt',
		'assets',
		'blocks',
		'classes',
		'includes',
		'languages',
	];
	const prospectiveTrackedFiles = execFileSync(
		'git',
		[
			'ls-files',
			'--cached',
			'--others',
			'--exclude-standard',
			'-z',
			'--',
			...releaseScope,
		],
		{ cwd: root, encoding: 'utf8' }
	)
		.split( '\0' )
		.filter( Boolean )
		.sort();

	assert.deepEqual( manifest.sourceFiles, prospectiveTrackedFiles );
	assert.ok(
		manifest.sourceFiles.includes( 'includes/compatibility-bootstrap.php' ),
		'the early 1.x compatibility bootstrap must ship'
	);
} );

test( 'distribution inspection rejects extra files, directories, and symlinks', ( t ) => {
	const fixture = mkdtempSync( join( tmpdir(), 'cyber-gallery-release-tree-' ) );
	t.after( () => rmSync( fixture, { force: true, recursive: true } ) );

	mkdirSync( join( fixture, 'assets' ) );
	writeFileSync( join( fixture, 'assets/runtime.js' ), 'runtime\n' );

	assert.doesNotThrow( () =>
		inspectDistributionTree( fixture, [ 'assets/runtime.js' ], [ 'assets' ] )
	);

	writeFileSync( join( fixture, 'assets/untracked.js' ), 'untracked\n' );
	assert.throws(
		() => inspectDistributionTree( fixture, [ 'assets/runtime.js' ], [ 'assets' ] ),
		/untracked, ignored, or unexpected file/
	);
	rmSync( join( fixture, 'assets/untracked.js' ) );

	mkdirSync( join( fixture, 'assets/cache' ) );
	assert.throws(
		() => inspectDistributionTree( fixture, [ 'assets/runtime.js' ], [ 'assets' ] ),
		/untracked, ignored, or unexpected directory/
	);
	rmSync( join( fixture, 'assets/cache' ), { recursive: true } );

	symlinkSync( 'runtime.js', join( fixture, 'assets/runtime-link.js' ) );
	assert.throws(
		() => inspectDistributionTree( fixture, [ 'assets/runtime.js' ], [ 'assets' ] ),
		/contains a symlink/
	);
} );

test( 'provenance, checksum sidecars, and artifact names are reproducible', () => {
	const sourceRevision = 'a'.repeat( 40 );
	const dirtyFingerprint = 'b'.repeat( 64 );
	const cleanName = createArtifactBase(
		'cyber-gallery-mai',
		'2.0.0',
		sourceRevision
	);
	const dirtyName = createArtifactBase(
		'cyber-gallery-mai',
		'2.0.0',
		sourceRevision,
		dirtyFingerprint
	);

	assert.equal( cleanName, 'cyber-gallery-mai-2.0.0' );
	assert.equal(
		dirtyName,
		`cyber-gallery-mai-2.0.0-dirty-aaaaaaaaaaaa-${ dirtyFingerprint }`
	);
	assert.notEqual( cleanName, dirtyName );

	const values = {
		artifact: `${ cleanName }.zip`,
		pluginName: 'Cyber Gallery for Mai Theme',
		pluginSlug: 'cyber-gallery-mai',
		version: '2.0.0',
		sourceRevision,
		sourceTree: 'c'.repeat( 40 ),
		dirtyFingerprint: '',
		sourceDateEpoch: 1_700_000_000,
		manifestSha256: 'd'.repeat( 64 ),
	};
	const firstEmbedded = serializeJson( createEmbeddedProvenance( values ) );
	const secondEmbedded = serializeJson( createEmbeddedProvenance( values ) );
	assert.equal( firstEmbedded, secondEmbedded );

	const archiveDigest = sha256( 'stable archive bytes' );
	const external = createExternalProvenance(
		JSON.parse( firstEmbedded ),
		20,
		archiveDigest
	);
	assert.equal( external.artifact.sha256, archiveDigest );
	assert.equal( external.embeddedProvenanceSha256, sha256( firstEmbedded ) );
	assert.equal(
		createChecksumSidecar( `${ cleanName }.zip`, archiveDigest ),
		`${ archiveDigest }  ${ cleanName }.zip\n`
	);
} );
