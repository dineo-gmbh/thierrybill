<?php
/**
 * Plugin Name: Thierry Bill – SEO
 * Description: Strukturierte Daten (Person), 301-Weiterleitungen der alten URLs, schlanker <head>, Formular-Skripte nur auf der Kontaktseite, WebP-Bilder.
 *
 * Bewusst als MU-Plugin und nicht im Theme: bleibt bei einem Theme-Wechsel aktiv.
 * Titel, Beschreibungen und Social-Bilder pro Seite setzt scripts/setup-content.php
 * (The SEO Framework, Post-Meta _genesis_title / _genesis_description).
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Strukturierte Daten: Person ---------- */

/**
 * The SEO Framework gibt Thierry Bill als Person aus (Name, URL, sameAs Instagram/Facebook),
 * eingebettet als publisher der WebSite. Hier kommen Beruf, Ausbildung, Band, Fachgebiete
 * und Porträt dazu – nur belegte Angaben.
 *
 * Ausserdem verweist TSF auf der Startseite bei WebPage.about fest auf eine «Organization»,
 * auch wenn «Person» eingestellt ist. Diese wird durch einen Verweis auf die Person ersetzt,
 * damit Google nur eine Entität «Thierry Bill» sieht.
 */
add_filter( 'the_seo_framework_schema_graph_data', static function ( array $graph ): array {
	$person_id = home_url( '/#/schema/Person' );

	$walk = static function ( array $node ) use ( &$walk, $person_id ): array {
		$type = $node['@type'] ?? '';
		$id   = (string) ( $node['@id'] ?? '' );

		if ( $type === 'Organization' && str_ends_with( $id, '#/schema/Organization' ) ) {
			return [ '@id' => $person_id ];
		}
		if ( $type === 'Person' && $id === $person_id ) {
			$node += tb_seo_person_details();
		}
		foreach ( $node as $key => $value ) {
			if ( is_array( $value ) ) {
				$node[ $key ] = $walk( $value );
			}
		}
		return $node;
	};

	return $walk( $graph );
} );

/**
 * Zusatzangaben zur Person (schema.org).
 */
function tb_seo_person_details(): array {
	$details = [
		'givenName'     => 'Thierry',
		'familyName'    => 'Bill',
		'description'   => 'Musiker (Jazz-Gitarrist), Tattoo-Artist und Grafikdesigner.',
		'jobTitle'      => [ 'Gitarrist', 'Tattoo-Artist', 'Grafikdesigner' ],
		'hasOccupation' => [
			[ '@type' => 'Occupation', 'name' => 'Musiker' ],
			[ '@type' => 'Occupation', 'name' => 'Tattoo-Artist' ],
			[ '@type' => 'Occupation', 'name' => 'Grafikdesigner', 'educationRequirements' => 'Mediamatiker EFZ' ],
		],
		// HSLU: laufendes Studium, daher affiliation statt alumniOf.
		'affiliation'   => [ '@type' => 'CollegeOrUniversity', 'name' => 'Hochschule Luzern – Musik (HSLU)' ],
		'alumniOf'      => [ '@type' => 'EducationalOrganization', 'name' => 'Swiss Jazz School Bern' ],
		'memberOf'      => [ '@type' => 'MusicGroup', 'name' => 'Cold Touch', 'location' => 'Biel' ],
		'knowsAbout'    => [ 'Jazz-Gitarre', 'Songwriting', 'Studio-Gitarre', 'Tattoo-Design', 'Linework-Tattoos', 'Grafikdesign', 'Webdesign', 'E-Commerce', 'Fotografie', 'Layout' ],
	];
	$portrait = tb_seo_image_url( 'portrait' );
	if ( $portrait ) {
		$details['image'] = $portrait;
	}
	return $details;
}

/**
 * URL des JPEG-Originals eines Theme-Bildes (Schluessel wie im Setup-Skript), leer, wenn es fehlt.
 */
function tb_seo_image_url( string $key ): string {
	if ( ! function_exists( 'tb_img_id' ) ) {
		return '';
	}
	$id = tb_img_id( $key );
	return $id ? (string) wp_get_original_image_url( $id ) : '';
}

/* ---------- 301-Weiterleitungen der alten Seite (Wezen/Elementor) ---------- */

