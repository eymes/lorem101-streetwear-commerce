<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Podstawowa konfiguracja motywu.
 */
function lorem101_theme_setup() {
	// Tłumaczenia
	load_theme_textdomain( 'lorem101-theme', LOREM101_THEME_DIR . '/languages' );

	// Miniaturki produktów/postów
	add_theme_support( 'post-thumbnails' );

	// Tytuł strony zarządzany przez WP (SEO plugin i tak to nadpisze jeśli używasz)
	add_theme_support( 'title-tag' );

	// HTML5 dla formularzy wyszukiwania, komentarzy itd.
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );

	// Menu nawigacyjne
	register_nav_menus( array(
		'primary' => __( 'Menu główne', 'lorem101-theme' ),
		'footer'  => __( 'Menu w stopce', 'lorem101-theme' ),
	) );

	// KLUCZOWE: mówi WordPressowi że motyw sam obsługuje style/markup WooCommerce
	add_theme_support( 'woocommerce' );

	// Włącza galerię zdjęć produktu (zoom, lightbox, slider) - wbudowane w WC
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'lorem101_theme_setup' );

/**
 * Szerokości obrazków w WooCommerce (opcjonalnie dostosuj do swojego layoutu)
 */
function lorem101_woocommerce_image_dimensions() {
	$catalog = array(
		'width'  => 600,
		'height' => 600,
		'crop'   => 1,
	);
	update_option( 'shop_catalog_image_size', $catalog );

	$single = array(
		'width'  => 900,
		'height' => 900,
		'crop'   => 1,
	);
	update_option( 'shop_single_image_size', $single );
}
add_action( 'after_switch_theme', 'lorem101_woocommerce_image_dimensions' );
