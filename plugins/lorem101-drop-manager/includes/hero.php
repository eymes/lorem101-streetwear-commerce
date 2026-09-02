<?php
/**
 * Tag dropu w sekcji hero na stronie głównej.
 *
 * Wtyczka udostępnia funkcję, motyw ją wywołuje - dzięki temu logika
 * numeracji i statusu została po stronie wtyczki, a motyw odpowiada
 * wyłącznie za wygląd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dane do wyświetlenia w hero.
 *
 * @return array|null
 *     'number'    int    numer dropu
 *     'name'      string nazwa kolekcji
 *     'status'    string upcoming|live
 *     'start'     int    znacznik czasu startu
 *     'label'     string gotowy tekst dla wariantu "live"
 */
function lorem101_drop_get_hero_data() {
	$drop = lorem101_drop_get_featured();

	if ( ! $drop ) {
		return null;
	}

	$status = lorem101_drop_get_status_by_term( $drop['term_id'] );

	return array(
		'number' => $drop['number'],
		'name'   => $drop['name'],
		'status' => $status,
		'start'  => $drop['start'],
		'label'  => sprintf( 'DROP %03d', $drop['number'] ),
	);
}

/**
 * Renderuje tag dropu (czerwony pasek nad nazwą marki).
 *
 * Przed premierą pokazuje odliczanie, po premierze "available now".
 * Gdy nie ma żadnego dropu z datą, nie wypisuje nic - motyw ma wtedy
 * własny tekst zapasowy.
 */
function lorem101_drop_render_hero_tag() {
	$data = lorem101_drop_get_hero_data();

	if ( ! $data ) {
		return;
	}

	if ( LOREM101_DROP_STATUS_UPCOMING === $data['status'] ) {
		printf(
			'<span class="hero__tag" data-drop-countdown data-drop-start="%1$s">%2$s — <span class="hero__countdown">%3$s</span></span>',
			esc_attr( $data['start'] ),
			esc_html( $data['label'] ),
			esc_html__( 'ładowanie…', 'lorem101-drop-manager' )
		);
		return;
	}

	printf(
		'<span class="hero__tag">%1$s — %2$s</span>',
		esc_html( $data['label'] ),
		esc_html__( 'available now', 'lorem101-drop-manager' )
	);
}
