<?php
/**
 * Nadpisany szablon: zakładka z opisem produktu.
 * Oryginał: woocommerce/templates/single-product/tabs/description.php
 *
 * Powód nadpisania: nad właściwym opisem dokładamy nazwę kolekcji, do której
 * należy produkt. Dane pochodzą z wtyczki Drop Manager, ale szablon działa
 * też bez niej - sprawdzamy function_exists, żeby wyłączenie wtyczki nie
 * wywaliło karty produktu.
 */

defined( 'ABSPATH' ) || exit;

global $product;

$heading = apply_filters( 'woocommerce_product_description_heading', __( 'Opis', 'lorem101-theme' ) );

$collection = null;

if ( $product instanceof WC_Product && function_exists( 'lorem101_drop_get_for_product' ) ) {
	$drop = lorem101_drop_get_for_product( $product->get_id() );

	if ( $drop ) {
		$collection = $drop['name'];
	}
}
?>

<?php if ( $heading ) : ?>
	<h2><?php echo esc_html( $heading ); ?></h2>
<?php endif; ?>

<?php if ( $collection ) : ?>
	<p class="product-collection">
		<?php
		printf(
			/* translators: %s: nazwa kolekcji */
			esc_html__( 'Kolekcja %s', 'lorem101-theme' ),
			esc_html( $collection )
		);
		?>
	</p>
<?php endif; ?>

<?php the_content(); ?>
