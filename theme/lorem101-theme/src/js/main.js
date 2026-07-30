import "../scss/main.scss";

/**
 * Punkt wejścia JS motywu. Vite dzięki temu importowi w JS wrzuca
 * skompilowany CSS do tego samego chunku - stąd w manifest.json
 * plik JS ma przypisane pole "css".
 */

document.addEventListener("DOMContentLoaded", () => {
	// WooCommerce triggeruje customowy event po zaktualizowaniu koszyka przez AJAX
	document.body.addEventListener("added_to_cart", (event) => {
		console.log("Produkt dodany do koszyka", event);
	});
});
