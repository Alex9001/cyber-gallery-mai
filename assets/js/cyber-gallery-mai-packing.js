/**
 * Cyber Gallery for Mai Theme source-ordered packing layouts.
 *
 * - Shortest-column Masonry.
 * - Exact Justified rows.
 * - Ratio-aware Tiled Mosaic.
 */

( function () {
	'use strict';

	const masonryStates = new WeakMap();
	const justifiedStates = new WeakMap();
	const tiledStates = new WeakMap();

	/**
	 * Get the document that owns an element-rooted gallery.
	 *
	 * WordPress 7.1 renders post-editor previews in an iframe. The editor
	 * bundle itself runs in the parent document, but every gallery element may
	 * belong to the preview document instead.
	 *
	 * @param {Element|null} root Gallery root.
	 * @return {Document}
	 */
	function getOwnerDocument( root ) {
		return root && root.ownerDocument ? root.ownerDocument : document;
	}

	/**
	 * Get the window that owns an element-rooted gallery.
	 *
	 * @param {Element|null} root Gallery root.
	 * @return {Window}
	 */
	function getOwnerWindow( root ) {
		const ownerDocument = getOwnerDocument( root );

		return ownerDocument.defaultView || window;
	}

	/**
	 * Run a callback once in the next animation frame.
	 *
	 * @param {Object}   state    Mutable state object.
	 * @param {Function} callback Callback.
	 * @return {void}
	 */
	function scheduleFrame( state, callback ) {
		const view = state.view;

		if ( state.frame ) {
			view.cancelAnimationFrame( state.frame );
		}

		state.frame = view.requestAnimationFrame( function () {
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
	 * Get direct gallery items from a track.
	 *
	 * @param {HTMLElement} track Gallery track.
	 * @return {HTMLElement[]}
	 */
	function getTrackItems( track ) {
		return Array.from( track.querySelectorAll( ':scope > .mai-cyber-gallery-item' ) );
	}

	/**
	 * Read Mai's current responsive column count.
	 *
	 * @param {CSSStyleDeclaration} computed Track styles.
	 * @return {number}
	 */
	function getResponsiveColumns( computed ) {
		return Math.max(
			1,
			Math.min(
				8,
				Math.round( positiveNumber( computed.getPropertyValue( '--cyber-columns' ), 1 ) )
			)
		);
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
	 * Pack a masonry gallery into the current shortest column.
	 *
	 * @param {HTMLElement} root  Gallery wrapper.
	 * @param {Object}      state Gallery state.
	 * @param {boolean}     force Ignore the width/column signature.
	 * @return {void}
	 */
	function layoutMasonry( root, state, force ) {
		const track = state.track;
		const items = getTrackItems( track );
		const width = track.clientWidth;

		if ( ! items.length ) {
			root.classList.remove( 'is-cyber-masonry-ready' );
			track.style.removeProperty( '--cyber-masonry-height' );
			state.signature = '';
			return;
		}

		if ( width < 1 ) {
			return;
		}

		const computed = state.view.getComputedStyle( track );
		const columns = getResponsiveColumns( computed );
		const columnGap = positiveNumber(
			computed.getPropertyValue( '--column-gap' ),
			positiveNumber( computed.columnGap, 0 )
		);
		const rowGap = positiveNumber(
			computed.getPropertyValue( '--row-gap' ),
			positiveNumber( computed.rowGap, 0 )
		);
		const flow = 'ordered' === track.dataset.cyberMasonryFlow
			? 'ordered'
			: 'balanced';
		const signature = [
			width.toFixed( 3 ),
			columns,
			columnGap.toFixed( 3 ),
			rowGap.toFixed( 3 ),
			flow,
		].join( ':' );

		if ( ! force && signature === state.signature ) {
			return;
		}

		state.signature = signature;

		const columnWidth = Math.max(
			1,
			( width - columnGap * Math.max( 0, columns - 1 ) ) / columns
		);
		const bottoms = Array( columns ).fill( 0 );

		root.classList.add( 'is-cyber-masonry-ready' );

		items.forEach( function ( item ) {
			item.style.setProperty( '--cyber-masonry-width', columnWidth.toFixed( 3 ) + 'px' );
		} );

		const writes = items.map( function ( item, itemIndex ) {
			let column = itemIndex % columns;

			if ( 'balanced' === flow ) {
				column = 0;

				for ( let index = 1; index < bottoms.length; index++ ) {
					if ( bottoms[ index ] < bottoms[ column ] - 0.1 ) {
						column = index;
					}
				}
			}

			const height = item.getBoundingClientRect().height;
			const x = column * ( columnWidth + columnGap );
			const y = bottoms[ column ];

			bottoms[ column ] = y + height + rowGap;

			return {
				item: item,
				x: x,
				y: y,
			};
		} );

		writes.forEach( function ( write ) {
			write.item.style.setProperty( '--cyber-masonry-x', write.x.toFixed( 3 ) + 'px' );
			write.item.style.setProperty( '--cyber-masonry-y', write.y.toFixed( 3 ) + 'px' );
		} );

		const height = Math.max( 0, Math.max.apply( null, bottoms ) - rowGap );
		track.style.setProperty( '--cyber-masonry-height', height.toFixed( 3 ) + 'px' );
	}

	/**
	 * Initialize source-ordered masonry packing.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeMasonry( root ) {
		const track = root.querySelector( '.mai-cyber-gallery' );
		const existing = masonryStates.get( root );

		if ( existing ) {
			if ( existing.track === track ) {
				watchImages( existing, track, function () {
					existing.schedule( true );
				} );
				existing.schedule( true );
				return;
			}

			if ( existing.frame ) {
				existing.view.cancelAnimationFrame( existing.frame );
			}

			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}

			masonryStates.delete( root );
			root.classList.remove( 'is-cyber-masonry-ready' );
		}

		if ( ! track ) {
			return;
		}

		const state = {
			track: track,
			document: getOwnerDocument( root ),
			view: getOwnerWindow( root ),
			frame: 0,
			signature: '',
			resizeObserver: null,
			images: new WeakSet(),
			schedule: null,
		};

		state.schedule = function ( force ) {
			if ( ! root.isConnected || masonryStates.get( root ) !== state ) {
				return;
			}

			scheduleFrame( state, function () {
				layoutMasonry( root, state, Boolean( force ) );
			} );
		};

		masonryStates.set( root, state );
		watchImages( state, track, function () {
			state.schedule( true );
		} );

		if ( 'ResizeObserver' in state.view ) {
			state.resizeObserver = new state.view.ResizeObserver( function () {
				state.schedule( false );
			} );
			state.resizeObserver.observe( track );
		}

		if ( state.document.fonts && state.document.fonts.ready ) {
			state.document.fonts.ready.then( function () {
				state.schedule( true );
			} );
		}

		layoutMasonry( root, state, true );
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

		const computed = state.view.getComputedStyle( track );
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
				existing.view.cancelAnimationFrame( existing.frame );
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
			document: getOwnerDocument( root ),
			view: getOwnerWindow( root ),
			frame: 0,
			lastWidth: 0,
			resizeObserver: null,
		};

		justifiedStates.set( root, state );

		const schedule = function ( force ) {
			if ( ! root.isConnected || justifiedStates.get( root ) !== state ) {
				return;
			}

			scheduleFrame( state, function () {
				layoutJustified( root, state, Boolean( force ) );
			} );
		};

		if ( 'ResizeObserver' in state.view ) {
			state.resizeObserver = new state.view.ResizeObserver( function () {
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

		if ( state.document.fonts && state.document.fonts.ready ) {
			state.document.fonts.ready.then( function () {
				schedule( true );
			} );
		}

		// The ratios are server-rendered, so the first pass can run immediately.
		// This prevents the responsive Mai grid fallback from painting first.
		layoutJustified( root, state, true );
	}

	/**
	 * Convert a tiled row shape into row-major, source-ordered groups.
	 *
	 * A shape such as [ 2, 1, 2 ] produces visual slots:
	 * 1 | 2 | 3
	 * 4 | 2 | 5
	 *
	 * @param {number[]} shape   Stack count for each visual group.
	 * @param {Object[]} entries Source-ordered entries.
	 * @return {Object[][]}
	 */
	function makeTiledGroups( shape, entries ) {
		const groups = shape.map( function () {
			return [];
		} );
		const levels = Math.max.apply( null, shape );
		let cursor = 0;

		for ( let level = 0; level < levels; level++ ) {
			shape.forEach( function ( count, groupIndex ) {
				if ( level < count && entries[ cursor ] ) {
					groups[ groupIndex ].push( entries[ cursor ] );
					cursor++;
				}
			} );
		}

		return groups;
	}

	/**
	 * Calculate equal-height widths for tiled row groups.
	 *
	 * @param {Object[][]} groups    Tiled groups.
	 * @param {number[]}   extras    Non-media height in each group.
	 * @param {number}     available Width available after horizontal gaps.
	 * @return {number[]}
	 */
	function getTiledGroupWidths( groups, extras, available ) {
		const factors = groups.map( function ( group ) {
			const inverseRatio = group.reduce( function ( sum, entry ) {
				return sum + 1 / entry.ratio;
			}, 0 );

			return 1 / Math.max( 0.1, inverseRatio );
		} );
		const factorSum = factors.reduce( function ( sum, factor ) {
			return sum + factor;
		}, 0 );
		const extraContribution = factors.reduce( function ( sum, factor, index ) {
			return sum + factor * extras[ index ];
		}, 0 );
		const rowHeight = ( available + extraContribution ) / Math.max( 0.1, factorSum );
		const widths = factors.map( function ( factor, index ) {
			return Math.max( 1, factor * Math.max( 1, rowHeight - extras[ index ] ) );
		} );
		const widthSum = widths.reduce( function ( sum, groupWidth ) {
			return sum + groupWidth;
		}, 0 );

		if ( 0 < widthSum && Math.abs( widthSum - available ) > 0.1 ) {
			const scale = available / widthSum;

			return widths.map( function ( groupWidth ) {
				return Math.max( 1, groupWidth * scale );
			} );
		}

		return widths;
	}

	/**
	 * Choose a ratio-aware tiled row shape.
	 *
	 * @param {Object[]} entries       Remaining entries.
	 * @param {number}   maximumGroups Responsive group limit.
	 * @param {number}   width         Track width.
	 * @param {number}   columnGap     Horizontal gap.
	 * @param {string}   previousShape Previously selected shape.
	 * @param {string}   pattern       Organic, calm, or dense pattern.
	 * @return {number[]}
	 */
	function chooseTiledShape( entries, maximumGroups, width, columnGap, previousShape, pattern ) {
		const libraries = {
			calm: [
				[ 1 ],
				[ 1, 1 ],
				[ 1, 1, 1 ],
				[ 1, 1, 1, 1 ],
			],
			dense: [
				[ 1 ],
				[ 1, 1 ],
				[ 1, 2 ],
				[ 2, 1 ],
				[ 2, 2 ],
				[ 1, 2, 1 ],
				[ 2, 1, 2 ],
				[ 1, 3 ],
				[ 3, 1 ],
				[ 1, 2, 2, 1 ],
			],
			organic: [
				[ 1 ],
				[ 1, 1 ],
				[ 1, 2 ],
				[ 2, 1 ],
				[ 1, 1, 1 ],
				[ 1, 2, 1 ],
				[ 2, 1, 2 ],
				[ 1, 3 ],
				[ 3, 1 ],
				[ 1, 1, 1, 1 ],
			],
		};
		const library = libraries[ pattern ] || libraries.organic;
		const remaining = entries.length;

		if ( remaining <= maximumGroups ) {
			library.push( Array( remaining ).fill( 1 ) );
		}

		const unique = new Map();

		library.forEach( function ( shape ) {
			const itemCount = shape.reduce( function ( sum, count ) {
				return sum + count;
			}, 0 );

			if ( shape.length <= maximumGroups && itemCount <= remaining ) {
				unique.set( shape.join( '-' ), shape );
			}
		} );

		const target = Math.max(
			180,
			Math.min( 340, width / Math.max( 1, Math.min( maximumGroups, 4 ) ) )
		);
		let best = [ 1 ];
		let bestScore = Number.POSITIVE_INFINITY;

		unique.forEach( function ( shape, key ) {
			const itemCount = shape.reduce( function ( sum, count ) {
				return sum + count;
			}, 0 );
			const groups = makeTiledGroups( shape, entries.slice( 0, itemCount ) );
			const available = Math.max( 1, width - columnGap * Math.max( 0, groups.length - 1 ) );
			const widths = getTiledGroupWidths( groups, Array( groups.length ).fill( 0 ), available );
			const approximateHeight = widths.reduce( function ( tallest, groupWidth, groupIndex ) {
				const groupHeight = groups[ groupIndex ].reduce( function ( sum, entry ) {
					return sum + groupWidth / entry.ratio;
				}, 0 );

				return Math.max( tallest, groupHeight );
			}, 0 );
			const minimumWidth = Math.min.apply( null, widths );
			const minimumMediaHeight = groups.reduce( function ( shortest, group, groupIndex ) {
				return group.reduce( function ( currentShortest, entry ) {
					return Math.min( currentShortest, widths[ groupIndex ] / entry.ratio );
				}, shortest );
			}, Number.POSITIVE_INFINITY );
			const leftover = remaining - itemCount;
			const allSingles = shape.every( function ( count ) {
				return 1 === count;
			} );
			let score = Math.abs( approximateHeight - target ) / target;

			if ( minimumWidth < 112 ) {
				score += ( 112 - minimumWidth ) / 112;
			}

			if ( minimumMediaHeight < 72 ) {
				score += ( 72 - minimumMediaHeight ) / 48;
			}

			if ( allSingles && remaining > maximumGroups ) {
				score += 'dense' === pattern ? 0.8 : 0.25;
			}

			if ( 'dense' === pattern && ! shape.some( function ( count ) {
				return 1 < count;
			} ) && remaining > shape.length ) {
				score += 0.45;
			}

			if ( 1 === leftover ) {
				score += 0.55;
			} else {
				score += Math.min( 0.25, leftover * 0.035 );
			}

			if ( key === previousShape ) {
				score += 0.12;
			}

			if ( score < bestScore ) {
				best = shape;
				bestScore = score;
			}
		} );

		return best;
	}

	/**
	 * Lay out a ratio-aware tiled mosaic.
	 *
	 * @param {HTMLElement} root  Gallery wrapper.
	 * @param {Object}      state Gallery state.
	 * @param {boolean}     force Ignore the width/column signature.
	 * @return {void}
	 */
	function layoutTiled( root, state, force ) {
		const track = state.track;
		const items = getTrackItems( track );
		const width = track.clientWidth;

		if ( ! items.length ) {
			root.classList.remove( 'is-cyber-tiled-ready' );
			track.style.removeProperty( '--cyber-tiled-height' );
			state.signature = '';
			return;
		}

		if ( width < 1 ) {
			return;
		}

		const computed = state.view.getComputedStyle( track );
		const maximumGroups = Math.max( 1, Math.min( 4, getResponsiveColumns( computed ) ) );
		const columnGap = positiveNumber(
			computed.getPropertyValue( '--column-gap' ),
			positiveNumber( computed.columnGap, 0 )
		);
		const rowGap = positiveNumber(
			computed.getPropertyValue( '--row-gap' ),
			positiveNumber( computed.rowGap, 0 )
		);
		const pattern = [ 'calm', 'dense' ].includes( track.dataset.cyberTiledPattern )
			? track.dataset.cyberTiledPattern
			: 'organic';
		const signature = [
			width.toFixed( 3 ),
			maximumGroups,
			columnGap.toFixed( 3 ),
			rowGap.toFixed( 3 ),
			pattern,
		].join( ':' );

		if ( ! force && signature === state.signature ) {
			return;
		}

		state.signature = signature;

		const entries = items.map( function ( item ) {
			return {
				item: item,
				media: item.querySelector( '.mai-cyber-gallery-media' ),
				ratio: Math.max(
					0.1,
					Math.min( 10, positiveNumber( item.dataset.cyberRatio, 1 ) )
				),
			};
		} );
		let cursor = 0;
		let y = 0;
		let previousShape = '';

		root.classList.add( 'is-cyber-tiled-ready' );

		while ( cursor < entries.length ) {
			const remaining = entries.slice( cursor );
			const shape = chooseTiledShape(
				remaining,
				maximumGroups,
				width,
				columnGap,
				previousShape,
				pattern
			);
			const itemCount = shape.reduce( function ( sum, count ) {
				return sum + count;
			}, 0 );
			const groups = makeTiledGroups( shape, remaining.slice( 0, itemCount ) );
			const available = Math.max( 1, width - columnGap * Math.max( 0, groups.length - 1 ) );
			let extras = groups.map( function ( group ) {
				return rowGap * Math.max( 0, group.length - 1 );
			} );
			let widths = [];

			// Two passes account for below-caption height and any line wrapping at
			// the calculated group width.
			for ( let pass = 0; pass < 2; pass++ ) {
				widths = getTiledGroupWidths( groups, extras, available );

				groups.forEach( function ( group, groupIndex ) {
					const groupWidth = widths[ groupIndex ];

					group.forEach( function ( entry ) {
						entry.item.style.setProperty(
							'--cyber-tiled-width',
							groupWidth.toFixed( 3 ) + 'px'
						);
						entry.item.style.setProperty(
							'--cyber-tiled-media-height',
							( groupWidth / entry.ratio ).toFixed( 3 ) + 'px'
						);
					} );
				} );

				extras = groups.map( function ( group ) {
					return group.reduce( function ( sum, entry ) {
						const itemHeight = entry.item.getBoundingClientRect().height;
						const mediaHeight = entry.media
							? entry.media.getBoundingClientRect().height
							: 0;

						return sum + Math.max( 0, itemHeight - mediaHeight );
					}, rowGap * Math.max( 0, group.length - 1 ) );
				} );
			}

			widths = getTiledGroupWidths( groups, extras, available );

			// Give the final group any sub-pixel residual so the row closes exactly.
			if ( 1 < widths.length ) {
				const previousWidths = widths.slice( 0, -1 ).reduce( function ( sum, groupWidth ) {
					return sum + groupWidth;
				}, 0 );
				widths[ widths.length - 1 ] = Math.max( 1, available - previousWidths );
			}

			// Apply final sizes together, then batch reads before any positioning
			// writes so a large gallery does not force one layout per item.
			groups.forEach( function ( group, groupIndex ) {
				const groupWidth = widths[ groupIndex ];

				group.forEach( function ( entry ) {
					entry.item.style.setProperty(
						'--cyber-tiled-width',
						groupWidth.toFixed( 3 ) + 'px'
					);
					entry.item.style.setProperty(
						'--cyber-tiled-media-height',
						( groupWidth / entry.ratio ).toFixed( 3 ) + 'px'
					);
				} );
			} );

			const heights = new Map();
			groups.forEach( function ( group ) {
				group.forEach( function ( entry ) {
					heights.set( entry, entry.item.getBoundingClientRect().height );
				} );
			} );

			const groupHeights = groups.map( function ( group ) {
				return group.reduce( function ( sum, entry ) {
					return sum + heights.get( entry );
				}, rowGap * Math.max( 0, group.length - 1 ) );
			} );
			const targetGroupHeight = Math.max.apply( null, groupHeights );
			let adjusted = false;

			// Caption wrapping can change at the final sub-pixel widths. Absorb any
			// small residual into the last tile so every visual group closes the row.
			groups.forEach( function ( group, groupIndex ) {
				const difference = targetGroupHeight - groupHeights[ groupIndex ];

				if ( difference > 0.25 ) {
					const last = group[ group.length - 1 ];
					const mediaHeight = widths[ groupIndex ] / last.ratio + difference;

					last.item.style.setProperty(
						'--cyber-tiled-media-height',
						mediaHeight.toFixed( 3 ) + 'px'
					);
					adjusted = true;
				}
			} );

			if ( adjusted ) {
				groups.forEach( function ( group ) {
					group.forEach( function ( entry ) {
						heights.set( entry, entry.item.getBoundingClientRect().height );
					} );
				} );
			}

			const positions = [];
			let x = 0;
			let rowBottom = y;

			groups.forEach( function ( group, groupIndex ) {
				let itemY = y;

				group.forEach( function ( entry ) {
					positions.push( {
						entry: entry,
						x: x,
						y: itemY,
					} );
					itemY += heights.get( entry ) + rowGap;
				} );

				rowBottom = Math.max( rowBottom, itemY - rowGap );
				x += widths[ groupIndex ] + columnGap;
			} );

			positions.forEach( function ( position ) {
				position.entry.item.style.setProperty(
					'--cyber-tiled-x',
					position.x.toFixed( 3 ) + 'px'
				);
				position.entry.item.style.setProperty(
					'--cyber-tiled-y',
					position.y.toFixed( 3 ) + 'px'
				);
			} );

			y = rowBottom + rowGap;
			cursor += itemCount;
			previousShape = shape.join( '-' );
		}

		track.style.setProperty(
			'--cyber-tiled-height',
			Math.max( 0, y - rowGap ).toFixed( 3 ) + 'px'
		);
	}

	/**
	 * Initialize a tiled mosaic.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeTiled( root ) {
		const track = root.querySelector( '.mai-cyber-gallery' );
		const existing = tiledStates.get( root );

		if ( existing ) {
			if ( existing.track === track ) {
				watchImages( existing, track, function () {
					existing.schedule( true );
				} );
				existing.schedule( true );
				return;
			}

			if ( existing.frame ) {
				existing.view.cancelAnimationFrame( existing.frame );
			}

			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}

			tiledStates.delete( root );
			root.classList.remove( 'is-cyber-tiled-ready' );
		}

		if ( ! track ) {
			return;
		}

		const state = {
			track: track,
			document: getOwnerDocument( root ),
			view: getOwnerWindow( root ),
			frame: 0,
			signature: '',
			resizeObserver: null,
			images: new WeakSet(),
			schedule: null,
		};

		state.schedule = function ( force ) {
			if ( ! root.isConnected || tiledStates.get( root ) !== state ) {
				return;
			}

			scheduleFrame( state, function () {
				layoutTiled( root, state, Boolean( force ) );
			} );
		};

		tiledStates.set( root, state );
		watchImages( state, track, function () {
			state.schedule( true );
		} );

		if ( 'ResizeObserver' in state.view ) {
			state.resizeObserver = new state.view.ResizeObserver( function () {
				state.schedule( false );
			} );
			state.resizeObserver.observe( track );
		}

		if ( state.document.fonts && state.document.fonts.ready ) {
			state.document.fonts.ready.then( function () {
				state.schedule( true );
			} );
		}

		layoutTiled( root, state, true );
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
	 * Disconnect and remove one packing state from a gallery.
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

		if ( state.resizeObserver ) {
			state.resizeObserver.disconnect();
		}

		states.delete( root );
		root.classList.remove( readyClass );
	}

	/**
	 * Initialize packing behaviors inside a root.
	 *
	 * @param {Document|Element} scope Search scope.
	 * @return {void}
	 */
	function initializeWithin( scope ) {
		getRootsWithin( scope, true ).forEach( function ( root ) {
			if ( root.classList.contains( 'mai-cyber-gallery-layout-masonry' ) ) {
				initializeMasonry( root );
			} else {
				clearGalleryState( root, masonryStates, 'is-cyber-masonry-ready' );
			}

			if ( root.classList.contains( 'mai-cyber-gallery-layout-justified' ) ) {
				initializeJustified( root );
			} else {
				clearGalleryState( root, justifiedStates, 'is-cyber-justified-ready' );
			}

			if ( root.classList.contains( 'mai-cyber-gallery-layout-tiled' ) ) {
				initializeTiled( root );
			} else {
				clearGalleryState( root, tiledStates, 'is-cyber-tiled-ready' );
			}
		} );
	}

	/**
	 * Disconnect packing observers owned by removed galleries.
	 *
	 * @param {Element} scope Removed element.
	 * @return {void}
	 */
	function destroyWithin( scope ) {
		getRootsWithin( scope, false ).forEach( function ( root ) {
			[
				[ masonryStates, 'is-cyber-masonry-ready' ],
				[ justifiedStates, 'is-cyber-justified-ready' ],
				[ tiledStates, 'is-cyber-tiled-ready' ],
			].forEach( function ( entry ) {
				clearGalleryState( root, entry[0], entry[1] );
			} );
		} );
	}

	// Internal bridge used by the editor bundle for iframe-rendered ACF previews.
	const runtime = {
		initialize: initializeWithin,
	};
	window.CyberGalleryMaiPackingRuntime = runtime;
	window.MaiCyberGalleryPackingRuntime = runtime;

	/**
	 * Start packing behavior and focused dynamic-content observation.
	 *
	 * @return {void}
	 */
	function boot() {
		initializeWithin( document );

		if ( ! ( 'MutationObserver' in window ) ) {
			return;
		}

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
				&& ( addedRoots.size || removedRoots.size || changedRoots.size )
			) {
				mutationFrame = window.requestAnimationFrame( flushMutations );
			}
		} );

		observer.observe( document.body || document.documentElement, {
			childList: true,
			subtree: true,
		} );
	}

	boot();
}() );
