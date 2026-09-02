<?php
/**
 * Strona główna sklepu. Siatka pokazuje jeden kafelek na KAŻDY kolor
 * każdego produktu - wybór rozmiaru zostaje na stronie produktu.
 *
 * $priority_order definiuje zarówno kolejność ZAKŁADEK jak i kolejność
 * PRODUKTÓW w siatce - jedno źródło prawdy zamiast dwóch osobnych list,
 * żeby taby i grid nigdy się nie rozjechały.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Nazwę kolekcji bierzemy z aktualnego dropu (ten sam, który pokazuje tag
// nad nazwą marki). Gdy wtyczka jest wyłączona albo nie ma jeszcze żadnego
// dropu z datą, wracamy do wartości zapasowej - motyw musi się wyrenderować
// także bez wtyczki.
$drop_data       = function_exists( 'lorem101_drop_get_hero_data' ) ? lorem101_drop_get_hero_data() : null;
$collection_name = $drop_data ? $drop_data['name'] : __( 'Wkrótce', 'lorem101-theme' );

$priority_order = array( 'hoodies', 't-shirts', 'shorts' );
?>

<section class="hero">
	<div class="hero__content">
		<?php
		// Tag dropu dostarcza wtyczka LOREM101 Drop Manager: numer, status
		// i odliczanie. Gdy wtyczka jest wyłączona albo nie ma jeszcze
		// żadnego dropu z datą, pokazujemy tekst zapasowy - motyw nie może
		// zależeć od wtyczki, żeby się wyrenderować.
		if ( function_exists( 'lorem101_drop_render_hero_tag' ) ) {
			lorem101_drop_render_hero_tag();
		}
		?>
		<h1 class="hero__title"><?php bloginfo( 'name' ); ?></h1>
		<p class="hero__subtitle">
			<?php esc_html_e( 'Najnowsza kolekcja', 'lorem101-theme' ); ?>
			<span class="hero__collection"><?php echo esc_html( $collection_name ); ?></span>
		</p>
	</div>
</section>

<section class="shop-grid" id="shop-grid">
	<?php
	// Zakładki w tej samej kolejności co $priority_order, plus wszystko
	// pozostałe (kategorie spoza listy) na końcu - w kolejności alfabetycznej.
	$all_categories = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	$ordered_terms  = array();

	if ( ! is_wp_error( $all_categories ) ) {
		foreach ( $priority_order as $slug ) {
			foreach ( $all_categories as $term ) {
				if ( $term->slug === $slug ) {
					$ordered_terms[] = $term;
				}
			}
		}
		foreach ( $all_categories as $term ) {
			if ( ! in_array( $term->slug, $priority_order, true ) ) {
				$ordered_terms[] = $term;
			}
		}
	}
	?>

	<?php if ( ! empty( $ordered_terms ) ) : ?>
		<div class="category-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filtruj produkty według kategorii', 'lorem101-theme' ); ?>">
			<button type="button" class="category-tabs__item is-active" data-filter="all" role="tab" aria-selected="true">
				<?php esc_html_e( 'All', 'lorem101-theme' ); ?>
			</button>
			<?php foreach ( $ordered_terms as $category ) : ?>
				<button type="button" class="category-tabs__item" data-filter="<?php echo esc_attr( $category->slug ); ?>" role="tab" aria-selected="false">
					<?php echo esc_html( $category->name ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="shop-grid__scroll">
		<ul class="products">
			<?php
			// Kolejność produktów w siatce ustalamy w PHP, bo filtrowanie
			// kategorii działa przez ukrywanie kafelków w JS - kolejność w DOM
			// jest więc wspólna dla widoku ALL i każdej pojedynczej kategorii.
			//
			// Sortujemy dwupoziomowo:
			//   1. numer dropu malejąco (najnowsza kolekcja na samej górze)
			//   2. kategoria wg $priority_order (bluzy, t-shirty, spodenki)
			$all_products = wc_get_products( array(
				'status' => 'publish',
				'limit'  => -1,
			) );

			$sorted = array();

			foreach ( $all_products as $product ) {
				$drop_number = 0;

				if ( function_exists( 'lorem101_drop_get_for_product' ) ) {
					$drop = lorem101_drop_get_for_product( $product->get_id() );
					$drop_number = $drop ? $drop['number'] : 0;
				}

				// Pozycja kategorii na liście priorytetów; produkty spoza listy
				// lądują na końcu swojej grupy
				$category_rank = count( $priority_order );
				$slugs = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'slugs' ) );

				if ( ! is_wp_error( $slugs ) ) {
					foreach ( $priority_order as $index => $slug ) {
						if ( in_array( $slug, $slugs, true ) ) {
							$category_rank = $index;
							break;
						}
					}
				}

				$sorted[] = array(
					'product'       => $product,
					'drop_number'   => $drop_number,
					'category_rank' => $category_rank,
				);
			}

			usort( $sorted, function ( $a, $b ) {
				if ( $a['drop_number'] !== $b['drop_number'] ) {
					return $b['drop_number'] <=> $a['drop_number']; // malejąco
				}

				return $a['category_rank'] <=> $b['category_rank']; // rosnąco
			} );

			foreach ( $sorted as $item ) {
				foreach ( lorem101_get_color_variant_tiles( $item['product'] ) as $tile ) {
					get_template_part( 'template-parts/product-variant-tile', null, $tile );
				}
			}
			?>
		</ul>
	</div>
</section>

<?php get_footer(); ?>
