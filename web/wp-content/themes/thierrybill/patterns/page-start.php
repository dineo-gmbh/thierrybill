<?php
/**
 * Title: Seite Start
 * Slug: thierrybill/page-start
 * Categories: thierrybill
 * Description: Startseite: Hero mit Name und Bildfolge, drei Bereiche, Über mich (Text).
 */

echo tb_hero(
	'Thierry Bill',
	'Musiker + Tattoo-Artist + Grafikdesigner',
	[ 'portrait-bewegung', 'live-streifen', 'tattoo-1', 'villa-graffiti', 'band-bruecke', 'portrait' ],
	[
		[ 'Musik', tb_page_url( 'musik' ) ],
		[ 'Tattoo', tb_page_url( 'tattoo' ) ],
		[ 'Grafik', tb_page_url( 'grafik' ) ],
	]
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_tile( 'live-streifen', 'Musik', tb_page_url( 'musik' ) ) )
	. tb_cell( 'tb-at-5 tb-w-4', tb_tile( 'tattoo-1', 'Tattoo', tb_page_url( 'tattoo' ) ) )
	. tb_cell( 'tb-at-9 tb-w-4', tb_tile( 'portrait-bewegung', 'Grafik', tb_page_url( 'grafik' ) ) ),
	'tb-tiles'
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über mich' ) )
	. tb_cell( 'tb-at-5 tb-w-4', tb_p( 'Ich bin Thierry. Am liebsten stehe ich mit der Gitarre auf der Bühne, zeichne Tattoos oder sitze an einem Designprojekt.' ) . tb_p( 'Mich interessiert vieles, und am meisten Spass macht es mir, wenn etwas Eigenes entsteht. Schau dich um, und wenn du Lust auf etwas Gemeinsames hast: schreib mir.' ) )
);
