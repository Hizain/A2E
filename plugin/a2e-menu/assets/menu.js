/**
 * A2E Header Menu Link - inserts one configurable, highlighted link into the site
 * header (and mobile menu) at runtime, so a hardcoded theme menu can feature a page
 * without editing the theme. Idempotent, and re-applies if the menu renders late.
 */
( function () {
	'use strict';

	var cfg = window.a2eMenuData;
	if ( ! cfg || ! cfg.url || ! cfg.label ) {
		return;
	}

	var FLAG = 'a2eMenuDone';

	function isCurrent() {
		try {
			var a = document.createElement( 'a' );
			a.href = cfg.url;
			return a.pathname.replace( /\/+$/, '' ) === window.location.pathname.replace( /\/+$/, '' ) && a.pathname !== '/';
		} catch ( e ) {
			return false;
		}
	}

	function anchor( variant ) {
		var a = document.createElement( 'a' );
		a.href = cfg.url;
		a.textContent = cfg.label;
		a.className = 'a2e-menu-link a2e-menu-link--' + variant + ( cfg.highlight ? ' is-hl' : '' ) + ( isCurrent() ? ' is-current' : '' );
		a.setAttribute( 'data-a2e-menu', '1' );
		if ( cfg.newtab ) {
			a.target = '_blank';
			a.rel = 'noopener';
		}
		return a;
	}

	function placeInList( list, variant ) {
		if ( ! list || list.querySelector( '[data-a2e-menu]' ) ) {
			return false;
		}
		var li = document.createElement( 'li' );
		li.className = 'a2e-menu-item a2e-menu-item--' + variant;
		li.setAttribute( 'data-a2e-menu-item', '1' );
		li.appendChild( anchor( variant ) );
		if ( 'start' === cfg.position && list.firstElementChild ) {
			list.insertBefore( li, list.firstElementChild );
		} else {
			list.appendChild( li );
		}
		return true;
	}

	function firstMatch( sel ) {
		if ( ! sel ) {
			return null;
		}
		try {
			return document.querySelector( sel );
		} catch ( e ) {
			return null;
		}
	}

	function apply() {
		var wantMain  = 'main' === cfg.placement || 'both' === cfg.placement;
		var wantTools = 'tools' === cfg.placement || 'both' === cfg.placement;
		var done = true;

		if ( wantMain ) {
			var mainOk = true;
			var dl = firstMatch( cfg.selMain );
			if ( dl && ! dl.querySelector( '[data-a2e-menu]' ) ) { placeInList( dl, 'main' ); }
			else if ( ! dl ) { mainOk = false; }
			var ml = firstMatch( cfg.selMobile );
			if ( ml && ! ml.querySelector( '[data-a2e-menu]' ) ) { placeInList( ml, 'mobile' ); }
			// Mobile menu is often hidden until opened; treat its absence as non-fatal.
			done = done && mainOk;
		}

		if ( wantTools ) {
			var toolsOk = false;
			var td = firstMatch( cfg.selTools );
			if ( td ) { placeInList( td, 'tools' ); toolsOk = true; }
			var tm = firstMatch( cfg.selToolsM );
			if ( tm ) { placeInList( tm, 'tools' ); }
			// If the tools list could not be found, fall back to the main header so the
			// link is never lost.
			if ( ! toolsOk && ! wantMain ) {
				var dl2 = firstMatch( cfg.selMain );
				if ( dl2 ) { placeInList( dl2, 'main' ); }
				var ml2 = firstMatch( cfg.selMobile );
				if ( ml2 ) { placeInList( ml2, 'mobile' ); }
				toolsOk = !! dl2;
			}
			done = done && toolsOk;
		}

		return done;
	}

	function run() {
		if ( window[ FLAG ] ) {
			// Still re-check in case the menu was replaced (e.g. a cached mobile drawer).
			apply();
			return;
		}
		var ok = apply();
		if ( ok ) {
			window[ FLAG ] = true;
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run );
	} else {
		run();
	}

	// Watch briefly for menus that render after load (some themes build them with JS).
	var tries = 0;
	var mo = new MutationObserver( function () {
		run();
		if ( ++tries > 40 || window[ FLAG ] ) {
			mo.disconnect();
		}
	} );
	try {
		mo.observe( document.body, { childList: true, subtree: true } );
		setTimeout( function () { try { mo.disconnect(); } catch ( e ) {} }, 8000 );
	} catch ( e ) {}
} )();
