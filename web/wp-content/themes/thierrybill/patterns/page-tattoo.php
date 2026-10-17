<?php
/**
 * Title: Seite Tattoo
 * Slug: thierrybill/page-tattoo
 * Categories: thierrybill
 * Description: Seite Tattoo: Hero mit Bildfolge (Arbeiten), Text, Anfrage.
 */

echo tb_hero( 'Tattoo', 'Custom Designs + Linework', [ 'tattoo-knie-teich', 'tattoo-bein', 'tattoo-1', 'tattoo-knie-detail', 'tattoo-knie-oben', 'tattoo-schulter', 'tattoo-oberschenkel-gross', 'tattoo-schulter-seite', 'tattoo-oberschenkel' ] );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Ich steche Tattoos nach meinen eigenen Zeichnungen.', true )
		. tb_p( 'Gezeichnet habe ich schon immer gern. Irgendwann wollte ich meine Zeichnungen nicht nur auf Papier sehen, und so bin ich zum Tätowieren gekommen. Mein Stil sind feine Linien und organische Formen, die sich dem Körper anpassen.' )
		. tb_p( 'Jedes Tattoo ist ein Einzelstück. Wir besprechen zusammen, was dir gefällt und wo es hin soll, und ich zeichne dann etwas, das zu dir passt.' )
	)
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Tattoo anfragen' ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_p( 'Hast du Lust auf ein Tattoo? Schreib mir einfach, was dir vorschwebt, wo es hin soll und wie gross es etwa sein darf. Eine fertige Idee brauchst du nicht.' )
		. tb_details( [
			[ 'Formular', sprintf( '<a href="%s">Kontakt</a>', esc_url( tb_page_url( 'kontakt' ) ) ) ],
			[ 'Instagram', '<a href="https://www.instagram.com/billthierryjoel/" target="_blank" rel="noreferrer noopener">@billthierryjoel</a>' ],
		] )
	)
);
