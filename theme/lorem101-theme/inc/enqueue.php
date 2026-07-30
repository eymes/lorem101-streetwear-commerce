<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ładuje assety zbudowane przez Vite (dist/manifest.json).
 * W trybie WP_DEBUG możesz przełączyć na serwer deweloperski Vite (npm run dev)
 * ustawiając stałą LOREM101_VITE_DEV_SERVER na true w wp-config.php.
 */
function lorem101_enqueue_assets() {

	$use_dev_server = defined( 'LOREM101_VITE_DEV_SERVER' ) && LOREM101_VITE_DEV_SERVER;

	if ( $use_dev_server ) {
		// Tryb developerski: Vite dev server serwuje pliki na żywo z HMR
		wp_enqueue_script( 'cwt-vite-client', 'http://localhost:5173/@vite/client', array(), null, false );
		wp_enqueue_script( 'cwt-main-js', 'http://localhost:5173/src/js/main.js', array(), null, true );
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( in_array( $handle, array( 'cwt-vite-client', 'cwt-main-js' ), true ) ) {
				$tag = str_replace( ' src', ' type="module" src', $tag );
			}
			return $tag;
		}, 10, 2 );
		return;
	}

	// Tryb produkcyjny: czytamy manifest wygenerowany przez `npm run build`
	$manifest_path = LOREM101_THEME_DIR . '/dist/.vite/manifest.json';

	if ( ! file_exists( $manifest_path ) ) {
		return;
	}

	$manifest = json_decode( file_get_contents( $manifest_path ), true );

	if ( isset( $manifest['src/js/main.js'] ) ) {
		$entry = $manifest['src/js/main.js'];

		if ( ! empty( $entry['css'] ) ) {
			foreach ( $entry['css'] as $i => $css_file ) {
				wp_enqueue_style( 'cwt-main-css-' . $i, LOREM101_THEME_URI . '/dist/' . $css_file, array(), LOREM101_VERSION );
			}
		}

		wp_enqueue_script( 'cwt-main-js', LOREM101_THEME_URI . '/dist/' . $entry['file'], array(), LOREM101_VERSION, true );
		wp_script_add_data( 'cwt-main-js', 'type', 'module' );
	}
}
add_action( 'wp_enqueue_scripts', 'lorem101_enqueue_assets' );
