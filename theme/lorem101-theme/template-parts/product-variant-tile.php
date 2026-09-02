<?php
/**
 * Pojedynczy kafelek reprezentujący jeden kolor produktu zmiennego.
 * Renderowany przez get_template_part( 'template-parts/product-variant-tile', null, $tile )
 * - dane przychodzą przez $args (WP 5.5+).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes = array_merge( array( 'product-card' ), $args['category_classes'] ?? array() );
?>
<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<a href="<?php echo esc_url( $args['url'] ); ?>" class="product-card__link">
		<div class="product-card__image">
			<?php if ( ! empty( $args['is_new'] ) ) : ?>
				<span class="product-card__badge"><?php esc_html_e( 'New', 'lorem101-theme' ); ?></span>
			<?php endif; ?>
			<?php echo $args['image_html']; ?>
		</div>
		<h2 class="product-card__title"><?php echo esc_html( $args['title'] ); ?></h2>
		<span class="product-card__price"><?php echo wp_kses_post( $args['price_html'] ); ?></span>
	</a>
</li>
