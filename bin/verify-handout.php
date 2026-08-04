<?php
/**
 * Fail when a change to the deck's SLIDES did not also rebuild the PDF handout.
 *
 * The gap this closes, twice in one day: a pull request rebuilds
 * Better-by-Default.pptx and leaves Better-by-Default.pdf alone, so the printed
 * handout keeps teaching whatever the deck just stopped saying. Both times every
 * check passed. `composer test` asserts one PDF page per slide, which a reworded
 * slide does not move, and `composer verify:pdf` is deliberately not in CI
 * because it converts with LibreOffice and different versions disagree for
 * reasons that say nothing about staleness.
 *
 * So compare the two artifacts to each other instead of converting anything.
 * This needs only PHP and zip, which makes it safe to run anywhere.
 *
 * Why slides and not the whole file: the handout is slides-only. A speaker-notes
 * edit changes the .pptx and legitimately leaves the .pdf untouched, so a naive
 * "pptx changed, pdf must change" rule would cry wolf on every notes pass — and
 * a guard that cries wolf is one people learn to override. Only ppt/slides/*.xml
 * is compared; ppt/notesSlides/*.xml is ignored on purpose.
 *
 * Usage: php bin/verify-handout.php [base-ref]   (default: origin/main)
 *
 * @package BetterByDefault
 */

$repo_root = dirname( __DIR__ );
$base_ref  = isset( $argv[1] ) ? $argv[1] : 'origin/main';
$deck_path = 'workshop/Better-by-Default.pptx';
$pdf_path  = 'workshop/Better-by-Default.pdf';

/**
 * Read a file at a git ref into a temporary path.
 *
 * @param string $ref  Git ref.
 * @param string $path Repo-relative path.
 * @return string|null Temp file path, or null when the ref has no such file.
 */
function wpyeg_handout_blob_at( $ref, $path ) {
	$temp = tempnam( sys_get_temp_dir(), 'wpyeg-handout-' );
	$cmd  = 'git show ' . escapeshellarg( $ref . ':' . $path ) . ' > ' . escapeshellarg( $temp ) . ' 2>/dev/null';
	exec( $cmd, $ignored_output, $status );
	unset( $ignored_output );

	if ( 0 !== $status || 0 === filesize( $temp ) ) {
		unlink( $temp );
		return null;
	}

	return $temp;
}

/**
 * The slide XML of a .pptx, concatenated. Notes are deliberately excluded.
 *
 * @param string $pptx Path to a .pptx.
 * @return string
 */
function wpyeg_handout_slide_xml( $pptx ) {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $pptx ) ) {
		return '';
	}

	$names = array();
	$count = count( $zip );

	for ( $i = 0; $i < $count; $i++ ) {
		$name = $zip->getNameIndex( $i );
		if ( preg_match( '#^ppt/slides/slide[0-9]+\.xml$#', $name ) ) {
			$names[] = $name;
		}
	}

	// Zip order is not guaranteed; sort so the comparison is about content.
	sort( $names );

	$xml = '';
	foreach ( $names as $name ) {
		$xml .= $zip->getFromName( $name );
	}

	$zip->close();

	return $xml;
}

$old_deck = wpyeg_handout_blob_at( $base_ref, $deck_path );

if ( null === $old_deck ) {
	fwrite( STDOUT, "No deck at {$base_ref} to compare against; nothing to check.\n" );
	exit( 0 );
}

$old_slides = wpyeg_handout_slide_xml( $old_deck );
$new_slides = wpyeg_handout_slide_xml( $repo_root . '/' . $deck_path );
unlink( $old_deck );

if ( $old_slides === $new_slides ) {
	fwrite( STDOUT, "Deck slides unchanged since {$base_ref}; handout not required to move.\n" );
	exit( 0 );
}

// Slides changed. The handout is converted from them, so it has to have moved.
$old_pdf = wpyeg_handout_blob_at( $base_ref, $pdf_path );

if ( null === $old_pdf ) {
	fwrite( STDOUT, "Deck slides changed and there is no handout at {$base_ref}; nothing to compare.\n" );
	exit( 0 );
}

$old_pdf_hash = hash_file( 'sha256', $old_pdf );
$new_pdf_hash = hash_file( 'sha256', $repo_root . '/' . $pdf_path );
unlink( $old_pdf );

if ( $old_pdf_hash === $new_pdf_hash ) {
	fwrite(
		STDERR,
		"The deck's slides changed since {$base_ref}, but the PDF handout did not.\n"
		. "The handout is converted from those slides, so it is now stale — it will\n"
		. "print whatever the deck just stopped saying.\n\n"
		. "Rebuild it:\n"
		. "  cd workshop && soffice --headless --convert-to pdf Better-by-Default.pptx\n\n"
		. "On macOS: /Applications/LibreOffice.app/Contents/MacOS/soffice\n"
		. "If only speaker notes moved, this guard should not have fired — say so,\n"
		. "because it compares ppt/slides/*.xml and ignores notes on purpose.\n"
	);
	exit( 1 );
}

fwrite( STDOUT, "Deck slides changed and the handout was rebuilt with them.\n" );
exit( 0 );
