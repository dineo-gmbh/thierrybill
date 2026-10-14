<?php
/**
 * Generator for email-safe PNG assets (2x retina 80x80 rendered for 40x40 display).
 */

$base_dir = dirname( __DIR__ ) . '/assets/images/email';

if ( ! extension_loaded( 'gd' ) ) {
	fwrite( STDERR, "GD extension is required.\n" );
	exit( 1 );
}

if ( ! is_dir( $base_dir ) ) {
	mkdir( $base_dir, 0755, true );
}

function create_rounded_icon( $w, $h, $bg_r, $bg_g, $bg_b, $badge_r, $badge_g, $badge_b, $is_spam = false ) {
	$scale = 2; // 80x80 canvas for retina 40x40
	$width = $w * $scale;
	$height = $h * $scale;
	
	$im = imagecreatetruecolor( $width, $height );
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	$transparent = imagecolorallocatealpha( $im, 0, 0, 0, 127 );
	imagefill( $im, 0, 0, $transparent );
	imagealphablending( $im, true );

	$bg_color = imagecolorallocate( $im, $bg_r, $bg_g, $bg_b );
	$white = imagecolorallocate( $im, 255, 255, 255 );
	$badge_color = imagecolorallocate( $im, $badge_r, $badge_g, $badge_b );
	$accent_line = imagecolorallocate( $im, $bg_r, $bg_g, $bg_b );
	$fold_color = imagecolorallocate( $im, $is_spam ? 252 : 210, $is_spam ? 229 : 243, $is_spam ? 229 : 248 );

	// Draw rounded background (radius 8px -> 16px)
	$r = 8 * $scale;
	imagefilledrectangle( $im, $r, 0, $width - $r - 1, $height - 1, $bg_color );
	imagefilledrectangle( $im, 0, $r, $width - 1, $height - $r - 1, $bg_color );
	imagefilledellipse( $im, $r, $r, $r * 2, $r * 2, $bg_color );
	imagefilledellipse( $im, $width - $r - 1, $r, $r * 2, $r * 2, $bg_color );
	imagefilledellipse( $im, $r, $height - $r - 1, $r * 2, $r * 2, $bg_color );
	imagefilledellipse( $im, $width - $r - 1, $height - $r - 1, $r * 2, $r * 2, $bg_color );

	// Document Paper (x: 12..28, y: 9..29) -> * 2
	$doc_x1 = 23;
	$doc_y1 = 17;
	$doc_x2 = 56;
	$doc_y2 = 60;
	$fold_size = 11;

	// Paper body polygon with top-right dog-ear fold
	imagefilledpolygon( $im, array(
		$doc_x1, $doc_y1,
		$doc_x2 - $fold_size, $doc_y1,
		$doc_x2, $doc_y1 + $fold_size,
		$doc_x2, $doc_y2,
		$doc_x1, $doc_y2
	), $white );

	// Fold corner triangle
	imagefilledpolygon( $im, array(
		$doc_x2 - $fold_size, $doc_y1,
		$doc_x2, $doc_y1 + $fold_size,
		$doc_x2 - $fold_size, $doc_y1 + $fold_size
	), $fold_color );

	// Document lines
	imagefilledrectangle( $im, 30, 31, 45, 34, $accent_line );
	imagefilledrectangle( $im, 30, 39, 50, 42, $accent_line );
	imagefilledrectangle( $im, 30, 47, 43, 50, $accent_line );

	// Badge circle at bottom-right (center 55, 55, radius 12)
	$cx = 55;
	$cy = 55;
	$cr = 13;
	imagefilledellipse( $im, $cx, $cy, ( $cr + 3 ) * 2, ( $cr + 3 ) * 2, $white );
	imagefilledellipse( $im, $cx, $cy, $cr * 2, $cr * 2, $badge_color );

	// Badge icon inside
	imagesetthickness( $im, 3 );
	if ( ! $is_spam ) {
		// Checkmark
		imageline( $im, 49, 55, 53, 59, $white );
		imageline( $im, 53, 59, 61, 50, $white );
	} else {
		// Exclamation mark / warning
		imageline( $im, 55, 48, 55, 56, $white );
		imagefilledellipse( $im, 55, 60, 4, 4, $white );
	}
	imagesetthickness( $im, 1 );

	return $im;
}

// Generate Page Icon (Cyan / Entries)
$page_im = create_rounded_icon( 40, 40, 36, 171, 200, 32, 138, 174, false );
imagepng( $page_im, $base_dir . '/page-icon.png' );
imagedestroy( $page_im );

// Generate Spam Icon (Red / Spam)
$spam_im = create_rounded_icon( 40, 40, 232, 88, 88, 191, 46, 46, true );
imagepng( $spam_im, $base_dir . '/spam-icon.png' );
imagedestroy( $spam_im );

// Generate logo (icon + wordmark) for email header.
$logo_w = 520;
$logo_h = 80;
$logo   = imagecreatetruecolor( $logo_w, $logo_h );
imagealphablending( $logo, false );
imagesavealpha( $logo, true );
$logo_transparent = imagecolorallocatealpha( $logo, 0, 0, 0, 127 );
imagefill( $logo, 0, 0, $logo_transparent );
imagealphablending( $logo, true );

$logo_cyan  = imagecolorallocate( $logo, 61, 213, 224 );
$logo_navy  = imagecolorallocate( $logo, 13, 33, 73 );
$logo_white = imagecolorallocate( $logo, 255, 255, 255 );
$logo_light = imagecolorallocate( $logo, 237, 251, 252 );

// App icon block.
imagefilledrectangle( $logo, 0, 8, 64, 72, $logo_cyan );
imagefilledrectangle( $logo, 0, 56, 64, 72, $logo_light );
imagefilledellipse( $logo, 48, 56, 24, 24, $logo_navy );
imagestring( $logo, 5, 18, 24, 'C', $logo_white );

// Wordmark.
imagestring( $logo, 5, 84, 28, 'CF7 Apps', $logo_navy );
imagepng( $logo, $base_dir . '/cf7apps-logo.png' );
imagedestroy( $logo );

echo "Generated page-icon.png, spam-icon.png, and cf7apps-logo.png successfully in {$base_dir}\n";
