<?php
/**
 * Ten plik jest wymagany przez WooCommerce - to on renderuje wszystkie
 * strony sklepu (archiwum produktów, single produkt, koszyk, checkout, moje konto),
 * chyba że masz bardziej szczegółowy plik (np. single-product.php, archive-product.php)
 * - wtedy WordPress użyje tego bardziej szczegółowego, zgodnie ze zwykłą hierarchią szablonów.
 *
 * W środku po prostu wywołujemy woocommerce_content(), a WooCommerce samo
 * decyduje jaki fragment (loop, single, cart...) wyrenderować w środku -
 * te fragmenty możesz nadpisywać przez pliki w folderze /woocommerce/ (patrz README).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' ); ?>

<div class="shop-wrapper">
	<?php woocommerce_content(); ?>
</div>

<?php get_footer( 'shop' ); ?>
