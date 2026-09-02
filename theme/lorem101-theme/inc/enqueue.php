<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lorem101_enqueue_assets() {
	$use_dev_server = defined( 'LOREM101_VITE_DEV_SERVER' ) && LOREM101_VITE_DEV_SERVER;

	if ( $use_dev_server ) {
		wp_enqueue_script( 'lorem101-vite-client', 'http://localhost:5173/@vite/client', array(), null, false );
		wp_enqueue_script( 'lorem101-main-js', 'http://localhost:5173/src/js/main.js', array(), null, true );
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( in_array( $handle, array( 'lorem101-vite-client', 'lorem101-main-js' ), true ) ) {
				$tag = str_replace( ' src', ' type="module" src', $tag );
			}
			return $tag;
		}, 10, 2 );

		lorem101_localize_rest_data();
		return;
	}

	$manifest_path = LOREM101_THEME_DIR . '/dist/.vite/manifest.json';
	if ( ! file_exists( $manifest_path ) ) {
		return;
	}

	$manifest = json_decode( file_get_contents( $manifest_path ), true );

	if ( isset( $manifest['src/js/main.js'] ) ) {
		$entry = $manifest['src/js/main.js'];

		if ( ! empty( $entry['css'] ) ) {
			foreach ( $entry['css'] as $i => $css_file ) {
				wp_enqueue_style( 'lorem101-main-css-' . $i, LOREM101_THEME_URI . '/dist/' . $css_file, array(), LOREM101_VERSION );
			}
		}

		wp_enqueue_script( 'lorem101-main-js', LOREM101_THEME_URI . '/dist/' . $entry['file'], array(), LOREM101_VERSION, true );
		wp_script_add_data( 'lorem101-main-js', 'type', 'module' );
	}

	lorem101_localize_rest_data();
}
add_action( 'wp_enqueue_scripts', 'lorem101_enqueue_assets' );

/**
 * Adres i token dla zapytań do naszego REST API.
 *
 * Token (nonce) jest konieczny, żeby WordPress rozpoznał zalogowanego
 * użytkownika w zapytaniu REST. Bez niego koszyk gościa i koszyk osoby
 * zalogowanej mogłyby się rozjechać - serwer traktowałby każde zapytanie
 * jako anonimowe.
 */
function lorem101_localize_rest_data() {
	wp_localize_script( 'lorem101-main-js', 'lorem101Rest', array(
		'addToCart' => esc_url_raw( rest_url( 'lorem101/v1/cart/add' ) ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
	) );
}
