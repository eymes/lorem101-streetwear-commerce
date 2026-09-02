<?php
/**
 * Logika dropów: numeracja, status, dostępność produktów.
 *
 * Status NIE jest nigdzie zapisywany - liczymy go z daty przy każdym
 * wywołaniu. Dzięki temu nie potrzebujemy zadania cyklicznego (WP-Cron),
 * które o wyznaczonej godzinie "przełącza" drop. WP-Cron w WordPressie
 * odpala się przy odwiedzinach strony, nie o dokładnej godzinie, więc
 * na sklepie bez ruchu premiera mogłaby się opóźnić.
 *
 * Drop ma TYLKO datę startu - brak daty końca jest celowy. Produkty
 * zostają w sprzedaży aż do wyczerpania zapasów, a tym zarządza już
 * sam WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LOREM101_DROP_STATUS_NONE     = 'none';     // produkt bez przypisanego dropu
const LOREM101_DROP_STATUS_UPCOMING = 'upcoming'; // przed premierą
const LOREM101_DROP_STATUS_LIVE     = 'live';     // po premierze, w sprzedaży

/**
 * Zamienia wartość z pola datetime-local na znacznik czasu.
 *
 * Pole zwraca czas lokalny bez strefy ("2026-08-20T17:00"). Interpretujemy
 * go w strefie ustawionej w WordPressie, żeby "17:00" oznaczało 17:00 dla
 * redaktora, a nie UTC.
 */
function lorem101_drop_parse_datetime( $value ) {
	if ( empty( $value ) ) {
		return 0;
	}

	try {
		$date = new DateTimeImmutable( $value, wp_timezone() );
	} catch ( Exception $e ) {
		return 0;
	}

	return $date->getTimestamp();
}

/**
 * Wszystkie dropy z ustawioną datą startu, posortowane chronologicznie.
 *
 * Wynik cachujemy w zmiennej statycznej, bo przy renderowaniu strony
 * pytamy o to wielokrotnie (hero, każdy produkt na listingu), a dane
 * w obrębie jednego żądania się nie zmieniają.
 *
 * @return array lista: [ 'term_id' => int, 'start' => int, 'number' => int ]
 */
function lorem101_drop_get_all() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$terms = get_terms( array(
		'taxonomy'   => LOREM101_DROP_TAXONOMY,
		'hide_empty' => false,
	) );

	$drops = array();

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$start = lorem101_drop_parse_datetime(
				get_term_meta( $term->term_id, LOREM101_DROP_META_START, true )
			);

			if ( ! $start ) {
				continue; // drop bez daty nie bierze udziału w numeracji
			}

			$drops[] = array(
				'term_id' => (int) $term->term_id,
				'name'    => $term->name,
				'start'   => $start,
			);
		}
	}

	usort( $drops, function ( $a, $b ) {
		return $a['start'] <=> $b['start'];
	} );

	// Numeracja wynika z kolejności chronologicznej - najwcześniejszy drop
	// dostaje numer 1. Nie przechowujemy jej w bazie, żeby nie mogła się
	// rozjechać z datami.
	foreach ( $drops as $index => $drop ) {
		$drops[ $index ]['number'] = $index + 1;
	}

	$cache = $drops;

	return $cache;
}

/**
 * Dane pojedynczego dropu po ID terminu.
 *
 * @return array|null
 */
function lorem101_drop_get( $term_id ) {
	foreach ( lorem101_drop_get_all() as $drop ) {
		if ( $drop['term_id'] === (int) $term_id ) {
			return $drop;
		}
	}

	return null;
}

/**
 * Numer dropu (1, 2, 3...) albo 0, gdy drop nie ma daty.
 */
function lorem101_drop_get_number( $term_id ) {
	$drop = lorem101_drop_get( $term_id );

	return $drop ? $drop['number'] : 0;
}

/**
 * Drop przypisany do produktu.
 *
 * Zakładamy jeden drop na produkt - gdyby ktoś zaznaczył kilka,
 * bierzemy pierwszy z ustawioną datą.
 *
 * @return array|null
 */
function lorem101_drop_get_for_product( $product_id ) {
	$terms = wp_get_post_terms( $product_id, LOREM101_DROP_TAXONOMY, array( 'fields' => 'ids' ) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term_id ) {
		$drop = lorem101_drop_get( $term_id );

		if ( $drop ) {
			return $drop;
		}
	}

	return null;
}

/**
 * Status dropu na podstawie daty startu.
 */
