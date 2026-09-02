<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Podstawy SEO realizowane bez wtyczki.
 *
 * W realnym projekcie komercyjnym część tego przejąłby Yoast/RankMath -
 * tutaj robimy to ręcznie, żeby projekt nie zależał od zewnętrznej wtyczki
 * i żeby było widać, że rozumiemy co te wtyczki właściwie robią.
 *
 * Czego tu świadomie NIE ma:
 * - JSON-LD dla produktów (schema.org Product/Offer) - WooCommerce generuje
 *   je samo od wersji 3.0, dublowanie tylko zaszkodziłoby
 * - tag <title> - obsługiwany przez add_theme_support('title-tag')
 * - lazy loading obrazków - WordPress dodaje loading="lazy" domyślnie od 5.5
 */

/**
 * Meta description dla strony głównej i produktów.
 */
function lorem101_meta_description() {
	$description = '';

	if ( is_front_page() ) {
		$description = get_bloginfo( 'description' );

		if ( ! $description ) {
			$description = sprintf(
				/* translators: %s: nazwa sklepu */
				__( '%s — limitowane kolekcje streetwear. Bluzy, t-shirty i spodenki w krótkich seriach.', 'lorem101-theme' ),
				get_bloginfo( 'name' )
			);
		}
	} elseif ( is_singular( 'product' ) ) {
		global $post;
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_strip_all_tags( $post->post_content );
		$description = wp_trim_words( $excerpt, 30, '' );
	} elseif ( is_singular() ) {
		$description = wp_trim_words( wp_strip_all_tags( get_the_content() ), 30, '' );
	}

	if ( ! $description ) {
		return;
	}

	printf(
		'<meta name="description" content="%s">' . "\n",
		esc_attr( $description )
	);
}
add_action( 'wp_head', 'lorem101_meta_description', 1 );

/**
 * Podstawowe Open Graph - kontroluje jak link wygląda po wklejeniu
 * na Instagramie, Discordzie czy w wiadomości. Dla sklepu streetwear
 * to realnie istotne, bo większość ruchu przychodzi z social mediów.
 */
function lorem101_open_graph_tags() {
	if ( is_singular( 'product' ) ) {
		// UWAGA: w wp_head globalny $product nie jest jeszcze obiektem
		// WC_Product - pętla WooCommerce startuje później, więc zmienna
		// bywa pustym stringiem. Dlatego pobieramy produkt po ID zamiast
		// polegać na globalu (to powodowało fatal error przy wywołaniu
		// get_image_id() na stringu).
		$product  = wc_get_product( get_the_ID() );
		$image_id = ( $product instanceof WC_Product ) ? $product->get_image_id() : 0;
		$image    = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

		printf( '<meta property="og:type" content="product">' . "\n" );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( get_the_title() ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( get_permalink() ) );

		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
	} elseif ( is_front_page() ) {
		printf( '<meta property="og:type" content="website">' . "\n" );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( home_url( '/' ) ) );
	}

	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
}
add_action( 'wp_head', 'lorem101_open_graph_tags', 2 );

/**
 * Canonical dla wariantów kolorystycznych.
 *
 * Nasze kafelki linkują do tego samego produktu z różnym ?attribute_kolor,
 * czyli ta sama treść pod kilkoma adresami. Bez canonical wyszukiwarka
 * potraktowałaby to jako duplicate content i sama wybrałaby, którą wersję
 * indeksować. Wskazujemy jawnie czysty adres produktu jako wersję główną.
 */
function lorem101_canonical_for_color_variants( $canonical ) {
	if ( is_singular( 'product' ) && isset( $_GET['attribute_kolor'] ) ) {
		return get_permalink();
	}
	return $canonical;
}
add_filter( 'get_canonical_url', 'lorem101_canonical_for_color_variants' );
