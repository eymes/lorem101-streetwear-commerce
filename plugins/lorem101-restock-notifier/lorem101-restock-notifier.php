<?php
/**
 * Plugin Name: LOREM101 Restock Notifier
 * Plugin URI: https://github.com/twoj-user/lorem101-streetwear-commerce
 * Description: Zbiera zgłoszenia „powiadom mnie o dostępności" dla wyprzedanych wariantów i wysyła maile, gdy magazyn zostanie uzupełniony.
 * Version: 1.0.0
 * Author: Twoje Imię
 * Text Domain: lorem101-restock
 * Requires Plugins: woocommerce
 *
 * ---
 *
 * Dlaczego osobna wtyczka: to funkcja sklepu, nie wygląd. Ma przeżyć zmianę
 * motywu razem z zebranymi adresami.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOREM101_RESTOCK_VERSION', '1.0.0' );
define( 'LOREM101_RESTOCK_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Nazwa tabeli z prefiksem bazy.
 *
 * Prefiks bywa różny (wp_, wordpress_, cokolwiek), więc nigdy nie wpisujemy
 * nazwy tabeli na sztywno.
 */
function lorem101_restock_table() {
	global $wpdb;

	return $wpdb->prefix . 'lorem101_restock_requests';
}

/**
 * Utworzenie tabeli przy aktywacji wtyczki.
 *
 * Zgłoszenia trzymamy we własnej tabeli, a nie w post meta, bo:
 *   - przy setkach zapisów meta robi się nieporęczne (trudno liczyć i filtrować),
 *   - potrzebujemy warunku unikalności (jeden adres = jedno zgłoszenie na wariant),
 *   - chcemy indeksów po wariancie i statusie wysyłki.
 *
 * dbDelta porównuje istniejącą strukturę z podaną i dopisuje różnice, więc
 * ta sama funkcja obsługuje instalację i aktualizacje schematu. Jest przy tym
 * wybredna co do formatowania: dwie spacje po PRIMARY KEY, typy małymi literami,
 * każde pole w osobnej linii.
 */
function lorem101_restock_install() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = lorem101_restock_table();
	$collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		product_id bigint(20) unsigned NOT NULL,
		variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
		email varchar(190) NOT NULL,
		created_at datetime NOT NULL,
		notified_at datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY variant_email (variation_id, email),
		KEY product_id (product_id),
		KEY notified_at (notified_at)
	) {$collate};";

	dbDelta( $sql );

	update_option( 'lorem101_restock_db_version', LOREM101_RESTOCK_VERSION );
}
register_activation_hook( __FILE__, 'lorem101_restock_install' );

/**
 * Zapis zgłoszenia.
 *
 * @return true|WP_Error
 */
function lorem101_restock_add_request( $product_id, $variation_id, $email ) {
	global $wpdb;

	$email = sanitize_email( $email );

	if ( ! is_email( $email ) ) {
		return new WP_Error(
			'lorem101_invalid_email',
			__( 'Podaj poprawny adres e-mail.', 'lorem101-restock' )
		);
	}

	$table = lorem101_restock_table();

	// $wpdb->insert sam przygotowuje zapytanie na podstawie formatów w ostatnim
	// argumencie - nie sklejamy SQL-a ręcznie, więc nie ma ryzyka wstrzyknięcia.
	$inserted = $wpdb->insert(
		$table,
		array(
			'product_id'   => absint( $product_id ),
			'variation_id' => absint( $variation_id ),
			'email'        => $email,
			'created_at'   => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s' )
	);

	if ( false === $inserted ) {
		// Najczęstsza przyczyna: naruszenie UNIQUE KEY, czyli ten adres
		// jest już zapisany na ten wariant. Traktujemy to jak sukces -
		// klient nie musi wiedzieć, że zgłaszał się dwa razy.
		if ( ! empty( $wpdb->last_error ) && false !== stripos( $wpdb->last_error, 'duplicate' ) ) {
			return true;
		}

		return new WP_Error(
			'lorem101_db_error',
			__( 'Nie udało się zapisać zgłoszenia. Spróbuj ponownie.', 'lorem101-restock' )
		);
	}

	return true;
}

/**
 * Liczba oczekujących na dany wariant.
 */
function lorem101_restock_count_for_variation( $variation_id ) {
	global $wpdb;

	$table = lorem101_restock_table();

	// prepare() zastępuje %d bezpiecznie zescapowaną liczbą - to standardowy
	// sposób obrony przed SQL injection przy własnych zapytaniach
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE variation_id = %d AND notified_at IS NULL",
			absint( $variation_id )
		)
	);
}

/**
 * Zgłoszenia oczekujące na powiadomienie dla danego wariantu.
 */
function lorem101_restock_get_pending( $variation_id ) {
	global $wpdb;

	$table = lorem101_restock_table();

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, email FROM {$table} WHERE variation_id = %d AND notified_at IS NULL",
			absint( $variation_id )
		)
	);
}

require_once LOREM101_RESTOCK_DIR . 'includes/frontend.php';
require_once LOREM101_RESTOCK_DIR . 'includes/rest.php';
require_once LOREM101_RESTOCK_DIR . 'includes/notifier.php';
require_once LOREM101_RESTOCK_DIR . 'includes/admin.php';
