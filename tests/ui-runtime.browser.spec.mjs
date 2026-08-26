import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';

const root = new URL( '../', import.meta.url );
const styles = readFileSync(
	fileURLToPath( new URL( 'assets/css/cyber-gallery-mai.css', root ) ),
	'utf8'
);
const packingScript = fileURLToPath(
	new URL( 'assets/js/cyber-gallery-mai-packing.js', root )
);
const carouselScript = fileURLToPath(
	new URL( 'assets/js/cyber-gallery-mai.js', root )
);
const editorScript = fileURLToPath(
	new URL( 'assets/js/cyber-gallery-mai-editor.js', root )
);
const lightboxScript = fileURLToPath(
	new URL( 'assets/js/cyber-gallery-mai-lightbox.js', root )
);

/**
 * Build source-ordered packing fixture items.
 *
 * @param {number} count Item count.
 * @return {string} Fixture markup.
 */
function packingItems( count ) {
	const markup = [];

	for ( let index = 0; index < count; index++ ) {
		markup.push( `<figure class="mai-cyber-gallery-item" data-cyber-ratio="1">`
			+ '<div class="mai-cyber-gallery-media"></div>'
			+ `<figcaption class="mai-cyber-gallery-image-caption">Item ${ index + 1 }</figcaption>`
			+ '</figure>' );
	}

	return markup.join( '' );
}

/**
 * Return overlapping direct-item index pairs for a gallery.
 *
 * @param {import('@playwright/test').Locator} rootLocator Gallery root.
 * @return {Promise<number[][]>} Overlap pairs.
 */
async function getOverlaps( rootLocator ) {
	return rootLocator.evaluate( function ( rootElement ) {
		const items = Array.from(
			rootElement.querySelectorAll( ':scope > .mai-cyber-gallery > .mai-cyber-gallery-item' )
		);
		const overlaps = [];

		items.forEach( function ( item, itemIndex ) {
			const itemBounds = item.getBoundingClientRect();

			items.slice( itemIndex + 1 ).forEach( function ( candidate, candidateOffset ) {
				const candidateBounds = candidate.getBoundingClientRect();
				const horizontal = Math.min( itemBounds.right, candidateBounds.right )
					- Math.max( itemBounds.left, candidateBounds.left );
				const vertical = Math.min( itemBounds.bottom, candidateBounds.bottom )
					- Math.max( itemBounds.top, candidateBounds.top );

				if ( 1 < horizontal && 1 < vertical ) {
					overlaps.push( [ itemIndex, itemIndex + candidateOffset + 1 ] );
				}
			} );
		} );

		return overlaps;
	} );
}

/**
 * Mark an iframe's ResizeObserver operations in its parent window.
 *
 * @param {import('@playwright/test').Page} page Browser page.
 * @param {string} markup Canvas markup installed after instrumentation.
 * @return {Promise<void>}
 */
async function instrumentEditorFrame( page, markup ) {
	await page.evaluate( function ( previewMarkup ) {
		const frame = document.querySelector( 'iframe[name="editor-canvas"]' );
		const view = frame.contentWindow;
		const NativeResizeObserver = view.ResizeObserver;

		view.ResizeObserver = class extends NativeResizeObserver {
			constructor( callback ) {
				super( callback );
				window.__cyberResizeCounts.created++;
			}

			disconnect() {
				window.__cyberResizeCounts.disconnected++;
				return super.disconnect();
			}
		};
		view.document.body.innerHTML = previewMarkup;
	}, markup );
}

