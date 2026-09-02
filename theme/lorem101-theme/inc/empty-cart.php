<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pusty koszyk - podmiana zawartości bloku Gutenberga.
//
// Domyślnie WooCommerce renderuje w bloku "empty-cart-block" nagłówek,
// separator i sekcję "New in store" z karuzelą produktów. Chcemy własną,
// spójną z marką grafikę (buźka z łzą) plus jedno zdanie.
//
// Wcześniej robiliśmy to selektorami CSS (::before, ::after + display:none
// na dzieciach) - podejście działało, ale było wrażliwe na zmiany strukturalne
// bloku po stronie WooCommerce i na kolejność ładowania. Filtr render_block
// przechwytuje HTML zanim trafi do przeglądarki - efekt jest ten sam
// niezależnie od tego, co WooCommerce włoży w środek bloku.
add_filter(
	'render_block',
	function ( $block_content, $block ) {
		if ( ! isset( $block['blockName'] ) || 'woocommerce/empty-cart-block' !== $block['blockName'] ) {
			return $block_content;
		}

		$text = esc_html__( 'Twój koszyk jest pusty', 'lorem101-theme' );

		// SVG inline zamiast data-URI - łatwiej edytować, lepiej się skaluje,
		// dziedziczy kolory z CSS przez currentColor tam, gdzie tego chcemy.
		$svg = '<svg class="lorem101-empty-cart__icon" width="96" height="96" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
			. '<circle cx="24" cy="24" r="21" fill="#141311" stroke="#c23a1f" stroke-width="2.5"/>'
			. '<circle cx="17" cy="19" r="2.5" fill="#c23a1f"/>'
			. '<circle cx="31" cy="19" r="2.5" fill="#c23a1f"/>'
			. '<path d="M17 24c0 3 2 5 2 7a2 2 0 0 1-4 0c0-2 2-4 2-7z" fill="#c23a1f"/>'
			. '<path d="M16 34c2.5-4 5.5-6 8-6s5.5 2 8 6" fill="none" stroke="#c23a1f" stroke-width="2.5" stroke-linecap="round"/>'
			. '</svg>';

		return '<div class="wp-block-woocommerce-empty-cart-block lorem101-empty-cart">'
			. '<div class="lorem101-empty-cart__inner">'
			. $svg
			. '<p class="lorem101-empty-cart__text">' . $text . '</p>'
			. '</div>'
			. '</div>';
	},
	10,
	2
);
