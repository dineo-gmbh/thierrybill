<?php
/**
 * Inhalte anlegen: Bilder, Kontaktformular, Seiten aus Theme-Patterns, Navigation, Optionen.
 * Idempotent, Aufruf: ddev wp eval-file scripts/setup-content.php
 */

defined( 'ABSPATH' ) || exit;

if ( wp_get_theme()->get_stylesheet() !== 'thierrybill' ) {
	WP_CLI::error( 'Theme thierrybill ist nicht aktiv (wp theme activate thierrybill).' );
}
if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	WP_CLI::error( 'Contact Form 7 ist nicht aktiv (wp plugin activate contact-form-7).' );
}

// Pattern-Liste des Themes neu einlesen (WordPress cached sie ausserhalb des Entwicklungsmodus).
wp_get_theme()->delete_pattern_cache();

/* ---------- Helfer ---------- */

// Pattern-Datei rendern, Asset-URLs relativ, damit der Inhalt auf jeder Domain stimmt.
$pattern = static function ( string $slug ): string {
	$file = get_theme_file_path( "patterns/$slug.php" );
	if ( ! is_file( $file ) ) {
		WP_CLI::error( "Pattern $slug fehlt." );
	}
	ob_start();
	include $file;
	$content = ob_get_clean();
	$host    = preg_quote( wp_parse_url( home_url(), PHP_URL_HOST ), '#' );
	return trim( preg_replace( '#https?://' . $host . '(/wp-content/)#', '$1', $content ) );
};

$upsert = static function ( string $type, string $slug, string $title, ?string $content = null, array $extra = [] ): int {
	$existing = get_posts( [
		'post_type'   => $type,
		'name'        => $slug,
		'post_status' => 'any',
		'numberposts' => 1,
	] );
	$data     = array_merge( [
		'post_type'      => $type,
		'post_status'    => 'publish',
		'post_name'      => $slug,
		'post_title'     => $title,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	], $extra );
	if ( $content !== null ) {
		$data['post_content'] = $content;
	}
	if ( $existing ) {
		$data['ID'] = $existing[0]->ID;
		$id         = wp_update_post( wp_slash( $data ), true );
	} else {
		$data['post_content'] = $data['post_content'] ?? '';
		$id                   = wp_insert_post( wp_slash( $data ), true );
	}
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( $id->get_error_message() );
	}
	return $id;
};

/* ---------- Bilder (scripts/data/img → Mediathek, Meta _tb_key) ---------- */

$images = [
	'live-rot'     => [ 'Thierry Bill live, rotes Licht', 'Thierry Bill mit Gitarre auf der Bühne, in rotes Licht getaucht' ],
	'live-blau'    => [ 'Thierry Bill live, blaues Licht', 'Thierry Bill an der E-Gitarre mit Band auf einer Kellerbühne' ],
	'live-pink'    => [ 'Thierry Bill live, pinkes Licht', 'Thierry Bill spielt Gitarre im pinken Scheinwerferlicht' ],
	'portrait'     => [ 'Thierry Bill, Porträt', 'Schwarz-Weiss-Porträt von Thierry Bill im dunklen Mantel' ],
	'portrait-duo' => [ 'Thierry Bill, Fotoshooting', 'Schwarz-Weiss-Foto: Thierry Bill im Mantel neben einem Freund vor einer Hausfassade' ],
	'tattoo-1'     => [ 'Tattoo von Thierry Bill, Knie', 'Feines Linework-Tattoo von Thierry Bill: organische, verästelte Ornamente über Oberschenkel und Knie' ],
];

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

foreach ( $images as $key => [ $title, $alt ] ) {
	if ( tb_img_id( $key ) ) {
		continue;
	}
	$source = __DIR__ . "/data/img/$key.jpg";
	$tmp    = wp_tempnam( $key );
	copy( $source, $tmp );
	$id = media_handle_sideload( [ 'name' => "thierry-bill-$key.jpg", 'tmp_name' => $tmp ], 0, $title );
	if ( is_wp_error( $id ) ) {
		WP_CLI::error( "Bild $key: " . $id->get_error_message() );
	}
	update_post_meta( $id, '_tb_key', $key );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	WP_CLI::log( "Bild geladen: $key (#$id)" );
}

/* ---------- Kontaktformular (Contact Form 7) ---------- */

$form_body = <<<'FORM'
<label>Name
[text* your-name autocomplete:name]</label>

<label>E-Mail
[email* your-email autocomplete:email]</label>

<label>Worum geht es?
[select your-topic "Musik / Booking" "Studio-Session" "Tattoo-Anfrage" "Grafik / Design" "Anderes"]</label>

<label>Betreff
[text your-subject]</label>

<label>Nachricht
[textarea* your-message]</label>

[honeypot website]

[submit "Nachricht senden"]
FORM;

