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
	[ 'portrait', 'tattoo-1', 'live-pink', 'portrait-duo', 'live-rot', 'live-blau' ],
	[
		[ 'Musik', tb_page_url( 'musik' ) ],
		[ 'Tattoo', tb_page_url( 'tattoo' ) ],
		[ 'Grafik', tb_page_url( 'grafik' ) ],
	]
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_tile( 'live-blau', 'Musik', tb_page_url( 'musik' ) ) )
	. tb_cell( 'tb-at-5 tb-w-4', tb_tile( 'tattoo-1', 'Tattoo', tb_page_url( 'tattoo' ) ) )
	. tb_cell( 'tb-at-9 tb-w-4', tb_tile( 'portrait-duo', 'Grafik', tb_page_url( 'grafik' ) ) ),
	'tb-tiles'
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über mich' ) )
	. tb_cell( 'tb-at-5 tb-w-4', tb_p( 'Ich arbeite gern kreativ und verbinde verschiedene Ausdrucksformen: Musik, Design und Tattoos. Daraus entstehen persönliche, eigenständige Projekte.' ) . tb_p( 'Echtheit und Individualität stehen für mich dabei immer im Mittelpunkt.' ) )
);
