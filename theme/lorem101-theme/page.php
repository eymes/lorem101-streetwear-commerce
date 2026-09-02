<?php
/**
 * Szablon pojedynczej strony.
 *
 * Ważne dla WooCommerce: koszyk i checkout to zwykłe strony WordPressa
 * z blokami WooCommerce w treści - NIE przechodzą przez woocommerce.php.
 * Bez tego pliku trafiały do index.php, który nie ogranicza szerokości,
 * przez co formularze rozjeżdżały się na całą szerokość ekranu.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="page-wrapper">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php
		// Uwaga: nagłówek strony na PUSTYM koszyku chowamy w CSS
		// (selektor :has() w woocommerce/_cart.scss), a nie tutaj.
		// Blok koszyka renderuje się po stronie przeglądarki, więc w PHP
		// stan koszyka bywa jeszcze nieaktualny.
		?>
		<h1 class="page-wrapper__title"><?php the_title(); ?></h1>

		<div class="page-wrapper__content">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</div>

<?php get_footer(); ?>