$form_id = $upsert( 'wpcf7_contact_form', 'kontaktformular', 'Kontakt' );
$form    = WPCF7_ContactForm::get_instance( $form_id );
$form->set_properties( [
	'form'     => $form_body,
	'mail'     => [
		'active'             => true,
		'subject'            => '[_site_title] [your-topic]: [your-subject]',
		'sender'             => '[_site_title] <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
		'recipient'          => '[_site_admin_email]',
		'body'               => "Von: [your-name] <[your-email]>\nThema: [your-topic]\nBetreff: [your-subject]\n\n[your-message]\n\n-- \nGesendet über das Kontaktformular auf [_site_url]",
		'additional_headers' => 'Reply-To: [your-name] <[your-email]>',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => true,
	],
	'messages' => [
		'mail_sent_ok'     => 'Danke! Deine Nachricht ist angekommen, ich melde mich bald.',
		'mail_sent_ng'     => 'Beim Senden ist ein Fehler aufgetreten. Bitte versuche es später nochmals oder schreib mir via Instagram.',
		'validation_error' => 'Bitte prüfe die markierten Felder.',
		'spam'             => 'Beim Senden ist ein Fehler aufgetreten.',
		'invalid_required' => 'Bitte ausfüllen.',
		'invalid_email'    => 'Bitte eine gültige E-Mail-Adresse angeben.',
	],
	'locale'   => 'de_CH',
] );
$form->save();
WP_CLI::log( "Kontaktformular #$form_id" );

/* ---------- Seiten ---------- */

// Rechtstexte (Impressum, Datenschutz) aus scripts/legal-content.php.
define( 'TB_SETUP_CONTENT', true );
require __DIR__ . '/legal-content.php';

$pages = [
	// slug => [ Titel, Pattern oder null, Template, Menue-Reihenfolge ]
	'start'       => [ 'Start', 'page-start', 'page-no-title', 0 ],
	'musik'       => [ 'Musik', 'page-musik', 'page-no-title', 1 ],
	'tattoo'      => [ 'Tattoo', 'page-tattoo', 'page-no-title', 2 ],
	'grafik'      => [ 'Grafik', 'page-grafik', 'page-no-title', 3 ],
	'kontakt'     => [ 'Kontakt', 'page-kontakt', 'page-no-title', 4 ],
	'impressum'   => [ 'Impressum', null, '', 10 ],
	'datenschutz' => [ 'Datenschutz', null, '', 11 ],
];

// Erst alle Seiten anlegen, damit tb_page_url() in den Patterns die Links findet.
foreach ( $pages as $slug => [ $title, , $template, $order ] ) {
	$upsert( 'page', $slug, $title, null, [ 'page_template' => $template, 'menu_order' => $order ] );
}
$ids = [];
foreach ( $pages as $slug => [ $title, $pattern_slug, $template, $order ] ) {
	$content      = $pattern_slug ? $pattern( $pattern_slug ) : tb_legal_content( $slug );
	$ids[ $slug ] = $upsert( 'page', $slug, $title, $content, [ 'page_template' => $template, 'menu_order' => $order ] );
	WP_CLI::log( "Seite $slug (#{$ids[ $slug ]})" );
}

/* ---------- Navigation ---------- */

$nav_items = '';
foreach ( [ 'musik', 'tattoo', 'grafik', 'kontakt' ] as $slug ) {
	$nav_items .= sprintf(
		"<!-- wp:navigation-link {\"label\":\"%s\",\"type\":\"page\",\"id\":%d,\"url\":\"%s\",\"kind\":\"post-type\"%s} /-->\n",
		esc_attr( $pages[ $slug ][0] ),
		$ids[ $slug ],
		esc_url( wp_make_link_relative( get_permalink( $ids[ $slug ] ) ) ),
		$slug === 'kontakt' ? ',"className":"tb-nav-cta"' : '' // Kontakt fett, wie ein Aufruf
	);
}
$nav_id = $upsert( 'wp_navigation', 'main-navigation', 'Hauptnavigation', trim( $nav_items ) );
WP_CLI::log( "Navigation #$nav_id" );

/* ---------- SEO (The SEO Framework) ---------- */

