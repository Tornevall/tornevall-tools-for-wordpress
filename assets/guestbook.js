( function () {
	'use strict';

	function messageFromResponse( response, fallback ) {
		if ( response && response.message ) {
			return String( response.message );
		}
		return fallback;
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

			data.forEach( function ( value, key ) {
				payload[ key ] = String( value );
			} );

			if ( button ) {
				button.disabled = true;
			}
			if ( status ) {
				status.textContent = 'Submitting...';
			}

			fetch( endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json'
				},
				body: JSON.stringify( payload ),
				credentials: 'same-origin'
			} )
				.then( function ( response ) {
					return response.json().catch( function () {
						return {};
					} ).then( function ( body ) {
						if ( ! response.ok ) {
							throw new Error( messageFromResponse( body, 'The guestbook entry could not be submitted.' ) );
						}
						return body;
					} );
				} )
				.then( function ( body ) {
					if ( status ) {
						status.textContent = messageFromResponse( body, 'Guestbook entry published.' );
					}
					form.reset();
				} )
				.catch( function ( error ) {
					if ( status ) {
						status.textContent = error && error.message ? error.message : 'The guestbook entry could not be submitted.';
					}
				} )
				.finally( function () {
					if ( button ) {
						button.disabled = false;
					}
				} );
		} );
	}

	function init() {
		document.querySelectorAll( '[data-ttfw-guestbook-form]' ).forEach( bindForm );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
