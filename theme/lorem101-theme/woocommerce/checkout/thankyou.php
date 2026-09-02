<?php
/**
 * Nadpisany szablon: potwierdzenie złożenia zamówienia.
 * Oryginał: woocommerce/templates/checkout/thankyou.php
 *
 * Domyślny szablon wypisuje podsumowanie jako listę bez wyraźnej hierarchii,
 * przez co numer zamówienia ginie wśród reszty. Tutaj numer jest głównym
 * elementem, a szczegóły trafiają do czytelnej siatki.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="order-confirmation">

	<?php if ( ! $order ) : ?>
		<p class="order-confirmation__lead">
			<?php echo esc_html( apply_filters( 'woocommerce_thankyou_order_received_text', __( 'Dziękujemy. Zamówienie zostało przyjęte.', 'lorem101-theme' ), null ) ); ?>
		</p>
		<?php return; ?>
	<?php endif; ?>

	<?php if ( $order->has_status( 'failed' ) ) : ?>

		<p class="order-confirmation__lead order-confirmation__lead--error">
			<?php esc_html_e( 'Płatność nie powiodła się. Spróbuj ponownie lub wybierz inną metodę płatności.', 'lorem101-theme' ); ?>
		</p>

		<p class="order-confirmation__actions">
			<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button">
				<?php esc_html_e( 'Zapłać ponownie', 'lorem101-theme' ); ?>
			</a>
		</p>

	<?php else : ?>

		<p class="order-confirmation__lead">
			<?php esc_html_e( 'Dziękujemy za zamówienie', 'lorem101-theme' ); ?>
		</p>

		<p class="order-confirmation__number">
			<?php echo esc_html( '#' . $order->get_order_number() ); ?>
		</p>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php endif; ?>

</div>
