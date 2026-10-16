<?php
/**
 * thierrybill – Block-Theme fuer Thierry Bill (Musiker, Tattoo-Artist, Grafikdesigner).
 */

defined( 'ABSPATH' ) || exit;

define( 'THIERRYBILL_VERSION', '4.2.1' );

/**
 * Theme-Setup.
 */
function thierrybill_setup(): void {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	remove_theme_support( 'core-block-patterns' );
	// Zwischenstufe fuer srcset: mobil reichen meist ~600 px (sonst springt der Browser auf 768).
	add_image_size( 'tb-600', 600, 0 );
}
add_action( 'after_setup_theme', 'thierrybill_setup' );

/**
 * Frontend-Stylesheet und Skript (Bildfolge im Hero).
 */
function thierrybill_assets(): void {
	wp_enqueue_style( 'thierrybill', get_stylesheet_uri(), [], THIERRYBILL_VERSION );
	wp_enqueue_script(
		'thierrybill',
		get_template_directory_uri() . '/assets/js/thierrybill.js',
		[],
		THIERRYBILL_VERSION,
		[ 'strategy' => 'defer', 'in_footer' => true ]
	);
}
add_action( 'wp_enqueue_scripts', 'thierrybill_assets' );

/**
 * Eigene Pattern-Kategorie.
 */
add_action( 'init', static function (): void {
	register_block_pattern_category( 'thierrybill', [ 'label' => 'Thierry Bill' ] );
} );

/**
 * Ballast abschalten: Emojis, XML-RPC, Kommentare, Autor-/Datums-Archive, REST-User-Listing.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links_extra', 3 );

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );
add_filter( 'comments_array', '__return_empty_array', 10, 2 );

add_action( 'admin_init', static function (): void {
	remove_post_type_support( 'page', 'comments' );
	remove_post_type_support( 'post', 'comments' );
} );

add_action( 'admin_menu', static function (): void {
	remove_menu_page( 'edit-comments.php' );
} );

add_action( 'template_redirect', static function (): void {
	if ( is_author() || is_date() || is_attachment() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );

add_filter( 'rest_endpoints', static function ( array $endpoints ): array {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );

/**
 * Contact Form 7: kein eigenes CSS laden, Theme stylt das Formular.
 */
add_filter( 'wpcf7_load_css', '__return_false' );

/**
 * Favicon aus dem Theme, solange kein Website-Icon im Customizer gesetzt ist.
 */
add_action( 'wp_head', static function (): void {
	if ( has_site_icon() ) {
		return;
	}
	printf(
		'<link rel="icon" href="%s" type="image/svg+xml">' . "\n",
		esc_url( get_template_directory_uri() . '/assets/img/favicon.svg' )
	);
}, 5 );

/* ---------- Bilder ---------- */

/**
 * Attachment-ID eines Theme-Bildes (Schluessel wie «live-rot»). Das Setup-Skript
 * laedt die Bilder aus scripts/data/img in die Mediathek und setzt die Meta _tb_key.
 */
function tb_img_id( string $key ): int {
	static $cache = [];
	if ( empty( $cache[ $key ] ) ) {
		// Nur Treffer cachen: das Setup-Skript fragt vor dem Hochladen nach und braucht danach die neue ID.
		$found         = get_posts( [
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'meta_key'    => '_tb_key',
			'meta_value'  => $key,
			'numberposts' => 1,
			'fields'      => 'ids',
		] );
		$cache[ $key ] = $found ? (int) $found[0] : 0;
	}
	return $cache[ $key ];
}

/**
 * Relative URL eines Theme-Bildes, leer, wenn es (noch) nicht in der Mediathek ist.
 */
function tb_img_url( string $key, string $size = 'large' ): string {
	$id = tb_img_id( $key );
	return $id ? wp_make_link_relative( (string) wp_get_attachment_image_url( $id, $size ) ) : '';
}

/**
 * Alt-Text eines Theme-Bildes.
 */
function tb_img_alt( string $key ): string {
	$id = tb_img_id( $key );
	return $id ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';
}

/**
 * URL einer Seite per Slug, relativ.
 */
function tb_page_url( string $slug ): string {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	return $page ? wp_make_link_relative( get_permalink( $page ) ) : '/' . $slug . '/';
}

/**
 * Image-Block-Markup fuer ein Theme-Bild (in Patterns). Leer, wenn das Bild fehlt.
 */
function tb_image_block( string $key, string $class = '', string $size = 'large' ): string {
	$id = tb_img_id( $key );
	if ( ! $id ) {
		return '';
	}
	$attrs = [ 'id' => $id, 'sizeSlug' => $size, 'linkDestination' => 'none' ];
	if ( $class ) {
		$attrs['className'] = $class;
	}
	return sprintf(
		"<!-- wp:image %1\$s -->\n<figure class=\"wp-block-image size-%2\$s%3\$s\"><img src=\"%4\$s\" alt=\"%5\$s\" class=\"wp-image-%6\$d\"/></figure>\n<!-- /wp:image -->",
		wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ),
		esc_attr( $size ),
		$class ? ' ' . esc_attr( $class ) : '',
		esc_url( tb_img_url( $key, $size ) ),
		esc_attr( tb_img_alt( $key ) ),
		$id
	);
}

