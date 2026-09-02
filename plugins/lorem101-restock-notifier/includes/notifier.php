<?php
/**
 * Wykrywanie uzupełnienia magazynu i wysyłka powiadomień.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reakcja na zmianę stanu magazynowego.
 *
 * woocommerce_variation_set_stock odpala się przy każdej zmianie stanu
 * wariacji - także przy zakupie, który go zmniejsza. Dlatego sprawdzamy,
 * czy wariant faktycznie wrócił do sprzedaży, zanim wyślemy cokolwiek.
 */
function lorem101_restock_check_stock( $variation ) {
	if ( ! $variation instanceof WC_Product ) {
		return;
	}

	if ( ! $variation->is_in_stock() ) {
		return;
	}

	$pending = lorem101_restock_get_pending( $variation->get_id() );

	if ( empty( $pending ) ) {
		return;
	}

	lorem101_restock_send_notifications( $variation, $pending );
}
add_action( 'woocommerce_variation_set_stock', 'lorem101_restock_check_stock' );
add_action( 'woocommerce_product_set_stock', 'lorem101_restock_check_stock' );

/**
 * Wysyłka maili i oznaczenie zgłoszeń jako obsłużonych.
 */
function lorem101_restock_send_notifications( $variation, $pending ) {
	global $wpdb;

	$parent = wc_get_product( $variation->get_parent_id() );
	$name   = $parent ? $parent->get_name() : $variation->get_name();

	// Nazwa wariantu: "Czarny, M" - klient ma wiedzieć, o który dokładnie chodzi
	$attributes = array();

	foreach ( $variation->get_variation_attributes() as $value ) {
		if ( $value ) {
			$attributes[] = ucfirst( $value );
		}
	}

	$variant_label = $attributes ? implode( ', ', $attributes ) : '';
	$permalink     = $variation->get_permalink();

	$subject = sprintf(
		/* translators: %s: nazwa produktu */
		__( '%s wrócił do sprzedaży', 'lorem101-restock' ),
		$name
	);

	$message = sprintf(
		/* translators: 1: nazwa produktu, 2: wariant, 3: adres strony produktu */
		__( "Cześć,\n\nprodukt %1\$s (%2\$s) jest znowu dostępny.\n\n%3\$s\n\nLimitowane serie schodzą szybko — nie czekaj zbyt długo.\n\nLOREM101", 'lorem101-restock' ),
		$name,
		$variant_label,
		$permalink
	);

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	$table = lorem101_restock_table();
	$now   = current_time( 'mysql' );

	foreach ( $pending as $request ) {
		$sent = wp_mail( $request->email, $subject, $message, $headers );

		if ( ! $sent ) {
			// Nieudanej wysyłki nie oznaczamy jako obsłużonej - zgłoszenie
			// zostaje w kolejce i doczeka kolejnej okazji
			continue;
		}

		$wpdb->update(
			$table,
			array( 'notified_at' => $now ),
			array( 'id' => (int) $request->id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
