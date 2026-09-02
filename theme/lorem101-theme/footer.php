<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>

<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__row">
			<p class="site-footer__copy">
				&copy; <span id="current-year"><?php echo esc_html( date_i18n( 'Y' ) ); ?></span>
				<?php bloginfo( 'name' ); ?>
			</p>

			<?php
			// rel="noopener" jest istotne przy target="_blank": bez niego otwarta
			// strona dostaje przez window.opener dostęp do naszej i może ją
			// podmienić. noreferrer dodatkowo nie przekazuje adresu źródłowego.
			?>
			<a
				class="site-footer__social"
				href="https://www.instagram.com/"
				target="_blank"
				rel="noopener noreferrer"
				aria-label="<?php esc_attr_e( 'Instagram — otwiera się w nowej karcie', 'lorem101-theme' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<rect x="2" y="2" width="20" height="20" rx="5"/>
					<circle cx="12" cy="12" r="4"/>
					<circle cx="17.5" cy="6.5" r="1.2" fill="currentColor" stroke="none"/>
				</svg>
			</a>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
