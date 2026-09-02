<?php
/**
 * Widok oczekujących zgłoszeń w panelu administratora.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Panel przy edycji produktu: ile osób czeka na który wariant.
 *
 * To konkretna informacja biznesowa — „czarny M: 12 osób" mówi wprost,
 * czego dorobić w następnej serii.
 */
function lorem101_restock_register_meta_box() {
	add_meta_box(
		'lorem101-restock-requests',
		__( 'Oczekujący na dostępność', 'lorem101-restock' ),
		'lorem101_restock_render_meta_box',
		'product',
		'side',
		'low'
	);
}
add_action( 'add_meta_boxes', 'lorem101_restock_register_meta_box' );

function lorem101_restock_render_meta_box( $post ) {
	global $wpdb;

	$table = lorem101_restock_table();

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT variation_id, COUNT(*) AS total
			 FROM {$table}
			 WHERE product_id = %d AND notified_at IS NULL
			 GROUP BY variation_id
			 ORDER BY total DESC",
			absint( $post->ID )
		)
	);

	if ( empty( $rows ) ) {
		echo '<p>' . esc_html__( 'Nikt jeszcze nie czeka na ten produkt.', 'lorem101-restock' ) . '</p>';
		return;
	}

	echo '<ul style="margin:0">';

	foreach ( $rows as $row ) {
		$variation = wc_get_product( $row->variation_id );

		$label = __( 'Nieznany wariant', 'lorem101-restock' );

		if ( $variation ) {
			$attributes = array_filter( $variation->get_variation_attributes() );
			$label      = $attributes ? implode( ', ', array_map( 'ucfirst', $attributes ) ) : $variation->get_name();
		}

		printf(
			'<li style="display:flex;justify-content:space-between;gap:1rem;padding:0.3rem 0;border-bottom:1px solid #eee"><span>%1$s</span><strong>%2$d</strong></li>',
			esc_html( $label ),
			(int) $row->total
		);
	}

	echo '</ul>';

	echo '<p class="description" style="margin-top:0.75rem">'
		. esc_html__( 'Maile wyjdą automatycznie po uzupełnieniu stanu magazynowego wariantu.', 'lorem101-restock' )
		. '</p>';
}
