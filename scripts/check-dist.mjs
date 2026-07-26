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
		binary: `terser${ executableSuffix }`,
		source: 'assets/js/mai-cyber-gallery.js',
		distribution: 'assets/js/mai-cyber-gallery.min.js',
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

	JSON.parse( readFileSync( new URL( 'blocks/mai-cyber-gallery/block.json', root ), 'utf8' ) );
	console.log( 'Verified blocks/mai-cyber-gallery/block.json' );
} finally {
	rmSync( temporaryDirectory, { force: true, recursive: true } );
}
