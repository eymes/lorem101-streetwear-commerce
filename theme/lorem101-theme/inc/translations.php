<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Polskie teksty w miejscach, gdzie WooCommerce używa własnych.
 *
 * Komunikat o stanie magazynowym ma osobny plik (inc/stock-display.php),
 * bo tam decydujemy nie tylko o brzmieniu, ale i o tym, czy w ogóle
 * go pokazać.
 *
 * WooCommerce ma pełne tłumaczenie polskie, ale wymaga ustawienia języka
 * witryny na polski (Ustawienia → Ogólne → Język). Te filtry działają
 * niezależnie od tego ustawienia i dotyczą wyłącznie miejsc widocznych
 * dla klienta w naszym motywie.
 *
 * Świadomie NIE tłumaczymy tu wszystkiego - panel administracyjny zostawiamy
 * WooCommerce, bo tłumaczenie go filtrami byłoby walką z wiatrakami.
 */

/**
 * Etykieta przycisku dodania do koszyka.
 */
add_filter( 'woocommerce_product_single_add_to_cart_text', function () {
	return __( 'Dodaj do koszyka', 'lorem101-theme' );
} );

add_filter( 'woocommerce_product_add_to_cart_text', function () {
	return __( 'Dodaj do koszyka', 'lorem101-theme' );
} );

/**
 * Nazwa domyślnej opcji w listach wyboru wariantu ("Choose an option").
 */
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', function ( $args ) {
	$args['show_option_none'] = __( 'Wybierz', 'lorem101-theme' );

	return $args;
}, 20 );

/**
 * Etykieta pola ilości dla czytników ekranu.
 */
add_filter( 'woocommerce_quantity_input_args', function ( $args ) {
	$args['input_name'] = 'quantity';

	return $args;
} );
