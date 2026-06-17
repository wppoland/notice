/**
 * Notice — storefront announcement bar (dismissal).
 *
 * Progressive enhancement, dependency-free. Dismissible bars are rendered with
 * [hidden] so there is no flash before this script decides whether the visitor
 * has already dismissed them. The choice is stored in localStorage (no cookies,
 * no PII) under a key that is versioned by the bar id and message text, so a
 * new announcement always reappears. If localStorage is unavailable the bar
 * simply stays visible and the close button hides it for the current page view.
 */
( function () {
	'use strict';

	function goLive( el ) {
		if ( ! el ) {
			return;
		}

		el.classList.remove( 'is-live' );
		void el.offsetWidth;
		el.classList.add( 'is-live' );
	}

	function initBar( bar ) {
		if ( ! bar ) {
			return;
		}

		var dismissible = bar.getAttribute( 'data-notice-dismissible' ) === '1';

		if ( ! dismissible ) {
			goLive( bar );
			return;
		}

		var key = bar.getAttribute( 'data-notice-key' ) || 'notice_dismissed';
		var days = parseInt( bar.getAttribute( 'data-notice-days' ), 10 );

		if ( isNaN( days ) || days < 0 ) {
			days = 0;
		}

		function store() {
			try {
				return window.localStorage;
			} catch ( e ) {
				return null;
			}
		}

		function isDismissed() {
			var ls = store();

			if ( ! ls ) {
				return false;
			}

			var raw = ls.getItem( key );

			if ( ! raw ) {
				return false;
			}

			if ( days === 0 ) {
				return true;
			}

			var when = parseInt( raw, 10 );

			if ( isNaN( when ) ) {
				return false;
			}

			var ageDays = ( Date.now() - when ) / 86400000;

			return ageDays < days;
		}

		function remember() {
			var ls = store();

			if ( ! ls ) {
				return;
			}

			try {
				ls.setItem( key, String( Date.now() ) );
			} catch ( e ) {
				/* Quota or private mode — fail quietly. */
			}
		}

		if ( isDismissed() ) {
			bar.parentNode && bar.parentNode.removeChild( bar );
			return;
		}

		bar.hidden = false;
		goLive( bar );

		var close = bar.querySelector( '.notice-bar__close' );

		if ( ! close ) {
			return;
		}

		var reduceMotion =
			window.matchMedia &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function drop() {
			bar.parentNode && bar.parentNode.removeChild( bar );
		}

		close.addEventListener( 'click', function () {
			remember();

			if ( document.body ) {
				document.body.setAttribute( 'tabindex', '-1' );
				document.body.focus();
				document.body.removeAttribute( 'tabindex' );
			}

			if ( reduceMotion ) {
				bar.hidden = true;
				window.setTimeout( drop, 50 );
				return;
			}

			bar.style.maxBlockSize = bar.offsetHeight + 'px';
			void bar.offsetWidth;
			bar.classList.add( 'is-signing-off' );

			var done = false;
			var finish = function () {
				if ( done ) {
					return;
				}

				done = true;
				drop();
			};

			bar.addEventListener( 'transitionend', finish );
			window.setTimeout( finish, 450 );
		} );
	}

	var bars = document.querySelectorAll( '.notice-bar' );

	if ( ! bars.length ) {
		return;
	}

	bars.forEach( initBar );
} )();
