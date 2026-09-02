<?php
/**
 * Nadpisany szablon: zdjęcie produktu na karcie produktu.
 * Oryginał: woocommerce/templates/single-product/product-image.php
 *
 * Powód nadpisania: domyślny szablon renderuje zdjęcie GŁÓWNE produktu
 * (Product Image). U nas jeden produkt "Hoodie" ma kilka kolorystyk,
 * a zdjęcia trzymamy na poziomie wariacji - nie da się wybrać jednego
 * zdjęcia reprezentującego wszystkie kolory naraz.
 *
 * Dlatego sami ustalamy, którą wariację pokazać: na podstawie
 * ?attribute_kolor z adresu (a gdy go brak - pierwsza dostępna kolorystyka).
 *
 * Świadomie robimy to w szablonie, a NIE przez filtr post_thumbnail_id:
 * tamten filtr odpalał się ponownie w środku wc_get_product() i wpadał
 * w nieskończoną rekurencję. Tutaj kod wykonuje się raz, przy renderowaniu.
 */

defined( 'ABSPATH' ) || exit;

global $product;

$image_id = 0;

// Zdjęcie wariacji odpowiadającej aktualnie oglądanej kolorystyce
if ( $product instanceof WC_Product_Variable && function_exists( 'lorem101_get_current_color_slug' ) ) {
	$slug = lorem101_get_current_color_slug( $product );

	if ( $slug ) {
		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation ) {
				continue;
			}

			if ( sanitize_title( $variation->get_attribute( 'kolor' ) ) !== $slug ) {
				continue;
			}

			if ( $variation->get_image_id() ) {
				$image_id = $variation->get_image_id();
				break;
			}
		}
	}
}

// Zapasowo: zdjęcie główne produktu (gdy wariacje nie mają wgranych zdjęć)
if ( ! $image_id ) {
	$image_id = $product->get_image_id();
}

$wrapper_classes = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array( 'woocommerce-product-gallery', 'woocommerce-product-gallery--columns-4', 'images' )
);
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>">
	<div class="woocommerce-product-gallery__wrapper">
		<div class="woocommerce-product-gallery__image">
			<?php
			if ( $image_id ) {
				echo wp_get_attachment_image(
					$image_id,
					'woocommerce_single',
					false,
					array(
						'class' => 'wp-post-image',
						'alt'   => the_title_attribute( array( 'echo' => false ) ),
					)
				);
			} else {
				echo wc_placeholder_img( 'woocommerce_single' );
			}
			?>
		</div>
	</div>
</div>
