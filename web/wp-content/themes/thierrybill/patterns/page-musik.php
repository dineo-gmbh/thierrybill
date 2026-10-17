<?php
/**
 * Title: Seite Musik
 * Slug: thierrybill/page-musik
 * Categories: thierrybill
 * Description: Seite Musik: Text, Fotos, Details.
 */

echo tb_hero( 'Musik', 'Gitarre + Songwriting + Studio', [ 'live-streifen', 'band-bruecke', 'live-scheune', 'live-rot', 'live-blau' ] );

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Über', 0 ) )
	. tb_cell( 'tb-at-5 tb-w-6',
		tb_big( 'Ich spiele Gitarre, am liebsten Jazz, aber auch Rock und Pop.', true )
		. tb_p( 'Zurzeit studiere ich Jazz-Gitarre an der HSLU in Luzern. Angefangen hat alles am Pre-College der Swiss Jazz School in Bern, wo ich schon früh mit Profis spielen durfte.' )
		. tb_p( 'Im Moment spiele ich Lead-Gitarre bei Cold Touch aus Biel. Vorher war ich bei Fatigues und Casual Minds dabei. Ausserdem schreibe ich Songs und spiele für andere im Studio, zum Beispiel im Studio U3.' )
		. tb_p( 'Am meisten Freude habe ich, wenn eine Band richtig zusammen funktioniert. Wenn du eine Gitarre für deine Band, einen Gig oder eine Aufnahme suchst, melde dich.' )
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
