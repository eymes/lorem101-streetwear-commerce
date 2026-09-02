<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rozbija produkt zmienny na osobne "kafelki" - jeden na każdy unikalny
 * kolor (bierzemy pierwszą napotkaną wariację danego koloru jako
 * reprezentanta - wybór rozmiaru zostaje na stronie produktu).
 *
 * Zwraca tablicę gotową do przekazania jako $args do
 * template-parts/product-variant-tile.php.
 */
function lorem101_get_color_variant_tiles( $product ) {
	$tiles = array();

	if ( ! $product instanceof WC_Product_Variable ) {
		return $tiles;
	}

	$category_classes = array();
	$terms = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $slug ) {
			$category_classes[] = 'product_cat-' . $slug;
		}
	}

	$seen_colors = array();

	// Produkty z najnowszej dostępnej kolekcji dostają oznaczenie NEW
	// na kafelku. Dane pochodzą z wtyczki Drop Manager, ale motyw działa
	// też bez niej - stąd sprawdzenie function_exists.
	$is_new = function_exists( 'lorem101_drop_is_product_new' )
		&& lorem101_drop_is_product_new( $product->get_id() );

	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->exists() ) {
			continue;
		}

		$color = $variation->get_attribute( 'kolor' );
		if ( '' === $color || isset( $seen_colors[ $color ] ) ) {
			continue; // ten kolor już ma swój kafelek (to tylko inny rozmiar)
		}
		$seen_colors[ $color ] = true;

		$image_id   = $variation->get_image_id();
		$image_id   = $image_id ? $image_id : $product->get_image_id();
		$image_html = $image_id
			? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail' )
			: wc_placeholder_img( 'woocommerce_thumbnail' );

		$tiles[] = array(
			'title'            => $product->get_name() . ' — ' . $color,
			'price_html'       => $variation->get_price_html(),
			'url'              => add_query_arg( 'attribute_kolor', sanitize_title( $color ), get_permalink( $product->get_id() ) ),
			'image_html'       => $image_html,
			'category_classes' => $category_classes,
			'is_new'           => $is_new,
		);
	}

	return $tiles;
}
