<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'product-card', $product ); ?>>
	<a href="<?php the_permalink(); ?>" class="product-card__link">
		<div class="product-card__image">
			<?php echo woocommerce_get_product_thumbnail(); ?>
		</div>

		<h2 class="product-card__title"><?php the_title(); ?></h2>

		<span class="product-card__price">
			<?php echo $product->get_price_html(); ?>
		</span>
	</a>

	<div class="product-card__actions">
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</div>
</li>