/**
 * Alte URLs von thierrybill.ch auf die neuen Seiten umleiten, damit Rankings und Links
 * erhalten bleiben. Greift nur, wenn WordPress die URL selbst nicht kennt (404).
 * Die alten Blogbeiträge waren Theme-Demoinhalte und gehen auf die Startseite.
 */
add_action( 'template_redirect', static function (): void {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	$map  = [
		'about-me'                                       => '/',
		'musician'                                       => '/musik/',
		'tattoo-artist'                                  => '/tattoo/',
		'graphic-designer'                               => '/grafik/',
		'contact'                                        => '/kontakt/',
		'hello-world'                                    => '/',
		'best-practices-for-animated-progress-indicators' => '/',
		'designing-the-perfect-feature-comparison-table' => '/',
		'an-overview-of-e-commerce-platforms'            => '/',
		'why-i-switched-to-sketch-for-ui-design'         => '/',
		'creative-and-innovative-navigation-designs'     => '/',
		'6-easy-steps-to-better-icon-design'             => '/',
		'how-to-make-a-wordpress-plugin-extensible'      => '/',
	];
	if ( isset( $map[ $path ] ) ) {
		wp_safe_redirect( home_url( $map[ $path ] ), 301, 'Thierry Bill' );
		exit;
	}
} );

/* ---------- Schlanker <head> ---------- */

// Keine Feeds (keine Beiträge), kein oEmbed-Discovery, kein REST-Link im <head>.
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );

