<?php
/**
 * Panel "Ustawienia dropu" przy edycji produktu.
 *
 * Dane trzymamy jako post meta produktu (nie osobny typ wpisu), bo drop
 * jest własnością konkretnego produktu, a nie bytem samodzielnym.
 * Dzięki temu odpada synchronizacja dwóch obiektów i wszystko, co
 * WooCommerce już potrafi (magazyn, warianty), zostaje bez zmian.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klucze meta w jednym miejscu - literówka w nazwie klucza to błąd,
 * którego PHP nie zgłosi, a dane po prostu cicho znikną.
 */
const LOREM101_DROP_META_START = '_lorem101_drop_start';
const LOREM101_DROP_META_END   = '_lorem101_drop_end';

/**
 * Rejestracja panelu w prawej kolumnie edycji produktu.
 */
function lorem101_drop_register_meta_box() {
	add_meta_box(
		'lorem101-drop-settings',
		__( 'Ustawienia dropu', 'lorem101-drop-manager' ),
		'lorem101_drop_render_meta_box',
		'product',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'lorem101_drop_register_meta_box' );

/**
 * Zawartość panelu.
 */
function lorem101_drop_render_meta_box( $post ) {
	$start = get_post_meta( $post->ID, LOREM101_DROP_META_START, true );
	$end   = get_post_meta( $post->ID, LOREM101_DROP_META_END, true );

	// Nonce chroni przed zapisem żądaniem spoza panelu (CSRF).
	wp_nonce_field( 'lorem101_drop_save', 'lorem101_drop_nonce' );
	?>
	<p>
		<label for="lorem101-drop-start"><strong><?php esc_html_e( 'Start dropu', 'lorem101-drop-manager' ); ?></strong></label><br>
		<input
			type="datetime-local"
			id="lorem101-drop-start"
			name="lorem101_drop_start"
			value="<?php echo esc_attr( $start ); ?>"
			style="width:100%">
	</p>

	<p>
		<label for="lorem101-drop-end"><strong><?php esc_html_e( 'Koniec dropu', 'lorem101-drop-manager' ); ?></strong></label><br>
		<input
			type="datetime-local"
			id="lorem101-drop-end"
			name="lorem101_drop_end"
			value="<?php echo esc_attr( $end ); ?>"
			style="width:100%">
	</p>

	<p class="description">
		<?php esc_html_e( 'Zostaw oba pola puste, żeby produkt zachowywał się jak zwykły towar, bez mechaniki dropu.', 'lorem101-drop-manager' ); ?>
	</p>
	<?php
}

/**
 * Zapis danych z panelu.
 *
 * Kolejność sprawdzeń jest tu istotna: save_post odpala się także przy
 * autozapisie i przy zapisie z innych ekranów, więc bez tych warunków
 * łatwo skasować dane, których użytkownik wcale nie edytował.
 */
function lorem101_drop_save_meta_box( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['lorem101_drop_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['lorem101_drop_nonce'] ), 'lorem101_drop_save' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		LOREM101_DROP_META_START => 'lorem101_drop_start',
		LOREM101_DROP_META_END   => 'lorem101_drop_end',
	);

	foreach ( $fields as $meta_key => $input_name ) {
		$value = isset( $_POST[ $input_name ] )
			? sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) )
			: '';

		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			continue;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}
}
add_action( 'save_post_product', 'lorem101_drop_save_meta_box' );
