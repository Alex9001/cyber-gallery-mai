import { expect, test } from '@playwright/test';

const siteUrl = String( process.env.CYBER_GALLERY_MAI_WP_URL || '' ).replace( /\/$/, '' );
const username = process.env.CYBER_GALLERY_MAI_WP_USERNAME || '';
const password = process.env.CYBER_GALLERY_MAI_WP_PASSWORD || '';
const postId = process.env.CYBER_GALLERY_MAI_WP_POST_ID || '';
const hasLiveFixture = Boolean( siteUrl && username && password && /^\d+$/.test( postId ) );

/**
 * Sign in to the disposable WordPress integration site.
 *
 * @param {import('@playwright/test').Page} page Browser page.
 * @return {Promise<void>}
 */
async function signIn( page ) {
	await page.goto( `${ siteUrl }/wp-login.php` );
	await page.locator( '#user_login' ).fill( username );
	await page.locator( '#user_pass' ).fill( password );
	await Promise.all( [
		page.waitForURL( /\/wp-admin\// ),
		page.locator( '#wp-submit' ).click(),
	] );
}

test( 'WordPress 7.1 iframe boots legacy previews and inserts the canonical block', async ( {
	page,
} ) => {
	test.skip(
		! hasLiveFixture,
		'Set the CYBER_GALLERY_MAI_WP_* variables to run the live WordPress 7.1 integration test.'
	);

	const browserErrors = [];
	page.on( 'pageerror', function ( error ) {
		browserErrors.push( `pageerror: ${ error.message }` );
	} );
	page.on( 'console', function ( message ) {
		if ( 'error' === message.type() ) {
			browserErrors.push( `console: ${ message.text() }` );
		}
	} );

	await signIn( page );
	await page.goto(
		`${ siteUrl }/wp-admin/post.php?post=${ postId }&action=edit`,
		{ waitUntil: 'domcontentloaded' }
	);

	const canvasElement = page.locator( 'iframe[name="editor-canvas"]' );
	await expect( canvasElement ).toBeVisible();
	await expect( canvasElement ).toHaveAttribute( 'src', /^blob:/ );

	const welcome = page.locator( '[role="dialog"]' ).filter( {
		hasText: 'Welcome to the editor',
	} );
	if ( await welcome.isVisible() ) {
		await welcome.getByRole( 'button', { name: 'Close' } ).click();
	}

	const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
	const legacyBlocks = canvas.locator( '[data-type="acf/mai-cyber-gallery"]' );
	await expect( legacyBlocks.first() ).toBeVisible();

	const slider = canvas.locator( '.mai-cyber-gallery-layout-slider' ).first();
	await expect( slider ).toHaveClass( /is-cyber-slider-ready/ );
	await expect( slider ).toHaveAttribute( 'data-cyber-slider-index', '1' );
	await slider.locator( '[data-cyber-slider-next]' ).click();
	await expect( slider ).toHaveAttribute( 'data-cyber-slider-index', '2' );
	await expect( slider.locator( '[data-cyber-slider-status]' ) ).toContainText( 'Slide 2 of 2' );

	const registration = await page.evaluate( () => {
		const canonical = window.wp.blocks.getBlockType( 'acf/cyber-gallery-mai' );
		const legacy = window.wp.blocks.getBlockType( 'acf/mai-cyber-gallery' );

		return {
			canonical: Boolean( canonical ),
			legacy: Boolean( legacy ),
			legacyInserter: legacy?.supports?.inserter,
			runtimeAlias: window.CyberGalleryMaiRuntime === window.MaiCyberGalleryRuntime,
		};
	} );

	expect( registration ).toEqual( {
		canonical: true,
		legacy: true,
		legacyInserter: false,
		runtimeAlias: true,
	} );

	const insertedClientId = await page.evaluate( () => {
		const block = window.wp.blocks.createBlock( 'acf/cyber-gallery-mai' );
		window.wp.data.dispatch( 'core/block-editor' ).insertBlocks( block );
		return block.clientId;
	} );
	const inserted = canvas.locator( `[data-block="${ insertedClientId }"]` );
	await expect( inserted ).toHaveAttribute( 'data-type', 'acf/cyber-gallery-mai' );
	await expect( inserted.locator( '.mai-cyber-gallery-placeholder' ) ).toBeVisible();

	await page.evaluate( ( clientId ) => {
		window.wp.data.dispatch( 'core/block-editor' ).removeBlock( clientId, false );
	}, insertedClientId );

	expect( browserErrors ).toEqual( [] );
} );
