( function () {
	'use strict';

	const initialized = new WeakSet();
	const settings = window.CyberGalleryMaiComparisonSettings
		|| window.MaiCyberGalleryComparisonSettings
		|| {};

	window.CyberGalleryMaiComparisonSettings = settings;
	window.MaiCyberGalleryComparisonSettings = settings;

	/**
	 * Clamp an input value to the supported comparison range.
	 *
	 * @param {*} value Candidate value.
	 * @return {number} Safe percentage.
	 */
	function clampPosition( value ) {
		const number = Number.parseInt( value, 10 );
		return Number.isFinite( number ) ? Math.max( 0, Math.min( 100, number ) ) : 50;
	}

	/**
	 * Format the range's accessible value description.
	 *
	 * @param {string} label    Before-side label.
	 * @param {number} position Visible percentage.
	 * @return {string} Accessible value text.
	 */
	function formatValue( label, position ) {
		const template = settings.valueText || '%1$s: %2$d%% visible';
		return template.replace( /%1\$s|%2\$d|%%/g, function ( token ) {
			if ( '%1$s' === token ) {
				return label;
			}

			return '%2$d' === token ? String( position ) : '%';
		} );
	}

	/**
	 * Synchronize one comparison with its native range.
	 *
	 * @param {HTMLInputElement} input Comparison range.
	 * @return {void}
	 */
	function updateComparison( input ) {
		const comparison = input.closest( '[data-cyber-comparison]' );

		if ( ! comparison ) {
			return;
		}

		const position = clampPosition( input.value );
		const label = input.dataset.cyberComparisonLabel || 'Before';

		input.value = String( position );
		input.setAttribute( 'aria-valuetext', formatValue( label, position ) );
		comparison.style.setProperty( '--cyber-comparison-position', `${ position }%` );
	}

	/**
	 * Initialize comparison controls within a document or preview fragment.
	 *
	 * @param {Document|Element} scope Search scope.
	 * @return {void}
	 */
	function initialize( scope ) {
		const root = scope && scope.querySelectorAll ? scope : document;
		const inputs = [];

		if ( root.matches && root.matches( '[data-cyber-comparison-range]' ) ) {
			inputs.push( root );
		}
		inputs.push( ...root.querySelectorAll( '[data-cyber-comparison-range]' ) );

		inputs.forEach( function ( input ) {
			const comparison = input.closest( '[data-cyber-comparison]' );

			if ( ! initialized.has( input ) ) {
				input.addEventListener( 'input', function () {
					updateComparison( input );
				} );
				initialized.add( input );
			}

			updateComparison( input );
			if ( comparison ) {
				comparison.classList.add( 'is-cyber-comparison-ready' );
			}
			input.disabled = false;
			input.hidden = false;
		} );
	}

	// Internal bridge used by the editor bundle.
	const runtime = {
		clampPosition,
		formatValue,
		initialize,
		updateComparison,
	};
	window.CyberGalleryMaiComparisonRuntime = runtime;
	window.MaiCyberGalleryComparisonRuntime = runtime;

	initialize( document );

	if ( 'MutationObserver' in window ) {
		const observer = new window.MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( 1 === node.nodeType ) {
						initialize( node );
					}
				} );
			} );
		} );

		observer.observe( document.body || document.documentElement, {
			childList: true,
			subtree: true,
		} );
	}
}() );
