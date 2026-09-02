<?php
/**
 * Zachowanie sklepu wobec dropów: ukrywanie produktów przed premierą,
 * blokada zakupu, informacja o pozostałych sztukach.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ładowanie zasobów wtyczki.
 *
 * Odliczanie jest potrzebne na stronie głównej (tag w hero) i na karcie
 * produktu, więc warunek obejmuje oba miejsca zamiast ładować wszędzie.
 */
function lorem101_drop_enqueue_assets() {
	if ( ! is_front_page() && ! ( function_exists( 'is_product' ) && is_product() ) ) {
		return;
	}

	wp_enqueue_style(
		'lorem101-drop',
		LOREM101_DROP_MANAGER_URL . 'assets/drop.css',
		array(),
		LOREM101_DROP_MANAGER_VERSION
	);

	wp_enqueue_script(
		'lorem101-drop',
		LOREM101_DROP_MANAGER_URL . 'assets/drop.js',
		array(),
		LOREM101_DROP_MANAGER_VERSION,
		true
	);

	// Teksty przekazujemy z PHP, żeby dało się je przetłumaczyć - wpisanie
	// ich na sztywno w pliku JS zamyka drogę do tłumaczeń.
	wp_localize_script( 'lorem101-drop', 'lorem101DropL10n', array(
		'days'    => __( 'd', 'lorem101-drop-manager' ),
		'hours'   => __( 'godz', 'lorem101-drop-manager' ),
		'minutes' => __( 'min', 'lorem101-drop-manager' ),
		'seconds' => __( 'sek', 'lorem101-drop-manager' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'lorem101_drop_enqueue_assets' );

/**
 * Ukrycie produktów z nadchodzących dropów z całego sklepu.
 *
 * Chodzi o efekt zaskoczenia typowy dla dropów - klient nie ma widzieć
 * różowej bluzy z dopiskiem "dostępna od czwartku", tylko zobaczyć ją
 * dopiero w momencie premiery.
 *
 * Filtrujemy zapytanie o produkty (a nie ukrywamy w CSS), bo to jedyny
 * sposób, żeby produkt nie trafił też do wyszukiwarki, kanałów RSS
 * i zapytań REST API.
 */
function lorem101_drop_hide_upcoming_products( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$upcoming_ids = lorem101_drop_get_upcoming_term_ids();

	if ( empty( $upcoming_ids ) ) {
		return;
	}

	$tax_query = (array) $query->get( 'tax_query' );

	$tax_query[] = array(
		'taxonomy' => LOREM101_DROP_TAXONOMY,
		'field'    => 'term_id',
		'terms'    => $upcoming_ids,
		'operator' => 'NOT IN',
	);

	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'lorem101_drop_hide_upcoming_products' );

/**
 * ID dropów, które jeszcze nie wystartowały.
 */
function lorem101_drop_get_upcoming_term_ids() {
	$ids = array();
	$now = time(); // UTC - zgodnie z tym, co zwraca lorem101_drop_parse_datetime()

	foreach ( lorem101_drop_get_all() as $drop ) {
		if ( $drop['start'] > $now ) {
			$ids[] = $drop['term_id'];
		}
	}

	return $ids;
}

/**
 * To samo ukrywanie, ale dla zapytań przez wc_get_products().
 *
 * Nasz motyw buduje siatkę na stronie głównej właśnie tą funkcją, a ona
 * nie przechodzi przez pre_get_posts - trzeba ją obsłużyć osobno.
 */
function lorem101_drop_filter_wc_query( $query_args ) {
	if ( is_admin() ) {
		return $query_args;
	}

	$upcoming_ids = lorem101_drop_get_upcoming_term_ids();

	if ( empty( $upcoming_ids ) ) {
		return $query_args;
	}

	$query_args['tax_query'] = isset( $query_args['tax_query'] )
		? (array) $query_args['tax_query']
		: array();

	$query_args['tax_query'][] = array(
		'taxonomy' => LOREM101_DROP_TAXONOMY,
		'field'    => 'term_id',
		'terms'    => $upcoming_ids,
		'operator' => 'NOT IN',
	);

	return $query_args;
}
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', function ( $wp_query_args, $query_vars ) {
	return lorem101_drop_filter_wc_query( $wp_query_args );
}, 10, 2 );

/**
 * Blokada zakupu produktu przed premierą jego dropu.
 *
 * Zabezpieczenie po stronie serwera - samo ukrycie produktu nie wystarczy,
 * bo ktoś znający adres mógłby wysłać żądanie dodania do koszyka ręcznie.
 */
function lorem101_drop_filter_purchasable( $purchasable, $product ) {
	if ( ! $product instanceof WC_Product ) {
		return $purchasable;
	}

	// Dla wariacji sprawdzamy drop przypisany do produktu nadrzędnego
	$product_id = $product->is_type( 'variation' )
		? $product->get_parent_id()
		: $product->get_id();

	if ( ! lorem101_drop_is_product_available( $product_id ) ) {
		return false;
	}

	return $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'lorem101_drop_filter_purchasable', 10, 2 );

/**
 * Informacja o pozostałych sztukach na karcie produktu.
 *
 * Pokazujemy ją dopiero przy niskim stanie - "zostało 47 sztuk" nie buduje
 * presji, "zostały 3 sztuki" już tak.
 */
function lorem101_drop_render_stock_info() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$drop = lorem101_drop_get_for_product( $product->get_id() );

	if ( ! $drop ) {
		return; // produkt spoza dropów
	}

	$stock = lorem101_drop_get_stock_left( $product );

	if ( null === $stock ) {
		return; // magazyn nie jest prowadzony
	}

	$threshold = (int) apply_filters( 'lorem101_drop_low_stock_threshold', 10 );

	if ( $stock > $threshold ) {
		return;
	}

	echo '<p class="lorem101-drop__stock">';

	if ( $stock > 0 ) {
		printf(
			/* translators: %d: liczba pozostałych sztuk */
			esc_html( _n( 'Została %d sztuka', 'Zostało tylko %d szt.', $stock, 'lorem101-drop-manager' ) ),
			(int) $stock
		);
	} else {
		esc_html_e( 'Wyprzedane', 'lorem101-drop-manager' );
	}

	echo '</p>';
}
add_action( 'woocommerce_single_product_summary', 'lorem101_drop_render_stock_info', 11 );
