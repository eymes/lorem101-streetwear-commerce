<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOREM101_THEME_DIR', get_template_directory() );
define( 'LOREM101_THEME_URI', get_template_directory_uri() );
define( 'LOREM101_VERSION', '1.0.0' );

require_once LOREM101_THEME_DIR . '/inc/theme-setup.php';
require_once LOREM101_THEME_DIR . '/inc/enqueue.php';
require_once LOREM101_THEME_DIR . '/inc/woocommerce-setup.php';
require_once LOREM101_THEME_DIR . '/inc/product-tiles.php';
require_once LOREM101_THEME_DIR . '/inc/product-color-swatches.php';
require_once LOREM101_THEME_DIR . '/inc/seo.php';
require_once LOREM101_THEME_DIR . '/inc/order-numbers.php';
require_once LOREM101_THEME_DIR . '/inc/rest-cart.php';
require_once LOREM101_THEME_DIR . '/inc/translations.php';
require_once LOREM101_THEME_DIR . '/inc/stock-display.php';
require_once LOREM101_THEME_DIR . '/inc/performance.php';
require_once LOREM101_THEME_DIR . '/inc/webp.php';