/* ---------- Raster-Bausteine fuer Patterns ---------- */

/**
 * Doppelseite («Spread»): 12-Spalten-Raster ueber die ganze Breite.
 */
function tb_spread( string $inner, string $class = '' ): string {
	$class = trim( 'tb-spread ' . $class );
	return sprintf(
		"<!-- wp:group {\"align\":\"wide\",\"className\":\"%1\$s\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group alignwide %1\$s\">%2\$s</div>\n<!-- /wp:group -->\n\n",
		esc_attr( $class ),
		$inner
	);
}

/**
 * Rasterzelle. $class z. B. «tb-at-7 tb-w-6 tb-rows-2» (Startspalte, Breite, Zeilen).
 */
function tb_cell( string $class, string $inner ): string {
	$class = trim( 'tb-cell ' . $class );
	return sprintf(
		"<!-- wp:group {\"className\":\"%1\$s\",\"layout\":{\"type\":\"default\"}} -->\n<div class=\"wp-block-group %1\$s\">%2\$s</div>\n<!-- /wp:group -->\n",
		esc_attr( $class ),
		$inner
	);
}

/**
 * Absatz-Block. $html wird nicht escaped (nur eigene, feste Texte uebergeben).
 */
function tb_p( string $html, string $class = '' ): string {
	if ( $class ) {
		return sprintf(
			"<!-- wp:paragraph {\"className\":\"%1\$s\"} -->\n<p class=\"%1\$s\">%2\$s</p>\n<!-- /wp:paragraph -->\n",
			esc_attr( $class ),
			$html
		);
	}
	return "<!-- wp:paragraph -->\n<p>$html</p>\n<!-- /wp:paragraph -->\n";
}

/**
 * Titel eines Abschnitts (gleiche Schriftgroesse wie der Text, nur dunkler).
 * Level 0: Absatz statt Ueberschrift, fuer reine Beschriftungen wie «Über».
 */
function tb_title( string $text, int $level = 2 ): string {
	if ( $level === 0 ) {
		return tb_p( esc_html( $text ), 'tb-title' );
	}
	return sprintf(
		"<!-- wp:heading {\"level\":%1\$d,\"className\":\"tb-title\"} -->\n<h%1\$d class=\"wp-block-heading tb-title\">%2\$s</h%1\$d>\n<!-- /wp:heading -->\n",
		$level,
		esc_html( $text )
	);
}

/**
 * Grosser Text (eine Aussage pro Seite). Mit $heading als H2, wenn er die Kernaussage
 * der Seite ist (wichtig fuer die Gliederung und Suchmaschinen).
 */
function tb_big( string $text, bool $heading = false ): string {
	if ( ! $heading ) {
		return tb_p( esc_html( $text ), 'tb-big' );
	}
	return sprintf(
		"<!-- wp:heading {\"className\":\"tb-big\"} -->\n<h2 class=\"wp-block-heading tb-big\">%s</h2>\n<!-- /wp:heading -->\n",
		esc_html( $text )
	);
}

/**
 * Zweispaltige Liste Begriff / Wert, z. B. Studium – Bachelor Jazz, HSLU Luzern.
 */
function tb_details( array $rows ): string {
	$html = '';
	foreach ( $rows as [ $term, $value ] ) {
		$html .= tb_p( '<span>' . esc_html( $term ) . '</span><span>' . $value . '</span>', 'tb-row' );
	}
	return $html;
}

/**
 * Bild mit Unterschrift darunter, optional verlinkt (Index der Startseite).
 */
function tb_tile( string $img, string $caption, string $url = '', string $ratio = 'tb-r-45' ): string {
	$visual  = $img ? tb_image_block( $img, 'tb-img tb-thumb ' . $ratio ) : tb_placeholder( $caption, $ratio );
	$caption = esc_html( $caption );
	if ( $url ) {
		$caption = sprintf( '<a href="%s">%s</a>', esc_url( $url ), $caption );
	}
	return $visual . tb_p( $caption, 'tb-caption' );
}

/**
 * Leere hellgraue Flaeche fuer fehlende Bilder; die Beschriftung ist nur fuer Screenreader/Editor.
 */
function tb_placeholder( string $label, string $ratio = 'tb-r-45' ): string {
	return tb_cell( 'tb-ph ' . $ratio, tb_p( esc_html( '[Platzhalter] ' . $label ), 'screen-reader-text' ) );
}

