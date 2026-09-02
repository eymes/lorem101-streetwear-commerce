<?php
/**
 * Endpoint REST przyjmujący zgłoszenia o powiadomienie.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lorem101_restock_register_route() {
	register_rest_route(
		'lorem101/v1',
		'/restock',
		array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'lorem101_restock_handle_request',

			// Endpoint otwarty - zapisać się może każdy, także niezalogowany.
			// Parametr musi jednak istnieć: jego brak daje ostrzeżenie
			// w WordPressie i jest częstym przeoczeniem.
			'permission_callback' => '__return_true',

			'args' => array(
				'product_id'   => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'variation_id' => array(
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
				),
				'email'        => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_email',
					'validate_callback' => 'is_email',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'lorem101_restock_register_route' );

function lorem101_restock_handle_request( WP_REST_Request $request ) {
	$product_id   = $request->get_param( 'product_id' );
	$variation_id = $request->get_param( 'variation_id' );
	$email        = $request->get_param( 'email' );

	$variation = wc_get_product( $variation_id );

	if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
		return new WP_Error(
			'lorem101_variation_not_found',
			__( 'Nie znaleziono wariantu produktu.', 'lorem101-restock' ),
			array( 'status' => 404 )
		);
	}

	// Zapis ma sens tylko dla wyprzedanych wariantów. Sprawdzamy to na
	// serwerze, bo warunek z JavaScriptu da się obejść.
	if ( $variation->is_in_stock() ) {
		return new WP_Error(
			'lorem101_in_stock',
			__( 'Ten wariant jest dostępny — możesz go kupić od razu.', 'lorem101-restock' ),
			array( 'status' => 409 )
		);
	}

	$result = lorem101_restock_add_request( $product_id, $variation_id, $email );

	if ( is_wp_error( $result ) ) {
		return new WP_Error(
			$result->get_error_code(),
			$result->get_error_message(),
			array( 'status' => 400 )
		);
	}

	return rest_ensure_response( array(
		'success' => true,
		'waiting' => lorem101_restock_count_for_variation( $variation_id ),
	) );
}
