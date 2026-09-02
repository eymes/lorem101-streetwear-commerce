<?php
/**
 * Plugin Name: LOREM101 Drop Manager
 * Plugin URI: https://github.com/twoj-user/lorem101-streetwear-commerce
 * Description: Zarządzanie limitowanymi kolekcjami (dropami) dla sklepu LOREM101.
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

/**
 * Wczytujemy moduły dopiero po potwierdzeniu, że WooCommerce działa -
 * inaczej wywołania wc_get_product() itp. wywaliłyby stronę błędem
 * krytycznym, gdyby ktoś wyłączył WooCommerce zostawiając naszą wtyczkę.
 */
function lorem101_drop_manager_init() {
	if ( ! lorem101_drop_manager_check_woocommerce() ) {
		return;
	}

	require_once LOREM101_DROP_MANAGER_DIR . 'includes/taxonomy.php';
	require_once LOREM101_DROP_MANAGER_DIR . 'includes/drop-status.php';
	require_once LOREM101_DROP_MANAGER_DIR . 'includes/frontend.php';
	require_once LOREM101_DROP_MANAGER_DIR . 'includes/hero.php';
}
add_action( 'plugins_loaded', 'lorem101_drop_manager_init' );
