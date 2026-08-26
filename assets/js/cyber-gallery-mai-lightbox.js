/**
 * Cyber Gallery for Mai Theme native dialog lightbox.
 */

( function () {
	'use strict';

	const settings = window.CyberGalleryMaiLightboxSettings
		|| window.MaiCyberGalleryLightboxSettings
		|| {};

	window.CyberGalleryMaiLightboxSettings = settings;
	window.MaiCyberGalleryLightboxSettings = settings;
	let dialogState = null;

	/**
	 * Parse a finite positive number.
	 *
	 * @param {*}      value    Candidate.
	 * @param {number} fallback Fallback.
	 * @return {number}
	 */
	function positiveNumber( value, fallback ) {
		const parsed = Number.parseFloat( value );
		return Number.isFinite( parsed ) && 0 < parsed ? parsed : fallback;
	}

	/**
	 * Make an icon button for the dialog.
	 *
	 * @param {string} className Extra class.
	 * @param {string} label     Accessible label.
	 * @param {string} icon      Mai Engine icon markup.
	 * @param {string} path      Fallback SVG path data.
	 * @return {HTMLButtonElement}
	 */
	function makeDialogButton( className, label, icon, path ) {
		const button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'button mai-cyber-gallery-dialog-button ' + className;
		button.setAttribute( 'aria-label', label );
		button.innerHTML = icon ||
			'<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="' +
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
		const icons = settings.icons || {};
		const close = makeDialogButton(
			'mai-cyber-gallery-dialog-close',
			settings.close || 'Close image viewer',
			icons.close || '',
			'M6.4 5 5 6.4l5.6 5.6L5 17.6 6.4 19l5.6-5.6 5.6 5.6 1.4-1.4-5.6-5.6L19 6.4 17.6 5 12 10.6z'
		);
		const previous = makeDialogButton(
			'mai-cyber-gallery-dialog-previous',
			settings.previous || 'Previous image',
			icons.previous || '',
			'M14.7 5.3 13.3 4l-8 8 8 8 1.4-1.4L8.1 12z'
		);
		const next = makeDialogButton(
			'mai-cyber-gallery-dialog-next',
			settings.next || 'Next image',
			icons.next || '',
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
	 * Format the live-region status without interpreting caption dollar signs
	 * as String.replace substitution patterns.
	 *
	 * @param {string} description Visible image description.
	 * @param {string} position    Localized image position.
	 * @return {string}
	 */
	function formatStatus( description, position ) {
		if ( ! description ) {
			return position;
		}

		return String( settings.status || '%1$s, %2$s' )
			.replace( /%1\$s|%2\$s/g, function ( token ) {
				return '%1$s' === token ? description : position;
			} );
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
		dialogState.status.textContent = formatStatus( description, position );
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
		state.dialog.dir = 'rtl' === window.getComputedStyle( trigger ).direction
			? 'rtl'
			: 'ltr';
		state.dialog.dataset.cyberArrowStyle = trigger.dataset.cyberArrowStyle || 'circle';

		showDialogImage( state.index );
		document.body.classList.add( 'mai-cyber-gallery-lightbox-open' );

		if ( ! state.dialog.open ) {
			state.dialog.showModal();
		}

		state.close.focus();
		return true;
	}

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
}() );
