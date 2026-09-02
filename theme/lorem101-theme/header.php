<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( is_front_page() ) : ?>
	<?php get_template_part( 'template-parts/entrance-gate' ); ?>
<?php endif; ?>

<header class="site-header">
	<div class="site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php bloginfo( 'name' ); ?>
		</a>

		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<?php $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>

			<a class="site-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Koszyk', 'lorem101-theme' ); ?>">
				<?php
				// Ikona jako SVG w kodzie zamiast biblioteki ikon - dwie ikony
				// nie uzasadniają pobierania całego zestawu. currentColor
				// sprawia, że dziedziczy kolor tekstu.
				?>
				<svg class="site-header__cart-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
					<path d="M3 6h18"/>
					<path d="M16 10a4 4 0 0 1-8 0"/>
				</svg>

				<?php if ( $cart_count > 0 ) : ?>
					<span class="site-header__cart-count"><?php echo esc_html( $cart_count ); ?></span>
				<?php endif; ?>
			</a>
		<?php endif; ?>
	</div>
</header>

<main class="site-main">