test( 'Masonry and Tiled repack changed and newly inserted direct items', async ( { page } ) => {
	await page.setContent(
		`<!doctype html><html><head><style>${ styles }
			body { margin: 0; }
			.fixture {
				width: 620px;
				--cyber-columns-xs: 2;
				--cyber-columns-sm: 2;
				--cyber-columns-md: 2;
				--cyber-columns-lg: 2;
				--cyber-columns-xl: 2;
				--column-gap: 10px;
				--row-gap: 10px;
				--spacing-xxs: 4px;
			}
			.mai-cyber-gallery-media { height: 100px; background: #ccc; }
			.mai-cyber-gallery-image-caption { font: 16px/20px sans-serif; }
		</style></head><body>
			<div id="masonry" class="fixture mai-cyber-gallery-wrap mai-cyber-gallery-layout-masonry">
				<div class="mai-cyber-gallery has-columns" data-cyber-masonry-flow="ordered">${ packingItems( 6 ) }</div>
			</div>
			<div id="tiled" class="fixture mai-cyber-gallery-wrap mai-cyber-gallery-layout-tiled">
				<div class="mai-cyber-gallery has-columns" data-cyber-tiled-pattern="dense">${ packingItems( 6 ) }</div>
			</div>
		</body></html>`
	);
	await page.evaluate( function () {
		const NativeResizeObserver = window.ResizeObserver;

		window.__packingObserverCounts = { observed: 0, unobserved: 0 };
		window.ResizeObserver = class extends NativeResizeObserver {
			observe( target, options ) {
				window.__packingObserverCounts.observed++;
				return super.observe( target, options );
			}

			unobserve( target ) {
				window.__packingObserverCounts.unobserved++;
				return super.unobserve( target );
			}
		};
	} );
	await page.addScriptTag( { path: packingScript } );

	for ( const id of [ 'masonry', 'tiled' ] ) {
		const layout = page.locator( `#${ id }` );
		await expect( layout ).toHaveClass( new RegExp( `is-cyber-${ id }-ready` ) );
		await expect.poll( () => getOverlaps( layout ) ).toEqual( [] );

		const initialHeight = await layout.locator( ':scope > .mai-cyber-gallery' ).evaluate(
			function ( track ) {
				return track.getBoundingClientRect().height;
			}
		);
		await layout.locator( '.mai-cyber-gallery-image-caption' ).first().evaluate(
			function ( caption ) {
				caption.textContent = 'A dynamically replaced caption with enough text to wrap. '.repeat( 18 );
			}
		);

		await expect.poll( async function () {
			return layout.locator( ':scope > .mai-cyber-gallery' ).evaluate( function ( track ) {
				return track.getBoundingClientRect().height;
			} );
		} ).toBeGreaterThan( initialHeight );
		await expect.poll( () => getOverlaps( layout ) ).toEqual( [] );
	}

	const observedBeforeInsert = await page.evaluate( () => window.__packingObserverCounts.observed );
	await page.locator( '#masonry > .mai-cyber-gallery' ).evaluate( function ( track ) {
		const item = document.createElement( 'figure' );

		item.id = 'inserted-item';
		item.className = 'mai-cyber-gallery-item';
		item.dataset.cyberRatio = '1';
		item.innerHTML = '<div class="mai-cyber-gallery-media"></div>'
			+ '<figcaption class="mai-cyber-gallery-image-caption">Inserted</figcaption>';
		track.prepend( item );
	} );
	await expect.poll( () => page.evaluate( () => window.__packingObserverCounts.observed ) )
		.toBeGreaterThan( observedBeforeInsert );
	await page.locator( '#inserted-item .mai-cyber-gallery-image-caption' ).evaluate(
		function ( caption ) {
			caption.textContent = 'An inserted item that changes after observation. '.repeat( 18 );
		}
	);
	await expect.poll( () => getOverlaps( page.locator( '#masonry' ) ) ).toEqual( [] );

	const unobservedBeforeRemoval = await page.evaluate(
		() => window.__packingObserverCounts.unobserved
	);
	await page.locator( '#inserted-item' ).evaluate( ( item ) => item.remove() );
	await expect.poll( () => page.evaluate( () => window.__packingObserverCounts.unobserved ) )
		.toBeGreaterThan( unobservedBeforeRemoval );
} );

