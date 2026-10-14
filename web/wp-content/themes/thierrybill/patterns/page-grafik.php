<?php
/**
 * Title: Seite Grafik
 * Slug: thierrybill/page-grafik
 * Categories: thierrybill
 * Description: Seite Grafik: Hero mit Bildfolge (Arbeiten), Text, Details.
 */

echo tb_hero( 'Grafik', 'Design + Web + Fotografie', [ 'portrait-duo' ] );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Grafikdesign: visuelle Konzepte, klar, funktional und ästhetisch umgesetzt.', true )
		. tb_p( 'Ich habe die Ausbildung zum Mediamatiker EFZ bei der biwac Webagentur abgeschlossen. Dort habe ich an vielfältigen digitalen Projekten gearbeitet, von Design und Layout über Websites und E-Commerce bis zur Fotografie.' )
		. tb_p( 'Gestaltung und Technik gehören für mich zusammen: Ich denke digitale Inhalte ganzheitlich.' )
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
