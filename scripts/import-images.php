<?php
/**
 * Bilder aus scripts/data/img einmalig in die Mediathek laden (erkannt an der Meta _tb_key).
 * Bereits vorhandene Bilder werden übersprungen, nichts wird überschrieben.
 *
 * - Wird von setup-content.php eingebunden.
 * - Direkt ausgeführt (z. B. auf Produktion): wp eval-file import-images.php
 *   Dafür import-images.php und den Ordner data/img/ nebeneinander hochladen.
 */

defined( 'ABSPATH' ) || exit;

// Schlüssel = Dateiname ohne .jpg => [ Titel, Alt-Text ]
$tb_images = [
	'live-rot'           => [ 'Thierry Bill live, rotes Licht', 'Thierry Bill mit Gitarre auf der Bühne, in rotes Licht getaucht' ],
	'live-blau'          => [ 'Thierry Bill live, blaues Licht', 'Thierry Bill an der E-Gitarre mit Band auf einer Kellerbühne' ],
	'live-pink'          => [ 'Thierry Bill live, pinkes Licht', 'Thierry Bill spielt Gitarre im pinken Scheinwerferlicht' ],
	'portrait'           => [ 'Thierry Bill, Porträt', 'Schwarz-Weiss-Porträt von Thierry Bill im dunklen Mantel' ],
	'portrait-duo'       => [ 'Thierry Bill, Fotoshooting', 'Schwarz-Weiss-Foto: Thierry Bill im Mantel neben einem Freund vor einer Hausfassade' ],
	'tattoo-1'           => [ 'Tattoo von Thierry Bill, Knie', 'Feines Linework-Tattoo von Thierry Bill: organische, verästelte Ornamente über Oberschenkel und Knie' ],
	'portrait-bewegung'  => [ 'Thierry Bill, Porträt an der Brücke', 'Schwarz-Weiss-Porträt von Thierry Bill mit Perlenkette vor einer Brücke, links und rechts verwischte Passanten' ],
	'live-streifen'      => [ 'Thierry Bill live, gestreiftes Hemd', 'Thierry Bill mit Cap und gestreiftem Hemd spielt eine Les-Paul-Gitarre, im Hintergrund ein Keyboarder' ],
	'live-scheune'       => [ 'Thierry Bill live in der Scheune', 'Thierry Bill in Lederjacke und Cap spielt E-Gitarre in einer Scheune' ],
	'band-bruecke'       => [ 'Bandfoto an der Betonbrücke', 'Schwarz-Weiss-Bandfoto: vier Musiker vor einer geschwungenen Betonbrücke, darunter Thierry Bill' ],
	'villa-graffiti'     => [ 'Fotoshooting in der Villa', 'Schwarz-Weiss-Foto: Thierry Bill lachend mit Freundinnen und Freunden in einem leerstehenden Altbau mit Graffiti' ],
];

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

foreach ( $tb_images as $key => [ $title, $alt ] ) {
	if ( tb_img_id( $key ) ) {
		continue;
	}
	$source = __DIR__ . "/data/img/$key.jpg";
	if ( ! is_readable( $source ) ) {
		WP_CLI::warning( "Bild $key: Datei fehlt ($source)" );
		continue;
	}
	$tmp = wp_tempnam( $key );
	copy( $source, $tmp );
	$id = media_handle_sideload( [ 'name' => "thierry-bill-$key.jpg", 'tmp_name' => $tmp ], 0, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( "Bild $key: " . $id->get_error_message() );
	}
	update_post_meta( $id, '_tb_key', $key );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	WP_CLI::log( "Bild geladen: $key (#$id)" );
}
