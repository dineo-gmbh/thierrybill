<?php
/**
 * Title: Seite Tattoo
 * Slug: thierrybill/page-tattoo
 * Categories: thierrybill
 * Description: Seite Tattoo: Hero mit Bildfolge (Arbeiten), Text, Anfrage.
 */

echo tb_hero( 'Tattoo', 'Custom Designs + Linework', [ 'tattoo-1' ] );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Individuelle Tattoos nach eigenen Entwürfen.', true )
		. tb_p( 'Neben der Musik ist Tattooing meine zweite Leidenschaft. Ich habe schon immer gerne gezeichnet und Dingen eine persönliche Handschrift gegeben. Daraus sind eigene Designs entstanden, die ich als Tattoos umsetze.' )
		. tb_p( 'Inzwischen habe ich mehrere Tattoos gestochen. Jedes Design entsteht gemeinsam mit dir und ist auf deine Persönlichkeit abgestimmt.' )
	)
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Tattoo anfragen' ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_p( 'Du möchtest ein individuelles Tattoo? Schreib mir mit deiner Idee, der gewünschten Stelle und Grösse.' )
		. tb_details( [
			[ 'Formular', sprintf( '<a href="%s">Kontakt</a>', esc_url( tb_page_url( 'kontakt' ) ) ) ],
			[ 'Instagram', '<a href="https://www.instagram.com/billthierryjoel/" target="_blank" rel="noreferrer noopener">@billthierryjoel</a>' ],
		] )
	)
);
