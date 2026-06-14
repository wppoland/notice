/**
 * Notice — admin settings enhancements (progressive, dependency-free).
 *
 * 1. Inline help: each "?" button is wired to an accessible popover. Where the
 *    native Popover API exists it is used; otherwise a small show/hide fallback
 *    keeps it keyboard- and screen-reader-operable via aria-expanded.
 * 2. Colour pickers: the native <input type="color"> mirrors its paired hex
 *    text field both ways.
 * 3. Live preview: reflects message, colours and the CTA in real time, debounced
 *    and via event delegation.
 *
 * Loaded with `defer`. With JS off, every setting still saves and the static
 * preview reflects the last saved state.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.notice-admin' );

	if ( ! root ) {
		return;
	}

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		Object.prototype.hasOwnProperty.call( HTMLElement.prototype, 'popover' );

	/* ---- Inline help popovers (fallback only) ------------------------ */

	function closeAllFallback( except ) {
		root.querySelectorAll( '.notice-admin__help[aria-expanded="true"]' ).forEach(
			function ( btn ) {
				if ( btn === except ) {
					return;
				}
				btn.setAttribute( 'aria-expanded', 'false' );
				var tip = document.getElementById(
					btn.getAttribute( 'aria-describedby' )
				);
				if ( tip ) {
					tip.hidden = true;
				}
			}
		);
	}

	root.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '.notice-admin__help' );

		if ( ! btn || supportsPopover ) {
			return;
		}

		var tip = document.getElementById( btn.getAttribute( 'aria-describedby' ) );

		if ( ! tip ) {
			return;
		}

		var open = btn.getAttribute( 'aria-expanded' ) === 'true';
		closeAllFallback( btn );
		btn.setAttribute( 'aria-expanded', String( ! open ) );
		tip.hidden = open;
	} );

	if ( ! supportsPopover ) {
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) {
				closeAllFallback( null );
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest( '.notice-admin__help, .notice-admin__tip' ) ) {
				closeAllFallback( null );
			}
		} );
	}

	/* ---- Colour picker mirroring ------------------------------------- */

	root.querySelectorAll( 'input[type="color"][data-notice-color-for]' ).forEach(
		function ( picker ) {
			var textId = picker.getAttribute( 'data-notice-color-for' );
			var text = document.getElementById( textId );

			if ( ! text ) {
				return;
			}

			picker.addEventListener( 'input', function () {
				text.value = picker.value;
				schedule();
			} );

			text.addEventListener( 'input', function () {
				if ( /^#?[0-9a-fA-F]{6}$/.test( text.value.trim() ) ) {
					var v = text.value.trim();
					picker.value = v.charAt( 0 ) === '#' ? v : '#' + v;
				}
				schedule();
			} );
		}
	);

	/* ---- Live preview ------------------------------------------------ */

	var stage = root.querySelector( '[data-notice-preview]' );
	var previewMessage = root.querySelector( '[data-notice-preview-message]' );
	var previewCta = root.querySelector( '[data-notice-preview-cta]' );

	function field( name ) {
		return root.querySelector(
			'[name="notice_settings[' + name + ']"]'
		);
	}

	function hex( name, fallback ) {
		var input = field( name );
		var v = input ? input.value.trim() : '';
		if ( /^#?[0-9a-fA-F]{6}$/.test( v ) ) {
			return v.charAt( 0 ) === '#' ? v : '#' + v;
		}
		return fallback;
	}

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	function render() {
		if ( ! stage ) {
			return;
		}

		stage.style.setProperty( '--notice-bg', hex( 'bg_color', '#1e1e1e' ) );
		stage.style.setProperty( '--notice-fg', hex( 'text_color', '#ffffff' ) );
		stage.style.setProperty( '--notice-link', hex( 'link_color', '#ffd166' ) );

		var messageField = field( 'message' );
		var message = messageField ? messageField.value.trim() : '';

		if ( previewMessage ) {
			// Show the typed message. We escape it here for safety in the live
			// preview; the saved output is sanitised server-side with wp_kses.
			previewMessage.innerHTML = message
				? escapeHtml( message )
				: escapeHtml( previewMessage.getAttribute( 'data-empty' ) || 'Your announcement will appear here.' );
		}

		if ( previewCta ) {
			var labelField = field( 'link_label' );
			var urlField = field( 'link_url' );
			var label = labelField ? labelField.value.trim() : '';
			var url = urlField ? urlField.value.trim() : '';

			if ( label && url ) {
				previewCta.textContent = label;
				previewCta.hidden = false;
			} else {
				previewCta.hidden = true;
			}
		}
	}

	if ( previewMessage && ! previewMessage.getAttribute( 'data-empty' ) ) {
		previewMessage.setAttribute(
			'data-empty',
			previewMessage.textContent.trim()
		);
	}

	var debounce;
	function schedule() {
		window.clearTimeout( debounce );
		debounce = window.setTimeout( render, 100 );
	}

	root.addEventListener( 'input', schedule );
	root.addEventListener( 'change', schedule );

	render();
} )();
