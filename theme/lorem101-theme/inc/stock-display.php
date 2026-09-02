<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Komunikat o dostępności: kiedy go pokazać i jak brzmi po polsku.
 *
 * WooCommerce ma trzy formaty wyświetlania stanu i żaden nie robi tego,
 * czego chcemy:
 *   - "zawsze pokazuj liczbę"    -> "50 in stock" przy każdym wariancie
 *   - "tylko przy niskim stanie" -> "In stock" powyżej progu, bez liczby
 *   - "nigdy nie pokazuj"        -> nic, nawet przy dwóch sztukach
 *
 * Chcemy: przy dużym zapasie cisza, przy niskim konkretna liczba.
 * W sklepie z limitowanymi seriami "zostały 3 sztuki" buduje presję,
 * a "jest dostępne" to szum.
 */
function lorem101_filter_availability_text( $text, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $text;
	}

	// Brak towaru obsługujemy osobno na karcie produktu (formularz
	// "powiadom mnie"), więc tutaj nie ingerujemy
	if ( ! $product->is_in_stock() ) {
		return $text;
	}

	if ( ! $product->managing_stock() ) {
		return '';
	}

	$stock     = (int) $product->get_stock_quantity();
	$threshold = (int) wc_get_low_stock_amount( $product );

	if ( $stock > $threshold ) {
		return '';
	}

	// Poniżej progu - własny, polski komunikat z liczbą
	return sprintf(
		/* translators: %d: liczba pozostałych sztuk */
		_n( 'Została tylko %d sztuka', 'Zostały tylko %d szt.', $stock, 'lorem101-theme' ),
		$stock
	);
}
add_filter( 'woocommerce_get_availability_text', 'lorem101_filter_availability_text', 10, 2 );
