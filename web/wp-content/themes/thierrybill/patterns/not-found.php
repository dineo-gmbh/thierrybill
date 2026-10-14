<?php
/**
 * Title: 404 – Seite nicht gefunden
 * Slug: thierrybill/not-found
 * Inserter: no
 */

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_title( 'Nicht gefunden', 1 ) )
	. tb_cell( 'tb-at-5 tb-w-6', tb_p( 'Diese Seite gibt es nicht (mehr). <a href="/">Zur Startseite</a>' ) )
);
