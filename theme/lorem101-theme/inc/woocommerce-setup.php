<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

add_filter( 'loop_shop_columns', function () {
	return 3;
} );

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

/**
 * Usuwa link "Clear" (reset wariantów) spod pól wyboru.
 * Przy jednym widocznym atrybucie (rozmiar) reset niczego sensownego
 * nie wnosi, a psuje układ formularza.
 */
add_filter( 'woocommerce_reset_variations_link', '__return_empty_string' );
