import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import {
	existsSync,
	lstatSync,
	mkdirSync,
	readFileSync,
	rmSync,
	writeFileSync,
} from 'node:fs';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '../', import.meta.url ) );
const fixture = JSON.parse(
	readFileSync( new URL( '../tests/fixtures/test-archives.json', import.meta.url ), 'utf8' )
);
const downloadDirectory = join( root, 'build', 'verified-test-archives' );

if ( 1 !== fixture.schemaVersion || ! Array.isArray( fixture.archives ) ) {
	throw new Error( 'tests/fixtures/test-archives.json has an invalid schema.' );
}

if (
	'verified-test-archives' !== basename( downloadDirectory )
	|| resolve( dirname( downloadDirectory ) ) !== resolve( root, 'build' )
) {
	throw new Error( `Refusing to clean unexpected archive path: ${ downloadDirectory }` );
}

rmSync( downloadDirectory, { force: true, recursive: true } );
mkdirSync( downloadDirectory, { recursive: true } );

for ( const archive of fixture.archives ) {
	if (
		! /^[a-zA-Z0-9._-]+\.zip$/.test( archive.name )
		|| ! /^[a-zA-Z0-9._-]+$/.test( archive.extractedDirectory )
		|| ! /^https:\/\//.test( archive.url )
		|| ! /^[a-f0-9]{64}$/.test( archive.sha256 )
	) {
		throw new Error( `Invalid test archive declaration: ${ archive.name || '(unnamed)' }` );
	}

	const response = await fetch( archive.url, { redirect: 'follow' } );
	if ( ! response.ok ) {
		throw new Error(
			`Unable to download ${ archive.url }: HTTP ${ response.status }`
		);
	}

	const contents = Buffer.from( await response.arrayBuffer() );
	const actualSha256 = createHash( 'sha256' )
		.update( contents )
		.digest( 'hex' );
	if ( archive.sha256 !== actualSha256 ) {
		throw new Error(
			`${ archive.name } SHA-256 mismatch: expected ${ archive.sha256 }, received ${ actualSha256 }.`
		);
	}

	const archivePath = join( downloadDirectory, archive.name );
	writeFileSync( archivePath, contents, {
		mode: 0o644,
	} );

	const archiveMembers = execFileSync( 'unzip', [ '-Z1', archivePath ], {
		encoding: 'utf8',
	} )
		.trim()
		.split( '\n' )
		.filter( Boolean );
	if (
		archiveMembers.some( ( path ) =>
			path.startsWith( '/' )
			|| path.split( '/' ).some( ( segment ) => '..' === segment )
		)
	) {
		throw new Error( `${ archive.name } contains an unsafe archive path.` );
	}

	execFileSync( 'unzip', [ '-q', archivePath, '-d', downloadDirectory ] );
	const extractedPath = join(
		downloadDirectory,
		archive.extractedDirectory
	);
	if ( ! existsSync( extractedPath ) || ! lstatSync( extractedPath ).isDirectory() ) {
		throw new Error(
			`${ archive.name } did not provide ${ archive.extractedDirectory }/.`
		);
	}
	console.log( `Verified ${ archive.name }: ${ actualSha256 }` );
}
