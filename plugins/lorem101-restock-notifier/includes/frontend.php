<?php
/**
 * Formularz „powiadom mnie" na karcie produktu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formularz wstawiamy zawsze, ale ukryty. Pokazuje go JavaScript dopiero
 * wtedy, gdy klient wybierze wyprzedany wariant.
 *
 * Dlaczego nie renderować go warunkowo w PHP: wariant wybiera się już po
 * wczytaniu strony, więc serwer w momencie generowania HTML jeszcze nie wie,
 * czy będzie potrzebny. Dorabianie formularza przez JS oznaczałoby budowanie
 * markupu w dwóch miejscach.
 */
function lorem101_restock_render_form() {
	global $product;

	if ( ! $product instanceof WC_Product_Variable ) {
		return;
	}
	?>
	<div class="restock-form" data-restock-form hidden>
		<p class="restock-form__label">
			<?php esc_html_e( 'Ten rozmiar jest wyprzedany. Damy znać, gdy wróci.', 'lorem101-restock' ); ?>
		</p>

		<div class="restock-form__row">
			<label class="screen-reader-text" for="restock-email">
				<?php esc_html_e( 'Adres e-mail', 'lorem101-restock' ); ?>
			</label>

			<input
				type="email"
				id="restock-email"
				class="restock-form__input"
				data-restock-email
				placeholder="<?php esc_attr_e( 'twoj@email.com', 'lorem101-restock' ); ?>"
				autocomplete="email">

			<button type="button" class="button restock-form__button" data-restock-submit>
				<?php esc_html_e( 'Powiadom mnie', 'lorem101-restock' ); ?>
			</button>
		</div>

		<p class="restock-form__message" data-restock-message role="status" hidden></p>
	</div>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'lorem101_restock_render_form', 31 );

/**
 * Zasoby ładujemy tylko na karcie produktu.
 */
function lorem101_restock_enqueue() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	wp_enqueue_style(
		'lorem101-restock',
		plugins_url( 'assets/restock.css', LOREM101_RESTOCK_DIR . 'lorem101-restock-notifier.php' ),
		array(),
		LOREM101_RESTOCK_VERSION
	);

	wp_enqueue_script(
		'lorem101-restock',
		plugins_url( 'assets/restock.js', LOREM101_RESTOCK_DIR . 'lorem101-restock-notifier.php' ),
		array(),
		LOREM101_RESTOCK_VERSION,
		true
	);

	wp_localize_script( 'lorem101-restock', 'lorem101Restock', array(
		'endpoint' => esc_url_raw( rest_url( 'lorem101/v1/restock' ) ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'texts'    => array(
			'sending' => __( 'Zapisuję…', 'lorem101-restock' ),
			'success' => __( 'Damy znać, gdy produkt wróci do sprzedaży.', 'lorem101-restock' ),
			'error'   => __( 'Nie udało się zapisać zgłoszenia.', 'lorem101-restock' ),
			'email'   => __( 'Podaj poprawny adres e-mail.', 'lorem101-restock' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'lorem101_restock_enqueue' );
