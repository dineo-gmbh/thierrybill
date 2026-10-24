<?php
/**
 * Fügt dem Kontaktformular das Turnstile-Feld hinzu (idempotent), ohne andere Inhalte zu ändern.
 * Lokal: ddev wp eval-file scripts/turnstile-forms.php · Produktion: wp eval-file turnstile-forms.php
 *
 * Die Schlüssel gehören nicht ins Git, sie werden direkt gesetzt:
 *   wp eval 'WPCF7::update_option( "turnstile", [ "SITE_KEY" => "SECRET_KEY" ] );'
 */

defined( 'ABSPATH' ) || exit;

foreach ( get_posts( [ 'post_type' => 'wpcf7_contact_form', 'numberposts' => -1, 'post_status' => 'any' ] ) as $post ) {
	$form = WPCF7_ContactForm::get_instance( $post->ID );
	$body = $form->prop( 'form' );
	if ( str_contains( $body, '[turnstile' ) ) {
		// Ältere Fassung ohne Aktion nachrüsten (Pflicht für die Prüfung im MU-Plugin).
		if ( ! str_contains( $body, 'action:kontakt' ) ) {
			$form->set_properties( [ 'form' => str_replace( '[turnstile theme:light language:de]', '[turnstile theme:light language:de action:kontakt]', $body ) ] );
			$form->save();
			WP_CLI::log( "Aktion ergänzt: {$post->post_title}" );
		} else {
			WP_CLI::log( "Bereits vorhanden: {$post->post_title}" );
		}
		continue;
	}
	$new = preg_replace( '#(\[submit )#', '<div class="tb-turnstile">[turnstile theme:light language:de action:kontakt]</div>' . "\n\n$1", $body, 1, $count );
	if ( ! $count ) {
		WP_CLI::warning( "Kein Senden-Knopf gefunden: {$post->post_title}" );
		continue;
	}
	$form->set_properties( [ 'form' => $new ] );
	$form->save();
	WP_CLI::log( "Turnstile ergänzt: {$post->post_title}" );
}
WP_CLI::success( 'Formulare geprüft.' );
