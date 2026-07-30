<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deklaracja kompatybilności z HPOS (High-Performance Order Storage) -
 * nowy sposób przechowywania zamówień w WooCommerce, warto zadeklarować od razu.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

/**
 * Wyłącza domyślne style WooCommerce, żeby pisać wszystko samemu w SCSS
 * (bez tego WC ładuje swój woocommerce.css, który się gryzie z custom CSS).
 */
add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

/**
 * Domyślnie WooCommerce dodaje sporo wrapperów/breadcrumbs - to zostawiamy,
 * ale poniżej masz gotowe miejsce na wyłączanie/zmienianie elementów, np.:
 *
 * remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
 * remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
 */

/**
 * Liczba produktów w kolumnie / rzędzie na stronie sklepu (jeśli używasz loop grid)
 */
add_filter( 'loop_shop_columns', function () {
	return 3;
} );

/**
 * Rejestracja miejsc na widgety, jeśli chcesz mieć sidebar w sklepie
 */
function lorem101_woocommerce_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sklep - sidebar', 'lorem101-theme' ),
		'id'            => 'shop-sidebar',
		'before_widget' => '<div class="widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget__title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'lorem101_woocommerce_widgets_init' );
