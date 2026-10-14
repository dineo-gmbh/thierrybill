/**
 * Bildfolge im Hero (.tb-slides): ein Bild sichtbar, Zaehler «01/05» darueber.
 * Klick auf das Bild zeigt das naechste, sonst wechselt es alle 4 Sekunden
 * (nicht bei prefers-reduced-motion). Ohne JavaScript bleibt das erste Bild stehen.
 */
( () => {
	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	document.querySelectorAll( '.tb-hero__media' ).forEach( ( media ) => {
		const slides = [ ...media.querySelectorAll( '.tb-slide' ) ];
		const cur = media.querySelector( '.tb-count__cur' );
		if ( slides.length < 2 ) {
			return;
		}
		let index = 0;
		let timer;

		const show = ( next ) => {
			slides[ index ].classList.remove( 'is-active' );
			index = ( next + slides.length ) % slides.length;
			slides[ index ].classList.add( 'is-active' );
			if ( cur ) {
				cur.textContent = String( index + 1 ).padStart( 2, '0' );
			}
		};
		const start = () => {
			if ( ! reduce ) {
				timer = window.setInterval( () => show( index + 1 ), 4000 );
			}
		};

		// Erst aktiv setzen, dann Ueberblendung einschalten: das erste Bild bleibt durchgehend sichtbar.
		slides[ 0 ].classList.add( 'is-active' );
		media.classList.add( 'is-ready' );
		media.querySelector( '.tb-slides' ).addEventListener( 'click', () => {
			window.clearInterval( timer );
			show( index + 1 );
			start();
		} );
		start();
	} );
} )();
