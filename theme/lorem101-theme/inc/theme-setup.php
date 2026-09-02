<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lorem101_theme_setup() {
	load_theme_textdomain( 'lorem101-theme', LOREM101_THEME_DIR . '/languages' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );

	register_nav_menus( array(
		'primary' => __( 'Menu główne', 'lorem101-theme' ),
		'footer'  => __( 'Menu w stopce', 'lorem101-theme' ),
	) );

	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'lorem101_theme_setup' );

function lorem101_woocommerce_image_dimensions() {
	update_option( 'shop_catalog_image_size', array( 'width' => 600, 'height' => 600, 'crop' => 1 ) );
	update_option( 'shop_single_image_size', array( 'width' => 900, 'height' => 900, 'crop' => 1 ) );
}
add_action( 'after_switch_theme', 'lorem101_woocommerce_image_dimensions' );
