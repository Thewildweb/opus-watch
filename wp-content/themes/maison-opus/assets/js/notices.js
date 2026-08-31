/**
 * WooCommerce notices — dismissible, and gone after five seconds.
 *
 * Notices arrive two ways: rendered server-side into the page, and injected
 * into the cart drawer by cart-drawer.js. Both call enhance().
 *
 * The countdown pauses while the pointer or the keyboard focus is on a
 * notice, so a message never disappears out from under someone reading it.
 */
( function () {
	'use strict';

	var SELECTOR = '.woocommerce-message, .woocommerce-error, .woocommerce-info';
	var TIMEOUT  = 5000;

	function remove( wrapper ) {
		if ( wrapper.parentNode ) {
			wrapper.parentNode.removeChild( wrapper );
		}
	}

	function dismiss( wrapper ) {
		if ( wrapper.classList.contains( 'is-dismissed' ) ) {
			return;
		}
		wrapper.classList.add( 'is-dismissed' );

		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			remove( wrapper );
			return;
		}
		var done = function () {
			wrapper.removeEventListener( 'transitionend', done );
			remove( wrapper );
		};
		wrapper.addEventListener( 'transitionend', done );
		// Belt and braces: if the transition never fires, clear it anyway.
		window.setTimeout( function () { remove( wrapper ); }, 600 );
	}

	function setup( notice ) {
		if ( notice.dataset.opusNotice ) {
			return;
		}
		notice.dataset.opusNotice = '1';

		// WooCommerce renders errors as a <ul>, so the close button cannot be
		// a child of the notice itself — wrap it instead.
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'opus-notice';
		notice.parentNode.insertBefore( wrapper, notice );
		wrapper.appendChild( notice );

		var close = document.createElement( 'button' );
		close.type = 'button';
		close.className = 'opus-notice__close';
		close.setAttribute( 'aria-label', wrapper.getAttribute( 'data-close-label' ) || 'Melding sluiten' );
		close.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg>';
		close.addEventListener( 'click', function () { dismiss( wrapper ); } );
		wrapper.appendChild( close );

		var timer = null;
		var start = function () {
			window.clearTimeout( timer );
			timer = window.setTimeout( function () { dismiss( wrapper ); }, TIMEOUT );
		};
		var stop = function () {
			window.clearTimeout( timer );
		};

		wrapper.addEventListener( 'mouseenter', stop );
		wrapper.addEventListener( 'mouseleave', start );
		wrapper.addEventListener( 'focusin', stop );
		wrapper.addEventListener( 'focusout', start );

		start();
	}

	function enhance( root ) {
		var scope = root || document;
		Array.prototype.forEach.call( scope.querySelectorAll( SELECTOR ), setup );
	}

	window.opusNotices = { enhance: enhance };

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () { enhance( document ); } );
	} else {
		enhance( document );
	}
} )();
