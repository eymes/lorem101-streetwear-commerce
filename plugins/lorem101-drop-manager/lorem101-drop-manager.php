<?php
/**
 * Plugin Name: LOREM101 Drop Manager
 * Plugin URI: https://github.com/twoj-user/lorem101-streetwear-commerce
 * Description: Zarządzanie limitowanymi kolekcjami (dropami) dla sklepu LOREM101 - daty startu/końca, countdown, limit sztuk, status dostępności.
 * Version: 0.1.0
 * Author: Twoje Imię
 * Text Domain: lorem101-drop-manager
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOREM101_DROP_MANAGER_VERSION', '0.1.0' );
define( 'LOREM101_DROP_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOREM101_DROP_MANAGER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Sprawdzenie czy WooCommerce jest aktywne - bez tego plugin nie ma sensu działać.
 */
function lorem101_drop_manager_check_woocommerce() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'LOREM101 Drop Manager wymaga aktywnej wtyczki WooCommerce.', 'lorem101-drop-manager' );
			echo '</p></div>';
		} );
		return false;
	}
	return true;
}
add_action( 'plugins_loaded', 'lorem101_drop_manager_check_woocommerce' );

// Kolejne moduły pluginu (meta box dropu, countdown, REST endpoints, notify-me)
// będziemy dokładać tutaj w kolejnych fazach jako osobne pliki w /includes/
