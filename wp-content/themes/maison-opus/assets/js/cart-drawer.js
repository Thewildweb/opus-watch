/**
 * Mini-cart drawer.
 *
 * Two jobs:
 *  1. Open the drawer whenever WooCommerce reports a product was added.
 *  2. Make the single-product add-to-cart form submit over AJAX, so buying a
 *     watch no longer reloads the page with no visible confirmation. Archive
 *     add-to-cart buttons already go through WooCommerce's own AJAX handler.
 *
 * Falls back to a normal form post whenever anything is unavailable.
 */
( function ( $ ) {
	'use strict';

	var drawer = document.getElementById( 'opus-cart-drawer' );
	if ( ! drawer ) {
		return;
	}

	var panel      = drawer.querySelector( '.opus-drawer__panel' );
	var lastFocus  = null;
	var FOCUSABLE  = 'a[href], button:not([disabled]), input, select, textarea';

	function open() {
		if ( ! drawer.hidden ) {
			return;
		}
		lastFocus   = document.activeElement;
		drawer.hidden = false;
		// Next frame, so the transition runs from the closed state.
		window.requestAnimationFrame( function () {
			drawer.classList.add( 'is-open' );
		} );
		document.body.classList.add( 'opus-drawer-open' );
		panel.focus();
	}

	function close() {
		if ( drawer.hidden ) {
			return;
		}
		drawer.classList.remove( 'is-open' );
		document.body.classList.remove( 'opus-drawer-open' );

		var done = function () {
			drawer.hidden = true;
			panel.removeEventListener( 'transitionend', done );
		};
		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			done();
		} else {
			panel.addEventListener( 'transitionend', done );
		}

		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}
	}

	drawer.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-opus-drawer-close]' ) ) {
			close();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( drawer.hidden ) {
			return;
		}
		if ( 'Escape' === e.key ) {
			close();
			return;
		}
		if ( 'Tab' !== e.key ) {
			return;
		}
		// Keep focus inside the panel while it is open.
		var items = Array.prototype.filter.call(
			panel.querySelectorAll( FOCUSABLE ),
			function ( el ) { return null !== el.offsetParent; }
		);
		if ( ! items.length ) {
			return;
		}
		var first = items[ 0 ];
		var last  = items[ items.length - 1 ];
		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	} );

	if ( ! $ ) {
		return;
	}

	// WooCommerce fires this after any successful AJAX add-to-cart from an
	// archive button.
	$( document.body ).on( 'added_to_cart', function () {
		open();
	} );

	// Set while our own add-to-cart is in flight, so the drawer opens on the
	// fragment refresh that follows it and not on the routine ones.
	var pendingOpen = false;
	var noticeSlot = drawer.querySelector( '.opus-drawer__notices' );

	/**
	 * Lift WooCommerce's notices out of the response and show them in the
	 * drawer. Without this they stay queued in the session and turn up later
	 * on whatever page prints notices next — which is how "u heeft dit horloge
	 * al in uw winkelwagen" ended up appearing out of nowhere.
	 *
	 * The success message is dropped on purpose: the drawer opening with the
	 * watch in it says the same thing, better.
	 */
	function showNotices( html ) {
		if ( ! noticeSlot ) {
			return;
		}
		noticeSlot.innerHTML = '';

		var doc = new DOMParser().parseFromString( html, 'text/html' );
		doc.querySelectorAll( '.woocommerce-error, .woocommerce-info' ).forEach( function ( node ) {
			// The drawer already offers "bekijk winkelwagen" and "afrekenen".
			node.querySelectorAll( 'a.button' ).forEach( function ( a ) {
				a.remove();
			} );
			noticeSlot.insertAdjacentHTML( 'beforeend', node.outerHTML );
		} );

		// Same close button and five-second timer as the ones in the page.
		if ( window.opusNotices ) {
			window.opusNotices.enhance( noticeSlot );
		}
	}

	$( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded', function () {
		if ( pendingOpen ) {
			pendingOpen = false;
			open();
		}
	} );

	// --- single product form over AJAX ---------------------------------
	var form = document.querySelector( 'form.cart' );
	if ( ! form || ! window.fetch ) {
		return;
	}

	// Grouped products post several quantities and are better served by the
	// normal reload; the same goes for a form with no add-to-cart button.
	var button = form.querySelector( '[name="add-to-cart"], button[type="submit"]' );
	if ( form.classList.contains( 'grouped_form' ) || ! button ) {
		return;
	}

	form.addEventListener( 'submit', function ( e ) {
		// Variable products must have a variation chosen; WooCommerce disables
		// the button until then, so let its own validation speak first.
		if ( button.disabled || button.classList.contains( 'disabled' ) ) {
			return;
		}

		e.preventDefault();

		var data = new FormData( form );
		if ( ! data.has( 'add-to-cart' ) && button.value ) {
			data.append( 'add-to-cart', button.value );
		}

		form.classList.add( 'is-adding' );
		button.disabled = true;

		fetch( window.location.href, {
			method: 'POST',
			body: data,
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		} )
			.then( function ( res ) {
				if ( ! res.ok ) {
					throw new Error( 'add-to-cart failed' );
				}
				return res.text();
			} )
			.then( function ( html ) {
				form.classList.remove( 'is-adding' );
				button.disabled = false;
				// Rendering the response server-side already cleared the notice
				// queue; this puts what it said in front of the customer.
				showNotices( html );
				// Let WooCommerce refresh its own fragments — it owns the
				// session storage and cart hash that go with them.
				pendingOpen = true;
				$( document.body ).trigger( 'wc_fragment_refresh' );
			} )
			.catch( function () {
				// Anything unexpected: fall back to the plain page reload.
				form.classList.remove( 'is-adding' );
				button.disabled = false;
				HTMLFormElement.prototype.submit.call( form );
			} );
	} );
} )( window.jQuery );
