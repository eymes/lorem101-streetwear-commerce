<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optymalizacje wydajności.
 *
 * Wszystkie zmiany dotyczą rzeczy, których ten sklep nie używa. Nic tu nie
 * przyspiesza kosztem funkcjonalności - usuwamy wyłącznie martwy balast.
 */

/**
 * Usunięcie obsługi emoji.
 *
 * WordPress dokłada na każdej stronie skrypt (~10 kB) zamieniający emoji
 * na obrazki - potrzebny tylko dla bardzo starych przeglądarek. Współczesne
 * radzą sobie same.
 */
function lorem101_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

	// Emoji doklejają się też do edytora wizualnego
	add_filter( 'tiny_mce_plugins', function ( $plugins ) {
		return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
	} );
}
add_action( 'init', 'lorem101_disable_emojis' );

/**
 * Usunięcie znaczników, których sklep nie wykorzystuje.
 *
 * oEmbed pozwala osadzać wpisy z tej strony na innych witrynach - w sklepie
 * z ubraniami nieprzydatne, a dokłada skrypt i dwa odnośniki w nagłówku.
 * RSD i wlwmanifest to relikty po edytorach zewnętrznych (Windows Live Writer).
 */
function lorem101_clean_head() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' ); // ukrywa wersję WordPressa
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );

	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'rest_api_init', 'wp_oembed_register_route' );

	// Odnośniki do kanałów RSS - sklep nie prowadzi bloga
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}
add_action( 'init', 'lorem101_clean_head' );

/**
 * Wyłączenie stylów bloków Gutenberga tam, gdzie ich nie używamy.
 *
 * WordPress ładuje wspólny arkusz bloków (~30 kB) na każdej stronie.
 * Koszyk i checkout są blokami, więc tam musi zostać - na stronie głównej
 * i karcie produktu nie ma czego stylować.
 */
function lorem101_dequeue_block_styles() {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return;
	}

	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'lorem101_dequeue_block_styles', 100 );

/**
 * Priorytet ładowania dla głównego zdjęcia produktu.
 *
 * To zwykle największy element widoczny bez przewijania, czyli ten, który
 * Google mierzy jako LCP (Largest Contentful Paint). fetchpriority="high"
 * mówi przeglądarce, żeby pobrała je przed resztą obrazków.
 *
 * Jednocześnie zdejmujemy z niego leniwe ładowanie - opóźnianie obrazu,
 * który i tak jest od razu widoczny, tylko pogarsza wynik.
 */
function lorem101_prioritize_product_image( $attr, $attachment, $size ) {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return $attr;
	}

	// Interesuje nas wyłącznie zdjęcie w galerii, nie miniatury kolorów
	if ( empty( $attr['class'] ) || false === strpos( $attr['class'], 'wp-post-image' ) ) {
		return $attr;
	}

	$attr['fetchpriority'] = 'high';
	$attr['loading']       = 'eager';

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'lorem101_prioritize_product_image', 10, 3 );

/**
 * Usunięcie jQuery Migrate.
 *
 * To warstwa zgodności dla kodu pisanego pod jQuery sprzed 2016 roku.
 * WooCommerce jej nie potrzebuje, a doklejała ~10 kB do każdej strony.
 */
function lorem101_remove_jquery_migrate( $scripts ) {
	if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
		return;
	}

	$scripts->registered['jquery']->deps = array_diff(
		$scripts->registered['jquery']->deps,
		array( 'jquery-migrate' )
	);
}
add_action( 'wp_default_scripts', 'lorem101_remove_jquery_migrate' );

/**
 * Wyłączenie mechanizmu "cart fragments" WooCommerce.
 *
 * Fragments to skrypt, który przy KAŻDYM wejściu na stronę wysyła zapytanie
 * AJAX po aktualny stan koszyka, żeby odświeżyć mini-koszyk w nagłówku.
 * To jedno z najczęściej wskazywanych wąskich gardeł wydajności WooCommerce -
 * dodatkowe zapytanie do serwera przy każdym kliknięciu w link.
 *
 * Nam jest zbędny z dwóch powodów: licznik renderujemy w PHP przy budowaniu
 * strony, a po dodaniu produktu aktualizujemy go własnym kodem
 * (updateCartCount w main.js), bez pytania serwera o cały koszyk.
 *
 * Zostawiamy go na stronach koszyka i checkoutu, gdzie WooCommerce używa
 * fragmentów do przeliczania sum.
 */
function lorem101_disable_cart_fragments() {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return;
	}

	wp_dequeue_script( 'wc-cart-fragments' );
}
add_action( 'wp_enqueue_scripts', 'lorem101_disable_cart_fragments', 100 );

/**
 * Ograniczenie liczby wersji roboczych wpisu.
 *
 * WordPress domyślnie zapisuje każdą zmianę bez limitu. Przy kilkudziesięciu
 * edycjach opisu produktu baza puchnie od kopii, których nikt nie ogląda.
 */
if ( ! defined( 'WP_POST_REVISIONS' ) ) {
	define( 'WP_POST_REVISIONS', 5 );
}