/**
 * Hero: grosser Titel mit kleiner Unterzeile (rechtsbuendig unter dem Titel), rechts eine
 * Bildfolge mit Zaehler («01/05», Klick oder automatisch weiter, assets/js/thierrybill.js),
 * unten links Pfeil-Links. Ohne Bilder bzw. Links entfallen die Bereiche.
 *
 * @param string   $title    Titel (Startseite: Name).
 * @param string   $subtitle Unterzeile, z. B. «Musiker + Tattoo-Artist + Grafikdesigner».
 * @param string[] $images   Bild-Schluessel (siehe tb_img_id()).
 * @param array    $links    [ [ Text, URL ], … ].
 */
function tb_hero( string $title, string $subtitle, array $images = [], array $links = [] ): string {
	$heading = sprintf(
		"<!-- wp:heading {\"level\":1,\"className\":\"tb-display\"} -->\n<h1 class=\"wp-block-heading tb-display\">%s</h1>\n<!-- /wp:heading -->\n",
		esc_html( $title )
	);
	$inner = tb_cell( 'tb-at-1 tb-w-8 tb-hero__name', $heading . tb_p( esc_html( $subtitle ), 'tb-hero__sub' ) );

	$images = array_values( array_filter( $images, static fn ( string $key ): bool => tb_img_id( $key ) > 0 ) );
	if ( $images ) {
		$slides = '';
		foreach ( $images as $key ) {
			$slides .= tb_image_block( $key, 'tb-img tb-r-45 tb-slide' );
		}
		$counter = sprintf( '<span class="tb-count__cur">01</span>/%02d', count( $images ) );
		$inner  .= tb_cell( 'tb-at-9 tb-w-4 tb-hero__media', tb_p( $counter, 'tb-count' ) . tb_cell( 'tb-slides', $slides ) );
	}

	if ( $links ) {
		$html = '';
		foreach ( $links as [ $text, $url ] ) {
			$html .= sprintf( '<a href="%s">%s <span aria-hidden="true">↘</span></a>', esc_url( $url ), esc_html( $text ) );
		}
		$inner .= tb_cell( 'tb-at-1 tb-w-8 tb-hero__links', tb_p( $html ) );
	}

	$variant = $images ? ( $links ? '' : ' tb-hero--page' ) : ' tb-hero--text';
	return tb_spread( $inner, 'tb-hero' . $variant );
}

/* ---------- Ladezeit ---------- */

/**
 * Bilder im Raster: passende sizes-Angabe (Spaltenbreite statt 1024 px), damit der Browser
 * die kleinste ausreichende Groesse laedt. Nur das erste Bild der Bildfolge im Hero laedt
 * sofort (mit hoher Prioritaet, groesstes sichtbares Element), alle weiteren lazy.
 */
add_filter( 'render_block_core/image', static function ( string $html, array $block ): string {
	static $slides = 0;
	$class = $block['attrs']['className'] ?? '';
	if ( ! str_contains( $class, 'tb-img' ) ) {
		return $html;
	}
	// WordPress ergaenzt srcset/sizes erst spaeter und uebernimmt dabei ein vorhandenes sizes.
	// Desktop: 4 von 12 Spalten. Mobil: Hero-Bild 78 %, Kacheln ein Drittel, sonst volle Breite.
	$mobile = str_contains( $class, 'tb-slide' ) ? '78vw' : ( str_contains( $class, 'tb-thumb' ) ? '32vw' : '100vw' );
	$sizes  = 'sizes="(min-width: 782px) 32vw, ' . $mobile . '"';
	$html  = preg_match( '/\ssizes="/', $html )
		? preg_replace( '/\ssizes="[^"]*"/', ' ' . $sizes, $html, 1 )
		: preg_replace( '/<img /', '<img ' . $sizes . ' ', $html, 1 );
	// Nur das erste Hero-Bild ist beim Laden sichtbar; alle anderen Rasterbilder lazy.
	$is_lcp = str_contains( $class, 'tb-slide' ) && $slides++ === 0;
	if ( $is_lcp ) {
		$html = str_replace( ' loading="lazy"', '', $html );
		if ( ! str_contains( $html, 'fetchpriority=' ) ) {
			$html = str_replace( '<img ', '<img fetchpriority="high" ', $html );
		}
	} else {
		$html = str_replace( ' fetchpriority="high"', '', $html );
		if ( ! str_contains( $html, 'loading=' ) ) {
			$html = str_replace( '<img ', '<img loading="lazy" ', $html );
		}
	}
	return $html;
}, 10, 2 );

/**
 * Schriften vorladen (verhindert spaetes Umspringen der Schrift).
 */
add_action( 'wp_head', static function (): void {
	// Inter (Text) und UnifrakturCook (Hero-Titel, oft das groesste sichtbare Element).
	foreach ( [ 'inter-latin.woff2', 'unifrakturcook-latin.woff2' ] as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) )
		);
	}
}, 1 );
