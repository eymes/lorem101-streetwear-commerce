<?php
/**
 * LOREM101 Theme - bootstrap motywu.
 * Cała logika jest rozbita na pliki w inc/, żeby functions.php się nie rozrastał.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOREM101_THEME_DIR', get_template_directory() );
define( 'LOREM101_THEME_URI', get_template_directory_uri() );
define( 'LOREM101_VERSION', '1.0.0' );

// Podstawowe wsparcie motywu (thumbnails, menu, itd.)
require_once LOREM101_THEME_DIR . '/inc/theme-setup.php';

// Kolejka CSS/JS zbudowanych przez Vite
require_once LOREM101_THEME_DIR . '/inc/enqueue.php';

// Wsparcie i konfiguracja WooCommerce
require_once LOREM101_THEME_DIR . '/inc/woocommerce-setup.php';

// Nagłówki nawigacyjne, ACF-style pola itp. - dodawaj tu kolejne moduły
