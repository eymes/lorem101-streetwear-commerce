/**
 * Formularz „powiadom mnie o dostępności".
 *
 * Pokazuje się dopiero wtedy, gdy klient wybierze wyprzedany wariant.
 * Informację o dostępności bierzemy z eventu found_variation, który
 * WooCommerce wysyła po rozstrzygnięciu wariantu — dane przychodzą
 * z serwera, więc nie musimy ich sami wyliczać.
 */
( function () {
	"use strict";

	var config = window.lorem101Restock || {};
	var texts = config.texts || {};

	function init() {
		var form = document.querySelector( "[data-restock-form]" );
		var variationForm = document.querySelector( ".variations_form" );

		if ( ! form || ! variationForm ) {
			return;
		}

		var emailField = form.querySelector( "[data-restock-email]" );
		var submitButton = form.querySelector( "[data-restock-submit]" );
		var messageBox = form.querySelector( "[data-restock-message]" );
		var addToCartButton = variationForm.querySelector( ".single_add_to_cart_button" );

		var currentVariation = 0;
		var currentProduct = parseInt( variationForm.dataset.product_id || 0, 10 );

		function showMessage( text, isError ) {
			messageBox.textContent = text;
			messageBox.hidden = false;
			messageBox.classList.toggle( "restock-form__message--error", !! isError );
		}

		function hideMessage() {
			messageBox.hidden = true;
		}

		// WooCommerce wysyła found_variation przez jQuery, więc nasłuchujemy
		// przez jQuery - natywny addEventListener nie wychwytuje zdarzeń
		// wywołanych metodą .trigger()
		if ( window.jQuery ) {
			window.jQuery( variationForm ).on( "found_variation", function ( event, variation ) {
				currentVariation = variation.variation_id;

				var soldOut = ! variation.is_in_stock;

				form.hidden = ! soldOut;
				hideMessage();

				// Przy wyprzedanym wariancie przycisk zakupu nie ma sensu
				if ( addToCartButton ) {
					addToCartButton.style.display = soldOut ? "none" : "";
				}
			} );

			window.jQuery( variationForm ).on( "reset_data", function () {
				form.hidden = true;
				hideMessage();

				if ( addToCartButton ) {
					addToCartButton.style.display = "";
				}
			} );
		}

		submitButton.addEventListener( "click", function () {
			var email = ( emailField.value || "" ).trim();

			if ( ! email || email.indexOf( "@" ) === -1 ) {
				showMessage( texts.email || "Podaj poprawny adres e-mail.", true );
				emailField.focus();
				return;
			}

			if ( ! currentVariation ) {
				return;
			}

			var originalLabel = submitButton.textContent;
			submitButton.disabled = true;
			submitButton.textContent = texts.sending || "…";

			fetch( config.endpoint, {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					"X-WP-Nonce": config.nonce
				},
				credentials: "same-origin",
				body: JSON.stringify( {
					product_id: currentProduct,
					variation_id: currentVariation,
					email: email
				} )
			} )
				.then( function ( response ) {
					return response.json().then( function ( data ) {
						return { ok: response.ok, data: data };
					} );
				} )
				.then( function ( result ) {
					if ( ! result.ok ) {
						showMessage( result.data.message || texts.error, true );
						return;
					}

					showMessage( texts.success, false );
					emailField.value = "";
				} )
				.catch( function () {
					showMessage( texts.error, true );
				} )
				.finally( function () {
					submitButton.disabled = false;
					submitButton.textContent = originalLabel;
				} );
		} );
	}

	document.addEventListener( "DOMContentLoaded", init );
} )();
