<?php
/**
 * Nadpisany szablon: karta produktu w listingu (archiwum sklepu, kategorie).
 * Oryginał: wp-content/plugins/woocommerce/templates/content-product.php
 *
 * ZASADA nadpisywania szablonów WooCommerce:
 * 1. Skopiuj oryginalny plik z woocommerce/templates/... (w folderze wtyczki)
 * 2. Wklej go do motywu, do folderu /woocommerce/ z DOKŁADNIE tą samą
 *    ścieżką względną (bez /templates/ na początku)
 * 3. Edytuj jak chcesz - WooCommerce automatycznie użyje wersji z motywu
 *
 * Poniżej uproszczona, własna wersja karty produktu (zamiast oryginalnych hooków WC).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>
	<a href="<?php the_permalink(); ?>" class="product-card__link">
		<div class="product-card__image">
			<?php echo woocommerce_get_product_thumbnail(); ?>
		</div>

		<h2 class="product-card__title"><?php the_title(); ?></h2>

		<span class="product-card__price">
			<?php echo $product->get_price_html(); ?>
		</span>
	</a>

	<div class="product-card__actions">
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</div>
</li>
