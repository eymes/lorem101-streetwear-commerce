<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automatyczna konwersja zdjęć do WebP.
 *
 * PNG dla zdjęć produktowych to zbędny narzut - WebP daje wizualnie ten sam
 * wynik przy wyraźnie mniejszym pliku (w tym sklepie: 300+ KB oszczędności
 * na samej stronie produktu, potwierdzone pomiarem Lighthouse).
 *
 * Zamiast każdorazowo konwertować pliki ręcznie, generujemy wersję .webp
 * obok oryginału przy każdym uploadzie i serwujemy ją zamiast PNG/JPG -
 * przezroczyście, bez zmiany czegokolwiek w panelu ani w szablonach.
 */

/**
 * Przy wgraniu zdjęcia WordPress generuje kilka rozmiarów (thumbnail,
 * medium, woocommerce_single itd.) - tworzymy wersję .webp dla każdego z nich.
 *
 * Hook odpala się już PO wygenerowaniu wszystkich rozmiarów, więc mamy
 * pewność, że pliki źródłowe istnieją na dysku.
 */
function lorem101_generate_webp_versions( $metadata, $attachment_id ) {
	if ( ! function_exists( 'imagewebp' ) ) {
		return $metadata; // środowisko bez wsparcia WebP w GD - nic nie robimy
	}

	$upload_dir = wp_get_upload_dir();
	$base_dir   = trailingslashit( $upload_dir['basedir'] );

	// Plik główny (oryginał w pełnym rozmiarze)
	$full_path = get_attached_file( $attachment_id );
	lorem101_convert_to_webp( $full_path );

	// Każdy wygenerowany rozmiar (thumbnail, woocommerce_single itd.)
	if ( ! empty( $metadata['sizes'] ) && ! empty( $metadata['file'] ) ) {
		$dir = trailingslashit( dirname( $base_dir . $metadata['file'] ) );

		foreach ( $metadata['sizes'] as $size ) {
			if ( ! empty( $size['file'] ) ) {
				lorem101_convert_to_webp( $dir . $size['file'] );
			}
		}
	}

	return $metadata;
}
add_filter( 'wp_generate_attachment_metadata', 'lorem101_generate_webp_versions', 10, 2 );

/**
 * Konwertuje pojedynczy plik PNG/JPG do WebP, jeśli wersja jeszcze nie istnieje.
 *
 * Jakość 82 to sprawdzony punkt równowagi: różnica wizualna względem 100
 * jest praktycznie niewidoczna gołym okiem, a plik jest zauważalnie lżejszy.
 */
function lorem101_convert_to_webp( $source_path ) {
	if ( ! $source_path || ! file_exists( $source_path ) ) {
		return;
	}

	$webp_path = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $source_path );

	if ( $webp_path === $source_path ) {
		return; // rozszerzenie inne niż png/jpg - nic do zrobienia
	}

	if ( file_exists( $webp_path ) ) {
		return; // wersja WebP już istnieje - nie nadpisujemy przy każdej edycji
	}

	$extension = strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) );
	$image     = 'png' === $extension ? @imagecreatefrompng( $source_path ) : @imagecreatefromjpeg( $source_path );

	if ( ! $image ) {
		return;
	}

	// PNG bywa z przezroczystością - bez tego traciłaby ją przy konwersji
	imagepalettetotruecolor( $image );
	imagealphablending( $image, true );
	imagesavealpha( $image, true );

	imagewebp( $image, $webp_path, 82 );
	imagedestroy( $image );
}

/**
 * Podmiana adresu na wersję .webp przy wyświetlaniu zdjęcia - jeśli istnieje.
 *
 * Działa na poziomie atrybutów <img>, więc obejmuje zarówno wp_get_attachment_image()
 * (używane w naszych szablonach), jak i domyślne wywołania WooCommerce.
 */
function lorem101_use_webp_if_available( $attr, $attachment, $size ) {
	if ( empty( $attr['src'] ) ) {
		return $attr;
	}

	$webp_src = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $attr['src'] );

	if ( $webp_src === $attr['src'] ) {
		return $attr; // nie png/jpg
	}

	// Sprawdzamy plik na dysku, nie tylko URL - bez tego podmienilibyśmy
	// adres na plik, który mógł nigdy nie powstać (np. stare zdjęcia
	// wgrane przed włączeniem tej funkcji, dla których trzeba odpalić
	// regenerację hurtową - patrz WP-CLI niżej)
	$upload_dir = wp_get_upload_dir();
	$webp_path  = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $webp_src );

	if ( ! file_exists( $webp_path ) ) {
		return $attr;
	}

	$attr['src'] = $webp_src;

	if ( ! empty( $attr['srcset'] ) ) {
		$attr['srcset'] = preg_replace( '/\.(png|jpe?g)(\s+\d+w)/i', '.webp$2', $attr['srcset'] );
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'lorem101_use_webp_if_available', 20, 3 );

/**
 * Komenda WP-CLI do jednorazowej konwersji zdjęć wgranych PRZED włączeniem
 * tej funkcji (hook wp_generate_attachment_metadata działa tylko przy
 * nowych uploadach - istniejące produkty potrzebują konwersji wstecznej).
 *
 * Użycie: wp lorem101 convert-webp
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'lorem101 convert-webp', function () {
		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'post_mime_type' => array( 'image/png', 'image/jpeg' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$count = 0;

		foreach ( $attachments as $id ) {
			$metadata = wp_get_attachment_metadata( $id );

			if ( $metadata ) {
				lorem101_generate_webp_versions( $metadata, $id );
				$count++;
			}
		}

		WP_CLI::success( sprintf( 'Przetworzono %d zdjęć.', $count ) );
	} );
}
