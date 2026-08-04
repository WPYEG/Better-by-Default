<?php
/**
 * Check that workshop/Better-by-Default.pptx still matches build_deck.js.
 *
 * The test suite reads the committed deck and asserts that every schema key,
 * the setting count, and the corrected claims are present. That catches a
 * setting added to the generator and never built. It does not catch a plain
 * rewrite — reword a slide in build_deck.js, skip the rebuild, and the suite
 * stays green while the deck says the old thing. That is exactly what happened
 * to the multicall correction, which sat right in the generator for two days
 * while the shipped deck contradicted it.
 *
 * So this runs the generator and compares its output with what is committed.
 * Running it is also the only check that proves the generator still runs at
 * all; it was broken through one commit and nothing noticed.
 *
 * The comparison is on extracted text, not bytes. A .pptx is a zip, and its
 * entries carry timestamps, so two builds of an identical deck are never
 * byte-identical. Slide text and speaker notes are stable.
 *
 * Run it with `composer verify:deck`. It builds into a temporary directory and
 * writes nothing.
 *
 * @package BetterByDefault
 */

$repo_root = dirname( __DIR__ );
$generator = $repo_root . '/workshop/build_deck.js';
$committed = $repo_root . '/workshop/Better-by-Default.pptx';

foreach ( array( $generator, $committed ) as $required ) {
	if ( ! is_readable( $required ) ) {
		fwrite( STDERR, 'Missing ' . basename( $required ) . ". Nothing to compare.\n" );
		exit( 1 );
	}
}

if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "This PHP build has no ZipArchive, so the deck cannot be read.\n" );
	exit( 1 );
}

$node = trim( (string) shell_exec( 'command -v node 2>/dev/null' ) );

if ( '' === $node ) {
	fwrite( STDERR, "node not found, so the deck cannot be verified.\n" );
	fwrite( STDERR, "Install Node, then `cd workshop && npm install pptxgenjs`.\n" );
	exit( 1 );
}

/**
 * Extract slide text and speaker notes from a .pptx.
 *
 * Entries are read in sorted name order so two archives are compared in the
 * same sequence regardless of how each was written.
 *
 * @param string $pptx_path Path to the presentation.
 * @return string Every text run, one entry per line.
 */
function wpyeg_verify_deck_text( $pptx_path ) {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $pptx_path ) ) {
		return '';
	}

	$names       = array();
	$entry_count = count( $zip );

	for ( $i = 0; $i < $entry_count; $i++ ) {
		$name = $zip->getNameIndex( $i );

		if ( preg_match( '#^ppt/(slides|notesSlides)/[^/]+\.xml$#', $name ) ) {
			$names[] = $name;
		}
	}

	sort( $names );

	$text = '';

	foreach ( $names as $name ) {
		preg_match_all( '#<a:t>(.*?)</a:t>#s', $zip->getFromName( $name ), $runs );
		$text .= $name . ': ' . implode( ' ', $runs[1] ) . "\n";
	}

	$zip->close();

	return $text;
}

/**
 * Remove the temporary build and its directory.
 *
 * @param string $built    Path to the rebuilt deck.
 * @param string $work_dir Temporary directory holding it.
 * @return void
 */
function wpyeg_verify_deck_cleanup( $built, $work_dir ) {
	if ( is_file( $built ) ) {
		unlink( $built );
	}

	if ( is_dir( $work_dir ) ) {
		rmdir( $work_dir );
	}
}

$work_dir = sys_get_temp_dir() . '/wpyeg-verify-deck-' . getmypid();

if ( ! mkdir( $work_dir, 0700, true ) && ! is_dir( $work_dir ) ) {
	fwrite( STDERR, "Could not create a temporary directory for the rebuild.\n" );
	exit( 1 );
}

// build_deck.js writes Better-by-Default.pptx into the working directory, so
// run it from the temporary one. Module resolution is unaffected: node resolves
// pptxgenjs from the script's own directory, not from the cwd.
$command = sprintf(
	'cd %s && %s %s 2>&1',
	escapeshellarg( $work_dir ),
	escapeshellarg( $node ),
	escapeshellarg( $generator )
);

exec( $command, $output, $status );

$built = $work_dir . '/Better-by-Default.pptx';

if ( 0 !== $status || ! is_readable( $built ) ) {
	fwrite( STDERR, "The deck generator failed:\n" . implode( "\n", $output ) . "\n" );
	wpyeg_verify_deck_cleanup( $built, $work_dir );
	exit( 1 );
}

$built_text     = wpyeg_verify_deck_text( $built );
$committed_text = wpyeg_verify_deck_text( $committed );

wpyeg_verify_deck_cleanup( $built, $work_dir );

if ( '' === $built_text ) {
	fwrite( STDERR, "The rebuilt deck yielded no readable slide text.\n" );
	exit( 1 );
}

if ( $built_text !== $committed_text ) {
	$built_lines     = explode( "\n", $built_text );
	$committed_lines = explode( "\n", $committed_text );
	$changed         = array();

	foreach ( $built_lines as $index => $line ) {
		$was = isset( $committed_lines[ $index ] ) ? $committed_lines[ $index ] : '';

		if ( $line !== $was && '' !== trim( $line ) ) {
			$changed[] = strtok( $line, ':' );
		}
	}

	$changed = array_slice( array_unique( $changed ), 0, 8 );

	fwrite( STDERR, "The committed deck does not match build_deck.js.\n" );

	if ( count( $built_lines ) !== count( $committed_lines ) ) {
		fwrite( STDERR, 'Slide count differs: generator produces ' . ( count( $built_lines ) - 1 ) . ', committed deck has ' . ( count( $committed_lines ) - 1 ) . ".\n" );
	}

	if ( ! empty( $changed ) ) {
		fwrite( STDERR, 'First differing entries: ' . implode( ', ', $changed ) . "\n" );
	}

	fwrite( STDERR, "Rebuild it with `composer build:deck`.\n" );
	exit( 1 );
}

$slides = substr_count( $committed_text, "\n" );

fwrite( STDOUT, "The committed deck matches build_deck.js ({$slides} slide and notes entries compared).\n" );