// Feeds auf die Startseite leiten statt leere Feeds auszuliefern.
add_action( 'template_redirect', static function (): void {
	if ( is_feed() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );

/* ---------- Formular-Assets nur auf der Kontaktseite ---------- */

/**
 * Contact Form 7 und der Honeypot laden ihr JS/CSS sonst auf jeder Seite.
 */
add_action( 'wp_enqueue_scripts', static function (): void {
	if ( is_page( 'kontakt' ) ) {
		return;
	}
	foreach ( [ 'contact-form-7', 'swv', 'wpcf7-recaptcha' ] as $handle ) {
		wp_dequeue_script( $handle );
	}
	foreach ( [ 'contact-form-7', 'cf7apps-honeypot-frontend' ] as $handle ) {
		wp_dequeue_style( $handle );
	}
}, 100 );

/* ---------- Bilder als WebP ---------- */

/**
 * Neue Bildgroessen als WebP erzeugen (deutlich kleiner als JPEG, bessere Ladezeit).
 * Bestehende Bilder: ddev wp media regenerate --yes
 */
add_filter( 'image_editor_output_format', static function ( array $formats ): array {
	$formats['image/jpeg'] = 'image/webp';
	return $formats;
} );

/**
 * Social-Vorschauen (og:image, twitter:image, Schema): JPEG-Original statt WebP, weil
 * WhatsApp und einige Messenger WebP in Link-Vorschauen nicht zuverlaessig anzeigen.
 */
$tb_seo_jpeg_images = static function ( array $details ): array {
	foreach ( $details as &$image ) {
		if ( empty( $image['id'] ) || ! str_ends_with( (string) ( $image['url'] ?? '' ), '.webp' ) ) {
			continue;
		}
		$path = wp_get_original_image_path( $image['id'] );
		$url  = wp_get_original_image_url( $image['id'] );
		if ( ! $path || ! $url || ! is_readable( $path ) || str_ends_with( $path, '.webp' ) ) {
			continue;
		}
		$size  = wp_getimagesize( $path );
		$image = array_merge( $image, [
			'url'      => $url,
			'width'    => $size[0] ?? $image['width'],
			'height'   => $size[1] ?? $image['height'],
			'filesize' => filesize( $path ),
		] );
	}
	unset( $image );
	return $details;
};
add_filter( 'the_seo_framework_custom_image_details', $tb_seo_jpeg_images );
add_filter( 'the_seo_framework_generated_image_details', $tb_seo_jpeg_images );

/* ---------- Barrierefreiheit Formular ---------- */

/**
 * Das Honeypot-Feld (CF7 Apps) setzt tabindex="1000": Tastatur-Nutzer springen dann zuerst
 * in das unsichtbare Feld. -1 nimmt es ganz aus der Tab-Reihenfolge (Lighthouse «tabindex»).
 */
add_filter( 'wpcf7_form_elements', static function ( string $html ): string {
	return preg_replace( '/(?<=\s)tabindex="1000"/', 'tabindex="-1"', $html );
}, 20 );

/* ---------- Bilder für Google (Bild-Sitemap, Schema) ---------- */

/**
 * Alle Bilder einer Seite: Beitragsbild zuerst, dann jedes Bild im Inhalt (Klasse wp-image-ID),
 * ohne Doppelte. Rückgabe: Attachment-IDs.
 */
function tb_seo_page_image_ids( int $post_id ): array {
	$ids   = [];
	$thumb = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb ) {
		$ids[] = $thumb;
	}
	if ( preg_match_all( '/wp-image-(\d+)/', (string) get_post_field( 'post_content', $post_id ), $m ) ) {
		$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
	}
	return array_values( array_unique( array_filter( $ids, static fn ( int $id ): bool => wp_attachment_is_image( $id ) ) ) );
}

/**
 * Indexierbare Seiten (veröffentlicht, nicht noindex).
 */
function tb_seo_indexable_pages(): array {
	return array_filter(
		get_posts( [ 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] ),
		static fn ( WP_Post $p ): bool => ! get_post_meta( $p->ID, '_genesis_noindex', true )
	);
}

/**
 * Bild-Sitemap /sitemap-images.xml: pro Seite alle darauf gezeigten Bilder (grösste Version).
 * Google findet so auch die Bilder der Bildfolge, die beim Laden versteckt sind und lazy laden.
 */
add_action( 'template_redirect', static function (): void {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	if ( $path !== 'sitemap-images.xml' ) {
		return;
	}
	if ( ! get_option( 'blog_public' ) ) {
		status_header( 404 );
		exit;
	}
	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
	foreach ( tb_seo_indexable_pages() as $page ) {
		$images = tb_seo_page_image_ids( $page->ID );
		if ( ! $images ) {
			continue;
		}
		echo "\t<url>\n\t\t<loc>" . esc_url( get_permalink( $page ) ) . "</loc>\n";
		echo "\t\t<lastmod>" . esc_html( get_post_modified_time( 'c', true, $page ) ) . "</lastmod>\n";
		foreach ( $images as $id ) {
			echo "\t\t<image:image><image:loc>" . esc_url( (string) wp_get_attachment_image_url( $id, 'full' ) ) . "</image:loc></image:image>\n";
		}
		echo "\t</url>\n";
	}
	echo "</urlset>\n";
	exit;
}, 0 );

/**
 * Bild-Sitemap in robots.txt eintragen (zusätzlich zur Seiten-Sitemap von The SEO Framework).
 */
add_filter( 'robots_txt', static function ( string $output, bool $public ): string {
	if ( $public ) {
		$output = rtrim( $output ) . "\nSitemap: " . home_url( '/sitemap-images.xml' ) . "\n";
	}
	return $output;
}, 99, 2 );

/**
 * Schema: jede Seite listet ihre Bilder als ImageObject (mit Beschreibung = Alt-Text);
 * das Beitragsbild ist das Hauptbild der Seite. Auf der Startseite ist Thierry Bill
 * als Person verknüpft (about), damit Google die Fotos der Person zuordnen kann.
 */
add_filter( 'the_seo_framework_schema_graph_data', static function ( array $graph ): array {
	if ( ! is_singular( 'page' ) ) {
		return $graph;
	}
	$ids = tb_seo_page_image_ids( (int) get_queried_object_id() );
	if ( ! $ids ) {
		return $graph;
	}
	$person = [ '@id' => home_url( '/#/schema/Person' ) ];
	$images = [];
	foreach ( $ids as $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $src ) {
			continue;
		}
		$images[] = array_filter( [
			'@type'      => 'ImageObject',
			'@id'        => $src[0],
			'url'        => $src[0],
			'contentUrl' => $src[0],
			'width'      => $src[1],
			'height'     => $src[2],
			'caption'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'about'      => $person,
		] );
	}
	foreach ( $graph as &$entity ) {
		if ( ( $entity['@type'] ?? '' ) === 'WebPage' ) {
			$entity['primaryImageOfPage'] = [ '@id' => $images[0]['@id'] ];
			$entity['image']              = $images;
		}
	}
	unset( $entity );
	return $graph;
}, 20 );
