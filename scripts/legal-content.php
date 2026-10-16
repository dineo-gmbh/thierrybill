<?php
/**
 * Rechtstexte (Impressum, Datenschutz).
 *
 * - Wird von setup-content.php eingebunden (Funktion tb_legal_content()).
 * - Direkt ausgeführt aktualisiert es NUR die beiden Rechtsseiten, alle anderen Inhalte bleiben unberührt.
 *   Lokal:      ddev wp eval-file scripts/legal-content.php
 *   Produktion: Datei hochladen, dann: wp eval-file legal-content.php
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'tb_legal_content' ) ) {

	/**
	 * Blöcke aus einer einfachen Liste: [ 'h', Text ] Überschrift, [ 'p', HTML ] Absatz,
	 * [ 'ul', [ HTML, … ] ] Liste, [ 'meta', HTML ] graue Zeile (Stand).
	 */
	function tb_legal_blocks( array $items ): string {
		$out = [];
		foreach ( $items as [ $type, $content ] ) {
			switch ( $type ) {
				case 'h':
					$out[] = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$content</h2>\n<!-- /wp:heading -->";
					break;
				case 'p':
					$out[] = "<!-- wp:paragraph -->\n<p>$content</p>\n<!-- /wp:paragraph -->";
					break;
				case 'meta':
					$out[] = "<!-- wp:paragraph {\"className\":\"tb-note\"} -->\n<p class=\"tb-note\">$content</p>\n<!-- /wp:paragraph -->";
					break;
				case 'ul':
					$li    = implode( '', array_map( static fn( $i ) => "<!-- wp:list-item -->\n<li>$i</li>\n<!-- /wp:list-item -->", $content ) );
					$out[] = "<!-- wp:list -->\n<ul class=\"wp-block-list\">$li</ul>\n<!-- /wp:list -->";
					break;
			}
		}
		return implode( "\n\n", $out );
	}

	/**
	 * Inhalt einer Rechtsseite ('impressum' oder 'datenschutz').
	 */
	function tb_legal_content( string $key ): string {
		$mail    = '<a href="mailto:tjb1612@gmail.com">tjb1612@gmail.com</a>';
		$address = 'Thierry Bill<br>Zielebachweg 1<br>3428 Wiler<br>Schweiz';
		$edoeb   = '<a href="https://www.edoeb.admin.ch" target="_blank" rel="noopener">EDÖB</a>';

		$texts = [];

		/* ---------- Impressum ---------- */

		$texts['impressum'] = [
			[ 'h', 'Verantwortlich für diese Website' ],
			[ 'p', $address ],
			[ 'p', "E-Mail: $mail" ],
			[ 'h', 'Haftungsausschluss' ],
			[ 'p', 'Die Inhalte dieser Website wurden mit grösstmöglicher Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte wird jedoch keine Gewähr übernommen.' ],
			[ 'h', 'Links auf andere Websites' ],
			[ 'p', 'Diese Website enthält Links zu Websites Dritter, etwa zu Instagram und Facebook. Für deren Inhalte sind ausschliesslich die jeweiligen Betreiber verantwortlich.' ],
			[ 'h', 'Urheberrecht' ],
			[ 'p', 'Texte, Bilder, Tattoo-Designs und Grafiken auf dieser Website sind urheberrechtlich geschützt. Jede Verwendung ausserhalb dieser Website ist nur mit schriftlicher Zustimmung der Rechteinhaber erlaubt.' ],
		];

		/* ---------- Datenschutz ---------- */

		$texts['datenschutz'] = [
			[ 'p', 'Diese Datenschutzerklärung informiert darüber, welche Personendaten beim Besuch von thierrybill.ch bearbeitet werden. Sie richtet sich nach dem Schweizer Datenschutzgesetz (DSG) und, soweit anwendbar, nach der Datenschutz-Grundverordnung der EU (DSGVO).' ],
			[ 'h', 'Verantwortlich' ],
			[ 'p', $address . "<br>E-Mail: $mail" ],
			[ 'h', 'Kurz zusammengefasst' ],
			[ 'ul', [
				'Keine Analyse- oder Tracking-Werkzeuge, keine Werbung.',
				'Keine Cookies für Besucherinnen und Besucher.',
				'Schriften werden vom eigenen Server geladen, nicht von Google.',
				'Daten werden nur bearbeitet, um die Website zu betreiben und Anfragen zu beantworten.',
			] ],
			[ 'h', 'Hosting und Server-Logfiles' ],
			[ 'p', 'Die Website wird bei der Mittwald CM Service GmbH &amp; Co. KG, Königsberger Strasse 4–6, 32339 Espelkamp, Deutschland, betrieben. Beim Aufruf einer Seite speichert der Server automatisch technische Angaben: IP-Adresse, Datum und Uhrzeit, aufgerufene Adresse, zuvor besuchte Seite (Referrer) sowie Browser und Betriebssystem. Diese Daten dienen ausschliesslich dem sicheren und stabilen Betrieb der Website, werden nicht mit anderen Daten zusammengeführt und nach kurzer Zeit gelöscht. Mittwald bearbeitet die Daten als Auftragsbearbeiterin; Deutschland verfügt über ein angemessenes Datenschutzniveau. Rechtsgrundlage nach DSGVO ist das berechtigte Interesse am Betrieb der Website (Art. 6 Abs. 1 lit. f).' ],
			[ 'h', 'Kontaktformular' ],
			[ 'p', 'Wer das Kontaktformular nutzt, übermittelt Name, E-Mail-Adresse, das gewählte Thema, Betreff und Nachricht. Diese Angaben werden ausschliesslich zur Bearbeitung der Anfrage verwendet, per E-Mail zugestellt und zusätzlich auf dem Server der Website gespeichert. Sie werden gelöscht, sobald sie nicht mehr benötigt werden, spätestens nach zwei Jahren, sofern keine gesetzlichen Aufbewahrungspflichten bestehen. Ein unsichtbares Zusatzfeld hält automatisierten Spam ab; dafür werden keine Daten an Dritte übermittelt. Rechtsgrundlage nach DSGVO ist die Anfrage selbst bzw. das berechtigte Interesse an deren Beantwortung (Art. 6 Abs. 1 lit. b und f).' ],
			[ 'h', 'Kontakt per E-Mail' ],
			[ 'p', "Wer direkt an $mail schreibt, übermittelt die E-Mail-Adresse und den Inhalt der Nachricht. Diese Adresse wird bei Gmail geführt, einem Dienst der Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland; dabei können Daten auch an die Google LLC in den USA gelangen. Google ist nach dem Data Privacy Framework zwischen der Schweiz, der EU und den USA zertifiziert. Die Angaben werden nur zur Beantwortung der Anfrage verwendet. Weitere Informationen: <a href=\"https://policies.google.com/privacy\" target=\"_blank\" rel=\"noopener\">Datenschutzerklärung von Google</a>." ],
			[ 'h', 'Cookies' ],
			[ 'p', 'Die Website setzt für Besucherinnen und Besucher keine Cookies. Technisch notwendige Cookies entstehen nur bei der Anmeldung im Redaktionsbereich.' ],
			[ 'h', 'Links zu sozialen Netzwerken' ],
			[ 'p', 'Die Website verlinkt auf Instagram und Facebook. Es handelt sich um einfache Links: Daten werden erst übermittelt, wenn ein Link angeklickt wird. Ab dann gelten die Datenschutzbestimmungen des jeweiligen Anbieters.' ],
			[ 'h', 'Sicherheit' ],
			[ 'p', 'Die Verbindung zur Website ist verschlüsselt (HTTPS). Zum Schutz des Redaktionsbereichs werden bei fehlgeschlagenen Anmeldeversuchen die IP-Adresse und der Zeitpunkt vorübergehend gespeichert.' ],
			[ 'h', 'Ihre Rechte' ],
			[ 'p', "Sie können jederzeit Auskunft über Ihre gespeicherten Daten verlangen sowie deren Berichtigung, Löschung oder Herausgabe fordern und der Bearbeitung widersprechen. Eine E-Mail an $mail genügt. Sie haben zudem das Recht, sich beim Eidgenössischen Datenschutz- und Öffentlichkeitsbeauftragten ($edoeb) oder, in der EU, bei der zuständigen Aufsichtsbehörde zu beschweren." ],
			[ 'h', 'Änderungen' ],
			[ 'p', 'Diese Datenschutzerklärung kann angepasst werden, wenn sich die Website oder die rechtlichen Grundlagen ändern. Es gilt die jeweils hier veröffentlichte Fassung.' ],
			[ 'meta', 'Stand: Oktober 2026' ],
		];

		return tb_legal_blocks( $texts[ $key ] );
	}
}

/* ---------- Direkt ausgeführt: nur die Rechtsseiten aktualisieren ---------- */

if ( defined( 'WP_CLI' ) && WP_CLI && ! defined( 'TB_SETUP_CONTENT' ) ) {
	foreach ( [ 'impressum', 'datenschutz' ] as $key ) {
		$page = get_page_by_path( $key, OBJECT, 'page' );
		if ( ! $page ) {
			WP_CLI::error( "Seite «$key» nicht gefunden." );
		}
		$result = wp_update_post( wp_slash( [ 'ID' => $page->ID, 'post_content' => tb_legal_content( $key ) ] ), true );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::log( sprintf( 'Aktualisiert: %s (#%d)', get_the_title( $page->ID ), $page->ID ) );
	}
	WP_CLI::success( 'Rechtstexte aktualisiert.' );
}
