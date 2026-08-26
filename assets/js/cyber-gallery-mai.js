/**
 * Cyber Gallery for Mai Theme carousel progressive enhancements.
 *
 * - Accessible, native-scroll filmstrip controls.
 * - An accessible, native-scroll slider with arrows, dots, and counters.
 */

( function () {
	'use strict';

	const settings = window.CyberGalleryMaiSettings
		|| window.MaiCyberGallerySettings
		|| {};
	const filmstripStates = new WeakMap();
	const sliderStates = new WeakMap();
	const managedEditorRoots = new WeakMap();
	const managedEditorBlocks = new Map();
	const managedEditorObservers = new WeakMap();

	// Keep the former globals as aliases for third-party integrations while the
	// canonical public namespace follows the renamed plugin.
	window.CyberGalleryMaiSettings = settings;
	window.MaiCyberGallerySettings = settings;

	/**
	 * Get the document which owns a runtime scope.
	 *
	 * WordPress may render block previews in an editor iframe while this bridge
	 * remains loaded in the parent window. DOM state must therefore come from the
	 * gallery, not from this script's global document.
	 *
	 * @param {Document|Element} scope Runtime scope.
	 * @return {Document}
	 */
	function getOwnerDocument( scope ) {
		if ( scope && 9 === scope.nodeType ) {
			return scope;
		}

		return scope && scope.ownerDocument ? scope.ownerDocument : document;
	}

	/**
	 * Get the view which owns a runtime scope.
	 *
	 * @param {Document|Element} scope Runtime scope.
	 * @return {Window}
	 */
	function getOwnerView( scope ) {
		return getOwnerDocument( scope ).defaultView || window;
	}

	/**
	 * Check whether a scope and its owning iframe remain connected.
	 *
	 * A node can remain connected to its own Document after WordPress replaces
	 * the iframe element which contains that document.
	 *
	 * @param {Document|Element} scope Runtime scope.
	 * @return {boolean}
	 */
	function isRuntimeConnected( scope ) {
		if ( ! scope || ! scope.isConnected ) {
			return false;
		}

		const view = getOwnerView( scope );

		try {
			return ! view.frameElement || view.frameElement.isConnected;
		} catch {
			return true;
		}
	}

	/**
	 * Run a callback once in the next animation frame.
	 *
	 * @param {Object}   state    Mutable state object.
	 * @param {Function} callback Callback.
	 * @return {void}
	 */
	function scheduleFrame( state, callback ) {
		if ( state.frame ) {
			state.view.cancelAnimationFrame( state.frame );
		}

		state.frame = state.view.requestAnimationFrame( function () {
			state.frame = 0;
			callback();
		} );
	}

	/**
	 * Parse a finite positive integer.
	 *
	 * @param {*}      value    Candidate.
	 * @param {number} fallback Fallback.
	 * @return {number}
	 */
	function positiveInteger( value, fallback ) {
		const parsed = Number.parseInt( value, 10 );
		return Number.isFinite( parsed ) && 0 < parsed ? parsed : fallback;
	}

	/**
	 * Parse one computed CSS time into milliseconds.
	 *
	 * @param {*}      value    CSS time such as 560ms or 0.56s.
	 * @param {number} fallback Fallback milliseconds.
	 * @return {number}
	 */
	function cssTimeMilliseconds( value, fallback ) {
		const string = String( value || '' ).trim().toLowerCase();
		const parsed = Number.parseFloat( string );

		if ( ! Number.isFinite( parsed ) || 0 > parsed ) {
			return fallback;
		}

		return string.endsWith( 'ms' ) ? parsed : ( string.endsWith( 's' ) ? parsed * 1000 : parsed );
	}

	/**
	 * Get direct gallery items from a track.
	 *
	 * @param {HTMLElement} track Gallery track.
	 * @return {HTMLElement[]}
	 */
	function getTrackItems( track ) {
		return Array.from( track.querySelectorAll( ':scope > .mai-cyber-gallery-item' ) );
	}

	/**
	 * Schedule relayouts when newly discovered images finish loading.
	 *
	 * @param {Object}      state    Gallery state.
	 * @param {HTMLElement} track    Gallery track.
	 * @param {Function}    callback Relayout callback.
	 * @return {void}
	 */
	function watchImages( state, track, callback ) {
		state.images = state.images || new WeakSet();

		track.querySelectorAll( 'img' ).forEach( function ( image ) {
			if ( state.images.has( image ) ) {
				return;
			}

			state.images.add( image );

			if ( ! image.complete ) {
				image.addEventListener( 'load', callback, { once: true } );
			}
		} );
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
	 * Get the indexes which occupy most of their visible filmstrip cell.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {number[]}
	 */
	function getFilmstripVisibleIndexes( state ) {
		const bounds = state.track.getBoundingClientRect();
		const visible = [];

		state.items.forEach( function ( item, index ) {
			const rect = item.getBoundingClientRect();
			const overlap = Math.min( rect.right, bounds.right ) - Math.max( rect.left, bounds.left );

			if ( overlap > Math.min( rect.width, bounds.width ) * 0.5 ) {
				visible.push( index );
			}
		} );

		return visible;
	}

	/**
	 * Format the currently visible Filmstrip range.
	 *
	 * @param {number} current First visible item number.
	 * @param {number} total   Total item count.
	 * @return {string}
	 */
	function formatFilmstripPosition( current, total ) {
		const template = String(
			settings.filmstripPosition || 'Image %1$d of %2$d'
		);

		return template
			.replace( /%1\$d/g, String( current ) )
			.replace( /%2\$d/g, String( total ) );
	}

	/**
	 * Update filmstrip control states.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function updateFilmstrip( state ) {
		state.items = getTrackItems( state.track );

		if ( ! state.items.length ) {
			state.canMove = false;
			state.root.classList.remove( 'is-cyber-filmstrip-scrollable' );
			if ( state.previous ) {
				state.previous.disabled = true;
			}
			if ( state.next ) {
				state.next.disabled = true;
			}
			return;
		}

		const bounds = state.track.getBoundingClientRect();
		const atStart = isFullyVisible( state.items[ 0 ], bounds );
		const atEnd = isFullyVisible( state.items[ state.items.length - 1 ], bounds );
		const visible = getFilmstripVisibleIndexes( state );
		const firstVisible = visible.length ? visible[ 0 ] : 0;

		state.canMove = state.track.scrollWidth > state.track.clientWidth + 1;
		state.root.classList.toggle( 'is-cyber-filmstrip-scrollable', state.canMove );
		if ( state.previous ) {
			state.previous.disabled = atStart;
		}
		if ( state.next ) {
			state.next.disabled = atEnd;
		}
		if ( state.status ) {
			const status = formatFilmstripPosition(
				firstVisible + 1,
				state.items.length
			);

			if ( state.status.textContent !== status ) {
				state.status.textContent = status;
			}
		}
	}

	/**
	 * Get the scroll position which aligns one Filmstrip item to the leading edge.
	 *
	 * @param {Object}      state Filmstrip state.
	 * @param {HTMLElement} item  Filmstrip item.
	 * @return {number}
	 */
	function getFilmstripItemScrollLeft( state, item ) {
		const trackBounds = state.track.getBoundingClientRect();
		const itemBounds = item.getBoundingClientRect();
		const delta = itemBounds.left - trackBounds.left;
		const target = state.track.scrollLeft + delta;
		const maximum = Math.max( 0, state.track.scrollWidth - state.track.clientWidth );
		const isRtl = state.view.getComputedStyle( state.track ).direction === 'rtl';

		return isRtl
			? Math.max( -maximum, Math.min( 0, target ) )
			: Math.max( 0, Math.min( maximum, target ) );
	}

	/**
	 * Clamp a Filmstrip scroll position while retaining modern RTL coordinates.
	 *
	 * @param {Object} state Filmstrip state.
	 * @param {number} value Candidate scroll position.
	 * @return {number}
	 */
	function clampFilmstripScrollLeft( state, value ) {
		const maximum = Math.max( 0, state.track.scrollWidth - state.track.clientWidth );
		const isRtl = state.view.getComputedStyle( state.track ).direction === 'rtl';

		return isRtl
			? Math.max( -maximum, Math.min( 0, value ) )
			: Math.max( 0, Math.min( maximum, value ) );
	}

	/**
	 * Render scroll, edge stretch, and velocity tilt for Filmstrip motion.
	 *
	 * @param {Object} state    Filmstrip state.
	 * @param {number} position Virtual scroll position.
	 * @param {number} velocity Scroll velocity in pixels per ms.
	 * @return {void}
	 */
	function renderFilmstripMotion( state, position, velocity ) {
		const clamped = clampFilmstripScrollLeft( state, position );
		const edgeOffset = state.motionQuery.matches
			? 0
			: Math.max( -16, Math.min( 16, ( clamped - position ) * 0.14 ) );
		const tilt = state.motionQuery.matches
			? 0
			: Math.max( -0.45, Math.min( 0.45, velocity * -0.12 ) );

		state.motionPosition = position;
		state.track.scrollLeft = clamped;
		state.track.style.setProperty( '--cyber-filmstrip-rubber-band', edgeOffset.toFixed( 3 ) + 'px' );
		state.track.style.setProperty( '--cyber-filmstrip-motion-tilt', tilt.toFixed( 3 ) + 'deg' );
	}

	/**
	 * Clear Filmstrip-only motion presentation.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function clearFilmstripMotionStyles( state ) {
		state.motionPosition = null;
		state.track.style.removeProperty( '--cyber-filmstrip-rubber-band' );
		state.track.style.removeProperty( '--cyber-filmstrip-motion-tilt' );
	}

	/**
	 * Stop a Filmstrip spring animation.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function cancelFilmstripMotion( state ) {
		if ( state.motionFrame ) {
			state.view.cancelAnimationFrame( state.motionFrame );
			state.motionFrame = 0;
		}

		state.root.classList.remove(
			'is-cyber-filmstrip-gliding',
			'is-cyber-filmstrip-settling'
		);
		clearFilmstripMotionStyles( state );
	}

	/**
	 * Finish one programmatic Filmstrip movement.
	 *
	 * @param {Object} state  Filmstrip state.
	 * @param {number} target Target scroll position.
	 * @return {void}
	 */
	function completeFilmstripMotion( state, target ) {
		state.track.scrollLeft = target;
		state.motionFrame = 0;
		state.root.classList.remove(
			'is-cyber-filmstrip-gliding',
			'is-cyber-filmstrip-settling'
		);
		clearFilmstripMotionStyles( state );
		updateFilmstrip( state );
		syncFilmstripAutoplay( state );
	}

	/**
	 * Move controls and autoplay with a restrained, no-overshoot easing.
	 *
	 * @param {Object} state  Filmstrip state.
	 * @param {number} target Target scroll position.
	 * @return {void}
	 */
	function glideFilmstripTo( state, target ) {
		const startingPosition = state.track.scrollLeft;
		const distance = target - startingPosition;

		cancelFilmstripMotion( state );

		if ( state.motionQuery.matches || 1 > Math.abs( distance ) ) {
			completeFilmstripMotion( state, target );
			return;
		}

		const startedAt = state.view.performance.now();
		const duration = Math.min( 650, Math.max( 420, Math.abs( distance ) * 0.45 ) );

		state.root.classList.add( 'is-cyber-filmstrip-gliding' );

		const glide = function ( time ) {
			const progress = Math.min( 1, Math.max( 0, ( time - startedAt ) / duration ) );
			const eased = 0.5 > progress
				? 4 * progress * progress * progress
				: 1 - Math.pow( -2 * progress + 2, 3 ) / 2;
			const position = startingPosition + distance * eased;

			state.motionPosition = position;
			state.track.scrollLeft = clampFilmstripScrollLeft( state, position );

			if ( 1 <= progress ) {
				completeFilmstripMotion( state, target );
				return;
			}

			state.motionFrame = state.view.requestAnimationFrame( glide );
		};

		state.motionFrame = state.view.requestAnimationFrame( glide );
	}

	/**
	 * Throw a manually dragged Filmstrip and let it visibly overshoot and settle.
	 *
	 * @param {Object} state           Filmstrip state.
	 * @param {number} target          Target scroll position.
	 * @param {number} initialVelocity Starting scroll velocity in pixels per ms.
	 * @return {void}
	 */
	function throwFilmstripTo( state, target, initialVelocity ) {
		const startingPosition = Number.isFinite( state.motionPosition )
			? state.motionPosition
			: state.track.scrollLeft;

		cancelFilmstripMotion( state );

		if ( state.motionQuery.matches ) {
			completeFilmstripMotion( state, target );
			return;
		}

		let position = startingPosition;
		let velocity = Number.isFinite( initialVelocity ) ? initialVelocity : 0;
		let previousTime = state.view.performance.now();
		const startedAt = previousTime;
		const startingDistance = target - position;

		if ( 0.04 > Math.abs( velocity ) && 1 < Math.abs( startingDistance ) ) {
			velocity = Math.sign( startingDistance ) * Math.min(
				0.9,
				Math.max( 0.3, Math.abs( startingDistance ) * 0.0028 )
			);
		}

		state.root.classList.add( 'is-cyber-filmstrip-settling' );
		renderFilmstripMotion( state, position, velocity );

		const settle = function ( time ) {
			const elapsed = Math.min( 32, Math.max( 1, time - previousTime ) );
			const distance = target - position;

			previousTime = time;
			velocity += distance * 0.00062 * elapsed;
			velocity *= Math.pow( 0.79, elapsed / 16.667 );
			position += velocity * elapsed;
			renderFilmstripMotion( state, position, velocity );

			if (
				( 0.4 > Math.abs( target - position ) && 0.015 > Math.abs( velocity ) )
				|| 900 < time - startedAt
			) {
				completeFilmstripMotion( state, target );
				return;
			}

			state.motionFrame = state.view.requestAnimationFrame( settle );
		};

		state.motionFrame = state.view.requestAnimationFrame( settle );
	}

	/**
	 * Find the closest Filmstrip snap position to a projected scroll position.
	 *
	 * @param {Object} state     Filmstrip state.
	 * @param {number} projected Projected scroll position.
	 * @return {number}
	 */
	function getFilmstripSnapPosition( state, projected ) {
		let closest = state.track.scrollLeft;
		let distance = Number.POSITIVE_INFINITY;

		state.items.forEach( function ( item ) {
			const target = getFilmstripItemScrollLeft( state, item );
			const currentDistance = Math.abs( target - projected );

			if ( currentDistance < distance ) {
				closest = target;
				distance = currentDistance;
			}
		} );

		return closest;
	}

	/**
	 * Finish a mouse or pen Filmstrip drag and spring to the nearest item.
	 *
	 * @param {Object}       state Filmstrip state.
	 * @param {PointerEvent} event Pointer event.
	 * @return {void}
	 */
	function finishFilmstripDrag( state, event ) {
		if ( null === state.dragPointerId || event.pointerId !== state.dragPointerId ) {
			return;
		}

		if ( state.track.hasPointerCapture && state.track.hasPointerCapture( event.pointerId ) ) {
			state.track.releasePointerCapture( event.pointerId );
		}

		const moved = state.dragMoved;
		const idleTime = Math.max( 0, event.timeStamp - state.dragLastTime );
		const measuredVelocity = state.dragVelocity * Math.max( 0, 1 - idleTime / 120 );
		const velocity = Math.max( -2, Math.min( 2, measuredVelocity ) );

		state.dragPointerId = null;
		state.dragMoved = false;
		state.root.classList.remove( 'is-cyber-filmstrip-dragging' );

		if ( ! moved ) {
			return;
		}

		state.suppressClick = true;
		state.view.setTimeout( function () {
			state.suppressClick = false;
		}, 0 );

		const projected = state.track.scrollLeft + velocity * 150;
		const target = getFilmstripSnapPosition( state, projected );
		throwFilmstripTo( state, target, velocity * 0.55 );
	}

	/**
	 * Scroll a filmstrip item into the leading edge of its track.
	 *
	 * @param {Object} state       Filmstrip state.
	 * @param {number} targetIndex Item index.
	 * @return {void}
	 */
	function scrollFilmstripTo( state, targetIndex ) {
		const target = state.items[ targetIndex ];

		if ( ! target ) {
			return;
		}

		const targetScrollLeft = getFilmstripItemScrollLeft( state, target );

		glideFilmstripTo( state, targetScrollLeft );
	}

	/**
	 * Resolve a Filmstrip movement to its target item index.
	 *
	 * @param {number}   itemCount      Total items.
	 * @param {number[]} visibleIndexes Mostly visible item indexes.
	 * @param {number}   direction      -1 or 1.
	 * @param {boolean}  wrap           Whether a forward boundary wraps.
	 * @param {number}   itemStep       Fixed item count; zero uses a visible group.
	 * @return {number}
	 */
	function getFilmstripMoveTargetIndex(
		itemCount,
		visibleIndexes,
		direction,
		wrap,
		itemStep = 0
	) {
		if ( 1 > itemCount ) {
			return -1;
		}

		const first = visibleIndexes.length ? visibleIndexes[0] : 0;
		const last = visibleIndexes.length
			? visibleIndexes[ visibleIndexes.length - 1 ]
			: first;
		const lastIndex = itemCount - 1;

		if ( wrap && 0 < direction && last >= lastIndex ) {
			return 0;
		}

		const step = positiveInteger( itemStep, 0 );

		if ( step ) {
			return Math.max( 0, Math.min( lastIndex, first + direction * step ) );
		}

		return 0 < direction
			? Math.min( lastIndex, last + 1 )
			: Math.max( 0, first - Math.max( 1, visibleIndexes.length ) );
	}

	/**
	 * Move a filmstrip by its visible group or a fixed number of items.
	 *
	 * @param {Object}  state     Filmstrip state.
	 * @param {number}  direction -1 or 1.
	 * @param {boolean} wrap      Whether a forward boundary wraps to the start.
	 * @param {number}  itemStep  Fixed item count; zero retains group navigation.
	 * @return {void}
	 */
	function moveFilmstrip( state, direction, wrap, itemStep = 0 ) {
		state.items = getTrackItems( state.track );

		if ( ! state.items.length ) {
			return;
		}

		const visible = getFilmstripVisibleIndexes( state );
		const targetIndex = getFilmstripMoveTargetIndex(
			state.items.length,
			visible,
			direction,
			wrap,
			itemStep
		);

		scrollFilmstripTo( state, targetIndex );
		scheduleFrame( state, function () {
			updateFilmstrip( state );
			syncFilmstripAutoplay( state );
		} );
	}

	/**
	 * Whether this filmstrip may currently rotate automatically.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {boolean}
	 */
	function canFilmstripAutoplay( state ) {
		const focusPauses = state.focused && state.document.activeElement !== state.autoplayToggle;

		return 0 < state.autoplayInterval
			&& 1 < state.items.length
			&& state.canMove
			&& ! state.autoplayPaused
			&& null === state.dragPointerId
			&& ! state.motionFrame
			&& ! state.motionQuery.matches
			&& ! state.document.hidden
			&& state.inViewport
			&& ! focusPauses
			&& ! ( state.pauseOnHover && state.hovered );
	}

	/**
	 * Clear one filmstrip autoplay timeout.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function clearFilmstripAutoplay( state ) {
		if ( state.autoplayTimer ) {
			state.view.clearTimeout( state.autoplayTimer );
			state.autoplayTimer = 0;
		}
	}

	/**
	 * Synchronize Filmstrip autoplay state and its accessible toggle.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function syncFilmstripAutoplay( state ) {
		const available = 0 < state.autoplayInterval
			&& 1 < state.items.length
			&& state.canMove;
		const rotationEnabled = available && ! state.autoplayPaused && ! state.motionQuery.matches;

		if ( state.autoplayToggle ) {
			state.autoplayToggle.hidden = ! available || state.motionQuery.matches;
			state.autoplayToggle.dataset.cyberFilmstripPaused = state.autoplayPaused ? 'true' : 'false';
			state.autoplayToggle.setAttribute(
				'aria-label',
				state.autoplayPaused
					? String( settings.startFilmstrip || 'Start automatic filmstrip' )
					: String( settings.pauseFilmstrip || 'Pause automatic filmstrip' )
			);
		}

		if ( state.status ) {
			state.status.setAttribute( 'aria-live', rotationEnabled ? 'off' : 'polite' );
		}
		clearFilmstripAutoplay( state );
		if ( ! canFilmstripAutoplay( state ) ) {
			return;
		}

		state.autoplayTimer = state.view.setTimeout( function () {
			state.autoplayTimer = 0;
			if ( ! canFilmstripAutoplay( state ) ) {
				syncFilmstripAutoplay( state );
				return;
			}

			moveFilmstrip( state, 1, true, state.autoplayItems );
		}, state.autoplayInterval );
	}

	/**
	 * Set the user-controlled Filmstrip pause state.
	 *
	 * @param {Object}  state  Filmstrip state.
	 * @param {boolean} paused Whether rotation is paused.
	 * @return {void}
	 */
	function setFilmstripAutoplayPaused( state, paused ) {
		state.autoplayPaused = paused;
		syncFilmstripAutoplay( state );
	}

	/**
	 * Release timers and observers owned only by Filmstrip.
	 *
	 * @param {Object} state Filmstrip state.
	 * @return {void}
	 */
	function cleanupFilmstripRuntime( state ) {
		clearFilmstripAutoplay( state );
		cancelFilmstripMotion( state );

		if ( state.intersectionObserver ) {
			state.intersectionObserver.disconnect();
		}
		if ( state.motionQuery && state.motionHandler ) {
			state.motionQuery.removeEventListener( 'change', state.motionHandler );
		}

		state.root.classList.remove(
			'is-cyber-filmstrip-scrollable',
			'is-cyber-filmstrip-dragging',
			'is-cyber-filmstrip-gliding',
			'is-cyber-filmstrip-settling'
		);
	}

	/**
	 * Initialize one filmstrip.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeFilmstrip( root ) {
		const ownerDocument = getOwnerDocument( root );
		const view = getOwnerView( root );
		const track = root.querySelector( '.mai-cyber-gallery' );
		const previous = root.querySelector( '[data-cyber-filmstrip-previous]' );
		const next = root.querySelector( '[data-cyber-filmstrip-next]' );
		const autoplayToggle = root.querySelector( '[data-cyber-filmstrip-autoplay-toggle]' );
		const status = root.querySelector( '[data-cyber-filmstrip-status]' );
		const items = track ? getTrackItems( track ) : [];
		const existing = filmstripStates.get( root );

		if ( existing ) {
			if (
				existing.track === track
				&& existing.previous === previous
				&& existing.next === next
				&& existing.autoplayToggle === autoplayToggle
				&& existing.status === status
				&& 2 <= items.length
			) {
				existing.items = items;
				existing.autoplayInterval = Math.max(
					0,
					Number.parseInt( root.dataset.cyberFilmstripAutoplay || '0', 10 ) || 0
				);
				existing.autoplayItems = positiveInteger(
					root.dataset.cyberFilmstripAutoplayItems,
					1
				);
				existing.pauseOnHover = 'true' === root.dataset.cyberFilmstripPauseHover;
				scheduleFrame( existing, function () {
					updateFilmstrip( existing );
					syncFilmstripAutoplay( existing );
				} );
				return;
			}

			cleanupFilmstripRuntime( existing );
			if ( existing.frame ) {
				existing.view.cancelAnimationFrame( existing.frame );
			}

			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}
			if ( existing.controller ) {
				existing.controller.abort();
			}

			filmstripStates.delete( root );
			root.classList.remove( 'is-cyber-filmstrip-ready' );
		}

		if ( ! track || 2 > items.length ) {
			return;
		}

		const rootBounds = root.getBoundingClientRect();
		const state = {
			document: ownerDocument,
			view: view,
			root: root,
			track: track,
			previous: previous,
			next: next,
			autoplayToggle: autoplayToggle,
			status: status,
			items: items,
			frame: 0,
			motionFrame: 0,
			motionPosition: null,
			resizeObserver: null,
			intersectionObserver: null,
			controller: 'AbortController' in view ? new view.AbortController() : null,
			autoplayInterval: Math.max(
				0,
				Number.parseInt( root.dataset.cyberFilmstripAutoplay || '0', 10 ) || 0
			),
			autoplayItems: positiveInteger(
				root.dataset.cyberFilmstripAutoplayItems,
				1
			),
			pauseOnHover: 'true' === root.dataset.cyberFilmstripPauseHover,
			autoplayTimer: 0,
			autoplayPaused: false,
			autoplayPointerFocus: false,
			hovered: false,
			focused: false,
			inViewport: 0 < rootBounds.bottom && rootBounds.top < view.innerHeight,
			canMove: true,
			dragPointerId: null,
			dragStartX: 0,
			dragStartY: 0,
			dragStartScrollLeft: 0,
			dragLastPosition: 0,
			dragLastTime: 0,
			dragVelocity: 0,
			dragMoved: false,
			suppressClick: false,
			motionQuery: view.matchMedia( '(prefers-reduced-motion: reduce)' ),
			motionHandler: null,
		};

		filmstripStates.set( root, state );

		const scheduleUpdate = function () {
			scheduleFrame( state, function () {
				updateFilmstrip( state );
				syncFilmstripAutoplay( state );
			} );
		};
		const listenerOptions = state.controller
			? { signal: state.controller.signal }
			: undefined;
		const passiveOptions = state.controller
			? { passive: true, signal: state.controller.signal }
			: { passive: true };
		const captureOptions = state.controller
			? { capture: true, signal: state.controller.signal }
			: { capture: true };

		watchImages( state, track, scheduleUpdate );

		if ( previous ) {
			previous.addEventListener( 'click', function () {
				setFilmstripAutoplayPaused( state, true );
				moveFilmstrip( state, -1, false );
			}, listenerOptions );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				setFilmstripAutoplayPaused( state, true );
				moveFilmstrip( state, 1, false );
			}, listenerOptions );
		}

		if ( autoplayToggle ) {
			autoplayToggle.addEventListener( 'pointerdown', function () {
				state.autoplayPointerFocus = true;
				state.view.setTimeout( function () {
					state.autoplayPointerFocus = false;
				}, 0 );
			}, listenerOptions );
			autoplayToggle.addEventListener( 'click', function () {
				setFilmstripAutoplayPaused( state, ! state.autoplayPaused );
			}, listenerOptions );
		}

		track.addEventListener( 'scroll', scheduleUpdate, passiveOptions );
		track.addEventListener( 'pointerdown', function ( event ) {
			setFilmstripAutoplayPaused( state, true );

			if (
				( 'mouse' !== event.pointerType
					&& 'pen' !== event.pointerType
					&& 'touch' !== event.pointerType )
				|| ! event.isPrimary
				|| 0 !== event.button
			) {
				return;
			}

			cancelFilmstripMotion( state );
			state.dragPointerId = event.pointerId;
			state.dragStartX = event.clientX;
			state.dragStartY = event.clientY;
			state.dragStartScrollLeft = track.scrollLeft;
			state.dragLastPosition = track.scrollLeft;
			state.dragLastTime = event.timeStamp;
			state.dragVelocity = 0;
			state.dragMoved = false;
			track.setPointerCapture( event.pointerId );
		}, listenerOptions );
		track.addEventListener( 'pointermove', function ( event ) {
			if ( null === state.dragPointerId || event.pointerId !== state.dragPointerId ) {
				return;
			}

			const distance = event.clientX - state.dragStartX;
			const verticalDistance = event.clientY - state.dragStartY;

			if ( ! state.dragMoved ) {
				if (
					'touch' === event.pointerType
					&& Math.abs( verticalDistance ) > Math.abs( distance )
				) {
					return;
				}

				if ( 4 > Math.abs( distance ) ) {
					return;
				}
			}

			event.preventDefault();
			state.dragMoved = true;
			state.root.classList.add( 'is-cyber-filmstrip-dragging' );
			const position = state.dragStartScrollLeft - distance;

			const elapsed = Math.max( 1, event.timeStamp - state.dragLastTime );
			const instantVelocity = ( position - state.dragLastPosition ) / elapsed;
			state.dragVelocity = state.dragVelocity * 0.32 + instantVelocity * 0.68;
			state.dragLastPosition = position;
			state.dragLastTime = event.timeStamp;
			renderFilmstripMotion( state, position, state.dragVelocity );
		}, listenerOptions );
		track.addEventListener( 'pointerup', function ( event ) {
			finishFilmstripDrag( state, event );
		}, listenerOptions );
		track.addEventListener( 'pointercancel', function ( event ) {
			finishFilmstripDrag( state, event );
		}, listenerOptions );
		track.addEventListener( 'dragstart', function ( event ) {
			if ( null !== state.dragPointerId ) {
				event.preventDefault();
			}
		}, listenerOptions );
		track.addEventListener( 'click', function ( event ) {
			if ( state.suppressClick ) {
				event.preventDefault();
				event.stopPropagation();
			}
		}, captureOptions );
		track.addEventListener( 'wheel', function () {
			setFilmstripAutoplayPaused( state, true );
		}, passiveOptions );
		track.addEventListener( 'keydown', function ( event ) {
			if ( event.target.closest( 'a, button, input, select, textarea' ) ) {
				return;
			}

			const isRtl = state.view.getComputedStyle( track ).direction === 'rtl';
			let direction = 0;

			if ( 'ArrowRight' === event.key ) {
				direction = isRtl ? -1 : 1;
			} else if ( 'ArrowLeft' === event.key ) {
				direction = isRtl ? 1 : -1;
			} else if ( 'Home' === event.key ) {
				setFilmstripAutoplayPaused( state, true );
				scrollFilmstripTo( state, 0 );
				event.preventDefault();
				return;
			} else if ( 'End' === event.key ) {
				setFilmstripAutoplayPaused( state, true );
				scrollFilmstripTo( state, state.items.length - 1 );
				event.preventDefault();
				return;
			}

			if ( direction ) {
				event.preventDefault();
				setFilmstripAutoplayPaused( state, true );
				moveFilmstrip( state, direction, false );
			}
		}, listenerOptions );

		root.addEventListener( 'pointerenter', function ( event ) {
			if ( 'mouse' === event.pointerType || 'pen' === event.pointerType ) {
				state.hovered = true;
				syncFilmstripAutoplay( state );
			}
		}, listenerOptions );
		root.addEventListener( 'pointerleave', function ( event ) {
			if ( 'mouse' === event.pointerType || 'pen' === event.pointerType ) {
				state.hovered = false;
				syncFilmstripAutoplay( state );
			}
		}, listenerOptions );
		root.addEventListener( 'pointerup', function () {
			state.autoplayPointerFocus = false;
		}, listenerOptions );
		root.addEventListener( 'pointercancel', function () {
			state.autoplayPointerFocus = false;
		}, listenerOptions );
		root.addEventListener( 'focusin', function ( event ) {
			state.focused = true;

			if ( event.target === autoplayToggle && state.autoplayPointerFocus ) {
				syncFilmstripAutoplay( state );
			} else {
				setFilmstripAutoplayPaused( state, true );
			}
		}, listenerOptions );
		root.addEventListener( 'focusout', function ( event ) {
			if ( ! root.contains( event.relatedTarget ) ) {
				state.focused = false;
				syncFilmstripAutoplay( state );
			}
		}, listenerOptions );
		state.document.addEventListener( 'visibilitychange', function () {
			syncFilmstripAutoplay( state );
		}, listenerOptions );

		state.motionHandler = function () {
			if ( state.motionQuery.matches && state.motionFrame ) {
				const target = getFilmstripSnapPosition( state, state.track.scrollLeft );
				cancelFilmstripMotion( state );
				state.track.scrollLeft = target;
				updateFilmstrip( state );
			}
			syncFilmstripAutoplay( state );
		};
		state.motionQuery.addEventListener( 'change', state.motionHandler );

		if ( 'ResizeObserver' in state.view ) {
			state.resizeObserver = new state.view.ResizeObserver( scheduleUpdate );
			state.resizeObserver.observe( track );
		}

		if ( 'IntersectionObserver' in state.view ) {
			state.intersectionObserver = new state.view.IntersectionObserver( function ( entries ) {
				const entry = entries[0];
				state.inViewport = Boolean( entry && entry.isIntersecting && 0.1 <= entry.intersectionRatio );
				syncFilmstripAutoplay( state );
			}, { threshold: [ 0, 0.1 ] } );
			state.intersectionObserver.observe( root );
		}

		updateFilmstrip( state );
		root.classList.add( 'is-cyber-filmstrip-ready' );
		syncFilmstripAutoplay( state );
	}

	/**
	 * Format a localized slider position.
	 *
	 * @param {number} current Current slide number.
	 * @param {number} total   Total slides.
	 * @return {string}
	 */
	function formatSlidePosition( current, total ) {
		return String( settings.slide || 'Slide %1$d of %2$d' )
			.replace( /%1\$d/g, String( current ) )
			.replace( /%2\$d/g, String( total ) );
	}

	/**
	 * Format a localized slider position with its visible title.
	 *
	 * @param {number} current Current slide number.
	 * @param {number} total   Total slides.
	 * @param {string} title   Visible slide title.
	 * @return {string}
	 */
	function formatSlideStatus( current, total, title ) {
		if ( ! title ) {
			return formatSlidePosition( current, total );
		}

		return String( settings.slideTitle || 'Slide %1$d of %2$d: %3$s' )
			.replace( /%1\$d/g, String( current ) )
			.replace( /%2\$d/g, String( total ) )
			.replace( /%3\$s/g, function () {
				return title;
			} );
	}

	/**
	 * Find the slide closest to the track center.
	 *
	 * @param {Object} state Slider state.
	 * @return {number}
	 */
	function getSliderIndex( state ) {
		const bounds = state.track.getBoundingClientRect();
		const center = bounds.left + bounds.width / 2;
		let closest = 0;
		let distance = Number.POSITIVE_INFINITY;

		state.items.forEach( function ( item, index ) {
			const rect = item.getBoundingClientRect();
			const currentDistance = Math.abs( rect.left + rect.width / 2 - center );

			if ( currentDistance < distance ) {
				closest = index;
				distance = currentDistance;
			}
		} );

		return closest;
	}

	/**
	 * Update the live slider status text.
	 *
	 * @param {Object} state Slider state.
	 * @return {void}
	 */
	function updateSliderStatus( state ) {
		if ( ! state.status || ! state.items[ state.index ] ) {
			return;
		}

		const status = formatSlideStatus(
			state.index + 1,
			state.items.length,
			state.items[ state.index ].dataset.cyberSlideTitle || ''
		);

		if ( state.status.textContent !== status ) {
			state.status.textContent = status;
		}
	}

	/**
	 * Commit active slide semantics and control state.
	 *
	 * @param {Object}  state        Slider state.
	 * @param {number}  index        Zero-based slide index.
	 * @param {boolean} updateStatus Whether to update the live status.
	 * @return {void}
	 */
	function commitSliderIndex( state, index, updateStatus ) {
		const previousIndex = state.index;
		const total = state.items.length;
		const focusedControl = state.document.activeElement;

		state.index = Math.max( 0, Math.min( total - 1, index ) );

		if ( state.navigationTarget === state.index ) {
			state.navigationTarget = null;
			state.intentIndex = state.index;
		} else if (
			null === state.navigationTarget
			&& ! state.transitionLayer
			&& ! state.pendingSource
		) {
			state.intentIndex = state.index;
		}

		state.root.dataset.cyberSliderIndex = String( state.index + 1 );

		if ( state.previous ) {
			state.previous.disabled = 0 === state.index;
		}

		if ( state.next ) {
			state.next.disabled = state.index === total - 1;
		}

		if (
			focusedControl === state.previous
			&& state.previous.disabled
			&& state.next
			&& ! state.next.disabled
		) {
			state.next.focus( { preventScroll: true } );
		} else if (
			focusedControl === state.next
			&& state.next.disabled
			&& state.previous
			&& ! state.previous.disabled
		) {
			state.previous.focus( { preventScroll: true } );
		}

		state.dots.forEach( function ( dot, dotIndex ) {
			if ( dotIndex === state.index ) {
				dot.setAttribute( 'aria-current', 'true' );
			} else {
				dot.removeAttribute( 'aria-current' );
			}
		} );

		const activeDot = state.dots[ state.index ];
		if ( activeDot && previousIndex !== state.index ) {
			const rail = activeDot.parentElement;
			const railBounds = rail.getBoundingClientRect();
			const dotBounds = activeDot.getBoundingClientRect();
			const delta = dotBounds.left < railBounds.left
				? dotBounds.left - railBounds.left
				: ( dotBounds.right > railBounds.right ? dotBounds.right - railBounds.right : 0 );

			if ( delta ) {
				rail.scrollBy( { left: delta, behavior: 'auto' } );
			}
		}

		const counter = String( state.index + 1 );
		if ( state.counter && state.counter.textContent !== counter ) {
			state.counter.textContent = counter;
		}

		const focusedItem = state.items.find( function ( item ) {
			return item.contains( state.document.activeElement );
		} );

		if ( focusedItem && focusedItem !== state.items[ state.index ] ) {
			state.track.focus( { preventScroll: true } );
		}

		state.items.forEach( function ( item, itemIndex ) {
			const active = itemIndex === state.index;

			item.inert = ! active;
			item.classList.toggle( 'is-cyber-slide-active', active );

			if ( active ) {
				item.removeAttribute( 'aria-hidden' );
			} else {
				item.setAttribute( 'aria-hidden', 'true' );
			}
		} );

		if ( false !== updateStatus && previousIndex !== state.index ) {
			updateSliderStatus( state );
		}
	}

	/**
	 * Update active slider state from intrinsic scroll position.
	 *
	 * @param {Object} state Slider state.
	 * @return {void}
	 */
	function updateSlider( state ) {
		if ( state.suppressUpdate ) {
			return;
		}

		state.items = getTrackItems( state.track );

		if ( ! state.items.length ) {
			return;
		}

		const previousIndex = state.index;
		commitSliderIndex( state, getSliderIndex( state ), true );

		if ( previousIndex !== state.index ) {
			syncSliderAutoplay( state );
		}
	}

	/**
	 * Remove duplicate IDs and interaction from a visual transition clone.
	 *
	 * @param {HTMLElement} item  Live slide.
	 * @param {string}      stateClass Visual state class.
	 * @return {HTMLElement}
	 */
	function cloneSliderItem( item, stateClass ) {
		const clone = item.cloneNode( true );

		clone.removeAttribute( 'id' );
		clone.querySelectorAll( '[id]' ).forEach( function ( element ) {
			element.removeAttribute( 'id' );
		} );
		clone.classList.remove( 'is-cyber-slide-active' );
		clone.classList.add( stateClass );
		clone.setAttribute( 'aria-hidden', 'true' );
		clone.inert = true;

		return clone;
	}

	/**
	 * Promote a LiteSpeed-lazy image when its slide becomes the navigation target.
	 *
	 * LiteSpeed keeps the real source in data attributes until its observer runs.
	 * Its bundled lazy-loader exposes a guarded static load method; the attribute
	 * fallback also covers navigation before that library has initialized.
	 *
	 * @param {HTMLImageElement} image Target image.
	 * @return {void}
	 */
	function requestSliderImage( image ) {
		const view = getOwnerView( image );

		image.loading = 'eager';

		if ( ! image.hasAttribute( 'data-lazyloaded' ) ) {
			return;
		}

		if (
			view.LazyLoad
			&& 'function' === typeof view.LazyLoad.load
		) {
			view.LazyLoad.load( image );
			return;
		}

		if ( image.dataset.srcset ) {
			image.srcset = image.dataset.srcset;
		}

		if ( image.dataset.sizes ) {
			image.sizes = image.dataset.sizes;
		}

		if ( image.dataset.src ) {
			image.src = image.dataset.src;
		}

		image.removeAttribute( 'data-lazyloaded' );
	}

	/**
	 * Encourage target images to decode before a visual transition starts.
	 *
	 * @param {HTMLElement} item Target slide.
	 * @return {Promise<void>}
	 */
	function prepareSliderItem( item ) {
		const view = getOwnerView( item );
		const images = Array.from( item.querySelectorAll( 'img' ) );

		if ( ! images.length ) {
			return Promise.resolve();
		}

		const decoding = images.map( function ( image ) {
			requestSliderImage( image );

			if ( image.complete && image.naturalWidth ) {
				return Promise.resolve();
			}

			if ( image.decode ) {
				return image.decode().catch( function () {} );
			}

			return new Promise( function ( resolve ) {
				image.addEventListener( 'load', resolve, { once: true } );
				image.addEventListener( 'error', resolve, { once: true } );
			} );
		} );

		return Promise.race( [
			Promise.all( decoding ).then( function () {} ),
			new Promise( function ( resolve ) {
				view.setTimeout( resolve, 1200 );
			} ),
		] );
	}

	/**
	 * Invalidate an asynchronous slide-image preparation request.
	 *
	 * @param {Object}  state         Slider state.
	 * @param {boolean} restoreIntent Reset requested navigation to the live slide.
	 * @return {void}
	 */
	function cancelSliderPreparation( state, restoreIntent ) {
		state.preloadToken++;
		state.pendingSource = null;
		state.pendingTarget = null;
		state.pendingStatus = true;

		if ( restoreIntent ) {
			state.intentIndex = Math.max( 0, state.index );
		}
	}

	/**
	 * Jump the intrinsic rail without exposing horizontal motion.
	 *
	 * @param {Object} state Slider state.
	 * @param {number} index Target index.
	 * @return {void}
	 */
	function jumpSliderTrack( state, index ) {
		const target = state.items[ index ];

		if ( ! target ) {
			return;
		}

		state.suppressUpdate = true;
		const scrollBehavior = state.track.style.scrollBehavior;
		state.track.style.scrollBehavior = 'auto';
		target.scrollIntoView( {
			behavior: 'auto',
			block: 'nearest',
			inline: 'start',
		} );
		state.track.style.scrollBehavior = scrollBehavior;

		if ( state.unlockFrame ) {
			state.view.cancelAnimationFrame( state.unlockFrame );
		}
		state.unlockFrame = state.view.requestAnimationFrame( function () {
			state.unlockFrame = 0;
			state.suppressUpdate = false;
		} );
	}

	/**
	 * Finish a control-driven visual transition.
	 *
	 * @param {Object}  state        Slider state.
	 * @param {boolean} updateStatus Whether to announce the committed slide.
	 * @return {void}
	 */
	function finishSliderTransition( state, updateStatus ) {
		if ( state.transitionTimer ) {
			state.view.clearTimeout( state.transitionTimer );
			state.transitionTimer = 0;
		}
		if ( state.transitionWatchFrame ) {
			state.view.cancelAnimationFrame( state.transitionWatchFrame );
			state.transitionWatchFrame = 0;
		}

		if ( state.transitionLayer ) {
			state.transitionLayer.remove();
			state.transitionLayer = null;
		}
		state.root.classList.remove( 'is-cyber-slider-transitioning' );

		const queuedIndex = state.queuedIndex;
		const queuedStatus = state.queuedStatus;
		const queuedSource = state.queuedSource;
		state.queuedIndex = null;
		state.queuedStatus = true;
		state.queuedSource = 'manual';

		if ( null !== queuedIndex && queuedIndex !== state.index ) {
			moveSlider( state, queuedIndex, queuedStatus, queuedSource );
			return;
		}

		if ( false !== updateStatus ) {
			updateSliderStatus( state );
		}
		syncSliderAutoplay( state );
	}

	/**
	 * Start a true in-place crossfade/effect between two visual slide copies.
	 *
	 * @param {Object}  state        Slider state.
	 * @param {number}  targetIndex  Target slide.
	 * @param {boolean} updateStatus Whether to announce after the effect.
	 * @return {void}
	 */
	function startSliderTransition( state, targetIndex, updateStatus ) {
		const current = state.items[ Math.max( 0, state.index ) ];
		const target = state.items[ targetIndex ];

		if ( ! current || ! target || current === target ) {
			jumpSliderTrack( state, targetIndex );
			commitSliderIndex( state, targetIndex, updateStatus );
			syncSliderAutoplay( state );
			return;
		}

		const layer = state.document.createElement( 'div' );
		const outgoing = cloneSliderItem( current, 'is-cyber-slide-outgoing' );
		const incoming = cloneSliderItem( target, 'is-cyber-slide-incoming' );

		layer.className = 'mai-cyber-gallery-slider-transition-layer';
		layer.setAttribute( 'aria-hidden', 'true' );
		layer.inert = true;
		layer.append( outgoing, incoming );

		state.navigationTarget = null;
		state.transitionLayer = layer;
		state.root.classList.add( 'is-cyber-slider-transitioning' );
		state.root.appendChild( layer );
		jumpSliderTrack( state, targetIndex );
		commitSliderIndex( state, targetIndex, false );

		const duration = cssTimeMilliseconds(
			state.view.getComputedStyle( state.root ).getPropertyValue( '--cyber-slider-transition-duration' ),
			560
		);
		const complete = function () {
			if ( state.transitionLayer === layer ) {
				finishSliderTransition( state, updateStatus );
			}
		};

		state.transitionWatchFrame = state.view.requestAnimationFrame( function () {
			state.transitionWatchFrame = 0;

			if (
				state.transitionLayer !== layer
				|| 'cinematic' === state.transition
				|| ! layer.getAnimations
			) {
				return;
			}

			const animations = layer.getAnimations( { subtree: true } );
			if ( animations.length ) {
				Promise.all(
					animations.map( function ( animation ) {
						return animation.finished.catch( function () {} );
					} )
				).then( complete );
			}
		} );
		state.transitionTimer = state.view.setTimeout( function () {
			complete();
		}, duration + ( 'cinematic' === state.transition ? 320 : 200 ) );
	}

	/**
	 * Whether this slider currently may rotate automatically.
	 *
	 * @param {Object} state Slider state.
	 * @return {boolean}
	 */
	function canSliderAutoplay( state ) {
		const focusPauses = state.focused && state.document.activeElement !== state.autoplayToggle;

		return 0 < state.autoplayInterval
			&& 1 < state.items.length
			&& ! state.autoplayPaused
			&& ! state.transitionLayer
			&& ! state.motionQuery.matches
			&& ! state.document.hidden
			&& state.inViewport
			&& ! focusPauses
			&& ! ( state.pauseOnHover && state.hovered );
	}

	/**
	 * Clear a slider autoplay timeout.
	 *
	 * @param {Object} state Slider state.
	 * @return {void}
	 */
	function clearSliderAutoplay( state ) {
		if ( state.autoplayTimer ) {
			state.view.clearTimeout( state.autoplayTimer );
			state.autoplayTimer = 0;
		}
	}

	/**
	 * Synchronize autoplay controls, live-region behavior, and timeout.
	 *
	 * @param {Object} state Slider state.
	 * @return {void}
	 */
	function syncSliderAutoplay( state ) {
		const available = 0 < state.autoplayInterval && 1 < state.items.length;
		const canPlay = canSliderAutoplay( state );
		const rotationEnabled = available && ! state.autoplayPaused && ! state.motionQuery.matches;

		if ( 'autoplay' === state.pendingSource && ! canPlay ) {
			cancelSliderPreparation( state, true );
		}

		if ( state.autoplayToggle ) {
			state.autoplayToggle.hidden = ! available || state.motionQuery.matches;
			state.autoplayToggle.dataset.cyberSliderPaused = state.autoplayPaused ? 'true' : 'false';
			state.autoplayToggle.setAttribute(
				'aria-label',
				state.autoplayPaused
					? String( settings.startAutoplay || 'Start automatic slides' )
					: String( settings.pauseAutoplay || 'Pause automatic slides' )
			);
		}

		if ( state.status ) {
			state.status.setAttribute( 'aria-live', rotationEnabled ? 'off' : 'polite' );
		}

		clearSliderAutoplay( state );
		if ( ! canPlay || state.pendingSource ) {
			return;
		}

		state.autoplayTimer = state.view.setTimeout( function () {
			state.autoplayTimer = 0;
			if ( ! canSliderAutoplay( state ) ) {
				syncSliderAutoplay( state );
				return;
			}

			const nextIndex = ( state.intentIndex + 1 ) % state.items.length;
			moveSlider( state, nextIndex, true, 'autoplay' );
		}, state.autoplayInterval );
	}

	/**
	 * Set the user-controlled autoplay pause state.
	 *
	 * @param {Object}  state  Slider state.
	 * @param {boolean} paused Whether rotation is paused.
	 * @return {void}
	 */
	function setSliderAutoplayPaused( state, paused ) {
		state.autoplayPaused = paused;
		syncSliderAutoplay( state );
	}

	/**
	 * Move a slider to an exact slide.
	 *
	 * @param {Object}  state        Slider state.
	 * @param {number}  index        Requested zero-based index.
	 * @param {boolean} updateStatus Whether to update the live status.
	 * @param {string}  source       Navigation source: manual or autoplay.
	 * @return {void}
	 */
	function moveSlider( state, index, updateStatus, source ) {
		const targetIndex = Math.max( 0, Math.min( state.items.length - 1, index ) );
		const target = state.items[ targetIndex ];
		const moveSource = 'autoplay' === source ? 'autoplay' : 'manual';

		if ( ! target ) {
			return;
		}

		if ( state.pendingSource ) {
			cancelSliderPreparation( state, false );
		}

		clearSliderAutoplay( state );
		state.intentIndex = targetIndex;

		if ( state.transitionLayer ) {
			state.queuedIndex = targetIndex;
			state.queuedStatus = false !== updateStatus;
			state.queuedSource = moveSource;
			return;
		}

		const useTransition = 'native' !== state.transition && ! state.motionQuery.matches;
		if ( useTransition && targetIndex !== state.index ) {
			const preloadToken = ++state.preloadToken;
			state.pendingSource = moveSource;
			state.pendingTarget = targetIndex;
			state.pendingStatus = false !== updateStatus;
			prepareSliderItem( target ).then( function () {
				if (
					preloadToken !== state.preloadToken
					|| ! isRuntimeConnected( state.root )
				) {
					return;
				}

				if ( targetIndex !== state.intentIndex ) {
					cancelSliderPreparation( state, true );
					syncSliderAutoplay( state );
					return;
				}

				state.pendingSource = null;
				state.pendingTarget = null;
				state.pendingStatus = true;

				if ( state.motionQuery.matches ) {
					moveSlider( state, targetIndex, updateStatus, moveSource );
					return;
				}

				if ( 'autoplay' === moveSource && ! canSliderAutoplay( state ) ) {
					state.intentIndex = Math.max( 0, state.index );
					syncSliderAutoplay( state );
					return;
				}

				startSliderTransition( state, targetIndex, false !== updateStatus );
			} );
			return;
		}

		state.navigationTarget = targetIndex === state.index ? null : targetIndex;
		const adjacent = Math.abs( targetIndex - state.index ) <= 1;
		const behavior = state.motionQuery.matches || ! adjacent || 'native' !== state.transition
			? 'auto'
			: 'smooth';
		const scrollBehavior = state.track.style.scrollBehavior;
		if ( 'auto' === behavior ) {
			state.track.style.scrollBehavior = 'auto';
		}
		target.scrollIntoView( {
			behavior: behavior,
			block: 'nearest',
			inline: 'start',
		} );
		state.track.style.scrollBehavior = scrollBehavior;

		scheduleFrame( state, function () {
			updateSlider( state );
			syncSliderAutoplay( state );
		} );
	}

	/**
	 * Release timers, observers, and temporary slider visuals.
	 *
	 * @param {Object} state Slider state.
	 * @return {void}
	 */
	function cleanupSliderRuntime( state ) {
		clearSliderAutoplay( state );
		cancelSliderPreparation( state, false );

		if ( state.transitionTimer ) {
			state.view.clearTimeout( state.transitionTimer );
		}
		if ( state.transitionWatchFrame ) {
			state.view.cancelAnimationFrame( state.transitionWatchFrame );
		}
		if ( state.transitionLayer ) {
			state.transitionLayer.remove();
		}
		if ( state.unlockFrame ) {
			state.view.cancelAnimationFrame( state.unlockFrame );
		}
		if ( state.intersectionObserver ) {
			state.intersectionObserver.disconnect();
		}
		if ( state.motionQuery && state.motionHandler ) {
			state.motionQuery.removeEventListener( 'change', state.motionHandler );
		}

		state.root.classList.remove( 'is-cyber-slider-transitioning' );
		state.navigationTarget = null;
		state.suppressUpdate = false;
	}

	/**
	 * Initialize one slider.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeSlider( root ) {
		const ownerDocument = getOwnerDocument( root );
		const view = getOwnerView( root );
		const track = root.querySelector( '.mai-cyber-gallery' );
		const previous = root.querySelector( '[data-cyber-slider-previous]' );
		const next = root.querySelector( '[data-cyber-slider-next]' );
		const dots = Array.from( root.querySelectorAll( '[data-cyber-slider-dot]' ) );
		const counter = root.querySelector( '[data-cyber-slider-current]' );
		const status = root.querySelector( '[data-cyber-slider-status]' );
		const autoplayToggle = root.querySelector( '[data-cyber-slider-autoplay-toggle]' );
		const items = track ? getTrackItems( track ) : [];
		const existing = sliderStates.get( root );

		if ( existing ) {
			if (
				existing.track === track
				&& existing.previous === previous
				&& existing.next === next
				&& existing.counter === counter
				&& existing.status === status
				&& existing.autoplayToggle === autoplayToggle
				&& existing.dots.length === dots.length
				&& existing.dots.every( function ( dot, index ) {
					return dot === dots[ index ];
				} )
			) {
				const transition = root.dataset.cyberSliderTransition || 'native';
				const autoplayInterval = Math.max(
					0,
					Number.parseInt( root.dataset.cyberSliderAutoplay || '0', 10 ) || 0
				);

				if (
					existing.pendingSource
					&& (
						existing.transition !== transition
						|| existing.autoplayInterval !== autoplayInterval
					)
				) {
					cancelSliderPreparation( existing, true );
				}

				existing.items = items;
				existing.transition = transition;
				existing.autoplayInterval = autoplayInterval;
				existing.pauseOnHover = 'true' === root.dataset.cyberSliderPauseHover;
				scheduleFrame( existing, function () {
					updateSlider( existing );
					syncSliderAutoplay( existing );
				} );
				return;
			}

			cleanupSliderRuntime( existing );

			if ( existing.frame ) {
				existing.view.cancelAnimationFrame( existing.frame );
			}
			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}
			if ( existing.controller ) {
				existing.controller.abort();
			}

			existing.items.forEach( function ( item ) {
				item.inert = false;
				item.classList.remove( 'is-cyber-slide-active' );
				item.removeAttribute( 'aria-hidden' );
			} );

			sliderStates.delete( root );
			root.classList.remove( 'is-cyber-slider-ready' );
		}

		if ( ! track || ! items.length ) {
			return;
		}

		const state = {
			document: ownerDocument,
			view: view,
			root: root,
			track: track,
			previous: previous,
			next: next,
			dots: dots,
			counter: counter,
			status: status,
			autoplayToggle: autoplayToggle,
			items: items,
			index: -1,
			intentIndex: 0,
			navigationTarget: null,
			frame: 0,
			unlockFrame: 0,
			suppressUpdate: false,
			resizeObserver: null,
			intersectionObserver: null,
			controller: 'AbortController' in view ? new view.AbortController() : null,
			transition: root.dataset.cyberSliderTransition || 'native',
			transitionLayer: null,
			transitionTimer: 0,
			transitionWatchFrame: 0,
			queuedIndex: null,
			queuedStatus: true,
			queuedSource: 'manual',
			preloadToken: 0,
			pendingSource: null,
			pendingTarget: null,
			pendingStatus: true,
			autoplayInterval: Math.max(
				0,
				Number.parseInt( root.dataset.cyberSliderAutoplay || '0', 10 ) || 0
			),
			pauseOnHover: 'true' === root.dataset.cyberSliderPauseHover,
			autoplayTimer: 0,
			autoplayPaused: false,
			hovered: false,
			focused: false,
			autoplayPointerFocus: false,
			inViewport: true,
			motionQuery: view.matchMedia( '(prefers-reduced-motion: reduce)' ),
			motionHandler: null,
		};

		sliderStates.set( root, state );

		const scheduleUpdate = function () {
			scheduleFrame( state, function () {
				updateSlider( state );
			} );
		};
		const listenerOptions = state.controller
			? { signal: state.controller.signal }
			: undefined;
		const passiveOptions = state.controller
			? { passive: true, signal: state.controller.signal }
			: { passive: true };

		if ( previous ) {
			previous.addEventListener( 'click', function () {
				setSliderAutoplayPaused( state, true );
				moveSlider( state, state.intentIndex - 1, true );
			}, listenerOptions );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				setSliderAutoplayPaused( state, true );
				moveSlider( state, state.intentIndex + 1, true );
			}, listenerOptions );
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				setSliderAutoplayPaused( state, true );
				moveSlider( state, Number.parseInt( dot.dataset.cyberSliderDot, 10 ) || 0, true );
			}, listenerOptions );
		} );

		if ( autoplayToggle ) {
			autoplayToggle.addEventListener( 'pointerdown', function () {
				state.autoplayPointerFocus = true;
				state.view.setTimeout( function () {
					state.autoplayPointerFocus = false;
				}, 0 );
			}, listenerOptions );
			autoplayToggle.addEventListener( 'click', function () {
				setSliderAutoplayPaused( state, ! state.autoplayPaused );
			}, listenerOptions );
		}

		track.addEventListener( 'scroll', scheduleUpdate, passiveOptions );
		track.addEventListener( 'pointerdown', function () {
			cancelSliderPreparation( state, true );
			state.navigationTarget = null;
			if ( state.transitionLayer ) {
				state.queuedIndex = null;
				finishSliderTransition( state, true );
			}
			setSliderAutoplayPaused( state, true );
		}, passiveOptions );
		track.addEventListener( 'wheel', function () {
			cancelSliderPreparation( state, true );
			state.navigationTarget = null;
			if ( state.transitionLayer ) {
				state.queuedIndex = null;
				finishSliderTransition( state, true );
			}
			setSliderAutoplayPaused( state, true );
		}, passiveOptions );
		track.addEventListener( 'keydown', function ( event ) {
			if ( event.target.closest( 'a, button, input, select, textarea' ) ) {
				return;
			}

			const isRtl = state.view.getComputedStyle( track ).direction === 'rtl';
			let target = null;

			if ( 'ArrowRight' === event.key ) {
				target = state.intentIndex + ( isRtl ? -1 : 1 );
			} else if ( 'ArrowLeft' === event.key ) {
				target = state.intentIndex + ( isRtl ? 1 : -1 );
			} else if ( 'Home' === event.key ) {
				target = 0;
			} else if ( 'End' === event.key ) {
				target = state.items.length - 1;
			}

			if ( null !== target ) {
				event.preventDefault();
				setSliderAutoplayPaused( state, true );
				moveSlider( state, target, true );
			}
		}, listenerOptions );

		root.addEventListener( 'pointerenter', function ( event ) {
			if ( 'mouse' === event.pointerType || 'pen' === event.pointerType ) {
				state.hovered = true;
				syncSliderAutoplay( state );
			}
		}, listenerOptions );
		root.addEventListener( 'pointerleave', function ( event ) {
			if ( 'mouse' === event.pointerType || 'pen' === event.pointerType ) {
				state.hovered = false;
				syncSliderAutoplay( state );
			}
		}, listenerOptions );
		root.addEventListener( 'pointerup', function () {
			state.autoplayPointerFocus = false;
		}, listenerOptions );
		root.addEventListener( 'pointercancel', function () {
			state.autoplayPointerFocus = false;
		}, listenerOptions );
		root.addEventListener( 'focusin', function ( event ) {
			state.focused = true;

			if ( event.target === autoplayToggle && state.autoplayPointerFocus ) {
				syncSliderAutoplay( state );
			} else {
				setSliderAutoplayPaused( state, true );
			}
		}, listenerOptions );
		root.addEventListener( 'focusout', function ( event ) {
			if ( ! root.contains( event.relatedTarget ) ) {
				state.focused = false;
				syncSliderAutoplay( state );
			}
		}, listenerOptions );
		state.document.addEventListener( 'visibilitychange', function () {
			syncSliderAutoplay( state );
		}, listenerOptions );

		state.motionHandler = function () {
			if ( state.motionQuery.matches && state.pendingSource ) {
				const pendingSource = state.pendingSource;
				const pendingTarget = state.pendingTarget;
				const pendingStatus = state.pendingStatus;

				cancelSliderPreparation( state, 'manual' !== pendingSource );
				if ( 'manual' === pendingSource && null !== pendingTarget ) {
					moveSlider( state, pendingTarget, pendingStatus, pendingSource );
				}
			}
			if ( state.motionQuery.matches && state.transitionLayer ) {
				finishSliderTransition( state, true );
			}
			syncSliderAutoplay( state );
		};
		state.motionQuery.addEventListener( 'change', state.motionHandler );

		if ( 'ResizeObserver' in state.view ) {
			state.resizeObserver = new state.view.ResizeObserver( scheduleUpdate );
			state.resizeObserver.observe( track );
		}

		if ( 'IntersectionObserver' in state.view ) {
			state.intersectionObserver = new state.view.IntersectionObserver( function ( entries ) {
				const entry = entries[0];
				state.inViewport = Boolean( entry && entry.isIntersecting && 0.1 <= entry.intersectionRatio );
				syncSliderAutoplay( state );
			}, { threshold: [ 0, 0.1 ] } );
			state.intersectionObserver.observe( root );
		}

		updateSlider( state );
		root.classList.add( 'is-cyber-slider-ready' );
		syncSliderAutoplay( state );
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
	 * Disconnect and remove one behavior state from a gallery.
	 *
	 * @param {HTMLElement} root       Gallery root.
	 * @param {WeakMap}     states     State map.
	 * @param {string}      readyClass Ready class to remove.
	 * @return {void}
	 */
	function clearGalleryState( root, states, readyClass ) {
		const state = states.get( root );

		if ( ! state ) {
			return;
		}

		if ( state.frame ) {
			state.view.cancelAnimationFrame( state.frame );
		}

		if ( filmstripStates === states ) {
			cleanupFilmstripRuntime( state );
		}

		if ( sliderStates === states ) {
			cleanupSliderRuntime( state );
		}

		if ( state.resizeObserver ) {
			state.resizeObserver.disconnect();
		}

		if ( state.controller ) {
			state.controller.abort();
		}

		if ( sliderStates === states && state.items ) {
			state.items.forEach( function ( item ) {
				item.inert = false;
				item.classList.remove( 'is-cyber-slide-active' );
				item.removeAttribute( 'aria-hidden' );
			} );
		}

		states.delete( root );
		root.classList.remove( readyClass );
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
			if ( root.classList.contains( 'mai-cyber-gallery-layout-filmstrip' ) ) {
				initializeFilmstrip( root );
			} else {
				clearGalleryState( root, filmstripStates, 'is-cyber-filmstrip-ready' );
			}

			if ( root.classList.contains( 'mai-cyber-gallery-layout-slider' ) ) {
				initializeSlider( root );
			} else {
				clearGalleryState( root, sliderStates, 'is-cyber-slider-ready' );
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
			[
				[ filmstripStates, 'is-cyber-filmstrip-ready' ],
				[ sliderStates, 'is-cyber-slider-ready' ],
			].forEach( function ( entry ) {
				clearGalleryState( root, entry[0], entry[1] );
			} );
		} );
	}

	/**
	 * Find the currently connected editor owner for a managed block entry.
	 *
	 * Gutenberg can replace the outer block element while retaining its client
	 * ID, so a fresh owner lookup is required before considering the entry stale.
	 *
	 * @param {string} blockId Managed block client ID.
	 * @param {Object} entry   Managed block entry.
	 * @return {Element|null}
	 */
	function getConnectedManagedOwner( blockId, entry ) {
		if ( isRuntimeConnected( entry.owner ) ) {
			return entry.owner;
		}

		if ( ! entry.document || ! entry.document.querySelectorAll ) {
			return null;
		}

		return Array.from( entry.document.querySelectorAll( '[data-block]' ) ).find(
			function ( candidate ) {
				return isRuntimeConnected( candidate )
					&& blockId === String( candidate.getAttribute( 'data-block' ) || '' );
			}
		) || null;
	}

	/**
	 * Drop detached preview roots and block entries from the strong-ID map.
	 *
	 * The currently reconciling ID is left intact until its old runtime state has
	 * been read, allowing a replaced preview to retain the active Slider index.
	 *
	 * @param {string} preservedBlockId Block ID currently being reconciled.
	 * @return {void}
	 */
	function pruneManagedEditorBlocks( preservedBlockId = '' ) {
		managedEditorBlocks.forEach( function ( entry, blockId ) {
			if ( blockId === preservedBlockId ) {
				return;
			}

			const connectedRoots = new Set();

			entry.roots.forEach( function ( root ) {
				if ( isRuntimeConnected( root ) ) {
					connectedRoots.add( root );
					return;
				}

				const state = sliderStates.get( root );
				if ( state ) {
					entry.sliderIndex = Math.max( 0, state.intentIndex );
				} else if ( root.classList.contains( 'mai-cyber-gallery-layout-slider' ) ) {
					entry.sliderIndex = Math.max(
						0,
						( Number.parseInt( root.dataset.cyberSliderIndex || '1', 10 ) || 1 ) - 1
					);
				}

				destroyWithin( root );
			} );

			entry.roots = connectedRoots;
			const connectedOwner = getConnectedManagedOwner( blockId, entry );

			if ( connectedOwner ) {
				entry.owner = connectedOwner;
				return;
			}

			if ( ! connectedRoots.size ) {
				managedEditorBlocks.delete( blockId );
			}
		} );
	}

	/**
	 * Prune managed editor state after mutations in its owning document.
	 *
	 * @param {Document} ownerDocument Editor document.
	 * @return {void}
	 */
	function observeManagedEditorDocument( ownerDocument ) {
		if ( managedEditorObservers.has( ownerDocument ) ) {
			return;
		}

		const view = ownerDocument.defaultView || window;
		const target = ownerDocument.body || ownerDocument.documentElement;

		if ( ! target || ! ( 'MutationObserver' in view ) ) {
			return;
		}

		const state = {
			frame: 0,
			view: view,
			observer: null,
		};

		state.observer = new view.MutationObserver( function () {
			if ( state.frame ) {
				return;
			}

			state.frame = state.view.requestAnimationFrame( function () {
				state.frame = 0;
				pruneManagedEditorBlocks();
			} );
		} );
		state.observer.observe( target, {
			childList: true,
			subtree: true,
		} );
		managedEditorObservers.set( ownerDocument, state );
	}

	/**
	 * Reconcile editor previews whose rendered gallery root may be replaced.
	 *
	 * @param {Document|Element} scope Current preview scope.
	 * @return {void}
	 */
	function initializeManagedWithin( scope ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return;
		}

		const blockOwner = scope.closest ? scope.closest( '[data-block]' ) : null;
		const blockId = blockOwner ? String( blockOwner.getAttribute( 'data-block' ) || '' ) : '';
		const owner = blockOwner || scope;
		const ownerDocument = getOwnerDocument( owner );

		if ( blockId ) {
			pruneManagedEditorBlocks( blockId );
			observeManagedEditorDocument( ownerDocument );
		}

		const previousEntry = blockId
			? managedEditorBlocks.get( blockId )
			: managedEditorRoots.get( owner );
		const previousRoots = previousEntry ? previousEntry.roots : new Set();
		const currentRoots = getRootsWithin( scope, true );
		let sliderIndex = previousEntry ? previousEntry.sliderIndex : null;
		let previousSliderRoot = null;
		let previousSliderState = null;

		previousRoots.forEach( function ( root ) {
			if ( root.classList.contains( 'mai-cyber-gallery-layout-slider' ) ) {
				const state = sliderStates.get( root );

				previousSliderRoot = root;
				previousSliderState = state || null;
				sliderIndex = state
					? Math.max( 0, state.intentIndex )
					: Math.max(
						0,
						( Number.parseInt( root.dataset.cyberSliderIndex || '1', 10 ) || 1 ) - 1
					);
			}

			if ( ! currentRoots.has( root ) ) {
				destroyWithin( root );
			}
		} );

		initializeWithin( scope );

		currentRoots.forEach( function ( root ) {
			if (
				null === sliderIndex
				|| ! root.classList.contains( 'mai-cyber-gallery-layout-slider' )
			) {
				return;
			}

			const state = sliderStates.get( root );

			if (
				! state
				|| ! state.items.length
				|| ( root === previousSliderRoot && state === previousSliderState )
			) {
				return;
			}

			const restoredIndex = Math.max(
				0,
				Math.min( state.items.length - 1, sliderIndex )
			);

			state.navigationTarget = null;
			state.intentIndex = restoredIndex;
			jumpSliderTrack( state, restoredIndex );
			commitSliderIndex( state, restoredIndex, false );
			updateSliderStatus( state );
			syncSliderAutoplay( state );
			sliderIndex = restoredIndex;
		} );

		const entry = {
			document: ownerDocument,
			owner: blockOwner,
			roots: currentRoots,
			sliderIndex: sliderIndex,
		};

		if ( blockId ) {
			managedEditorBlocks.set( blockId, entry );
		} else {
			managedEditorRoots.set( owner, entry );
		}
	}

	/**
	 * Tear down runtime and managed-editor state within a discarded scope.
	 *
	 * The public editor bridge calls this before ACF replaces a preview or
	 * WordPress discards an iframe document. Preserve the last Slider index while
	 * the owning block remains connected so the next preview can restore it.
	 *
	 * @param {Document|Element} scope Discarded preview scope.
	 * @return {void}
	 */
	function destroyManagedWithin( scope ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return;
		}

		const scopedRoots = getRootsWithin( scope, false );
		const scopedDocument = 9 === scope.nodeType ? scope : null;
		const documentObserver = scopedDocument
			? managedEditorObservers.get( scopedDocument )
			: null;

		if ( documentObserver ) {
			if ( documentObserver.frame ) {
				documentObserver.view.cancelAnimationFrame( documentObserver.frame );
			}
			documentObserver.observer.disconnect();
			managedEditorObservers.delete( scopedDocument );
		}

		managedEditorBlocks.forEach( function ( entry, blockId ) {
			const ownsDocument = Boolean( scopedDocument && entry.document === scopedDocument );
			const ownsBlock = Boolean(
				entry.owner
				&& (
					entry.owner === scope
					|| ( scope.contains && scope.contains( entry.owner ) )
				)
			);
			const remainingRoots = new Set();

			entry.roots.forEach( function ( root ) {
				const isScoped = ownsDocument
					|| scopedRoots.has( root )
					|| ( scope.contains && scope.contains( root ) );

				if ( ! isScoped ) {
					remainingRoots.add( root );
					return;
				}

				const state = sliderStates.get( root );
				if ( state ) {
					entry.sliderIndex = Math.max( 0, state.intentIndex );
				} else if ( root.classList.contains( 'mai-cyber-gallery-layout-slider' ) ) {
					entry.sliderIndex = Math.max(
						0,
						( Number.parseInt( root.dataset.cyberSliderIndex || '1', 10 ) || 1 ) - 1
					);
				}

				destroyWithin( root );
			} );

			entry.roots = remainingRoots;

			if ( remainingRoots.size ) {
				return;
			}

			const connectedOwner = ownsDocument || ownsBlock
				? null
				: getConnectedManagedOwner( blockId, entry );

			if ( connectedOwner ) {
				entry.owner = connectedOwner;
				return;
			}

			managedEditorBlocks.delete( blockId );
		} );

		destroyWithin( scope );
	}

	// Internal bridge used by the editor bundle for iframe-rendered ACF previews.
	const runtime = {
		destroy: destroyManagedWithin,
		initialize: initializeManagedWithin,
	};
	window.CyberGalleryMaiRuntime = runtime;
	window.MaiCyberGalleryRuntime = runtime;

	/**
	 * Start global behavior.
	 *
	 * @return {void}
	 */
	function boot() {
		initializeWithin( document );

		if ( 'MutationObserver' in window ) {
			const addedRoots = new Set();
			const removedRoots = new Set();
			const changedRoots = new Set();
			let mutationFrame = 0;

			const flushMutations = function () {
				mutationFrame = 0;

				removedRoots.forEach( function ( root ) {
					if ( ! root.isConnected ) {
						destroyWithin( root );
					}
				} );

				addedRoots.forEach( function ( root ) {
					if ( root.isConnected ) {
						initializeWithin( root );
					}
				} );

				changedRoots.forEach( function ( root ) {
					if ( root.isConnected && ! addedRoots.has( root ) ) {
						initializeWithin( root );
					}
				} );

				pruneManagedEditorBlocks();

				addedRoots.clear();
				removedRoots.clear();
				changedRoots.clear();
			};

			const observer = new window.MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					mutation.removedNodes.forEach( function ( node ) {
						if ( 1 === node.nodeType ) {
							getRootsWithin( node, false ).forEach( function ( root ) {
								removedRoots.add( root );
							} );
						}
					} );

					mutation.addedNodes.forEach( function ( node ) {
						if ( 1 === node.nodeType ) {
							getRootsWithin( node, false ).forEach( function ( root ) {
								addedRoots.add( root );
							} );
						}
					} );

					if (
						1 === mutation.target.nodeType
						&& mutation.target.matches( '.mai-cyber-gallery' )
					) {
						const root = mutation.target.closest( '.mai-cyber-gallery-wrap' );

						if ( root ) {
							changedRoots.add( root );
						}
					}
				} );

				if (
					! mutationFrame
					&& (
						addedRoots.size
						|| removedRoots.size
						|| changedRoots.size
						|| managedEditorBlocks.size
					)
				) {
					mutationFrame = window.requestAnimationFrame( flushMutations );
				}
			} );

			observer.observe( document.body || document.documentElement, {
				childList: true,
				subtree: true,
			} );
		}
	}

	// The registered script uses the native defer strategy, so the complete
	// document is available here without an initial parser-mutation scan.
	boot();
}() );
