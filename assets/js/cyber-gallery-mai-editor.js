/**
 * Cyber Gallery for Mai Theme block-editor integrations.
 */

( function () {
	'use strict';

	const targetBlock = 'acf/cyber-gallery-mai';
	const legacyBlock = 'acf/mai-cyber-gallery';
	const manageButtonStates = new WeakSet();
	const editorCanvasFrames = new WeakMap();
	const logoScaleControlStates = new WeakMap();

	/**
	 * Get the browsing context that owns an editor element.
	 *
	 * WordPress 7.1 always iframes the post editor canvas. Native events must
	 * therefore be constructed by the same window that owns their target.
	 *
	 * @param {Document|Element|null} element Editor document or element.
	 * @return {Window} Owning window.
	 */
	function getElementView( element ) {
		if ( element && 9 === element.nodeType && element.defaultView ) {
			return element.defaultView;
		}

		return element
			&& element.ownerDocument
			&& element.ownerDocument.defaultView
			? element.ownerDocument.defaultView
			: window;
	}

	/**
	 * Keep the two Slider navigation selects from choosing the same control.
	 *
	 * @param {Document|Element} scope   Search scope.
	 * @param {HTMLSelectElement|null} changed Select that initiated the update.
	 * @return {void}
	 */
	function syncSliderNavigationSelects( scope, changed ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return;
		}

		const primarySelector = '.acf-field[data-key="mai_cyber_gallery_slider_navigation"]';
		const secondarySelector = '.acf-field[data-key="mai_cyber_gallery_slider_navigation_secondary"]';
		const primaryFields = Array.from( scope.querySelectorAll( primarySelector ) );

		if ( scope.matches && scope.matches( primarySelector ) ) {
			primaryFields.unshift( scope );
		}

		primaryFields.forEach( function ( primaryField ) {
			const fields = primaryField.closest( '.acf-fields' );
			const secondaryField = fields ? fields.querySelector( secondarySelector ) : null;
			const primary = primaryField.querySelector( 'select' );
			const secondary = secondaryField ? secondaryField.querySelector( 'select' ) : null;

			if ( ! primary || ! secondary ) {
				return;
			}

			if ( primary.value === secondary.value && 'none' !== primary.value ) {
				const reset = changed === secondary ? primary : secondary;
				const none = Array.from( reset.options ).find( function ( option ) {
					return 'none' === option.value;
				} );

				if ( none ) {
					reset.value = 'none';
					const EventConstructor = getElementView( reset ).Event;
					reset.dispatchEvent( new EventConstructor( 'change', { bubbles: true } ) );
				}
			}

			[ primary, secondary ].forEach( function ( select ) {
				select.querySelectorAll( 'option[data-cyber-duplicate-disabled]' ).forEach( function ( option ) {
					option.disabled = false;
					delete option.dataset.cyberDuplicateDisabled;
				} );
			} );

			[
				[ primary, secondary.value ],
				[ secondary, primary.value ],
			].forEach( function ( pair ) {
				const select = pair[0];
				const duplicate = pair[1];

				if ( 'none' === duplicate || 'arrows-dots' === duplicate ) {
					return;
				}

				const option = Array.from( select.options ).find( function ( candidate ) {
					return candidate.value === duplicate && ! candidate.selected;
				} );

				if ( option ) {
					option.disabled = true;
					option.dataset.cyberDuplicateDisabled = 'true';
				}
			} );
		} );
	}

	/**
	 * Keep one conditionally hidden Cyber field enabled for ACF block v3
	 * serialization without changing its visible conditional-logic state.
	 *
	 * @param {Object} field ACF Field instance.
	 * @return {void}
	 */
	function preserveCyberField( field ) {
		if ( ! window.acf || ! field || ! field.$el ) {
			return;
		}

		const key = 'function' === typeof field.get
			? String( field.get( 'key' ) || '' )
			: String( field.$el.data ? field.$el.data( 'key' ) || '' : '' );
		const element = field.$el[0] || null;
		const insideColumnsClone = Boolean(
			element
			&& element.closest
			&& element.closest(
				'.acf-field[data-key="mai_cyber_gallery_columns_clone"]'
			)
		);
		const conditions = field.conditions || null;
		const hidden = 'function' === typeof field.prop
			? Boolean( field.prop( 'hidden' ) )
			: field.$el.hasClass( 'acf-hidden' );

		if (
			( ! key.startsWith( 'mai_cyber_gallery_' ) && ! insideColumnsClone )
			|| ! conditions
			|| ! hidden
		) {
			return;
		}

		window.acf.enable( field.$el, conditions.cid );
		if ( 'function' === typeof field.prop ) {
			field.prop( 'disabled', false );
		}
	}

	/**
	 * Preserve every currently condition-hidden Cyber field known to ACF.
	 *
	 * @return {void}
	 */
	function preserveCyberFields() {
		if ( ! window.acf || 'function' !== typeof window.acf.getFields ) {
			return;
		}

		const fields = window.acf.getFields();

		if ( ! fields || 'function' !== typeof fields.forEach ) {
			return;
		}

		fields.forEach( preserveCyberField );
	}

	/**
	 * Find the ACF block-fields wrapper that owns an editor element.
	 *
	 * @param {Element|null} element Editor element.
	 * @return {Element|null}
	 */
	function getCyberEditorRoot( element ) {
		if ( ! element || ! element.closest ) {
			return null;
		}

		return element.closest( '.acf-block-fields' )
			|| element.closest( '.acf-block-component' )
			|| element.closest( '.acf-fields' );
	}

	/**
	 * Get an initialized ACF field model for one field element.
	 *
	 * @param {Element|null} element ACF field element.
	 * @return {Object|null}
	 */
	function getAcfFieldModel( element ) {
		if (
			! element
			|| ! window.acf
			|| 'function' !== typeof window.acf.getField
			|| ! window.jQuery
		) {
			return null;
		}

		return window.acf.getField( window.jQuery( element ) ) || null;
	}

	/**
	 * Get the canonical logo-overrides repeater and its ACF model.
	 *
	 * @param {Element|null} root ACF block-fields wrapper.
	 * @return {Object|null}
	 */
	function getLogoOverrideStorage( root ) {
		if ( ! root || ! root.querySelector ) {
			return null;
		}

		const element = root.querySelector(
			'.acf-field[data-key="mai_cyber_gallery_filmstrip_logo_overrides"]'
		);
		const model = getAcfFieldModel( element );

		if (
			! model
			|| 'function' !== typeof model.$rows
			|| 'function' !== typeof model.add
			|| 'function' !== typeof model.remove
		) {
			return null;
		}

		return { element, model };
	}

	/**
	 * Clamp one logo scale to the field's canonical range and increment.
	 *
	 * @param {*} value Candidate scale.
	 * @return {number}
	 */
	function normalizeLogoScale( value ) {
		const parsed = Number.parseFloat( value );
		const scale = Number.isFinite( parsed ) ? parsed : 100;

		return Math.max( 50, Math.min( 200, Math.round( scale / 5 ) * 5 ) );
	}

	/**
	 * Read the canonical inputs from one logo-overrides repeater row.
	 *
	 * @param {Element|null} element Repeater row.
	 * @return {Object|null}
	 */
	function getLogoOverrideRow( element ) {
		if ( ! element || ! element.querySelector ) {
			return null;
		}

		const imageField = element.querySelector(
			'.acf-field[data-key="mai_cyber_gallery_filmstrip_logo_override_image"]'
		);
		const scaleField = element.querySelector(
			'.acf-field[data-key="mai_cyber_gallery_filmstrip_logo_override_scale"]'
		);
		const imageInput = imageField
			? imageField.querySelector( '.acf-image-uploader > input[type="hidden"]' )
			: null;
		const scaleInput = scaleField
			? scaleField.querySelector( 'input[type="range"], input[type="number"]' )
			: null;

		if ( ! imageInput || ! scaleInput ) {
			return null;
		}

		return {
			element,
			imageField,
			scaleField,
			imageInput,
			scaleInput,
			attachmentId: Number.parseInt( imageInput.value, 10 ) || 0,
			scale: normalizeLogoScale( scaleInput.value ),
		};
	}

	/**
	 * Read every active row from the canonical logo-overrides repeater.
	 *
	 * @param {Object} storage Repeater storage.
	 * @return {Object[]}
	 */
	function getLogoOverrideRows( storage ) {
		if ( ! storage || ! storage.model ) {
			return [];
		}

		return Array.from( storage.model.$rows() )
			.filter( function ( element ) {
				return 'true' !== element.dataset.cyberLogoRemoving;
			} )
			.map( getLogoOverrideRow )
			.filter( Boolean );
	}

	/**
	 * Get the effective row for an attachment. Last duplicate wins, matching
	 * the PHP sanitizer and the existing public API.
	 *
	 * @param {Object} storage      Repeater storage.
	 * @param {number} attachmentId Attachment ID.
	 * @return {Object|null}
	 */
	function getLogoOverride( storage, attachmentId ) {
		const rows = getLogoOverrideRows( storage );
		let match = null;

		rows.forEach( function ( row ) {
			if ( row.attachmentId === attachmentId ) {
				match = row;
			}
		} );

		return match;
	}

	/**
	 * Set an ACF field through its model, with a native-input fallback.
	 *
	 * @param {Element}          fieldElement ACF field element.
	 * @param {HTMLInputElement} input        Canonical input.
	 * @param {string|number}    value        Value.
	 * @return {void}
	 */
	function setLogoOverrideInput( fieldElement, input, value ) {
		const next = String( value );

		if ( input.value === next ) {
			return;
		}

		const model = getAcfFieldModel( fieldElement );

		if ( model && 'function' === typeof model.val ) {
			model.val( next );
		} else {
			input.value = next;
		}

		const EventConstructor = getElementView( input ).Event;
		input.dispatchEvent( new EventConstructor( 'change', { bubbles: true } ) );
	}

	/**
	 * Write one editor control to the canonical repeater.
	 *
	 * @param {Object}  storage      Repeater storage.
	 * @param {number}  attachmentId Attachment ID.
	 * @param {boolean} enabled      Whether the override is enabled.
	 * @param {number}  scale        Scale percentage.
	 * @return {boolean} Whether the write succeeded.
	 */
	function setLogoOverride( storage, attachmentId, enabled, scale ) {
		if ( ! storage || ! attachmentId || ! window.jQuery ) {
			return false;
		}

		const matches = getLogoOverrideRows( storage ).filter( function ( row ) {
			return row.attachmentId === attachmentId;
		} );

		if ( ! enabled ) {
			matches.forEach( function ( row ) {
				row.element.dataset.cyberLogoRemoving = 'true';
				storage.model.remove( window.jQuery( row.element ) );
			} );

			return true;
		}

		let row = matches.length ? matches[ matches.length - 1 ] : null;

		if ( ! row ) {
			const added = storage.model.add();
			const element = added && added[ 0 ] ? added[ 0 ] : null;
			row = getLogoOverrideRow( element );
		}

		if ( ! row ) {
			return false;
		}

		setLogoOverrideInput( row.imageField, row.imageInput, attachmentId );
		setLogoOverrideInput(
			row.scaleField,
			row.scaleInput,
			normalizeLogoScale( scale )
		);

		return true;
	}

	/**
	 * Read a select from this block's ACF field group.
	 *
	 * @param {Element} root ACF block-fields wrapper.
	 * @param {string}  key  ACF field key.
	 * @return {string}
	 */
	function getCyberSelectValue( root, key ) {
		const select = root.querySelector( '.acf-field[data-key="' + key + '"] select' );
		return select ? select.value : '';
	}

	/**
	 * Whether the current block configuration uses contained filmstrip logos.
	 *
	 * @param {Element} root ACF block-fields wrapper.
	 * @return {boolean}
	 */
	function isFilmstripLogoMode( root ) {
		return 'filmstrip' === getCyberSelectValue( root, 'mai_cyber_gallery_layout' )
			&& 'logo-contain' === getCyberSelectValue(
				root,
				'mai_cyber_gallery_filmstrip_image_mode'
			);
	}

	/**
	 * Feature-detect the active image editor before hiding the fallback.
	 *
	 * @param {Element} root ACF block-fields wrapper.
	 * @return {boolean}
	 */
	function supportsLogoScaleEditor( root ) {
		const customLinks = 'custom' === getCyberSelectValue(
			root,
			'mai_cyber_gallery_click_action'
		);

		if ( customLinks ) {
			return Boolean(
				root.querySelector(
					'.acf-field[data-key="mai_cyber_gallery_image"] '
					+ '[data-cyber-logo-scale-linked]'
				)
			);
		}

		const gallery = root.querySelector(
			'.acf-field[data-key="mai_cyber_gallery_images"]'
		);
		const marker = gallery
			? gallery.querySelector( '[data-cyber-logo-scale-gallery-support]' )
			: null;
		const model = getAcfFieldModel( gallery );

		return Boolean(
			marker
			&& model
			&& 'function' === typeof model.get
			&& 'gallery' === model.get( 'type' )
		);
	}

	/**
	 * Get the attachment represented by one progressive editor control.
	 *
	 * @param {Element} control Logo-scale control.
	 * @return {number}
	 */
	function getLogoScaleAttachmentId( control ) {
		if ( control.hasAttribute( 'data-cyber-logo-scale-linked' ) ) {
			const field = control.closest(
				'.acf-field[data-key="mai_cyber_gallery_image"]'
			);
			const input = field
				? field.querySelector( '.acf-image-uploader > input[type="hidden"]' )
				: null;

			return input ? Number.parseInt( input.value, 10 ) || 0 : 0;
		}

		return Number.parseInt( control.dataset.attachmentId, 10 ) || 0;
	}

	/**
	 * Hydrate and show or hide one logo-scale editor control.
	 *
	 * @param {Element} control Logo-scale control.
	 * @param {Object}  storage Repeater storage.
	 * @param {Element} root    ACF block-fields wrapper.
	 * @return {boolean} Whether the required control inputs exist.
	 */
	function refreshLogoScaleControl( control, storage, root ) {
		const checkbox = control.querySelector( '[data-cyber-logo-scale-enabled]' );
		const range = control.querySelector( '[data-cyber-logo-scale-range]' );
		const input = control.querySelector( '[data-cyber-logo-scale-input]' );
		const output = control.querySelector( '[data-cyber-logo-scale-output]' );
		const attachmentId = getLogoScaleAttachmentId( control );
		const visible = Boolean( attachmentId && isFilmstripLogoMode( root ) );

		if ( ! checkbox || ! range || ! input || ! output ) {
			control.hidden = true;
			return false;
		}

		control.hidden = ! visible;

		if ( ! visible ) {
			return true;
		}

		const override = getLogoOverride( storage, attachmentId );
		const enabled = Boolean( override );
		const scale = override ? override.scale : 100;

		checkbox.checked = enabled;
		range.hidden = ! enabled;
		input.disabled = ! enabled;
		input.value = String( scale );
		output.textContent = String( scale ) + '%';
		control.classList.toggle( 'is-cyber-logo-scale-enabled', enabled );

		return true;
	}

	/**
	 * Reveal the canonical repeater if a progressive control cannot write.
	 *
	 * @param {Element} root ACF block-fields wrapper.
	 * @return {void}
	 */
	function disableLogoScaleEditor( root ) {
		root.classList.remove( 'is-cyber-logo-scale-enhanced' );
		root.querySelectorAll( '[data-cyber-logo-scale-control]' ).forEach( function ( control ) {
			control.hidden = true;
		} );
	}

	/**
	 * Bind one progressive logo-scale editor control once.
	 *
	 * @param {Element} control Logo-scale control.
	 * @param {Object}  storage Repeater storage.
	 * @param {Element} root    ACF block-fields wrapper.
	 * @return {boolean} Whether the control initialized.
	 */
	function bindLogoScaleControl( control, storage, root ) {
		const existingState = logoScaleControlStates.get( control );

		if ( existingState ) {
			existingState.storage = storage;
			existingState.root = root;
			return refreshLogoScaleControl( control, storage, root );
		}

		const checkbox = control.querySelector( '[data-cyber-logo-scale-enabled]' );
		const range = control.querySelector( '[data-cyber-logo-scale-range]' );
		const input = control.querySelector( '[data-cyber-logo-scale-input]' );
		const output = control.querySelector( '[data-cyber-logo-scale-output]' );

		if ( ! checkbox || ! range || ! input || ! output ) {
			return false;
		}

		logoScaleControlStates.set( control, { storage, root } );

		checkbox.addEventListener( 'change', function ( event ) {
			event.stopPropagation();

			const state = logoScaleControlStates.get( control );
			const enabled = checkbox.checked;
			const attachmentId = getLogoScaleAttachmentId( control );

			range.hidden = ! enabled;
			input.disabled = ! enabled;
			control.classList.toggle( 'is-cyber-logo-scale-enabled', enabled );

			if (
				! state
				|| ! attachmentId
				|| ! setLogoOverride(
					state.storage,
					attachmentId,
					enabled,
					normalizeLogoScale( input.value )
				)
			) {
				disableLogoScaleEditor( state ? state.root : root );
				return;
			}

			refreshLogoScaleControl( control, state.storage, state.root );
		} );

		input.addEventListener( 'input', function ( event ) {
			event.stopPropagation();
			output.textContent = String( normalizeLogoScale( input.value ) ) + '%';
		} );

		input.addEventListener( 'change', function ( event ) {
			event.stopPropagation();

			const state = logoScaleControlStates.get( control );
			const attachmentId = getLogoScaleAttachmentId( control );

			if (
				! state
				|| ! attachmentId
				|| ! setLogoOverride(
					state.storage,
					attachmentId,
					true,
					normalizeLogoScale( input.value )
				)
			) {
				disableLogoScaleEditor( state ? state.root : root );
				return;
			}

			refreshLogoScaleControl( control, state.storage, state.root );
		} );

		return refreshLogoScaleControl( control, storage, root );
	}

	/**
	 * Initialize block-local logo sizing in one ACF block-fields wrapper.
	 *
	 * @param {Element} root ACF block-fields wrapper.
	 * @return {void}
	 */
	function initializeLogoScaleRoot( root ) {
		const storage = getLogoOverrideStorage( root );

		if ( ! storage || ! supportsLogoScaleEditor( root ) ) {
			disableLogoScaleEditor( root );
			return;
		}

		const controls = Array.from(
			root.querySelectorAll( '[data-cyber-logo-scale-control]' )
		).filter( function ( control ) {
			return ! control.closest( '.acf-clone' );
		} );
		const ready = controls.every( function ( control ) {
			return bindLogoScaleControl( control, storage, root );
		} );

		if ( ! ready ) {
			disableLogoScaleEditor( root );
			return;
		}

		root.classList.add( 'is-cyber-logo-scale-enhanced' );
	}

	/**
	 * Initialize logo controls in an ACF action scope.
	 *
	 * @param {Document|Element|Object} scope DOM node or ACF jQuery collection.
	 * @return {void}
	 */
	function initializeLogoScaleEditors( scope ) {
		const element = scope && scope[ 0 ] ? scope[ 0 ] : scope;

		if ( ! element || ! element.querySelectorAll ) {
			return;
		}

		const selector = [
			'.acf-field[data-key="mai_cyber_gallery_filmstrip_logo_overrides"]',
			'[data-cyber-logo-scale-control]',
			'[data-cyber-logo-scale-gallery-support]',
		].join( ',' );
		const candidates = Array.from( element.querySelectorAll( selector ) );
		const roots = new Set();

		if ( element.matches && element.matches( selector ) ) {
			candidates.unshift( element );
		}

		candidates.forEach( function ( candidate ) {
			const root = getCyberEditorRoot( candidate );

			if ( root ) {
				roots.add( root );
			}
		} );

		roots.forEach( initializeLogoScaleRoot );
	}


	/**
	 * Normalize an attachment ID without consulting the media store.
	 *
	 * @param {*} value Candidate ID.
	 * @return {number} Positive attachment ID or zero.
	 */
	function normalizeAttachmentId( value ) {
		const id = Number.parseInt( value, 10 );
		return Number.isFinite( id ) && 0 < id ? id : 0;
	}

	/**
	 * Normalize a value saved by an ACF true/false field.
	 *
	 * @param {*} value Candidate value.
	 * @return {boolean} Whether the field is enabled.
	 */
	function isAcfTruthy( value ) {
		return true === value || 1 === value || '1' === String( value );
	}

	/**
	 * Normalize a scalar field to a safe slug while retaining Mai extensions.
	 *
	 * @param {*}      value    Candidate value.
	 * @param {string} fallback Value used when the candidate is empty.
	 * @return {string} Safe slug.
	 */
	function normalizeSlug( value, fallback ) {
		if ( ! [ 'string', 'number' ].includes( typeof value ) ) {
			return fallback;
		}

		return String( value )
			.toLowerCase()
			.replace( /[^a-z0-9_-]/g, '' ) || fallback;
	}

	/**
	 * Normalize a Mai column count to Cyber's supported range.
	 *
	 * The original gallery also offered a non-numeric Fit choice. Cyber's grid
	 * renderer requires an integer, so unsupported values use the Mai default.
	 *
	 * @param {*}      value    Candidate value.
	 * @param {number} fallback Default column count.
	 * @return {string} Column count for ACF block data.
	 */
	function normalizeColumns( value, fallback ) {
		const columns = Number( value );

		return Number.isInteger( columns ) && 1 <= columns && 8 >= columns
			? String( columns )
			: String( fallback );
	}

	/**
	 * Use a saved choice when it is supported by the target Mai field.
	 *
	 * @param {*}        value    Candidate value.
	 * @param {string[]} choices  Supported values.
	 * @param {string}   fallback Default value.
	 * @return {string} Supported choice.
	 */
	function normalizeChoice( value, choices, fallback ) {
		const choice = [ 'string', 'number' ].includes( typeof value )
			? String( value )
			: '';

		return choices.includes( choice ) ? choice : fallback;
	}

	/**
	 * Copy block-level presentation attributes supported by Cyber Gallery.
	 *
	 * @param {Object} source Source block attributes.
	 * @param {Object} target Target block attributes.
	 * @return {Object} Target attributes.
	 */
	function copyGalleryBlockAttributes( source, target ) {
		if ( source && [ 'wide', 'full' ].includes( source.align ) ) {
			target.align = source.align;
		}

		if ( source && 'string' === typeof source.anchor && source.anchor ) {
			target.anchor = source.anchor;
		}

		if ( source && 'string' === typeof source.className && source.className ) {
			target.className = source.className;
		}

		return target;
	}

	/**
	 * Convert the former saved block name without altering its ACF schema.
	 *
	 * @param {Object}   attributes  Legacy ACF block attributes.
	 * @param {Object[]} innerBlocks Nested blocks, when present.
	 * @return {Object} Canonical Cyber Gallery block.
	 */
	function transformLegacyGallery( attributes, innerBlocks ) {
		const nextAttributes = Object.assign( {}, attributes || {}, {
			name: targetBlock,
		} );

		if (
			attributes
			&& attributes.data
			&& 'object' === typeof attributes.data
			&& ! Array.isArray( attributes.data )
		) {
			nextAttributes.data = Object.assign( {}, attributes.data );
		}

		return window.wp.blocks.createBlock(
			targetBlock,
			nextAttributes,
			Array.isArray( innerBlocks ) ? innerBlocks : []
		);
	}

	/**
	 * Get a Mai Gallery's serialized ACF data.
	 *
	 * @param {Object} attributes Mai Gallery block attributes.
	 * @return {Object} Serialized field data.
	 */
	function getMaiGalleryData( attributes ) {
		return attributes
			&& attributes.data
			&& 'object' === typeof attributes.data
			&& ! Array.isArray( attributes.data )
			? attributes.data
			: {};
	}

	/**
	 * Collect ordered custom-link rows from nested or flattened ACF data.
	 *
	 * Mai Gallery normally serializes repeaters as `images_links_0_image`
	 * fields. Supporting nested rows as well makes the transform tolerant of
	 * programmatically created and older block content.
	 *
	 * @param {Object} data Mai Gallery ACF data.
	 * @return {Object[]} Ordered valid image/link rows.
	 */
	function getMaiGalleryLinkedImages( data ) {
		let rows = [];

		if ( Array.isArray( data.images_links ) ) {
			rows = data.images_links;
		} else {
			const count = Number.parseInt( data.images_links, 10 );
			const indexes = new Set();

			if ( Number.isFinite( count ) && 0 < count ) {
				for ( let index = 0; index < count; index++ ) {
					indexes.add( index );
				}
			}

			Object.keys( data ).forEach( function ( key ) {
				const match = key.match( /^images_links_(\d+)_image$/ );

				if ( match ) {
					indexes.add( Number.parseInt( match[1], 10 ) );
				}
			} );

			rows = Array.from( indexes )
				.sort( function ( left, right ) {
					return left - right;
				} )
				.map( function ( index ) {
					return {
						image: data[ `images_links_${ index }_image` ],
						url: data[ `images_links_${ index }_url` ],
					};
				} );
		}

		return rows.map( function ( row ) {
			const image = normalizeAttachmentId( row && row.image );

			if ( ! image ) {
				return null;
			}

			const sourceLink = row && row.link && 'object' === typeof row.link
				? row.link
				: {};
			const url = 'string' === typeof sourceLink.url
				? sourceLink.url
				: ( row && 'string' === typeof row.url ? row.url : '' );

			return {
				image,
				link: {
					url: url.trim(),
					title: 'string' === typeof sourceLink.title ? sourceLink.title : '',
					target: '_blank' === sourceLink.target ? '_blank' : '',
				},
			};
		} ).filter( Boolean );
	}

	/**
	 * Get the active, ordered source for a Mai Gallery.
	 *
	 * @param {Object} attributes Mai Gallery block attributes.
	 * @return {{ custom: boolean, ids: number[], rows: Object[] }} Active source.
	 */
	function getMaiGallerySource( attributes ) {
		const data = getMaiGalleryData( attributes );
		const custom = isAcfTruthy( data.links );
		const rows = custom ? getMaiGalleryLinkedImages( data ) : [];
		const ids = custom
			? rows.map( function ( row ) {
				return row.image;
			} )
			: ( Array.isArray( data.images ) ? data.images : [] )
				.map( normalizeAttachmentId )
				.filter( Boolean );

		return { custom, ids, rows };
	}

	/**
	 * Add one ACF value and its field-key reference to serialized block data.
	 *
	 * @param {Object} data     Target ACF data.
	 * @param {string} name     Field name.
	 * @param {string} fieldKey ACF field key.
	 * @param {*}      value    Serialized value.
	 * @return {void}
	 */
	function setAcfBlockField( data, name, fieldKey, value ) {
		data[ name ] = value;
		data[ `_${ name }` ] = fieldKey;
	}

	/**
	 * Serialize custom-linked images for Cyber's ACF repeater.
	 *
	 * @param {Object}   data Target ACF block data.
	 * @param {Object[]} rows Ordered linked-image rows.
	 * @return {void}
	 */
	function setCyberLinkedImages( data, rows ) {
		setAcfBlockField(
			data,
			'images_links',
			'mai_cyber_gallery_images_links',
			String( rows.length )
		);

		rows.forEach( function ( row, index ) {
			setAcfBlockField(
				data,
				`images_links_${ index }_image`,
				'mai_cyber_gallery_image',
				String( row.image )
			);
			setAcfBlockField(
				data,
				`images_links_${ index }_link`,
				'mai_cyber_gallery_link',
				row.link
			);
		} );
	}

	/**
	 * Collect ordered attachment IDs from modern or legacy Core Gallery data.
	 *
	 * @param {Object}   attributes  Core Gallery attributes.
	 * @param {Object[]} innerBlocks Modern core/image children.
	 * @return {number[]} Ordered valid IDs.
	 */
	function getCoreGalleryIds( attributes, innerBlocks ) {
		let values = [];

		if ( Array.isArray( innerBlocks ) && innerBlocks.length ) {
			values = innerBlocks
				.filter( function ( block ) {
					return block && 'core/image' === block.name;
				} )
				.map( function ( block ) {
					return block.attributes ? block.attributes.id : 0;
				} );
		} else if ( attributes && Array.isArray( attributes.ids ) && attributes.ids.length ) {
			values = attributes.ids;
		} else if ( attributes && Array.isArray( attributes.images ) ) {
			values = attributes.images.map( function ( image ) {
				return image && image.id ? image.id : 0;
			} );
		}

		return values.map( normalizeAttachmentId ).filter( Boolean );
	}

	/**
	 * Convert a Core Gallery aspect ratio to the closest Mai orientation.
	 *
	 * @param {Object} attributes Core Gallery attributes.
	 * @return {string} Mai orientation slug.
	 */
	function getCoreGalleryOrientation( attributes ) {
		if ( attributes && false === attributes.imageCrop ) {
			return 'custom';
		}

		const value = String( attributes && attributes.aspectRatio || '' ).trim();
		const parts = value.split( '/' ).map( Number );
		const ratio = parts.length && Number.isFinite( parts[0] )
			? parts[0] / ( Number.isFinite( parts[1] ) && parts[1] ? parts[1] : 1 )
			: Number.NaN;

		if ( ! Number.isFinite( ratio ) || 0 >= ratio ) {
			return 'landscape';
		}

		if ( 1 === ratio ) {
			return 'square';
		}

		return 1 > ratio ? 'portrait' : 'landscape';
	}

	/**
	 * Convert Core's gallery-level destination to Cyber's click action.
	 *
	 * @param {Object}   attributes  Core Gallery attributes.
	 * @param {Object[]} innerBlocks Modern core/image children.
	 * @return {string} Cyber click action.
	 */
	function getCoreGalleryClickAction( attributes, innerBlocks ) {
		const hasLightbox = Boolean(
			attributes
			&& attributes.lightbox
			&& attributes.lightbox.enabled
		) || (
			Array.isArray( innerBlocks )
			&& innerBlocks.some( function ( block ) {
				return Boolean(
					block
					&& 'core/image' === block.name
					&& block.attributes
					&& block.attributes.lightbox
					&& block.attributes.lightbox.enabled
				);
			} )
		);

		if ( hasLightbox ) {
			return 'lightbox';
		}

		switch ( String( attributes && attributes.linkTo || '' ) ) {
			case 'lightbox':
				return 'lightbox';
			case 'file':
			case 'media':
				return 'media';
			case 'post':
			case 'attachment':
				return 'attachment';
			default:
				return 'none';
		}
	}

	/**
	 * Build a Cyber Gallery block from a Core Gallery.
	 *
	 * @param {Object}   attributes  Core Gallery attributes.
	 * @param {Object[]} innerBlocks Modern core/image children.
	 * @return {Object} Cyber Gallery block.
	 */
	function transformCoreGallery( attributes, innerBlocks ) {
		const ids = getCoreGalleryIds( attributes, innerBlocks );
		const requestedColumns = Number.parseInt( attributes && attributes.columns, 10 );
		const columns = Number.isFinite( requestedColumns ) && 0 < requestedColumns
			? Math.max( 1, Math.min( 8, requestedColumns ) )
			: Math.max( 1, Math.min( 3, ids.length ) );
		const sizeSlug = String( attributes && attributes.sizeSlug || 'large' )
			.toLowerCase()
			.replace( /[^a-z0-9_-]/g, '' ) || 'large';
		const blockAttributes = {
			name: targetBlock,
			mode: 'preview',
			data: {
				images: ids.map( String ),
				_images: 'mai_cyber_gallery_images',
				layout: 'grid',
				_layout: 'mai_cyber_gallery_layout',
				columns: String( columns ),
				_columns: 'mai_columns',
				image_size: sizeSlug,
				_image_size: 'mai_cyber_gallery_image_size',
				image_orientation: getCoreGalleryOrientation( attributes ),
				_image_orientation: 'mai_cyber_gallery_image_orientation',
				click_action: getCoreGalleryClickAction( attributes, innerBlocks ),
				_click_action: 'mai_cyber_gallery_click_action',
				captions: 'below',
				_captions: 'mai_cyber_gallery_captions',
			},
		};

		return window.wp.blocks.createBlock(
			targetBlock,
			copyGalleryBlockAttributes( attributes, blockAttributes )
		);
	}

	/**
	 * Build a Cyber Gallery block from the original Mai Gallery block.
	 *
	 * @param {Object} attributes Mai Gallery block attributes.
	 * @return {Object} Cyber Gallery block.
	 */
	function transformMaiGallery( attributes ) {
		const source = getMaiGallerySource( attributes );
		const oldData = getMaiGalleryData( attributes );
		const data = {};
		const imageOrientation = normalizeSlug(
			oldData.image_orientation,
			'landscape'
		);
		const blockAttributes = {
			name: targetBlock,
			mode: 'preview',
			data,
		};

		setAcfBlockField( data, 'layout', 'mai_cyber_gallery_layout', 'grid' );
		setAcfBlockField(
			data,
			'click_action',
			'mai_cyber_gallery_click_action',
			source.custom
				? 'custom'
				: ( isAcfTruthy( oldData.lightbox ) ? 'lightbox' : 'none' )
		);

		if ( source.custom ) {
			setCyberLinkedImages( data, source.rows );
		} else {
			setAcfBlockField(
				data,
				'images',
				'mai_cyber_gallery_images',
				source.ids.map( String )
			);
		}

		setAcfBlockField(
			data,
			'image_orientation',
			'mai_cyber_gallery_image_orientation',
			imageOrientation
		);
		setAcfBlockField(
			data,
			'image_size',
			'mai_cyber_gallery_image_size',
			normalizeSlug(
				oldData.image_size,
				'custom' === imageOrientation ? 'landscape-md' : 'large'
			)
		);
		setAcfBlockField( data, 'image_radius', 'mai_cyber_gallery_image_radius', 'rounded' );
		setAcfBlockField( data, 'captions', 'mai_cyber_gallery_captions', 'below' );
		setAcfBlockField(
			data,
			'shadow',
			'mai_cyber_gallery_shadow',
			isAcfTruthy( oldData.shadow ) ? '1' : '0'
		);
		setAcfBlockField( data, 'hover', 'mai_cyber_gallery_hover', 'zoom' );
		setAcfBlockField(
			data,
			'columns',
			'mai_columns',
			normalizeColumns( oldData.columns, 3 )
		);
		setAcfBlockField(
			data,
			'columns_responsive',
			'mai_columns_responsive',
			isAcfTruthy( oldData.columns_responsive ) ? '1' : '0'
		);
		setAcfBlockField(
			data,
			'columns_md',
			'mai_columns_md',
			normalizeColumns( oldData.columns_md, 1 )
		);
		setAcfBlockField(
			data,
			'columns_sm',
			'mai_columns_sm',
			normalizeColumns( oldData.columns_sm, 1 )
		);
		setAcfBlockField(
			data,
			'columns_xs',
			'mai_columns_xs',
			normalizeColumns( oldData.columns_xs, 1 )
		);
		setAcfBlockField(
			data,
			'align_columns',
			'mai_align_columns',
			normalizeChoice( oldData.align_columns, [ '', 'start', 'center', 'end' ], '' )
		);
		setAcfBlockField(
			data,
			'align_columns_vertical',
			'mai_align_columns_vertical',
			normalizeChoice(
				oldData.align_columns_vertical,
				[ '', 'full', 'top', 'middle', 'bottom' ],
				''
			)
		);

		[
			[ 'column_gap', 'mai_column_gap', 'xl' ],
			[ 'row_gap', 'mai_row_gap', 'xl' ],
			[ 'margin_top', 'mai_margin_top', '' ],
			[ 'margin_bottom', 'mai_margin_bottom', '' ],
		].forEach( function ( field ) {
			setAcfBlockField(
				data,
				field[0],
				field[1],
				normalizeChoice(
					oldData[ field[0] ],
					[ '', 'md', 'lg', 'xl', 'xxl', 'xxxl', 'xxxxl' ],
					field[2]
				)
			);
		} );

		return window.wp.blocks.createBlock(
			targetBlock,
			copyGalleryBlockAttributes( attributes, blockAttributes )
		);
	}

	/**
	 * Add supported gallery transforms while preserving other transforms.
	 *
	 * @param {Object} settings Block settings.
	 * @param {string} name     Block name.
	 * @return {Object} Filtered block settings.
	 */
	function addGalleryTransforms( settings, name ) {
		if ( targetBlock !== name ) {
			return settings;
		}

		const transforms = settings.transforms || {};
		const from = Array.isArray( transforms.from ) ? transforms.from : [];

		return Object.assign( {}, settings, {
			transforms: Object.assign( {}, transforms, {
				from: from.concat( [
					{
						type: 'block',
						blocks: [ legacyBlock ],
						transform: transformLegacyGallery,
					},
					{
						type: 'block',
						blocks: [ 'core/gallery' ],
						isMatch: function ( attributes, innerBlocks ) {
							return 0 < getCoreGalleryIds( attributes, innerBlocks ).length;
						},
						transform: transformCoreGallery,
					},
					{
						type: 'block',
						blocks: [ 'acf/mai-gallery' ],
						isMatch: function ( attributes ) {
							return 0 < getMaiGallerySource( attributes ).ids.length;
						},
						transform: transformMaiGallery,
					},
				] ),
			} ),
		} );
	}

	/**
	 * Get a registered editor store without logging an invalid-store warning.
	 *
	 * @param {string} storeName Store name.
	 * @return {Object|null} Store dispatchers.
	 */
	function getEditorDispatch( storeName ) {
		try {
			if ( ! window.wp.data.select( storeName ) ) {
				return null;
			}
			return window.wp.data.dispatch( storeName );
		} catch {
			return null;
		}
	}

	/**
	 * Open the block inspector when its editor store exposes the public action.
	 *
	 * @return {void}
	 */
	function openBlockInspector() {
		[
			[ 'core/edit-post', 'edit-post/block' ],
			[ 'core/edit-site', 'edit-site/block' ],
		].some( function ( entry ) {
			const dispatch = getEditorDispatch( entry[0] );
			if ( ! dispatch || 'function' !== typeof dispatch.openGeneralSidebar ) {
				return false;
			}
			dispatch.openGeneralSidebar( entry[1] );
			return true;
		} );
	}

	/**
	 * Find ACF's current expanded-editor launcher for the selected block.
	 *
	 * @return {HTMLButtonElement|null} Visible launcher.
	 */
	function getExpandedEditorLauncher() {
		const sidebar = Array.from(
			document.querySelectorAll( '.acf-blocks-open-expanded-editor-btn' )
		).find( function ( button ) {
			return ! button.disabled && 0 < button.getClientRects().length;
		} );

		if ( sidebar ) {
			return sidebar;
		}

		const editLabel = window.acf && 'function' === typeof window.acf.__
			? window.acf.__( 'Edit Block' )
			: 'Edit Block';

		return Array.from(
			document.querySelectorAll( '.block-editor-block-toolbar button[aria-label]' )
		).find( function ( button ) {
			return button.getAttribute( 'aria-label' ) === editLabel
				&& ! button.disabled
				&& 0 < button.getClientRects().length;
		} ) || null;
	}

	/**
	 * Select a Cyber block and progressively open ACF's expanded editor.
	 *
	 * @param {HTMLButtonElement} button Preview button.
	 * @return {void}
	 */
	function openExpandedEditor( button ) {
		const block = button.closest( '[data-block]' );
		const clientId = block ? block.getAttribute( 'data-block' ) : '';
		const blockEditor = getEditorDispatch( 'core/block-editor' );
		const status = button.parentElement
			? button.parentElement.querySelector( '[data-cyber-manage-gallery-status]' )
			: null;

		if ( ! clientId || ! blockEditor || 'function' !== typeof blockEditor.selectBlock ) {
			if ( status ) {
				status.hidden = false;
			}
			return;
		}

		if ( status ) {
			status.hidden = true;
		}

		blockEditor.selectBlock( clientId );
		openBlockInspector();

		let attempts = 0;
		const tryOpen = function () {
			attempts++;
			let selection = null;
			try {
				selection = window.wp.data.select( 'core/block-editor' );
			} catch {
				if ( status ) {
					status.hidden = false;
				}
				return;
			}
			const selected = selection && 'function' === typeof selection.getSelectedBlockClientId
				? selection.getSelectedBlockClientId()
				: '';

			if ( selected !== clientId ) {
				return;
			}

			const launcher = getExpandedEditorLauncher();
			if ( launcher ) {
				launcher.click();
				return;
			}

			if ( 10 > attempts ) {
				window.setTimeout( tryOpen, 50 );
			} else if ( status ) {
				status.hidden = false;
			}
		};

		window.setTimeout( tryOpen, 0 );
	}

	/**
	 * Bind empty-preview Manage Gallery buttons in one ACF preview.
	 *
	 * @param {Document|Element|Object} scope ACF action scope.
	 * @return {void}
	 */
	function bindManageGalleryButtons( scope ) {
		const element = scope && scope[0] ? scope[0] : scope;
		if ( ! element || ! element.querySelectorAll ) {
			return;
		}

		const buttons = Array.from(
			element.querySelectorAll( '[data-cyber-manage-gallery]' )
		);
		if ( element.matches && element.matches( '[data-cyber-manage-gallery]' ) ) {
			buttons.unshift( element );
		}

		buttons.forEach( function ( button ) {
			if ( manageButtonStates.has( button ) ) {
				return;
			}
			manageButtonStates.add( button );
			button.addEventListener( 'click', function () {
				openExpandedEditor( button );
			} );
		} );
	}

	/**
	 * Collect each distinct preview runtime available to an iframe or its parent.
	 *
	 * @param {Document|Element} scope Preview scope.
	 * @return {Object[]} Available runtime objects.
	 */
	function getPreviewRuntimes( scope ) {
		const previewView = getElementView( scope );
		const runtimes = [
			previewView.CyberGalleryMaiPackingRuntime,
			previewView.CyberGalleryMaiRuntime,
			previewView.CyberGalleryMaiComparisonRuntime,
			window.CyberGalleryMaiPackingRuntime,
			window.CyberGalleryMaiRuntime,
			window.CyberGalleryMaiComparisonRuntime,
			previewView.MaiCyberGalleryPackingRuntime,
			previewView.MaiCyberGalleryRuntime,
			previewView.MaiCyberGalleryComparisonRuntime,
			window.MaiCyberGalleryPackingRuntime,
			window.MaiCyberGalleryRuntime,
			window.MaiCyberGalleryComparisonRuntime,
		];

		return Array.from( new Set( runtimes.filter( Boolean ) ) );
	}

	/**
	 * Tear down stateful enhancements within a discarded preview scope.
	 *
	 * @param {Document|Element} scope Discarded preview scope.
	 * @return {void}
	 */
	function destroyBlockPreview( scope ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return;
		}

		getPreviewRuntimes( scope ).forEach( function ( runtime ) {
			if ( 'function' === typeof runtime.destroy ) {
				runtime.destroy( scope );
			}
		} );
	}

	/**
	 * Reinitialize progressive enhancements after an ACF preview render.
	 *
	 * Runtime objects may live in the parent editor window or in the canvas
	 * window. Supporting both keeps this bridge compatible with the always-
	 * iframed WordPress 7.1 post editor and older editor configurations.
	 *
	 * @param {Object|Element} block ACF preview wrapper or element.
	 * @return {void}
	 */
	function initializeBlockPreview( block ) {
		const element = block && block[0] ? block[0] : block;

		getPreviewRuntimes( element ).forEach( function ( runtime ) {
			if ( 'function' === typeof runtime.initialize ) {
				runtime.initialize( element );
			}
		} );
		preserveCyberFields();
		bindManageGalleryButtons( element );
	}

	/**
	 * Initialize every Cyber Gallery preview already present in one scope.
	 *
	 * ACF can finish the first server-rendered previews before this bundle adds
	 * its render_block_preview actions. WordPress 7.1 also keeps those previews
	 * in a separate canvas document, outside observers attached to the editor
	 * parent. An explicit initial scan closes both timing gaps.
	 *
	 * @param {Document|Element} scope Editor canvas document or added subtree.
	 * @return {void}
	 */
	function initializeExistingBlockPreviews( scope ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return;
		}

		const selector = '[data-type="acf/cyber-gallery-mai"],'
			+ '[data-type="acf/mai-cyber-gallery"]';
		const blocks = new Set( scope.querySelectorAll( selector ) );

		if ( scope.matches && scope.matches( selector ) ) {
			blocks.add( scope );
		}

		blocks.forEach( initializeBlockPreview );
	}

	/**
	 * Disconnect one canvas document and release its preview runtimes.
	 *
	 * @param {Object} state Editor iframe state.
	 * @return {void}
	 */
	function disconnectEditorDocument( state ) {
		if ( state.observer ) {
			state.observer.disconnect();
			state.observer = null;
		}

		if ( state.document ) {
			destroyBlockPreview( state.document );
			state.document = null;
		}
	}

	/**
	 * Disconnect a removed editor-canvas iframe.
	 *
	 * @param {HTMLIFrameElement} frame Removed editor canvas.
	 * @return {void}
	 */
	function disconnectEditorCanvas( frame ) {
		const state = editorCanvasFrames.get( frame );

		if ( ! state ) {
			return;
		}

		disconnectEditorDocument( state );
		if ( state.loadHandler ) {
			frame.removeEventListener( 'load', state.loadHandler );
		}
		editorCanvasFrames.delete( frame );
	}

	/**
	 * Connect one WordPress editor-canvas iframe to the parent editor bridge.
	 *
	 * @param {HTMLIFrameElement} frame Editor canvas iframe.
	 * @return {void}
	 */
	function connectEditorCanvas( frame ) {
		if ( ! frame || ! frame.addEventListener ) {
			return;
		}

		let state = editorCanvasFrames.get( frame );

		if ( ! state ) {
			state = {
				document: null,
				observer: null,
				loadHandler: null,
			};
			state.loadHandler = function () {
				connectEditorCanvas( frame );
			};
			editorCanvasFrames.set( frame, state );
			frame.addEventListener( 'load', state.loadHandler );
		}

		let editorDocument = null;
		try {
			editorDocument = frame.contentDocument;
		} catch {
			disconnectEditorDocument( state );
			return;
		}

		if ( ! editorDocument || ! editorDocument.querySelectorAll ) {
			disconnectEditorDocument( state );
			return;
		}

		if ( state.document !== editorDocument ) {
			disconnectEditorDocument( state );
			state.document = editorDocument;

			const editorView = editorDocument.defaultView;
			const target = editorDocument.body || editorDocument.documentElement;

			if ( editorView && target && 'MutationObserver' in editorView ) {
				state.observer = new editorView.MutationObserver( function ( mutations ) {
					mutations.forEach( function ( mutation ) {
						mutation.removedNodes.forEach( function ( node ) {
							if ( 1 === node.nodeType ) {
								destroyBlockPreview( node );
							}
						} );
						mutation.addedNodes.forEach( function ( node ) {
							if ( 1 === node.nodeType ) {
								initializeExistingBlockPreviews( node );
							}
						} );
					} );
				} );
				state.observer.observe( target, {
					childList: true,
					subtree: true,
				} );
			}
		}

		initializeExistingBlockPreviews( editorDocument );
	}

	/**
	 * Collect WordPress editor canvas iframes in one parent-document scope.
	 *
	 * @param {Document|Element} scope Parent document or subtree.
	 * @return {Set<HTMLIFrameElement>} Matching canvas iframes.
	 */
	function getEditorCanvases( scope ) {
		if ( ! scope || ! scope.querySelectorAll ) {
			return new Set();
		}

		const selector = 'iframe[name="editor-canvas"], iframe.editor-canvas__iframe';
		const frames = new Set( scope.querySelectorAll( selector ) );

		if ( scope.matches && scope.matches( selector ) ) {
			frames.add( scope );
		}

		return frames;
	}

	/**
	 * Discover WordPress editor canvas iframes in one parent-document scope.
	 *
	 * @param {Document|Element} scope Parent document or added subtree.
	 * @return {void}
	 */
	function initializeEditorCanvases( scope ) {
		getEditorCanvases( scope ).forEach( connectEditorCanvas );
	}

	/**
	 * Disconnect editor canvas iframes removed with a parent-document subtree.
	 *
	 * @param {Document|Element} scope Removed subtree.
	 * @return {void}
	 */
	function disconnectEditorCanvases( scope ) {
		getEditorCanvases( scope ).forEach( disconnectEditorCanvas );
	}

	/**
	 * Register ACF integrations once.
	 *
	 * @return {void}
	 */
	function initializeAcf() {
		if (
			initializeAcf.done
			|| ! window.acf
			|| 'function' !== typeof window.acf.addAction
		) {
			return;
		}

		initializeAcf.done = true;
		window.acf.addAction( 'hide_field', function ( field, context ) {
			if ( 'conditional_logic' === context ) {
				preserveCyberField( field );
			}
		} );
		window.acf.addAction( 'append', function ( element ) {
			preserveCyberFields();
			initializeLogoScaleEditors( element );
		} );
		window.acf.addAction( 'ready', function () {
			preserveCyberFields();
			initializeLogoScaleEditors( document );
		} );
		[
			'new_field/key=mai_cyber_gallery_image',
			'remount_field/key=mai_cyber_gallery_image',
			'remount_field/key=mai_cyber_gallery_filmstrip_logo_overrides',
		].forEach( function ( action ) {
			window.acf.addAction( action, function ( field ) {
				initializeLogoScaleEditors( field && field.$el ? field.$el : document );
			} );
		} );
		window.acf.addAction( 'change_field', function ( field ) {
			if (
				field
				&& 'function' === typeof field.get
				&& 'mai_cyber_gallery_image' === field.get( 'key' )
			) {
				initializeLogoScaleEditors( field.$el ? field.$el : document );
			}
		} );
		[
			'render_block_preview/type=cyber-gallery-mai',
			'render_block_preview/type=mai-cyber-gallery',
		].forEach( function ( action ) {
			window.acf.addAction( action, initializeBlockPreview );
		} );

		preserveCyberFields();
		initializeLogoScaleEditors( document );
	}

	if ( window.wp && window.wp.hooks && window.wp.blocks ) {
		window.wp.hooks.addFilter(
			'blocks.registerBlockType',
			'cyber-gallery-mai/gallery-transforms',
			addGalleryTransforms
		);
	}

	document.addEventListener( 'change', function ( event ) {
		const target = event.target;

		if (
			target
			&& target.matches
			&& target.matches(
				'.acf-field[data-key="mai_cyber_gallery_slider_navigation"] select,'
				+ '.acf-field[data-key="mai_cyber_gallery_slider_navigation_secondary"] select'
			)
		) {
			syncSliderNavigationSelects( target.closest( '.acf-fields' ) || document, target );
		}

		if (
			target
			&& target.matches
			&& target.matches(
				'.acf-field[data-key="mai_cyber_gallery_layout"] select,'
				+ '.acf-field[data-key="mai_cyber_gallery_click_action"] select,'
				+ '.acf-field[data-key="mai_cyber_gallery_filmstrip_image_mode"] select,'
				+ '.acf-field[data-key="mai_cyber_gallery_image"] '
				+ '.acf-image-uploader > input[type="hidden"]'
			)
		) {
			initializeLogoScaleEditors( getCyberEditorRoot( target ) || document );
		}
	} );

	if ( 'MutationObserver' in window ) {
		const observer = new window.MutationObserver( function ( mutations ) {
			initializeAcf();
			mutations.forEach( function ( mutation ) {
				mutation.removedNodes.forEach( function ( node ) {
					if ( 1 === node.nodeType ) {
						destroyBlockPreview( node );
						disconnectEditorCanvases( node );
					}
				} );
				mutation.addedNodes.forEach( function ( node ) {
					if ( 1 === node.nodeType ) {
						syncSliderNavigationSelects( node, null );
						initializeLogoScaleEditors( node );
						bindManageGalleryButtons( node );
						initializeEditorCanvases( node );
					}
				} );
			} );
		} );
		observer.observe( document.body || document.documentElement, {
			childList: true,
			subtree: true,
		} );
	}

	syncSliderNavigationSelects( document, null );
	initializeAcf();
	initializeEditorCanvases( document );
}() );
