import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';

const root = new URL( '../', import.meta.url );
const markup = execFileSync(
	'php',
	[ fileURLToPath( new URL( 'fixtures/render-comparison.php', import.meta.url ) ) ],
	{ encoding: 'utf8' }
);
const stylePath = fileURLToPath(
	new URL( 'assets/css/cyber-gallery-mai.css', root )
);
const styles = readFileSync( stylePath, 'utf8' );
const scriptPath = fileURLToPath(
	new URL( 'assets/js/cyber-gallery-mai-comparison.js', root )
);

/**
 * Load the real server markup and unminified production stylesheet.
 *
 * @param {import('@playwright/test').Page} page Browser page.
 * @param {'ltr'|'rtl'} direction Document direction.
 * @return {Promise<void>}
 */
async function loadComparison( page, direction = 'ltr' ) {
	await page.setContent(
		`<!doctype html><html dir="${ direction }"><head><style>${ styles }</style></head><body>${ markup }</body></html>`
	);
}

test( 'the no-JavaScript fallback is static and has no invisible focus target', async ( {
	browser,
} ) => {
	const context = await browser.newContext( { javaScriptEnabled: false } );
	const page = await context.newPage();

	await loadComparison( page );

	const range = page.locator( '[data-cyber-comparison-range]' );
	const comparison = page.locator( '[data-cyber-comparison]' );

	await expect( range ).toBeHidden();
	await expect( range ).toBeDisabled();
	await expect( comparison ).not.toHaveClass( /is-cyber-comparison-ready/ );
	await expect( comparison ).toHaveCSS( '--cyber-comparison-position', '35%' );

	await page.keyboard.press( 'Tab' );
	await expect( range ).not.toBeFocused();
	await expect( page.locator( '.mai-cyber-gallery-comparison-divider' ) ).toHaveCSS(
		'visibility',
		'hidden'
	);

	await context.close();
} );

test( 'JavaScript activates native keyboard, RTL pointer, and print behavior', async ( {
	page,
} ) => {
	await loadComparison( page, 'rtl' );
	await page.evaluate( () => {
		window.CyberGalleryMaiComparisonSettings = {
			valueText: '%1$s: %2$d%% visible',
		};
	} );
	await page.addScriptTag( { path: scriptPath } );

	const range = page.locator( '[data-cyber-comparison-range]' );
	const comparison = page.locator( '[data-cyber-comparison]' );
	const divider = page.locator( '.mai-cyber-gallery-comparison-divider' );

	await expect( comparison ).toHaveClass( /is-cyber-comparison-ready/ );
	await expect( range ).toBeVisible();
	await expect( range ).toBeEnabled();
	await expect( range ).toHaveCSS( 'pointer-events', 'auto' );
	await expect( divider ).toHaveCSS( 'visibility', 'visible' );

	await range.focus();
	await page.keyboard.press( 'ArrowRight' );
	await expect( range ).toHaveValue( '34' );
	await expect( comparison ).toHaveCSS( '--cyber-comparison-position', '34%' );
	await expect( range ).toHaveAttribute( 'aria-valuetext', 'Original: 34% visible' );

	const box = await range.boundingBox();
	expect( box ).not.toBeNull();
	await page.mouse.click( box.x + ( box.width * 0.25 ), box.y + ( box.height / 2 ) );
	await expect( range ).toHaveValue( /7[45]/ );

	const rtlPosition = await comparison.evaluate( ( element ) =>
		element.style.getPropertyValue( '--cyber-comparison-position' )
	);
	expect( Number.parseInt( rtlPosition, 10 ) ).toBeGreaterThanOrEqual( 74 );

	await page.emulateMedia( { media: 'print' } );
	await expect( range ).toHaveCSS( 'display', 'none' );
	await expect( divider ).toHaveCSS( 'display', 'none' );
	expect(
		await comparison.evaluate( ( element ) =>
			element.style.getPropertyValue( '--cyber-comparison-position' )
		)
	).toBe( rtlPosition );
} );
