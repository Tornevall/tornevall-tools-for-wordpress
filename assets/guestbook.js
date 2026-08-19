( function () {
	'use strict';

	function messageFromResponse( response, fallback ) {
		if ( response && response.message ) {
			return String( response.message );
		}
		return fallback;
	}

	function themeStyles( theme ) {
		var palettes = {
			tools: { bg: '#f8fafc', panel: '#ffffff', text: '#172033', muted: '#64748b', border: '#dbe3ee', accent: '#2563eb', radius: '14px', font: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif' },
			miazma: { bg: '#05090d', panel: '#0b1820', text: '#f8fafc', muted: '#a8cfdf', border: '#2f8cbb', accent: '#3399cc', radius: '6px', font: 'Verdana,Arial,Helvetica,sans-serif' },
			terminal: { bg: '#050b07', panel: '#09150d', text: '#d9ffe4', muted: '#8ac99b', border: '#245e34', accent: '#22c55e', radius: '4px', font: 'ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace' }
		};
		var p = palettes[ theme ] || palettes.tools;
		return ':host{display:block}.shell{box-sizing:border-box;background:' + p.bg + ';color:' + p.text + ';border:1px solid ' + p.border + ';border-radius:' + p.radius + ';font-family:' + p.font + ';overflow:hidden}.shell *{box-sizing:border-box}.header{display:flex;justify-content:space-between;gap:1rem;padding:1rem;background:' + p.panel + ';border-bottom:1px solid ' + p.border + '}.header span,.meta span,.meta time{color:' + p.muted + ';font-size:.82rem}.list{padding:0 1rem}.entry{padding:1rem 0;border-bottom:1px solid ' + p.border + '}.entry:last-child{border-bottom:0}.meta{display:flex;gap:.6rem;flex-wrap:wrap;align-items:baseline}.meta time{margin-left:auto}.message{margin-top:.65rem;white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.55}.homepage{display:inline-block;margin-top:.65rem;color:' + p.accent + ';overflow-wrap:anywhere}.empty,.error{padding:1rem;color:' + p.muted + '}';
	}

	function renderList( target, body ) {
		var root = target.shadowRoot || target.attachShadow( { mode: 'open' } );
		while ( root.firstChild ) {
			root.removeChild( root.firstChild );
		}

		var style = document.createElement( 'style' );
		style.textContent = themeStyles( target.getAttribute( 'data-theme' ) || 'tools' );
		root.appendChild( style );

		var shell = document.createElement( 'section' );
		shell.className = 'shell';
		var header = document.createElement( 'header' );
		header.className = 'header';
		var title = document.createElement( 'strong' );
		title.textContent = 'Guestbook';
		var count = document.createElement( 'span' );
		var total = body && body.pagination ? Number( body.pagination.total || 0 ) : 0;
		count.textContent = total + ( total === 1 ? ' entry' : ' entries' );
		header.appendChild( title );
		header.appendChild( count );
		shell.appendChild( header );

		var list = document.createElement( 'div' );
		list.className = 'list';
		var entries = body && Array.isArray( body.entries ) ? body.entries : [];
		if ( ! entries.length ) {
			var empty = document.createElement( 'div' );
			empty.className = 'empty';
			empty.textContent = 'No guestbook entries yet.';
			list.appendChild( empty );
		}

		entries.forEach( function ( entry ) {
			var article = document.createElement( 'article' );
			article.className = 'entry';
			var meta = document.createElement( 'div' );
			meta.className = 'meta';
			var author = document.createElement( 'strong' );
			author.textContent = entry.name || 'Guest';
			meta.appendChild( author );
			if ( entry.homecity ) {
				var city = document.createElement( 'span' );
				city.textContent = entry.homecity;
				meta.appendChild( city );
			}
			if ( entry.posted_at ) {
				var time = document.createElement( 'time' );
				time.textContent = String( entry.posted_at ).replace( 'T', ' ' ).substring( 0, 16 );
				meta.appendChild( time );
			}
			article.appendChild( meta );

			var message = document.createElement( 'div' );
			message.className = 'message';
			message.textContent = entry.message || '';
			article.appendChild( message );

			if ( /^https?:\/\//i.test( String( entry.homepage || '' ) ) ) {
				var homepage = document.createElement( 'a' );
				homepage.className = 'homepage';
				homepage.href = entry.homepage;
				homepage.target = '_blank';
				homepage.rel = 'nofollow ugc noopener';
				homepage.textContent = entry.homepage;
				article.appendChild( homepage );
			}
			list.appendChild( article );
		} );

		shell.appendChild( list );
		root.appendChild( shell );
	}

	function renderListError( target, message ) {
		renderList( target, { entries: [], pagination: { total: 0 } } );
		var root = target.shadowRoot;
		var shell = root ? root.querySelector( '.shell' ) : null;
		if ( shell ) {
			var error = document.createElement( 'div' );
			error.className = 'error';
			error.textContent = message;
			shell.appendChild( error );
		}
	}

	function loadList( target ) {
		var endpoint = target.getAttribute( 'data-endpoint' );
		var limit = Math.max( 1, Math.min( 50, Number( target.getAttribute( 'data-limit' ) || 10 ) ) );
		var url = endpoint + ( endpoint.indexOf( '?' ) === -1 ? '?' : '&' ) + 'limit=' + encodeURIComponent( limit );
		return fetch( url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' } )
			.then( function ( response ) {
				return response.json().catch( function () { return {}; } ).then( function ( body ) {
					if ( ! response.ok ) {
						throw new Error( messageFromResponse( body, 'The guestbook could not be loaded.' ) );
					}
					return body;
				} );
			} )
			.then( function ( body ) { renderList( target, body ); } )
			.catch( function ( error ) { renderListError( target, error && error.message ? error.message : 'The guestbook could not be loaded.' ); } );
	}

	function resetTurnstile( form ) {
		if ( typeof window.turnstile !== 'object' || typeof window.turnstile.reset !== 'function' ) {
			return;
		}
		var widget = form.querySelector( '.cf-turnstile' );
		if ( widget ) {
			window.turnstile.reset( widget );
		}
	}

	function bindForm( form ) {
		if ( form.dataset.ttfwGuestbookBound === '1' ) {
			return;
		}
		form.dataset.ttfwGuestbookBound = '1';

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var endpoint = form.getAttribute( 'data-endpoint' );
			var status = form.querySelector( '[data-ttfw-guestbook-status]' );
			var button = form.querySelector( 'button[type="submit"]' );
			var data = new FormData( form );
			var payload = {};
			data.forEach( function ( value, key ) { payload[ key ] = String( value ); } );

			if ( button ) { button.disabled = true; }
			if ( status ) { status.textContent = 'Submitting...'; }

			fetch( endpoint, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
				body: JSON.stringify( payload ),
				credentials: 'same-origin'
			} )
				.then( function ( response ) {
					return response.json().catch( function () { return {}; } ).then( function ( body ) {
						if ( ! response.ok ) { throw new Error( messageFromResponse( body, 'The guestbook entry could not be submitted.' ) ); }
						return body;
					} );
				} )
				.then( function ( body ) {
					if ( status ) { status.textContent = messageFromResponse( body, 'Guestbook entry published.' ); }
					form.reset();
					var targetId = form.getAttribute( 'data-list-target' );
					var target = targetId ? document.getElementById( targetId ) : null;
					if ( target ) { loadList( target ); }
				} )
				.catch( function ( error ) {
					if ( status ) { status.textContent = error && error.message ? error.message : 'The guestbook entry could not be submitted.'; }
				} )
				.finally( function () {
					resetTurnstile( form );
					if ( button ) { button.disabled = false; }
				} );
		} );
	}

	function init() {
		document.querySelectorAll( '[data-ttfw-guestbook-list]' ).forEach( loadList );
		document.querySelectorAll( '[data-ttfw-guestbook-form]' ).forEach( bindForm );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
