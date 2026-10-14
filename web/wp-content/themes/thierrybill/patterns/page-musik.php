<?php
/**
 * Title: Seite Musik
 * Slug: thierrybill/page-musik
 * Categories: thierrybill
 * Description: Seite Musik: Text, Fotos, Details.
 */

echo tb_hero( 'Musik', 'Gitarre + Songwriting + Studio', [ 'live-pink', 'live-rot', 'live-blau' ] );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Gitarrist mit Schwerpunkt Jazz, offen zwischen Jazz, Rock und Pop.', true )
		. tb_p( 'Ich studiere Jazz-Gitarre im Bachelor an der HSLU Luzern. Begonnen hat mein Weg am Pre-College der Swiss Jazz School Bern, wo ich früh in die professionelle Musikszene eintauchen konnte.' )
		. tb_p( 'Aktuell spiele ich Lead-Gitarre bei Cold Touch aus Biel, davor in Bands wie Fatigues und Casual Minds. Neben Konzerten arbeite ich als Songwriter und Studio-Gitarrist, unter anderem im Studio U3.' )
		. tb_p( 'Wichtig sind mir musikalischer Ausdruck und gute Zusammenarbeit.' )
	)
);

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Details' ) )
	. tb_cell( 'tb-at-5 tb-w-6', tb_details( [
		[ 'Band', 'Cold Touch, Lead-Gitarre' ],
		[ 'Frühere Bands', 'Fatigues, Casual Minds' ],
		[ 'Studium', 'Bachelor Jazz, HSLU Luzern' ],
		[ 'Ausbildung', 'Pre-College, Swiss Jazz School Bern' ],
		[ 'Studio', 'Songwriting und Studio-Gitarre, u. a. Studio U3' ],
		[ 'Bühnen', 'Mühle Hunziken, Gaskessel Bern, Bierhübeli, Polenta 7000, Stadtfest Bern' ],
	] ) )
);
