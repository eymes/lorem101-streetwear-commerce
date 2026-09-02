<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Numeracja zamówień zaczynająca się od 1.
 *
 * WordPress przydziela identyfikatory ze wspólnej puli dla wszystkich
 * typów wpisów - produkty, wariacje, strony, wersje robocze. Pierwsze
 * zamówienie w sklepie dostaje więc numer rzędu 95, co wygląda jak
 * dziewięćdziesiąt cztery wcześniejsze transakcje.
 *
 * Nadajemy własny numer przy składaniu zamówienia i zapisujemy go jako
 * meta. Identyfikator w bazie zostaje bez zmian - zmieniamy wyłącznie to,
 * co widzi klient i obsługa sklepu.
 */

const LOREM101_ORDER_COUNTER_OPTION = 'lorem101_order_counter';
const LOREM101_ORDER_NUMBER_META    = '_lorem101_order_number';

/**
 * Nadanie numeru przy tworzeniu zamówienia.
 *
 * Hook woocommerce_checkout_order_created odpala się raz, po zapisaniu
 * zamówienia - dzięki temu numer nie zmieni się przy późniejszych edycjach.
 */
function lorem101_assign_order_number( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}

	if ( $order->get_meta( LOREM101_ORDER_NUMBER_META ) ) {
		return; // numer już nadany
	}

	$next = (int) get_option( LOREM101_ORDER_COUNTER_OPTION, 0 ) + 1;

	update_option( LOREM101_ORDER_COUNTER_OPTION, $next );

	$order->update_meta_data( LOREM101_ORDER_NUMBER_META, $next );
	$order->save();
}
add_action( 'woocommerce_checkout_order_created', 'lorem101_assign_order_number' );

// Blokowy checkout (ten, którego używa nasz sklep) NIE przechodzi przez
// woocommerce_checkout_order_created - komunikuje się przez Store API,
// które ma własny zestaw hooków. Bez tej linijki numer nadawałby się
// tylko przy klasycznym checkoucie na shortcode'ach.
add_action( 'woocommerce_store_api_checkout_order_processed', 'lorem101_assign_order_number' );

/**
 * Podmiana numeru wyświetlanego w sklepie, panelu i mailach.
 *
 * Zamówienia złożone przed włączeniem tej funkcji nie mają zapisanego
 * numeru - dla nich zostawiamy identyfikator z bazy, żeby nie pokazywać
 * pustego pola.
 */
function lorem101_display_order_number( $number, $order ) {
	$custom = $order->get_meta( LOREM101_ORDER_NUMBER_META );

	return $custom ? $custom : $number;
}
add_filter( 'woocommerce_order_number', 'lorem101_display_order_number', 10, 2 );