test( 'editor bridge disconnects preview, prior-document, and removed-frame observers', async ( {
	page,
} ) => {
	const previewMarkup = '<div data-type="acf/cyber-gallery-mai" data-block="fixture-block">'
		+ '<div class="mai-cyber-gallery-wrap mai-cyber-gallery-layout-masonry">'
		+ '<div class="mai-cyber-gallery"><figure class="mai-cyber-gallery-item"></figure></div>'
		+ '</div>'
		+ '<div class="mai-cyber-gallery-wrap mai-cyber-gallery-layout-slider">'
		+ '<div class="mai-cyber-gallery" tabindex="0"><figure class="mai-cyber-gallery-item"></figure></div>'
		+ '</div></div>';

	await page.setContent( '<!doctype html><html><body><iframe name="editor-canvas"></iframe></body></html>' );
	await page.evaluate( function () {
		window.__cyberResizeCounts = { created: 0, disconnected: 0 };
	} );
	await instrumentEditorFrame( page, previewMarkup );
	await page.addScriptTag( { path: packingScript } );
	await page.addScriptTag( { path: carouselScript } );
	await page.addScriptTag( { path: editorScript } );

	await expect.poll( () => page.evaluate( () => window.__cyberResizeCounts.created ) ).toBe( 2 );
	await page.evaluate( function () {
		document.querySelector( 'iframe' ).contentDocument.querySelector( '[data-block]' ).remove();
	} );
	await expect.poll( () => page.evaluate( () => window.__cyberResizeCounts.disconnected ) )
		.toBe( 2 );

	await page.evaluate( function ( markup ) {
		document.querySelector( 'iframe' ).contentDocument.body.innerHTML = markup;
	}, previewMarkup );
	await expect.poll( () => page.evaluate( () => window.__cyberResizeCounts.created ) ).toBe( 4 );
	await page.evaluate( function () {
		return new Promise( function ( resolve ) {
			const frame = document.querySelector( 'iframe' );

			frame.addEventListener( 'load', resolve, { once: true } );
			frame.srcdoc = '<!doctype html><html><body></body></html>';
		} );
	} );
	await expect.poll( () => page.evaluate( () => window.__cyberResizeCounts.disconnected ) )
		.toBe( 4 );

	await instrumentEditorFrame( page, previewMarkup );
	await expect.poll( () => page.evaluate( () => window.__cyberResizeCounts.created ) ).toBe( 6 );
	await page.locator( 'iframe[name="editor-canvas"]' ).evaluate( ( frame ) => frame.remove() );
	await expect.poll( () => page.evaluate( () => window.__cyberResizeCounts.disconnected ) )
		.toBe( 6 );
} );

test( 'nested direction controls carousel, comparison, and lightbox presentation', async ( {
	page,
} ) => {
	const imageHref = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg"/%3E';

	await page.setContent(
		`<!doctype html><html dir="ltr"><head><style>${ styles }</style></head><body>
			<section dir="rtl">
				<div id="rtl-slider" class="mai-cyber-gallery-wrap mai-cyber-gallery-layout-slider">
					<button class="mai-cyber-gallery-slider-arrow"><svg viewBox="0 0 10 10"></svg></button>
					<a id="rtl-trigger" href="${ imageHref }" data-cyber-lightbox data-cyber-group="rtl">RTL</a>
				</div>
				<div id="rtl-comparison" class="mai-cyber-gallery-comparison" style="--cyber-comparison-position:35%">
					<figure class="mai-cyber-gallery-comparison-layer is-before"></figure>
					<span class="mai-cyber-gallery-comparison-divider"><span class="mai-cyber-gallery-comparison-handle"></span></span>
				</div>
				<section dir="ltr">
					<div id="ltr-slider" class="mai-cyber-gallery-wrap mai-cyber-gallery-layout-slider">
						<button class="mai-cyber-gallery-slider-arrow"><svg viewBox="0 0 10 10"></svg></button>
						<a id="ltr-trigger" href="${ imageHref }" data-cyber-lightbox data-cyber-group="ltr">LTR</a>
					</div>
					<div id="ltr-comparison" class="mai-cyber-gallery-comparison" style="--cyber-comparison-position:35%">
						<figure class="mai-cyber-gallery-comparison-layer is-before"></figure>
						<span class="mai-cyber-gallery-comparison-divider"><span class="mai-cyber-gallery-comparison-handle"></span></span>
					</div>
				</section>
			</section>
		</body></html>`
	);
	await page.addScriptTag( { path: lightboxScript } );

	await expect( page.locator( '#rtl-slider svg' ) ).toHaveCSS(
		'transform',
		'matrix(-1, 0, 0, 1, 0, 0)'
	);
	await expect( page.locator( '#ltr-slider svg' ) ).toHaveCSS( 'transform', 'none' );

	const rtlClip = await page.locator( '#rtl-comparison .is-before' ).evaluate(
		( layer ) => getComputedStyle( layer ).clipPath
	);
	const ltrClip = await page.locator( '#ltr-comparison .is-before' ).evaluate(
		( layer ) => getComputedStyle( layer ).clipPath
	);
	expect( rtlClip ).toMatch( /inset\(0px 0px 0px 65%\)/ );
	expect( ltrClip ).toMatch( /inset\(0px 65% 0px 0px\)/ );

	await page.locator( '#rtl-trigger' ).click();
	await expect( page.locator( '.mai-cyber-gallery-dialog' ) ).toHaveAttribute( 'dir', 'rtl' );
	await page.locator( '.mai-cyber-gallery-dialog' ).evaluate( ( dialog ) => dialog.close() );
	await page.locator( '#ltr-trigger' ).click();
	await expect( page.locator( '.mai-cyber-gallery-dialog' ) ).toHaveAttribute( 'dir', 'ltr' );
} );
