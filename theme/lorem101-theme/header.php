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

<header class="site-header">
	<div class="site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php bloginfo( 'name' ); ?>
		</a>

		<nav class="site-header__nav" aria-label="<?php esc_attr_e( 'Menu główne', 'lorem101-theme' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'site-header__menu',
				'fallback_cb'    => false,
			) );
			?>
		</nav>

		<div class="site-header__actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="site-header__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main class="site-main">
