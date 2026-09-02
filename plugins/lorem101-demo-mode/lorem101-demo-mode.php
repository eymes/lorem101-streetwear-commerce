<?php
/**
 * Plugin Name: LOREM101 Demo Mode
 * Plugin URI: https://github.com/twoj-user/lorem101-streetwear-commerce
 * Description: Tryb prezentacyjny: pozwala przejść cały proces zakupowy, ale blokuje finalizację zamówienia. Do wyłączenia, gdyby sklep miał kiedyś sprzedawać naprawdę.
 * Version: 1.0.0
 * Author: Twoje Imię
 * Text Domain: lorem101-demo-mode
 * Requires Plugins: woocommerce
 *
 * ---
 *
 * Dlaczego osobna wtyczka, a nie kod w motywie:
 * to nie jest funkcja sklepu, tylko zabezpieczenie prezentacji. Trzymana
 * osobno daje się wyłączyć jednym kliknięciem i nie zaśmieca motywu kodem,
 * który w prawdziwym wdrożeniu nie miałby racji bytu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOREM101_DEMO_VERSION', '1.0.0' );

/**
 * Komunikat pokazywany przy próbie złożenia zamówienia.
 */
function lorem101_demo_get_message() {
	return __(
		'To jest projekt portfolio — zamówienia nie są realizowane. Cały proces zakupowy działa, ale finalizacja jest celowo zablokowana.',
		'lorem101-demo-mode'
	);
}

/**
 * Blokada finalizacji w KLASYCZNYM checkoucie (shortcode).
 *
 * woocommerce_after_checkout_validation odpala się po sprawdzeniu pól,
 * a przed utworzeniem zamówienia - dodanie błędu przerywa proces.
 */
function lorem101_demo_block_classic_checkout( $data, $errors ) {
	$errors->add( 'lorem101_demo', lorem101_demo_get_message() );
}
add_action( 'woocommerce_after_checkout_validation', 'lorem101_demo_block_classic_checkout', 10, 2 );

/**
 * Blokada finalizacji w BLOKOWYM checkoucie (Store API).
 *
 * Nowy checkout nie przechodzi przez woocommerce_after_checkout_validation -
 * komunikuje się z serwerem przez Store API, które ma własny zestaw hooków.
 * Rzucenie RouteException zwraca błąd w formacie, który blok potrafi
 * wyświetlić użytkownikowi.
 */
function lorem101_demo_block_blocks_checkout() {
	if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\Exceptions\RouteException' ) ) {
		return;
	}

	throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException(
		'lorem101_demo_mode',
		lorem101_demo_get_message(),
		403
	);
}
add_action( 'woocommerce_store_api_checkout_update_order_from_request', 'lorem101_demo_block_blocks_checkout' );

/**
 * Informacja o trybie demo na stronie koszyka i checkoutu.
 *
 * Klient dowiaduje się o ograniczeniu ZANIM wypełni formularz, a nie dopiero
 * przy kliknięciu "złóż zamówienie" - inaczej wyglądałoby to jak awaria.
 */
function lorem101_demo_render_notice() {
	if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
		return;
	}
	?>
	<p class="lorem101-demo-notice"><?php echo esc_html( lorem101_demo_get_message() ); ?></p>
	<?php
}
add_action( 'wp_body_open', 'lorem101_demo_render_notice' );

/**
 * Styl komunikatu.
 *
 * Osadzony w PHP zamiast osobnego pliku CSS, bo to kilka linijek używanych
 * na dwóch podstronach - osobne żądanie HTTP kosztowałoby więcej niż zysk
 * z rozdzielenia.
 */
function lorem101_demo_enqueue_styles() {
	if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
		return;
	}

	$css = '
		.lorem101-demo-notice {
			max-width: 1200px;
			margin: 1.5rem auto 0;
			padding: 0.9rem 1rem;
			border: 1px solid #c23a1f;
			font-family: "Courier New", monospace;
			font-size: 0.75rem;
			line-height: 1.6;
			letter-spacing: 0.03em;
			color: #c23a1f;
			text-align: center;
		}
	';

	wp_register_style( 'lorem101-demo', false, array(), LOREM101_DEMO_VERSION );
	wp_enqueue_style( 'lorem101-demo' );
	wp_add_inline_style( 'lorem101-demo', $css );
}
add_action( 'wp_enqueue_scripts', 'lorem101_demo_enqueue_styles' );

/**
 * Informacja w panelu administratora, że tryb demo jest aktywny.
 *
 * Bez tego łatwo zapomnieć, dlaczego zamówienia nie przechodzą.
 */
function lorem101_demo_admin_notice() {
	$screen = get_current_screen();

	if ( ! $screen || 'plugins' !== $screen->id ) {
		return;
	}

	echo '<div class="notice notice-info"><p><strong>LOREM101 Demo Mode</strong> — ';
	esc_html_e( 'tryb prezentacyjny jest aktywny: klienci mogą przejść cały proces zakupowy, ale nie złożą zamówienia. Wyłącz tę wtyczkę, żeby uruchomić sprzedaż.', 'lorem101-demo-mode' );
	echo '</p></div>';
}
add_action( 'admin_notices', 'lorem101_demo_admin_notice' );
