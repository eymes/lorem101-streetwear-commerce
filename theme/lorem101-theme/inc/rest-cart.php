<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Własny endpoint REST do dodawania produktów do koszyka.
 *
 * WooCommerce ma własne Store API (/wp-json/wc/store/v1/cart/add-item),
 * które zrobiłoby to samo. Piszemy własny, bo potrzebujemy kontroli nad
 * dwiema rzeczami:
 *
 *   1. sprawdzeniem statusu dropu - Store API nie wie nic o naszych
 *      limitowanych kolekcjach i przepuściłoby produkt przed premierą,
 *   2. kształtem odpowiedzi - zwracamy dokładnie to, czego potrzebuje
 *      nasz interfejs (liczba sztuk w koszyku, nazwa wariantu), zamiast
 *      pełnej reprezentacji koszyka.
 */

const LOREM101_REST_NAMESPACE = 'lorem101/v1';

/**
 * Rejestracja tras.
 *
 * rest_api_init to jedyny właściwy moment - wcześniej REST API jeszcze
 * nie istnieje, później trasy są już zamrożone.
 */
function lorem101_register_cart_routes() {
	register_rest_route(
		LOREM101_REST_NAMESPACE,
		'/cart/add',
		array(
			'methods'  => WP_REST_Server::CREATABLE, // POST
			'callback' => 'lorem101_rest_add_to_cart',

			// Endpoint jest publiczny - koszyk działa też dla niezalogowanych.
			// permission_callback musi jednak istnieć: pominięcie go powoduje
			// ostrzeżenie w WordPressie i jest częstym błędem.
			'permission_callback' => '__return_true',

			'args' => array(
				'product_id'   => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'variation_id' => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'quantity'     => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
					'validate_callback' => function ( $value ) {
						return $value > 0;
					},
				),
				'attributes'   => array(
					'required' => false,
					'type'     => 'object',
					'default'  => array(),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'lorem101_register_cart_routes' );

/**
 * Dodanie produktu do koszyka.
 *
 * Zwracamy WP_Error zamiast rzucać wyjątkiem - REST API zamienia go
 * na poprawną odpowiedź HTTP z kodem błędu, którą JS potrafi obsłużyć.
 */
function lorem101_rest_add_to_cart( WP_REST_Request $request ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return new WP_Error(
			'lorem101_cart_unavailable',
			__( 'Koszyk jest niedostępny.', 'lorem101-theme' ),
			array( 'status' => 500 )
		);
	}

	$product_id   = $request->get_param( 'product_id' );
	$variation_id = $request->get_param( 'variation_id' );
	$quantity     = $request->get_param( 'quantity' );
	$attributes   = (array) $request->get_param( 'attributes' );

	$product = wc_get_product( $variation_id ? $variation_id : $product_id );

	if ( ! $product ) {
		return new WP_Error(
			'lorem101_product_not_found',
			__( 'Nie znaleziono produktu.', 'lorem101-theme' ),
			array( 'status' => 404 )
		);
	}

	// Blokada dropu przed premierą. Filtr woocommerce_is_purchasable
	// (w Drop Managerze) i tak by to złapał, ale sprawdzamy jawnie,
	// żeby zwrócić czytelny komunikat zamiast ogólnego błędu koszyka.
	if ( function_exists( 'lorem101_drop_is_product_available' )
		&& ! lorem101_drop_is_product_available( $product_id ) ) {
		return new WP_Error(
			'lorem101_drop_not_started',
			__( 'Ten produkt nie jest jeszcze dostępny.', 'lorem101-theme' ),
			array( 'status' => 403 )
		);
	}

	if ( ! $product->is_purchasable() ) {
		return new WP_Error(
			'lorem101_not_purchasable',
			__( 'Tego produktu nie można obecnie kupić.', 'lorem101-theme' ),
			array( 'status' => 403 )
		);
	}

	if ( ! $product->has_enough_stock( $quantity ) ) {
		return new WP_Error(
			'lorem101_out_of_stock',
			__( 'Nie ma wystarczającej liczby sztuk w magazynie.', 'lorem101-theme' ),
			array( 'status' => 409 )
		);
	}

	// Klucze atrybutów muszą mieć postać attribute_nazwa - tak oczekuje
	// ich WooCommerce przy rozstrzyganiu wariacji
	$variation_data = array();

	foreach ( $attributes as $key => $value ) {
		$key = sanitize_key( $key );

		if ( 0 !== strpos( $key, 'attribute_' ) ) {
			$key = 'attribute_' . $key;
		}

		$variation_data[ $key ] = sanitize_text_field( $value );
	}

	$cart_item_key = WC()->cart->add_to_cart(
		$product_id,
		$quantity,
		$variation_id,
		$variation_data
	);

	if ( ! $cart_item_key ) {
		// add_to_cart zwraca false i zapisuje powód przez wc_add_notice -
		// wyciągamy go, żeby użytkownik zobaczył konkretny komunikat
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();

		$message = ! empty( $notices[0]['notice'] )
			? wp_strip_all_tags( $notices[0]['notice'] )
			: __( 'Nie udało się dodać produktu do koszyka.', 'lorem101-theme' );

		return new WP_Error(
			'lorem101_add_failed',
			$message,
			array( 'status' => 400 )
		);
	}

	return rest_ensure_response( array(
		'success'    => true,
		'cart_count' => WC()->cart->get_cart_contents_count(),
		'cart_url'   => wc_get_cart_url(),
		'item_name'  => $product->get_name(),
	) );
}
