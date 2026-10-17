<?php
/**
 * Title: Seite Grafik
 * Slug: thierrybill/page-grafik
 * Categories: thierrybill
 * Description: Seite Grafik: Hero mit Bildfolge (Arbeiten), Text, Details.
 */

echo tb_hero( 'Grafik', 'Design + Web + Fotografie', [ 'portrait-bewegung' ] );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Ich gestalte Websites, Logos, Layouts und mache Fotos.', true )
		. tb_p( 'Gelernt habe ich Mediamatiker bei der biwac Webagentur. Dort habe ich an ganz unterschiedlichen Projekten mitgearbeitet: Websites, Onlineshops, Layouts und Fotoshootings.' )
		. tb_p( 'Mir ist wichtig, dass etwas nicht nur gut aussieht, sondern auch einfach funktioniert. Wenn du Hilfe bei einem Auftritt, einem Logo oder einer Website brauchst, schreib mir.' )
	)
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Details' ) )
	. tb_cell( 'tb-at-5 tb-w-6', tb_details( [
		[ 'Ausbildung', 'Mediamatiker EFZ' ],
		[ 'Betrieb', 'biwac Webagentur' ],
		[ 'Bereiche', 'Design, Layout, Web, E-Commerce, Fotografie' ],
	] ) )
);
