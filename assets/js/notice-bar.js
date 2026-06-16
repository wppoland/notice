/**
 * Notice — storefront announcement bar (dismissal).
 *
 * Progressive enhancement, dependency-free. The bar is rendered with [hidden]
 * when dismissible so there is no flash before this script decides whether the
 * visitor has already dismissed it. The choice is stored in localStorage (no
 * cookies, no PII) under a key that is versioned by the message text, so a new
 * announcement always reappears. If localStorage is unavailable the bar simply
 * stays visible and the close button hides it for the current page view.
 */
( function () {
	'use strict';

	// Reveal-time "going on air" sweep: play it once, for any visible bar,
	// the moment it is shown. Presentation only — the bar works without it.
	function goLive( el ) {
		if ( ! el ) {
			return;
		}

		// Re-trigger the CSS animation reliably even if the class lingers.
		el.classList.remove( 'is-live' );

		// Force a reflow so removing/adding the class restarts the animation.
		void el.offsetWidth;

		el.classList.add( 'is-live' );
	}

	var dismissibleBar = document.querySelector(
		'.notice-bar[data-notice-dismissible="1"]'
	);

	// A non-dismissible bar is shown server-side (no [hidden]); play its sweep
	// now and we are done — there is nothing else to wire up.
	if ( ! dismissibleBar ) {
		goLive( document.querySelector( '.notice-bar' ) );
		return;
	}

	var bar = dismissibleBar;

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

		// days === 0 means "remember forever".
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
		// Leave it hidden and out of the layout.
		bar.parentNode && bar.parentNode.removeChild( bar );
		return;
	}

	// Reveal the bar now that we know it should show, and send it on air.
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

		// Move focus somewhere sensible before the region disappears.
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

		// Sign off: collapse the bar's own height, then remove it. Pin the
		// current height first so max-block-size has a value to animate from.
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

		// Fallback in case transitionend never fires.
		window.setTimeout( finish, 450 );
	} );
} )();
