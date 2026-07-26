/**
 * Mai Cyber Gallery progressive enhancements.
 *
 * - Source-ordered shortest-column masonry packing.
 * - Exact justified-row sizing with a resilient Mai grid fallback.
 * - Ratio-aware tiled mosaic rows.
 * - Accessible, native-scroll filmstrip controls.
 * - An accessible, native-scroll slider with arrows, dots, and counters.
 * - A native dialog lightbox with keyboard navigation and focus restoration.
 */

( function () {
	'use strict';

	const settings = window.MaiCyberGallerySettings || {};
	const masonryStates = new WeakMap();
	const justifiedStates = new WeakMap();
	const tiledStates = new WeakMap();
	const filmstripStates = new WeakMap();
	const sliderStates = new WeakMap();
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

		const computed = window.getComputedStyle( track );
		const columns = getResponsiveColumns( computed );
		const columnGap = positiveNumber(
			computed.getPropertyValue( '--column-gap' ),
			positiveNumber( computed.columnGap, 0 )
		);
		const rowGap = positiveNumber(
			computed.getPropertyValue( '--row-gap' ),
			positiveNumber( computed.rowGap, 0 )
		);
		const signature = [
			width.toFixed( 3 ),
			columns,
			columnGap.toFixed( 3 ),
			rowGap.toFixed( 3 ),
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

		const writes = items.map( function ( item ) {
			let column = 0;

			for ( let index = 1; index < bottoms.length; index++ ) {
				if ( bottoms[ index ] < bottoms[ column ] - 0.1 ) {
					column = index;
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
				window.cancelAnimationFrame( existing.frame );
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

		if ( 'ResizeObserver' in window ) {
			state.resizeObserver = new window.ResizeObserver( function () {
				state.schedule( false );
			} );
			state.resizeObserver.observe( track );
		}

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
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
			if ( ! root.isConnected || justifiedStates.get( root ) !== state ) {
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
	 * @return {number[]}
	 */
	function chooseTiledShape( entries, maximumGroups, width, columnGap, previousShape ) {
		const library = [
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
		];
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
				score += 0.25;
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

		const computed = window.getComputedStyle( track );
		const maximumGroups = Math.max( 1, Math.min( 4, getResponsiveColumns( computed ) ) );
		const columnGap = positiveNumber(
			computed.getPropertyValue( '--column-gap' ),
			positiveNumber( computed.columnGap, 0 )
		);
		const rowGap = positiveNumber(
			computed.getPropertyValue( '--row-gap' ),
			positiveNumber( computed.rowGap, 0 )
		);
		const signature = [
			width.toFixed( 3 ),
			maximumGroups,
			columnGap.toFixed( 3 ),
			rowGap.toFixed( 3 ),
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
				previousShape
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
				window.cancelAnimationFrame( existing.frame );
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

		if ( 'ResizeObserver' in window ) {
			state.resizeObserver = new window.ResizeObserver( function () {
				state.schedule( false );
			} );
			state.resizeObserver.observe( track );
		}

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( function () {
				state.schedule( true );
			} );
		}

		layoutTiled( root, state, true );
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
	 * Update active slider state and controls.
	 *
	 * @param {Object} state Slider state.
	 * @return {void}
	 */
	function updateSlider( state ) {
		state.items = getTrackItems( state.track );

		if ( ! state.items.length ) {
			return;
		}

		const previousIndex = state.index;
		const index = getSliderIndex( state );
		const total = state.items.length;
		const focusedControl = document.activeElement;

		state.index = index;
		state.root.dataset.cyberSliderIndex = String( index + 1 );

		if ( state.previous ) {
			state.previous.disabled = 0 === index;
		}

		if ( state.next ) {
			state.next.disabled = index === total - 1;
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
			if ( dotIndex === index ) {
				dot.setAttribute( 'aria-current', 'true' );
			} else {
				dot.removeAttribute( 'aria-current' );
			}
		} );

		const activeDot = state.dots[ index ];
		if ( activeDot && previousIndex !== index ) {
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

		const counter = String( index + 1 );
		if ( state.counter && state.counter.textContent !== counter ) {
			state.counter.textContent = counter;
		}

		const focusedItem = state.items.find( function ( item ) {
			return item.contains( document.activeElement );
		} );

		if ( focusedItem && focusedItem !== state.items[ index ] ) {
			state.track.focus( { preventScroll: true } );
		}

		state.items.forEach( function ( item, itemIndex ) {
			const active = itemIndex === index;

			item.inert = ! active;

			if ( active ) {
				item.removeAttribute( 'aria-hidden' );
			} else {
				item.setAttribute( 'aria-hidden', 'true' );
			}
		} );

		if ( state.status && previousIndex !== index ) {
			state.status.textContent = formatSlideStatus(
				index + 1,
				total,
				state.items[ index ].dataset.cyberSlideTitle || ''
			);
		}
	}

	/**
	 * Move a slider to an exact slide.
	 *
	 * @param {Object} state Slider state.
	 * @param {number} index Requested zero-based index.
	 * @return {void}
	 */
	function moveSlider( state, index ) {
		const targetIndex = Math.max( 0, Math.min( state.items.length - 1, index ) );
		const target = state.items[ targetIndex ];

		if ( ! target ) {
			return;
		}

		const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		const adjacent = Math.abs( targetIndex - state.index ) <= 1;
		const behavior = reduceMotion || ! adjacent ? 'auto' : 'smooth';

		// The track's CSS enables smooth scrolling by default. Temporarily override
		// it so distant dot jumps and reduced-motion requests are genuinely instant.
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
	}

	/**
	 * Initialize one slider.
	 *
	 * @param {HTMLElement} root Gallery wrapper.
	 * @return {void}
	 */
	function initializeSlider( root ) {
		const track = root.querySelector( '.mai-cyber-gallery' );
		const previous = root.querySelector( '[data-cyber-slider-previous]' );
		const next = root.querySelector( '[data-cyber-slider-next]' );
		const dots = Array.from( root.querySelectorAll( '[data-cyber-slider-dot]' ) );
		const counter = root.querySelector( '[data-cyber-slider-current]' );
		const status = root.querySelector( '[data-cyber-slider-status]' );
		const items = track ? getTrackItems( track ) : [];
		const existing = sliderStates.get( root );

		if ( existing ) {
			if (
				existing.track === track
				&& existing.previous === previous
				&& existing.next === next
				&& existing.counter === counter
				&& existing.status === status
				&& existing.dots.length === dots.length
				&& existing.dots.every( function ( dot, index ) {
					return dot === dots[ index ];
				} )
			) {
				existing.items = items;
				scheduleFrame( existing, function () {
					updateSlider( existing );
				} );
				return;
			}

			if ( existing.frame ) {
				window.cancelAnimationFrame( existing.frame );
			}

			if ( existing.resizeObserver ) {
				existing.resizeObserver.disconnect();
			}

			if ( existing.controller ) {
				existing.controller.abort();
			}

			existing.items.forEach( function ( item ) {
				item.inert = false;
				item.removeAttribute( 'aria-hidden' );
			} );

			sliderStates.delete( root );
			root.classList.remove( 'is-cyber-slider-ready' );
		}

		if ( ! track || ! items.length ) {
			return;
		}

		const state = {
			root: root,
			track: track,
			previous: previous,
			next: next,
			dots: dots,
			counter: counter,
			status: status,
			items: items,
			index: -1,
			frame: 0,
			resizeObserver: null,
			controller: 'AbortController' in window ? new window.AbortController() : null,
		};

		sliderStates.set( root, state );
		root.classList.add( 'is-cyber-slider-ready' );

		const scheduleUpdate = function () {
			scheduleFrame( state, function () {
				updateSlider( state );
			} );
		};
		const listenerOptions = state.controller
			? { signal: state.controller.signal }
			: undefined;
		const scrollOptions = state.controller
			? { passive: true, signal: state.controller.signal }
			: { passive: true };

		if ( previous ) {
			previous.addEventListener( 'click', function () {
				moveSlider( state, state.index - 1 );
			}, listenerOptions );
		}

		if ( next ) {
			next.addEventListener( 'click', function () {
				moveSlider( state, state.index + 1 );
			}, listenerOptions );
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				moveSlider( state, Number.parseInt( dot.dataset.cyberSliderDot, 10 ) || 0 );
			}, listenerOptions );
		} );

		track.addEventListener( 'scroll', scheduleUpdate, scrollOptions );
		track.addEventListener( 'keydown', function ( event ) {
			if ( event.target.closest( 'a, button, input, select, textarea' ) ) {
				return;
			}

			const isRtl = window.getComputedStyle( track ).direction === 'rtl';
			let target = null;

			if ( 'ArrowRight' === event.key ) {
				target = state.index + ( isRtl ? -1 : 1 );
			} else if ( 'ArrowLeft' === event.key ) {
				target = state.index + ( isRtl ? 1 : -1 );
			} else if ( 'Home' === event.key ) {
				target = 0;
			} else if ( 'End' === event.key ) {
				target = state.items.length - 1;
			}

			if ( null !== target ) {
				event.preventDefault();
				moveSlider( state, target );
			}
		}, listenerOptions );

		if ( 'ResizeObserver' in window ) {
			state.resizeObserver = new window.ResizeObserver( scheduleUpdate );
			state.resizeObserver.observe( track );
		}

		updateSlider( state );
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
		const focusedControl = document.activeElement;

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

		if (
			focusedControl === dialogState.previous
			&& dialogState.previous.disabled
			&& ! dialogState.next.disabled
		) {
			dialogState.next.focus( { preventScroll: true } );
		} else if (
			focusedControl === dialogState.next
			&& dialogState.next.disabled
			&& ! dialogState.previous.disabled
		) {
			dialogState.previous.focus( { preventScroll: true } );
		}

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
		state.dialog.dataset.cyberArrowStyle = trigger.dataset.cyberArrowStyle || 'circle';

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
			window.cancelAnimationFrame( state.frame );
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
				masonryStates,
				justifiedStates,
				tiledStates,
				filmstripStates,
				sliderStates,
			].forEach( function ( states ) {
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

				if ( state.controller ) {
					state.controller.abort();
				}

				if ( sliderStates === states && state.items ) {
					state.items.forEach( function ( item ) {
						item.inert = false;
						item.removeAttribute( 'aria-hidden' );
					} );
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
