<?php
/**
 * Nadpisany szablon: strona pojedynczego produktu.
 * Oryginał: wp-content/plugins/woocommerce/templates/content-single-product.php
 *
 * Zmiana względem oryginału to wyłącznie struktura DOM - dwie kolumny
 * (galeria / podsumowanie) zamiast domyślnych pływających divów. Cała
 * zawartość nadal pochodzi z hooków WooCommerce, więc nic nie tracimy:
 * galeria, formularz wariantów, koszyk i wtyczki podpięte pod te hooki
 * działają tak samo. Kolejność elementów w prawej kolumnie przestawiamy
 * w inc/product-color-swatches.php, a nie tutaj.
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'product-single', $product ); ?>>

	<div class="product-single__gallery">
		<?php
		/**
		 * Hook: woocommerce_before_single_product_summary.
		 * @hooked woocommerce_show_product_sale_flash - 10
		 * @hooked woocommerce_show_product_images - 20
		 */
		do_action( 'woocommerce_before_single_product_summary' );
		?>
	</div>

	<div class="product-single__summary">
		<?php
		/**
		 * Hook: woocommerce_single_product_summary.
		 * Kolejność (po naszych zmianach): tytuł, cena, formularz wariantu
		 * z rozmiarem i przyciskiem, opis, inne kolory.
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>
	</div>

</div>

<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