// Titel (max. ~60 Zeichen), Beschreibung (max. ~155 Zeichen), Social-Bild je Seite.
// no_blogname: Titel enthaelt den Namen schon, kein « – Thierry Bill» anhaengen.
$seo = [
	'start'       => [
		'title'       => 'Thierry Bill – Musiker, Tattoo-Artist & Grafikdesigner',
		'description' => 'Portfolio von Thierry Bill: Jazz-Gitarrist (HSLU Luzern, Cold Touch), Tattoo-Artist mit eigenen Linework-Designs und Grafikdesigner (Mediamatiker EFZ).',
		'image'       => 'portrait',
	],
	'musik'       => [
		'title'       => 'Musik – Jazz-Gitarrist Thierry Bill',
		'description' => 'Thierry Bill ist Jazz-Gitarrist (Bachelor HSLU Luzern) und Lead-Gitarrist von Cold Touch aus Biel. Live u. a. im Gaskessel Bern, Bierhübeli und Mühle Hunziken.',
		'image'       => 'live-pink',
	],
	'tattoo'      => [
		'title'       => 'Tattoo – Custom Tattoos & Linework von Thierry Bill',
		'description' => 'Individuelle Tattoos nach eigenen Entwürfen: Thierry Bill gestaltet custom Designs gemeinsam mit dir. Jetzt Tattoo anfragen – per Formular oder Instagram.',
		'image'       => 'tattoo-1',
	],
	'grafik'      => [
		'title'       => 'Grafikdesign – Thierry Bill, Mediamatiker EFZ',
		'description' => 'Grafikdesign, Webdesign, E-Commerce, Fotografie und Layout: Thierry Bill (Mediamatiker EFZ) setzt visuelle Konzepte klar, funktional und ästhetisch um.',
		'image'       => 'portrait-duo',
	],
	'kontakt'     => [
		'title'       => 'Kontakt – Thierry Bill',
		'description' => 'Kontakt zu Thierry Bill für Booking, Studio-Sessions, Tattoo-Anfragen oder Designprojekte – per Formular, Instagram oder Facebook.',
		'image'       => 'portrait',
	],
	'impressum'   => [ 'noindex' => true ],
	'datenschutz' => [ 'noindex' => true ],
];
foreach ( $seo as $slug => $meta ) {
	$id = $ids[ $slug ];
	if ( ! empty( $meta['title'] ) ) {
		update_post_meta( $id, '_genesis_title', $meta['title'] );
		update_post_meta( $id, '_tsf_title_no_blogname', 1 );
		update_post_meta( $id, '_genesis_description', $meta['description'] );
	}
	if ( ! empty( $meta['image'] ) && tb_img_id( $meta['image'] ) ) {
		// Beitragsbild = Social-Bild (og:image) und Bild der Seite in der Suche.
		set_post_thumbnail( $id, tb_img_id( $meta['image'] ) );
	}
	if ( ! empty( $meta['noindex'] ) ) {
		update_post_meta( $id, '_genesis_noindex', 1 );
	}
}

// Website-weit: Person statt Organisation, Profile, Trennzeichen, Startseite, Sitemap.
$tsf = get_option( 'autodescription-site-settings', [] );
$portrait_id = tb_img_id( 'portrait' );
$tsf = array_merge( is_array( $tsf ) ? $tsf : [], [
	'title_separator'      => 'ndash',
	'knowledge_output'     => 1,
	'knowledge_type'       => 'person',
	'knowledge_name'       => 'Thierry Bill',
	'knowledge_logo'       => 1,
	'knowledge_logo_id'    => $portrait_id,
	'knowledge_logo_url'   => $portrait_id ? wp_get_attachment_image_url( $portrait_id, 'full' ) : '',
	'knowledge_instagram'  => 'https://www.instagram.com/billthierryjoel/',
	'knowledge_facebook'   => 'https://www.facebook.com/billthierryjoel/',
	'homepage_title'       => $seo['start']['title'],
	'homepage_description' => $seo['start']['description'],
	'homepage_tagline'     => 0,
	'social_image_fb_id'   => $portrait_id,
	'social_image_fb_url'  => $portrait_id ? wp_get_attachment_image_url( $portrait_id, 'full' ) : '',
	'ld_json_enabled'      => 1,
	'ld_json_breadcrumbs'  => 1,
	'ld_json_searchbox'    => 0, // keine Suche auf der Seite
	'sitemaps_output'      => 1,
	'sitemaps_robots'      => 1,
	'sitemaps_modified'    => 1,
] );
update_option( 'autodescription-site-settings', $tsf );
WP_CLI::log( 'SEO-Einstellungen gesetzt' );

/* ---------- Beispielinhalte entfernen ---------- */

foreach ( [ 'hello-world' => 'post', 'hallo-welt' => 'post', 'sample-page' => 'page', 'beispielseite' => 'page', 'datenschutzerklaerung' => 'page', 'privacy-policy' => 'page' ] as $slug => $type ) {
	$post = get_page_by_path( $slug, OBJECT, $type );
	if ( $post && ! in_array( $post->ID, $ids, true ) ) {
		wp_delete_post( $post->ID, true );
		WP_CLI::log( "Entfernt: $slug" );
	}
}

/* ---------- Optionen ---------- */

foreach ( [
	'blogname'               => 'Thierry Bill',
	'blogdescription'        => 'Musiker, Tattoo-Artist, Grafikdesigner',
	'show_on_front'          => 'page',
	'page_on_front'          => $ids['start'],
	'wp_page_for_privacy_policy' => $ids['datenschutz'],
	'timezone_string'        => 'Europe/Zurich',
	'date_format'            => 'j. F Y',
	'time_format'            => 'H:i',
	'start_of_week'          => 1,
	'WPLANG'                 => 'de_CH',
	'default_comment_status' => 'closed',
	'default_ping_status'    => 'closed',
	'blog_public'            => 0,
] as $key => $value ) {
	update_option( $key, $value );
}

global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/%postname%/' );
flush_rewrite_rules( false );

WP_CLI::success( 'Inhalte eingerichtet: ' . home_url( '/' ) );