function lorem101_drop_get_status_by_term( $term_id ) {
	$drop = lorem101_drop_get( $term_id );

	if ( ! $drop ) {
		return LOREM101_DROP_STATUS_NONE;
	}

	// UWAGA: porównujemy time(), a NIE current_time('timestamp').
	// current_time zwraca znacznik przesunięty o offset strefy (dla Polski
	// +2h), a nasze daty startu to prawdziwe znaczniki UTC z DateTimeImmutable.
	// Mieszanie tych dwóch powodowało, że drop zaplanowany za godzinę
	// wyglądał na już rozpoczęty.
	return time() < $drop['start']
		? LOREM101_DROP_STATUS_UPCOMING
		: LOREM101_DROP_STATUS_LIVE;
}

/**
 * Status dropu, do którego należy produkt.
 */
function lorem101_drop_get_product_status( $product_id ) {
	$drop = lorem101_drop_get_for_product( $product_id );

	if ( ! $drop ) {
		return LOREM101_DROP_STATUS_NONE;
	}

	return lorem101_drop_get_status_by_term( $drop['term_id'] );
}

/**
 * Czy produkt da się teraz kupić z punktu widzenia dropu.
 *
 * Produkty bez przypisanego dropu zachowują się normalnie - wtyczka nie może
 * blokować całego sklepu tylko dlatego, że jest zainstalowana.
 */
function lorem101_drop_is_product_available( $product_id ) {
	return LOREM101_DROP_STATUS_UPCOMING !== lorem101_drop_get_product_status( $product_id );
}

/**
 * Drop do pokazania w sekcji hero na stronie głównej.
 *
 * Kolejność wyboru:
 *   1. najbliższy NADCHODZĄCY drop (budowanie napięcia przed premierą)
 *   2. ostatni, który już wystartował ("available now")
 *   3. null, gdy nie ma żadnego dropu z datą
 *
 * @return array|null
 */
function lorem101_drop_get_featured() {
	$drops = lorem101_drop_get_all();

	if ( empty( $drops ) ) {
		return null;
	}

	$now      = time(); // patrz uwaga przy lorem101_drop_get_status_by_term()
	$upcoming = null;
	$latest   = null;

	foreach ( $drops as $drop ) {
		if ( $drop['start'] > $now ) {
			// lista jest posortowana rosnąco, więc pierwszy trafiony
			// nadchodzący drop jest jednocześnie najbliższy
			if ( null === $upcoming ) {
				$upcoming = $drop;
			}
			continue;
		}

		$latest = $drop; // nadpisujemy, żeby zostać z najpóźniejszym
	}

	return $upcoming ? $upcoming : $latest;
}

/**
 * Najnowszy drop, który już wystartował.
 *
 * To on jest "nowością" w sklepie - jego produkty dostają oznaczenie NEW.
 * Świadomie pomijamy dropy nadchodzące: ich produkty są jeszcze ukryte,
 * więc nie ma czego oznaczać.
 *
 * @return array|null
 */
function lorem101_drop_get_latest_live() {
	$latest = null;
	$now    = time();

	foreach ( lorem101_drop_get_all() as $drop ) {
		if ( $drop['start'] > $now ) {
			continue;
		}

		// lista jest posortowana rosnąco po dacie, więc ostatni pasujący
		// jest jednocześnie najnowszym
		$latest = $drop;
	}

	return $latest;
}

/**
 * Czy produkt należy do najnowszej dostępnej kolekcji.
 */
function lorem101_drop_is_product_new( $product_id ) {
	$latest = lorem101_drop_get_latest_live();

	if ( ! $latest ) {
		return false;
	}

	$drop = lorem101_drop_get_for_product( $product_id );

	return $drop && $drop['term_id'] === $latest['term_id'];
}

/**
 * Liczba sztuk pozostałych w produkcie.
 *
 * Sumujemy stany wariacji, bo magazyn prowadzony jest na ich poziomie
 * (osobno "M / czarny", osobno "L / szary") - produkt nadrzędny nie zna
 * własnego stanu.
 *
 * @return int|null null gdy magazyn nie jest prowadzony
 */
function lorem101_drop_get_stock_left( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product instanceof WC_Product ) {
		return null;
	}

	if ( ! $product instanceof WC_Product_Variable ) {
		return $product->managing_stock() ? (int) $product->get_stock_quantity() : null;
	}

	$total   = 0;
	$tracked = false;

	foreach ( $product->get_children() as $variation_id ) {
		$variation = wc_get_product( $variation_id );

		if ( ! $variation || ! $variation->managing_stock() ) {
			continue;
		}

		$tracked = true;
		$total  += max( 0, (int) $variation->get_stock_quantity() );
	}

	return $tracked ? $total : null;
}
