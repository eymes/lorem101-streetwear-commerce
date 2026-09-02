<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Przestawienie kolejności elementów w prawej kolumnie strony produktu.
 *
 * Domyślnie WooCommerce renderuje: tytuł(5), cena(10), krótki opis(20),
 * formularz dodania do koszyka(30), meta(40), sharing(50).
 *
 * Usuwamy meta (SKU / kategoria) i sharing - nie pasują do minimalistycznego
 * layoutu marki. Krótki opis przesuwamy pod formularz (35).
 */
add_action( 'init', function () {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 35 );

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
} );

/**
 * Zostawiamy tylko zakładkę z opisem (bez "Additional information" i recenzji)
 * i zmieniamy jej nazwę na "Opis". Sam pasek zakładek chowamy w CSS,
 * bo przy jednej zakładce nie ma czego przełączać.
 */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['additional_information'] );
	unset( $tabs['reviews'] );

	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title'] = __( 'Opis', 'lorem101-theme' );
	}

	return $tabs;
}, 98 );

/**
 * Nagłówek wewnątrz zakładki opisu ("Description" -> "Opis").
 */
add_filter( 'woocommerce_product_description_heading', function () {
	return __( 'Opis', 'lorem101-theme' );
} );

/**
 * Tytuł produktu wzbogacony o aktualnie oglądaną kolorystykę,
 * np. "Heavy Oversized Hoodie — Czarny".
 *
 * Podmieniamy całą funkcję renderującą tytuł (zamiast filtrować the_title),
 * bo the_title jest używany w wielu miejscach - w breadcrumbach, tagu <title>
 * strony, powiadomieniach koszyka - i dopisywanie tam koloru robiłoby bałagan.
 * Tutaj zmiana dotyczy wyłącznie nagłówka na karcie produktu.
 */
function lorem101_single_product_title() {
	global $product;

	$title = get_the_title();
	$slug  = lorem101_get_current_color_slug( $product );

	if ( $slug ) {
		foreach ( lorem101_get_product_colors( $product ) as $color ) {
			if ( $color['slug'] === $slug ) {
				$title .= ' — ' . $color['name'];
				break;
			}
		}
	}

	echo '<h1 class="product_title entry-title">' . esc_html( $title ) . '</h1>';
}

add_action( 'init', function () {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	add_action( 'woocommerce_single_product_summary', 'lorem101_single_product_title', 5 );
} );

/**
 * Zachowuje wybraną kolorystykę po dodaniu produktu do koszyka.
 *
 * Formularz "dodaj do koszyka" na karcie produktu wysyła się klasycznym
 * POST-em (WooCommerce używa AJAX tylko na listingach, dla produktów
 * prostych), a jego domyślny adres docelowy to czysty permalink produktu -
 * bez ?attribute_kolor. Po przeładowaniu strona pokazywała więc pierwszy
 * kolor z listy zamiast tego, który klient właśnie oglądał.
 *
 * Dopisujemy parametr do adresu formularza, żeby po dodaniu do koszyka
 * użytkownik został na tej samej kolorystyce.
 */
add_filter( 'woocommerce_add_to_cart_form_action', function ( $url ) {
	if ( empty( $_GET['attribute_kolor'] ) ) {
		return $url;
	}

	return add_query_arg(
		'attribute_kolor',
		sanitize_title( wp_unslash( $_GET['attribute_kolor'] ) ),
		$url
	);
} );

/**
 * Zbiera unikalne kolory produktu wraz ze zdjęciem wariacji.
 */
function lorem101_get_product_colors( $product ) {
	$colors = array();

	if ( ! $product instanceof WC_Product_Variable ) {
		return $colors;
	}

	$seen = array();

	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation ) {
			continue;
		}

		$color = $variation->get_attribute( 'kolor' );
		if ( '' === $color || isset( $seen[ $color ] ) ) {
			continue;
		}
		$seen[ $color ] = true;

		$image_id = $variation->get_image_id();
		$image_id = $image_id ? $image_id : $product->get_image_id();

		$colors[] = array(
			'name'  => $color,
			'slug'  => sanitize_title( $color ),
			'image' => $image_id
				? wp_get_attachment_image( $image_id, 'woocommerce_gallery_thumbnail' )
				: wc_placeholder_img( 'woocommerce_gallery_thumbnail' ),
		);
	}

	return $colors;
}

/**
 * Który kolor jest "aktualnie oglądany" na tej stronie.
 * Bierzemy z ?attribute_kolor=... w URL, a jeśli go nie ma - pierwszy dostępny.
 * Dzięki temu strona produktu ZAWSZE prezentuje konkretną kolorystykę,
 * co pozwala nam schować pole wyboru koloru z formularza (kolor wybiera się
 * przez kliknięcie kafelka, nie przez dropdown).
 */
function lorem101_get_current_color_slug( $product ) {
	$colors = lorem101_get_product_colors( $product );

	if ( empty( $colors ) ) {
		return '';
	}

	if ( isset( $_GET['attribute_kolor'] ) ) {
		$requested = sanitize_title( wp_unslash( $_GET['attribute_kolor'] ) );
		foreach ( $colors as $color ) {
			if ( $color['slug'] === $requested ) {
				return $requested;
			}
		}
	}

	return $colors[0]['slug'];
}

/**
 * Wymusza zaznaczenie aktualnego koloru w (ukrytym) dropdownie wariantu.
 * Bez tego WooCommerce nie potrafiłby rozstrzygnąć wariacji i przycisk
 * "dodaj do koszyka" pozostałby zablokowany.
 */
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', function ( $args ) {
	if ( isset( $args['attribute'] ) && 'kolor' === strtolower( $args['attribute'] ) ) {
		global $product;
		$args['selected'] = lorem101_get_current_color_slug( $product );
	}
	return $args;
} );

/**
 * Sekcja "Inne kolory" - kwadratowe kafelki ze zdjęciami POZOSTAŁYCH
 * kolorystyk (bez tej aktualnie oglądanej). Każdy link prowadzi na tę samą
 * stronę produktu, ale z innym ?attribute_kolor - czyli otwiera tę samą
 * kartę produktu w innej kolorystyce.
 */
function lorem101_render_other_colors() {
	global $product;

	$colors  = lorem101_get_product_colors( $product );
	$current = lorem101_get_current_color_slug( $product );

	$others = array_filter( $colors, function ( $color ) use ( $current ) {
		return $color['slug'] !== $current;
	} );

	if ( empty( $others ) ) {
		return;
	}
	?>
	<div class="other-colors">
		<h2 class="other-colors__heading"><?php esc_html_e( 'Inne kolory:', 'lorem101-theme' ); ?></h2>
		<ul class="other-colors__list">
			<?php foreach ( $others as $color ) : ?>
				<li class="other-colors__item">
					<a href="<?php echo esc_url( add_query_arg( 'attribute_kolor', $color['slug'], get_permalink( $product->get_id() ) ) ); ?>"
					   title="<?php echo esc_attr( $color['name'] ); ?>">
						<?php echo $color['image']; ?>
						<span class="other-colors__name"><?php echo esc_html( $color['name'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'lorem101_render_other_colors', 40 );
