<?php
/**
 * Einzelne Seiten neu aus den Theme-Patterns erzeugen, ohne sonst etwas anzufassen.
 * Gedacht für Produktion, wo setup-content.php nicht laufen soll.
 *
 * Lokal:      ddev wp eval-file scripts/render-pages.php start musik
 * Produktion: Datei hochladen, dann: wp eval-file render-pages.php start
 *
 * Seiten-Slug → Pattern: start → page-start, musik → page-musik, usw.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'tb_img_id' ) ) {
	WP_CLI::error( 'Theme thierrybill ist nicht aktiv.' );
}
$slugs = $args ?? [];
if ( ! $slugs ) {
	WP_CLI::error( 'Bitte Seiten angeben, z. B.: wp eval-file render-pages.php start' );
}

// Patterns enthalten SVG (Pfeile); ohne diese Zeile entfernt der Inhaltsfilter sie beim Speichern.
kses_remove_filters();
wp_get_theme()->delete_pattern_cache();

foreach ( $slugs as $slug ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$file = get_theme_file_path( "patterns/page-$slug.php" );
	if ( ! $page || ! is_file( $file ) ) {
		WP_CLI::warning( "Übersprungen: $slug (Seite oder Pattern fehlt)" );
		continue;
	}
	ob_start();
	include $file;
	$content = ob_get_clean();
	// Asset-URLs relativ speichern, wie im Setup-Skript.
	$host    = preg_quote( wp_parse_url( home_url(), PHP_URL_HOST ), '#' );
	$content = trim( preg_replace( '#https?://' . $host . '(/wp-content/)#', '$1', $content ) );

	$result = wp_update_post( wp_slash( [ 'ID' => $page->ID, 'post_content' => $content ] ), true );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( $result->get_error_message() );
	}
	WP_CLI::log( sprintf( 'Aktualisiert: %s (#%d)', get_the_title( $page->ID ), $page->ID ) );
}
WP_CLI::success( 'Fertig.' );
