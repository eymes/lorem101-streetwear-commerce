/**
 * Odliczanie do premiery dropu.
 *
 * PHP przekazuje znacznik czasu w atrybucie data-drop-start. Samo odliczanie
 * musi dziać się w przeglądarce - PHP renderuje stronę raz i nie potrafi
 * aktualizować liczb w czasie rzeczywistym.
 *
 * Uwaga: zegar liczy według czasu urządzenia użytkownika, więc źle ustawiony
 * zegar da błędny wynik. Dostępność produktów sprawdza serwer (filtr
 * woocommerce_is_purchasable), więc to wyłącznie warstwa wizualna.
 */
( function () {
	"use strict";

	var texts = window.lorem101DropL10n || {};

	function pad( value ) {
		return value < 10 ? "0" + value : String( value );
	}

	function format( secondsLeft ) {
		var days = Math.floor( secondsLeft / 86400 );
		var hours = Math.floor( ( secondsLeft % 86400 ) / 3600 );
		var minutes = Math.floor( ( secondsLeft % 3600 ) / 60 );
		var seconds = secondsLeft % 60;

		var parts = [];

		if ( days > 0 ) {
			parts.push( days + " " + ( texts.days || "d" ) );
		}

		parts.push( pad( hours ) + ":" + pad( minutes ) + ":" + pad( seconds ) );

		return parts.join( " " );
	}

	function initCountdown( element ) {
		// PHP podaje czas w sekundach (znacznik uniksowy), JS liczy w milisekundach
		var target = parseInt( element.getAttribute( "data-drop-start" ), 10 ) * 1000;

		if ( ! target ) {
			return;
		}

		// Element, w którym wypisujemy liczby: albo dedykowany .hero__countdown,
		// albo sam element z atrybutem (na karcie produktu)
		var output = element.querySelector( ".hero__countdown" ) || element;

		function tick() {
			var secondsLeft = Math.floor( ( target - Date.now() ) / 1000 );

			if ( secondsLeft <= 0 ) {
				window.clearInterval( timer );

				// Premiera właśnie nastąpiła. Przeładowujemy stronę, żeby serwer
				// wyrenderował ją od nowa - z produktami dropu w katalogu
				// i tagiem "available now" zamiast odliczania.
				window.location.reload();
				return;
			}

			output.textContent = format( secondsLeft );
		}

		var timer = window.setInterval( tick, 1000 );
		tick(); // pierwszy odczyt od razu, bez czekania sekundy
	}

	document.addEventListener( "DOMContentLoaded", function () {
		var elements = document.querySelectorAll( "[data-drop-countdown]" );

		Array.prototype.forEach.call( elements, initCountdown );
	} );
} )();
