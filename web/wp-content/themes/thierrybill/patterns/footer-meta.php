<?php
/**
 * Title: Footer
 * Slug: thierrybill/footer-meta
 * Inserter: no
 */

echo tb_spread(
	tb_cell( 'tb-at-1 tb-w-4', tb_p( '© ' . esc_html( wp_date( 'Y' ) ) . ' Thierry Bill' ) )
	. tb_cell( 'tb-at-5 tb-w-8', tb_p( sprintf(
		'<a href="https://www.instagram.com/billthierryjoel/" target="_blank" rel="noreferrer noopener">Instagram</a><a href="https://www.facebook.com/billthierryjoel/" target="_blank" rel="noreferrer noopener">Facebook</a><a href="%s">Impressum</a><a href="%s">Datenschutz</a>',
		esc_url( tb_page_url( 'impressum' ) ),
		esc_url( tb_page_url( 'datenschutz' ) )
	), 'tb-links' ) ),
	'tb-footer-grid'
);
