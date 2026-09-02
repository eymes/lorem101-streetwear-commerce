<?php
/**
 * Ekran wejściowy pokazywany raz na sesję.
 *
 * Uwaga SEO: logo jest tu <div>, a nie <h1>. Właściwy <h1> strony to
 * nagłówek w sekcji hero - dwa <h1> na jednej stronie rozmywają sygnał
 * dla wyszukiwarek o tym, co jest głównym tematem strony. Wygląd bez zmian,
 * bo stylowanie opiera się na klasie, nie na typie znacznika.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="entrance-gate" id="entrance-gate" aria-hidden="false">
	<?php
	// Warstwa z unoszącym się pyłem. aria-hidden, bo to czysta dekoracja -
	// czytnik ekranu nie ma o niej mówić.
	?>
	<canvas class="dust-canvas" data-dust aria-hidden="true"></canvas>

	<div class="entrance-gate__content">
		<div class="entrance-gate__logo"><?php bloginfo( 'name' ); ?></div>
		<p class="entrance-gate__subtitle"><?php esc_html_e( 'Wejdź do naszego świata', 'lorem101-theme' ); ?></p>
		<button type="button" class="entrance-gate__button" id="entrance-gate-button">
			<?php esc_html_e( 'Enter', 'lorem101-theme' ); ?>
		</button>
	</div>

	<div class="entrance-gate__loading" id="entrance-gate-loading" aria-hidden="true">
		<span><?php esc_html_e( 'Loading...', 'lorem101-theme' ); ?></span>
	</div>
</div>
