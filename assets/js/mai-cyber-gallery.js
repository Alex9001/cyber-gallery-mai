/**
 * Mai Cyber Gallery progressive enhancements.
 *
 * - Exact justified-row sizing with a resilient Mai grid fallback.
 * - Accessible, native-scroll filmstrip controls.
 * - A native dialog lightbox with keyboard navigation and focus restoration.
 */

( function () {
	'use strict';

	const settings = window.MaiCyberGallerySettings || {};
	const justifiedStates = new WeakMap();
	const filmstripStates = new WeakMap();
	let dialogState = null;

	/**
	 * Run a callback once in the next animation frame.
	 *
	 * @param {Object}   state    Mutable state object.
	 * @param {Function} callback Callback.
	 * @return {void}
	 */
	function scheduleFrame( state, callback ) {
		if ( state.frame ) {
			window.cancelAnimationFrame( state.frame );
		}

		state.frame = window.requestAnimationFrame( function () {
			state.frame = 0;
			callback();
		} );
	}

	/**
	 * Parse a finite positive number.
	 *
	 * @param {*}     value    Candidate.
	 * @param {number} fallback Fallback.
	 * @return {number}
	 */
	function positiveNumber( value, fallback ) {
		const parsed = Number.parseFloat( value );
		return Number.isFinite( parsed ) && 0 < parsed ? parsed : fallback;
	}

	/**
	 * Lay out a justified gallery.
	 *
	 * Reads are batched before writes to avoid ResizeObserver feedback loops.
	 *
	 * @param {HTMLElement} root  Gallery wrapper.
	 * @param {Object}      state Gallery state.
	 * @param {boolean}     force Ignore the one-pixel resize threshold.
	 * @return {void}
	 */
	function layoutJustified( root, state, force ) {
		const track = state.track;
		const items = Array.from( track.querySelectorAll( ':scope > .mai-cyber-gallery-item' ) );
		const width = track.clientWidth;

		if ( ! items.length || width < 1 ) {
			return;
		}

		if ( ! force && Math.abs( width - state.lastWidth ) < 1 ) {
			return;
		}

		state.lastWidth = width;

		const computed = window.getComputedStyle( track );
		const columnGap = positiveNumber( computed.columnGap, 0 );
		const target = Math.max(
			100,
			Math.min( 600, positiveNumber( track.dataset.cyberRowHeight, 240 ) )
		);
		const measured = items.map( function ( item ) {
			return {
				item: item,
				ratio: Math.max(
					0.1,
					Math.min( 10, positiveNumber( item.dataset.cyberRatio, 1 ) )
				),
			};
		} );
		const rows = [];
		let row = [];
		// Keep ordinary landscape pairs viable on narrow tracks, while allowing
		// truly panoramic pairs to fall back to a taller single-image row.
		const minimumRowHeight = Math.min(
			target * 0.33,
			Math.max( 1, ( width - columnGap ) / 4 )
		);
		const getRowHeight = function ( entries ) {
			const ratioSum = entries.reduce( function ( sum, current ) {
				return sum + current.ratio;
			}, 0 );

			return (
				width - columnGap * Math.max( 0, entries.length - 1 )
			) / ratioSum;
		};

		measured.forEach( function ( entry ) {
			if ( ! row.length ) {
				row = [ entry ];
				return;
			}

			const candidate = row.concat( entry );
			const previousHeight = getRowHeight( row );
			const candidateHeight = getRowHeight( candidate );

			if ( candidateHeight <= target ) {
				const previousDistance = Math.abs( previousHeight - target );
				const candidateDistance = Math.abs( candidateHeight - target );
				const pairingIsTooThin = candidateHeight < minimumRowHeight;
				const previousCanClose = 1 < row.length || pairingIsTooThin;

				// Prefer at least two images unless pairing extreme panoramas
				// would make a strip less than a third of the requested height.
				if ( previousDistance < candidateDistance && previousCanClose ) {
					rows.push( row );
					row = [ entry ];
				} else {
					rows.push( candidate );
					row = [];
				}
			} else {
				row = candidate;
			}
		} );

		// Rebalance an avoidable final orphan. Borrow from a three-plus row when
		// possible; otherwise merge a two-item row into a sane three-item row.
		if ( 1 === row.length && rows.length ) {
			const previous = rows.pop();

			if ( 2 < previous.length ) {
				const borrowedPair = [ previous[ previous.length - 1 ], row[ 0 ] ];

				if ( getRowHeight( borrowedPair ) >= minimumRowHeight ) {
					previous.pop();
					row = borrowedPair;
					rows.push( previous );
				} else {
					rows.push( previous );
				}
			} else {
				const merged = previous.concat( row );

				if ( getRowHeight( merged ) >= minimumRowHeight ) {
					row = merged;
				} else {
					rows.push( previous );
				}
			}
		}

		if ( row.length ) {
			rows.push( row );
		}

		const writes = [];

		rows.forEach( function ( entries, rowIndex ) {
			const ratioSum = entries.reduce( function ( sum, entry ) {
				return sum + entry.ratio;
			}, 0 );
			// Reserve a fraction of a pixel so browser rounding cannot wrap the
			// final flex item onto a phantom row.
			const available = Math.max(
				1,
				width - columnGap * Math.max( 0, entries.length - 1 ) - 0.1
			);
			const fillHeight = available / ratioSum;
			const isLast = rowIndex === rows.length - 1;
			const height = Math.max( 1, isLast ? Math.min( target, fillHeight ) : fillHeight );

			entries.forEach( function ( entry ) {
				writes.push( {
					item: entry.item,
					width: entry.ratio * height,
					height: height,
				} );
			} );
		} );

		writes.forEach( function ( write ) {
			write.item.style.setProperty( '--cyber-item-width', write.width.toFixed( 3 ) + 'px' );
			write.item.style.setProperty( '--cyber-item-height', write.height.toFixed( 3 ) + 'px' );
		} );

		root.classList.add( 'is-cyber-justified-ready' );
	}

	/**
	 * Initialize a justified gallery once.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeJustified( root ) {
		const existing = justifiedStates.get( root );
		const track = root.querySelector( '.mai-cyber-gallery' );

		if ( existing ) {
			if ( existing.track === track ) {
				scheduleFrame( existing, function () {
					layoutJustified( root, existing, true );
				} );
				return;
			}

			if ( existing.frame ) {
				window.cancelAnimationFrame( existing.frame );
			}

			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}

			justifiedStates.delete( root );
		}

		if ( ! track ) {
			return;
		}

		const state = {
			track: track,
			frame: 0,
			lastWidth: 0,
			resizeObserver: null,
		};

		justifiedStates.set( root, state );

		const schedule = function ( force ) {
			if ( ! root.isConnected ) {
				return;
			}

			scheduleFrame( state, function () {
				layoutJustified( root, state, Boolean( force ) );
			} );
		};

		if ( 'ResizeObserver' in window ) {
			state.resizeObserver = new window.ResizeObserver( function () {
				schedule( false );
			} );
			state.resizeObserver.observe( track );
		}

		track.querySelectorAll( 'img' ).forEach( function ( image ) {
			if ( ! image.complete ) {
				image.addEventListener( 'load', function () {
					schedule( true );
				}, { once: true } );
			}
		} );

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				schedule( true );
			} );
		}

		// The ratios are server-rendered, so the first pass can run immediately.
		// This prevents the responsive Mai grid fallback from painting first.
		layoutJustified( root, state, true );
	}

	/**
	 * Test whether an item is fully visible in a scroll container.
	 *
	 * @param {HTMLElement} item  Item.
	 * @param {DOMRect}     bounds Track bounds.
	 * @return {boolean}
	 */
	function isFullyVisible( item, bounds ) {
		const rect = item.getBoundingClientRect();
		return rect.left >= bounds.left - 1 && rect.right <= bounds.right + 1;
	}

	/**
	 * Update filmstrip control states.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function updateFilmstrip( state ) {
		state.items = Array.from(
			state.track.querySelectorAll( ':scope > .mai-cyber-gallery-item' )
		);

		if ( ! state.items.length ) {
			state.previous.disabled = true;
			state.next.disabled = true;
			return;
		}

		const bounds = state.track.getBoundingClientRect();
		state.previous.disabled = isFullyVisible( state.items[ 0 ], bounds );
		state.next.disabled = isFullyVisible( state.items[ state.items.length - 1 ], bounds );
	}

	/**
	 * Move a filmstrip by its currently visible group.
	 *
	 * @param {Object} state     Filmstrip state.
	 * @param {number} direction -1 or 1.
	 * @return {void}
	 */
	function moveFilmstrip( state, direction ) {
		state.items = Array.from(
			state.track.querySelectorAll( ':scope > .mai-cyber-gallery-item' )
		);

		if ( ! state.items.length ) {
			return;
		}

		const bounds = state.track.getBoundingClientRect();
		const visible = [];

		state.items.forEach( function ( item, index ) {
			const rect = item.getBoundingClientRect();
			const overlap = Math.min( rect.right, bounds.right ) - Math.max( rect.left, bounds.left );

			if ( overlap > Math.min( rect.width, bounds.width ) * 0.5 ) {
				visible.push( index );
			}
		} );

		const first = visible.length ? visible[ 0 ] : 0;
		const last = visible.length ? visible[ visible.length - 1 ] : first;
		const targetIndex = 0 < direction
			? Math.min( state.items.length - 1, last + 1 )
			: Math.max( 0, first - Math.max( 1, visible.length ) );
		const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		state.items[ targetIndex ].scrollIntoView( {
			behavior: reduceMotion ? 'auto' : 'smooth',
			block: 'nearest',
			inline: 'start',
		} );
	}

	/**
	 * Initialize one filmstrip.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeFilmstrip( root ) {
		const track = root.querySelector( '.mai-cyber-gallery' );
		const previous = root.querySelector( '[data-cyber-filmstrip-previous]' );
		const next = root.querySelector( '[data-cyber-filmstrip-next]' );
		const items = track
			? Array.from( track.querySelectorAll( ':scope > .mai-cyber-gallery-item' ) )
			: [];
		const existing = filmstripStates.get( root );

		if ( existing ) {
			if (
				existing.track === track
				&& existing.previous === previous
				&& existing.next === next
				&& 2 <= items.length
			) {
				scheduleFrame( existing, function () {
					updateFilmstrip( existing );
				} );
				return;
			}

			if ( existing.frame ) {
				window.cancelAnimationFrame( existing.frame );
			}

			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}

			filmstripStates.delete( root );
			root.classList.remove( 'is-cyber-filmstrip-ready' );
		}

		if ( ! track || ! previous || ! next || 2 > items.length ) {
			return;
		}

		const state = {
			track: track,
			previous: previous,
			next: next,
			items: items,
			frame: 0,
			resizeObserver: null,
		};

		filmstripStates.set( root, state );
		root.classList.add( 'is-cyber-filmstrip-ready' );

		const scheduleUpdate = function () {
			scheduleFrame( state, function () {
				updateFilmstrip( state );
			} );
		};

		previous.addEventListener( 'click', function () {
			moveFilmstrip( state, -1 );
		} );

		next.addEventListener( 'click', function () {
			moveFilmstrip( state, 1 );
		} );

		track.addEventListener( 'scroll', scheduleUpdate, { passive: true } );
		track.addEventListener( 'keydown', function ( event ) {
			if ( event.target.closest( 'a, button, input, select, textarea' ) ) {
				return;
			}

			const isRtl = window.getComputedStyle( track ).direction === 'rtl';
			let direction = 0;

			if ( 'ArrowRight' === event.key ) {
				direction = isRtl ? -1 : 1;
			} else if ( 'ArrowLeft' === event.key ) {
				direction = isRtl ? 1 : -1;
			} else if ( 'Home' === event.key ) {
				state.items[ 0 ].scrollIntoView( { block: 'nearest', inline: 'start' } );
				event.preventDefault();
				return;
			} else if ( 'End' === event.key ) {
				state.items[ state.items.length - 1 ].scrollIntoView( { block: 'nearest', inline: 'start' } );
				event.preventDefault();
				return;
			}

			if ( direction ) {
				event.preventDefault();
				moveFilmstrip( state, direction );
			}
		} );

		if ( 'ResizeObserver' in window ) {
			state.resizeObserver = new window.ResizeObserver( scheduleUpdate );
			state.resizeObserver.observe( track );
		}

		scheduleUpdate();
	}

	/**
	 * Make an icon button for the dialog.
	 *
	 * @param {string} className Extra class.
	 * @param {string} label     Accessible label.
	 * @param {string} path      SVG path data.
	 * @return {HTMLButtonElement}
	 */
	function makeDialogButton( className, label, path ) {
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'mai-cyber-gallery-dialog-button ' + className;
		button.setAttribute( 'aria-label', label );
		button.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="' +
			path + '"></path></svg>';
		return button;
	}

	/**
	 * Create the single shared lightbox dialog.
	 *
	 * @return {Object|null}
	 */
	function ensureDialog() {
		if ( dialogState ) {
			return dialogState;
		}

		if ( ! ( 'HTMLDialogElement' in window ) ) {
			return null;
		}

		const dialog = document.createElement( 'dialog' );
		const inner = document.createElement( 'div' );
		const stage = document.createElement( 'div' );
		const image = document.createElement( 'img' );
		const meta = document.createElement( 'div' );
		const caption = document.createElement( 'p' );
		const position = document.createElement( 'p' );
		const status = document.createElement( 'p' );
		const close = makeDialogButton(
			'mai-cyber-gallery-dialog-close',
			settings.close || 'Close image viewer',
			'M6.4 5 5 6.4l5.6 5.6L5 17.6 6.4 19l5.6-5.6 5.6 5.6 1.4-1.4-5.6-5.6L19 6.4 17.6 5 12 10.6z'
		);
		const previous = makeDialogButton(
			'mai-cyber-gallery-dialog-previous',
			settings.previous || 'Previous image',
			'M14.7 5.3 13.3 4l-8 8 8 8 1.4-1.4L8.1 12z'
		);
		const next = makeDialogButton(
			'mai-cyber-gallery-dialog-next',
			settings.next || 'Next image',
			'M9.3 18.7 10.7 20l8-8-8-8-1.4 1.4 6.6 6.6z'
		);

		dialog.className = 'mai-cyber-gallery-dialog';
		dialog.setAttribute( 'aria-label', settings.dialogLabel || 'Image viewer' );
		inner.className = 'mai-cyber-gallery-dialog-inner';
		stage.className = 'mai-cyber-gallery-dialog-stage';
		image.className = 'mai-cyber-gallery-dialog-image';
		meta.className = 'mai-cyber-gallery-dialog-meta';
		caption.className = 'mai-cyber-gallery-dialog-caption';
		position.className = 'mai-cyber-gallery-dialog-position';
		status.className = 'mai-cyber-gallery-dialog-status screen-reader-text';
		status.setAttribute( 'aria-live', 'polite' );
		status.setAttribute( 'aria-atomic', 'true' );

		meta.append( caption, position );
		stage.append( image );
		inner.append( stage, meta, status );
		dialog.append( inner, close, previous, next );
		document.body.append( dialog );

		dialogState = {
			dialog: dialog,
			image: image,
			caption: caption,
			position: position,
			status: status,
			close: close,
			previous: previous,
			next: next,
			triggers: [],
			index: 0,
			opener: null,
		};

		close.addEventListener( 'click', function () {
			dialog.close();
		} );
		previous.addEventListener( 'click', function () {
			showDialogImage( dialogState.index - 1 );
		} );
		next.addEventListener( 'click', function () {
			showDialogImage( dialogState.index + 1 );
		} );
		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog ) {
				dialog.close();
			}
		} );
		dialog.addEventListener( 'keydown', function ( event ) {
			const isRtl = window.getComputedStyle( dialog ).direction === 'rtl';

			if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				showDialogImage( dialogState.index + ( isRtl ? 1 : -1 ) );
			} else if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				showDialogImage( dialogState.index + ( isRtl ? -1 : 1 ) );
			} else if ( 'Home' === event.key ) {
				event.preventDefault();
				showDialogImage( 0 );
			} else if ( 'End' === event.key ) {
				event.preventDefault();
				showDialogImage( dialogState.triggers.length - 1 );
			}
		} );
		dialog.addEventListener( 'close', function () {
			document.body.classList.remove( 'mai-cyber-gallery-lightbox-open' );
			image.removeAttribute( 'src' );
			image.removeAttribute( 'srcset' );
			image.removeAttribute( 'sizes' );
			image.removeAttribute( 'width' );
			image.removeAttribute( 'height' );

			if ( dialogState.opener && document.contains( dialogState.opener ) ) {
				dialogState.opener.focus( { preventScroll: true } );
			}
		} );

		return dialogState;
	}

	/**
	 * Format a WordPress positional string.
	 *
	 * @param {number} current Current number.
	 * @param {number} total   Total.
	 * @return {string}
	 */
	function formatPosition( current, total ) {
		return String( settings.position || '%1$d of %2$d' )
			.replace( /%1\$d/g, String( current ) )
			.replace( /%2\$d/g, String( total ) );
	}

	/**
	 * Display one lightbox image.
	 *
	 * @param {number} requestedIndex Requested zero-based index.
	 * @return {void}
	 */
	function showDialogImage( requestedIndex ) {
		if ( ! dialogState || ! dialogState.triggers.length ) {
			return;
		}

		const total = dialogState.triggers.length;
		const index = Math.max( 0, Math.min( total - 1, requestedIndex ) );
		const trigger = dialogState.triggers[ index ];
		const data = trigger.dataset;
		const description = data.cyberAlt || data.cyberCaption || data.cyberLabel || '';
		const position = formatPosition( index + 1, total );

		dialogState.index = index;
		dialogState.image.alt = description;

		if ( data.cyberSrcset ) {
			dialogState.image.srcset = data.cyberSrcset;
		} else {
			dialogState.image.removeAttribute( 'srcset' );
		}

		if ( data.cyberSizes ) {
			dialogState.image.sizes = data.cyberSizes;
		} else {
			dialogState.image.removeAttribute( 'sizes' );
		}

		if ( data.cyberWidth ) {
			dialogState.image.width = positiveNumber( data.cyberWidth, 1 );
		} else {
			dialogState.image.removeAttribute( 'width' );
		}

		if ( data.cyberHeight ) {
			dialogState.image.height = positiveNumber( data.cyberHeight, 1 );
		} else {
			dialogState.image.removeAttribute( 'height' );
		}

		dialogState.image.src = trigger.href;
		dialogState.caption.textContent = data.cyberCaption || '';
		dialogState.caption.hidden = ! data.cyberCaption;
		dialogState.position.textContent = position;
		dialogState.status.textContent = description
			? String( settings.status || '%1$s, %2$s' )
				.replace( /%1\$s/g, description )
				.replace( /%2\$s/g, position )
			: position;
		dialogState.previous.disabled = 0 === index;
		dialogState.next.disabled = index === total - 1;

		// Warm the next image without changing loading behavior for the gallery.
		if ( index < total - 1 ) {
			const preload = new window.Image();
			const nextTrigger = dialogState.triggers[ index + 1 ];

			if ( nextTrigger.dataset.cyberSrcset ) {
				preload.srcset = nextTrigger.dataset.cyberSrcset;
			}

			if ( nextTrigger.dataset.cyberSizes ) {
				preload.sizes = nextTrigger.dataset.cyberSizes;
			}

			preload.src = nextTrigger.href;
		}
	}

	/**
	 * Open the dialog for a gallery trigger.
	 *
	 * @param {HTMLAnchorElement} trigger Trigger.
	 * @return {boolean} Whether the native dialog opened.
	 */
	function openDialog( trigger ) {
		const state = ensureDialog();

		if ( ! state || 'function' !== typeof state.dialog.showModal ) {
			return false;
		}

		const group = trigger.dataset.cyberGroup;
		state.triggers = Array.from( document.querySelectorAll( 'a[data-cyber-lightbox]' ) )
			.filter( function ( candidate ) {
				return candidate.dataset.cyberGroup === group;
			} );
		state.index = Math.max( 0, state.triggers.indexOf( trigger ) );
		state.opener = trigger;

		showDialogImage( state.index );
		document.body.classList.add( 'mai-cyber-gallery-lightbox-open' );

		if ( ! state.dialog.open ) {
			state.dialog.showModal();
		}

		state.close.focus();
		return true;
	}

	/**
	 * Collect gallery roots in or optionally around a scope.
	 *
	 * @param {Document|Element} scope           Search scope.
	 * @param {boolean}          includeAncestor Include the closest parent gallery.
	 * @return {Set<HTMLElement>} Gallery roots.
	 */
	function getRootsWithin( scope, includeAncestor ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return new Set();
		}

		const roots = new Set();

		if ( scope.matches && scope.matches( '.mai-cyber-gallery-wrap' ) ) {
			roots.add( scope );
		} else if ( includeAncestor && scope.closest ) {
			const ancestor = scope.closest( '.mai-cyber-gallery-wrap' );

			if ( ancestor ) {
				roots.add( ancestor );
			}
		}

		scope.querySelectorAll( '.mai-cyber-gallery-wrap' ).forEach( function ( root ) {
			roots.add( root );
		} );

		return roots;
	}

	/**
	 * Initialize gallery behaviors inside a root.
	 *
	 * @param {Document|Element} scope Search scope.
	 * @return {void}
	 */
	function initializeWithin( scope ) {
		const roots = getRootsWithin( scope, true );

		roots.forEach( function ( root ) {
			if ( root.classList.contains( 'mai-cyber-gallery-layout-justified' ) ) {
				initializeJustified( root );
			}

			if ( root.classList.contains( 'mai-cyber-gallery-layout-filmstrip' ) ) {
				initializeFilmstrip( root );
			}
		} );
	}

	/**
	 * Disconnect observers owned by galleries removed from the document.
	 *
	 * @param {Element} scope Removed element.
	 * @return {void}
	 */
	function destroyWithin( scope ) {
		getRootsWithin( scope, false ).forEach( function ( root ) {
			[ justifiedStates, filmstripStates ].forEach( function ( states ) {
				const state = states.get( root );

				if ( ! state ) {
					return;
				}

				if ( state.frame ) {
					window.cancelAnimationFrame( state.frame );
				}

				if ( state.resizeObserver ) {
					state.resizeObserver.disconnect();
				}

				states.delete( root );
			} );
		} );
	}

	/**
	 * Start global behavior.
	 *
	 * @return {void}
	 */
	function boot() {
		initializeWithin( document );

		document.addEventListener( 'click', function ( event ) {
			const trigger = event.target && event.target.closest
				? event.target.closest( 'a[data-cyber-lightbox]' )
				: null;

			if (
				trigger
				&& ! event.defaultPrevented
				&& 0 === event.button
				&& ! event.metaKey
				&& ! event.ctrlKey
				&& ! event.shiftKey
				&& ! event.altKey
				&& openDialog( trigger )
			) {
				event.preventDefault();
			}
		} );

		if ( 'MutationObserver' in window ) {
			const observer = new window.MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					mutation.removedNodes.forEach( function ( node ) {
						if ( 1 === node.nodeType ) {
							destroyWithin( node );
						}
					} );

					mutation.addedNodes.forEach( function ( node ) {
						if ( 1 === node.nodeType ) {
							initializeWithin( node );
						}
					} );

					if ( 1 === mutation.target.nodeType ) {
						initializeWithin( mutation.target );
					}
				} );
			} );

			observer.observe( document.body || document.documentElement, {
				childList: true,
				subtree: true,
			} );
		}

		const initializeAcf = function () {
			if (
				window.acf
				&& 'function' === typeof window.acf.addAction
				&& ! boot.acfInitialized
			) {
				boot.acfInitialized = true;
				window.acf.addAction(
					'render_block_preview/type=mai-cyber-gallery',
					function ( block ) {
						const element = block && block[ 0 ] ? block[ 0 ] : block;
						initializeWithin( element );
					}
				);
			}
		};

		initializeAcf();

		if ( 'loading' === document.readyState ) {
			document.addEventListener(
				'DOMContentLoaded',
				function () {
					initializeWithin( document );
					initializeAcf();
				},
				{ once: true }
			);
		}
	}

	// Start immediately. When loaded in the head, the observer initializes a
	// gallery as the parser adds it, before the CSS fallback can be painted.
	boot();
}() );
