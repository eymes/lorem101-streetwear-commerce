<?php
/**
 * Taksonomia "Dropy".
 *
 * Drop jest wydarzeniem obejmującym wiele produktów (kolekcja "Spider" =
 * bluzy + t-shirty + spodenki), a nie właściwością pojedynczego produktu.
 * Dlatego taksonomia, a nie post meta: data startu leży w jednym miejscu,
 * zmiana godziny premiery to jedna edycja zamiast poprawiania każdego
 * produktu z osobna.
 *
 * Dodatkowo dostajemy za darmo ekran zarządzania, przypisywanie produktów
 * i archiwum dropu pod własnym adresem - bez pisania kodu.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LOREM101_DROP_TAXONOMY   = 'lorem101_drop';
const LOREM101_DROP_META_START = 'lorem101_drop_start';

/**
 * Rejestracja taksonomii.
 */
function lorem101_drop_register_taxonomy() {
	register_taxonomy(
		LOREM101_DROP_TAXONOMY,
		'product',
		array(
			'labels'            => array(
				'name'          => __( 'Dropy', 'lorem101-drop-manager' ),
				'singular_name' => __( 'Drop', 'lorem101-drop-manager' ),
				'add_new_item'  => __( 'Dodaj nowy drop', 'lorem101-drop-manager' ),
				'edit_item'     => __( 'Edytuj drop', 'lorem101-drop-manager' ),
				'search_items'  => __( 'Szukaj dropów', 'lorem101-drop-manager' ),
				'menu_name'     => __( 'Dropy', 'lorem101-drop-manager' ),
			),
			// hierarchical => true daje listę z checkboxami (jak kategorie),
			// a nie pole tagów. Produkt należy do jednego dropu, więc chcemy
			// wybór z listy, nie swobodne wpisywanie.
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'drop' ),
		)
	);
}
add_action( 'init', 'lorem101_drop_register_taxonomy' );

/**
 * Pole daty startu na formularzu DODAWANIA dropu.
 */
function lorem101_drop_add_form_field() {
	?>
	<div class="form-field">
		<label for="lorem101_drop_start"><?php esc_html_e( 'Start dropu', 'lorem101-drop-manager' ); ?></label>
		<input type="datetime-local" name="lorem101_drop_start" id="lorem101_drop_start" value="">
		<p><?php esc_html_e( 'Data i godzina premiery. Produkty z tego dropu pozostaną w sprzedaży do wyczerpania zapasów.', 'lorem101-drop-manager' ); ?></p>
	</div>
	<?php
}
add_action( LOREM101_DROP_TAXONOMY . '_add_form_fields', 'lorem101_drop_add_form_field' );

/**
 * Pole daty startu na formularzu EDYCJI dropu.
 *
 * WordPress używa dwóch osobnych hooków i dwóch układów HTML (div vs tabela),
 * dlatego to nie jest ta sama funkcja co wyżej.
 */
function lorem101_drop_edit_form_field( $term ) {
	$start = get_term_meta( $term->term_id, LOREM101_DROP_META_START, true );
	?>
	<tr class="form-field">
		<th scope="row">
			<label for="lorem101_drop_start"><?php esc_html_e( 'Start dropu', 'lorem101-drop-manager' ); ?></label>
		</th>
		<td>
			<input type="datetime-local" name="lorem101_drop_start" id="lorem101_drop_start" value="<?php echo esc_attr( $start ); ?>">
			<p class="description"><?php esc_html_e( 'Data i godzina premiery. Produkty z tego dropu pozostaną w sprzedaży do wyczerpania zapasów.', 'lorem101-drop-manager' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( LOREM101_DROP_TAXONOMY . '_edit_form_fields', 'lorem101_drop_edit_form_field' );

/**
 * Zapis daty startu.
 *
 * WordPress sprawdza nonce i uprawnienia dla formularzy taksonomii sam,
 * zanim odpali te hooki - dlatego nie powtarzamy tego tutaj (inaczej niż
 * przy meta boksach, gdzie weryfikacja jest po naszej stronie).
 */
function lorem101_drop_save_term_meta( $term_id ) {
	if ( ! isset( $_POST['lorem101_drop_start'] ) ) {
		return;
	}

	$value = sanitize_text_field( wp_unslash( $_POST['lorem101_drop_start'] ) );

	if ( '' === $value ) {
		delete_term_meta( $term_id, LOREM101_DROP_META_START );
		return;
	}

	update_term_meta( $term_id, LOREM101_DROP_META_START, $value );
}
add_action( 'created_' . LOREM101_DROP_TAXONOMY, 'lorem101_drop_save_term_meta' );
add_action( 'edited_' . LOREM101_DROP_TAXONOMY, 'lorem101_drop_save_term_meta' );

/**
 * Ukrycie pola "Kategoria nadrzędna" na ekranie dropów.
 *
 * Taksonomia jest hierarchiczna, bo tylko wtedy WordPress pokazuje przy
 * produkcie listę z checkboxami (a nie pole tagów). Ale same dropy nie
 * tworzą hierarchii - "Spider" nie jest podrzędny wobec niczego - więc
 * pole tylko myli redaktora.
 *
 * Ukrywamy je stylem zamiast wyłączać hierarchię, bo wyłączenie zmieniłoby
 * też sposób przypisywania produktów.
 */
function lorem101_drop_hide_parent_field() {
	$screen = get_current_screen();

	if ( ! $screen || 'edit-' . LOREM101_DROP_TAXONOMY !== $screen->id ) {
		return;
	}

	echo '<style>.term-parent-wrap, .form-field.term-parent-wrap { display: none; }</style>';
}
add_action( 'admin_head', 'lorem101_drop_hide_parent_field' );

/**
 * Kolumna z datą startu na liście dropów.
 */
add_filter( 'manage_edit-' . LOREM101_DROP_TAXONOMY . '_columns', function ( $columns ) {
	$columns['lorem101_start'] = __( 'Start', 'lorem101-drop-manager' );
	return $columns;
} );

add_filter( 'manage_' . LOREM101_DROP_TAXONOMY . '_custom_column', function ( $content, $column, $term_id ) {
	if ( 'lorem101_start' !== $column ) {
		return $content;
	}

	$start = get_term_meta( $term_id, LOREM101_DROP_META_START, true );

	if ( ! $start ) {
		return '—';
	}

	$number = lorem101_drop_get_number( $term_id );
	$label  = $number ? sprintf( 'DROP %03d — ', $number ) : '';

	return esc_html( $label . str_replace( 'T', ' ', $start ) );
}, 10, 3 );
