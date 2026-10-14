<?php
/**
 * Title: Seite Kontakt
 * Slug: thierrybill/page-kontakt
 * Categories: thierrybill
 * Description: Kontaktseite mit Formular (Contact Form 7) und Social Links.
 */

$form = get_posts( [ 'post_type' => 'wpcf7_contact_form', 'name' => 'kontaktformular', 'numberposts' => 1 ] );
$code = $form ? '[contact-form-7 id="' . (int) $form[0]->ID . '" title="Kontakt"]' : '[Kontaktformular]';

echo tb_hero( 'Kontakt', 'Booking + Tattoo + Design' );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Nachricht', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Ich freue mich auf deine Nachricht.', true )
		. tb_p( 'Für Booking, Studio-Sessions, Tattoo-Anfragen oder Designprojekte.' )
		. "<!-- wp:shortcode -->\n$code\n<!-- /wp:shortcode -->\n"
		. tb_details( [
			[ 'Instagram', '<a href="https://www.instagram.com/billthierryjoel/" target="_blank" rel="noreferrer noopener">@billthierryjoel</a>' ],
			[ 'Facebook', '<a href="https://www.facebook.com/billthierryjoel/" target="_blank" rel="noreferrer noopener">billthierryjoel</a>' ],
		] )
	)
);
