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

	var bar = document.querySelector( '.notice-bar[data-notice-dismissible="1"]' );

	if ( ! bar ) {
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

	// Reveal the bar now that we know it should show.
	bar.hidden = false;

	var close = bar.querySelector( '.notice-bar__close' );

	if ( ! close ) {
		return;
	}

	close.addEventListener( 'click', function () {
		remember();
		bar.hidden = true;

		// Move focus somewhere sensible after the region disappears.
		if ( document.body ) {
			document.body.setAttribute( 'tabindex', '-1' );
			document.body.focus();
			document.body.removeAttribute( 'tabindex' );
		}

		window.setTimeout( function () {
			bar.parentNode && bar.parentNode.removeChild( bar );
		}, 50 );
	} );
} )();
