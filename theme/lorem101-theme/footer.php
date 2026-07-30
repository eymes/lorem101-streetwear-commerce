<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="site-footer">
	<div class="site-footer__inner">
		<nav aria-label="<?php esc_attr_e( 'Menu w stopce', 'lorem101-theme' ); ?>">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'site-footer__menu',
				'fallback_cb'    => false,
			) );
			?>
		</nav>
		<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
