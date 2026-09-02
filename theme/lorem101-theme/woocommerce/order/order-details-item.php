<?php
/**
 * Nadpisany szablon: pojedyncza pozycja w podsumowaniu zamówienia.
 * Oryginał: woocommerce/templates/order/order-details-item.php
 *
 * Powód nadpisania: domyślnie pozycja to sama nazwa i cena. Dokładamy
 * miniaturę, żeby klient rozpoznał produkt bez czytania nazwy wariantu.
 */

defined( 'ABSPATH' ) || exit;

if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
	return;
}

// Zdjęcie bierzemy z wariacji, a nie z produktu nadrzędnego - w tym sklepie
// każda kolorystyka ma własne zdjęcie, a produkt nadrzędny żadnego.
$thumbnail = '';

if ( $product instanceof WC_Product ) {
	$image_id  = $product->get_image_id();
	$thumbnail = $image_id
		? wp_get_attachment_image( $image_id, 'woocommerce_gallery_thumbnail' )
		: wc_placeholder_img( 'woocommerce_gallery_thumbnail' );
}
?>
<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order ) ); ?>">

	<td class="woocommerce-table__product-name product-name">
		<div class="order-item">
			<?php if ( $thumbnail ) : ?>
				<div class="order-item__image"><?php echo $thumbnail; ?></div>
			<?php endif; ?>

			<div class="order-item__details">
				<span class="order-item__name">
					<?php
					echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
					echo wp_kses_post( apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">&times;&nbsp;' . $item->get_quantity() . '</strong>', $item ) );
					?>
				</span>

				<?php do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false ); ?>
				<?php wc_display_item_meta( $item ); ?>
				<?php do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false ); ?>
			</div>
		</div>
	</td>

	<td class="woocommerce-table__product-total product-total">
		<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
	</td>

</tr>

<?php if ( $show_purchase_note && $purchase_note ) : ?>
	<tr class="woocommerce-table__product-purchase-note product-purchase-note">
		<td colspan="2"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); ?></td>
	</tr>
<?php endif; ?>
