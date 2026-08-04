<?php
/**
 * Check that workshop/Better-by-Default.pdf still matches the committed deck.
 *
 * The handout is a LibreOffice conversion of Better-by-Default.pptx, and the
 * conversion is NOT byte-reproducible: LibreOffice numbers PDF objects and tags
 * font subsets differently on every run, so two builds of an unchanged deck
 * differ in tens of thousands of bytes while rendering identically. Committing
 * a rebuild "just to be safe" therefore produces a large diff that hides the
 * real handout changes it is meant to surface.
 *
 * So this rebuilds into a temporary directory, compares, and writes nothing.
 * Run it with `composer verify:pdf`.
 *
 * What a pass means: the committed handout has the same page count and, once
 * timestamps and the document /ID are normalized away, the same byte length as
 * a fresh conversion of the current deck. A content change moves the length.
 *
 * What a pass does not mean: this is a structural comparison, not a pixel one.
 * An edit that changed the rendering while leaving the compressed length
 * exactly unchanged would slip through. That is vanishingly unlikely for real
 * edits and it is still not proof — rebuild and look at the handout before it
 * goes out.
 *
 * @package BetterByDefault
 */

$repo_root = dirname( __DIR__ );
$deck      = $repo_root . '/workshop/Better-by-Default.pptx';
$handout   = $repo_root . '/workshop/Better-by-Default.pdf';

/**
 * Locate the LibreOffice binary.
 *
 * On macOS it lives inside the application bundle and is not on PATH, which is
 * the single most common reason the documented command fails.
 *
 * @return string|null Executable path, or null when LibreOffice is not present.
 */
function wpyeg_verify_find_soffice() {
	$candidates = array(
		'/Applications/LibreOffice.app/Contents/MacOS/soffice',
		'/usr/bin/soffice',
		'/usr/local/bin/soffice',
		'/opt/homebrew/bin/soffice',
	);

	foreach ( $candidates as $candidate ) {
		if ( is_executable( $candidate ) ) {
			return $candidate;
		}
	}

	$found = trim( (string) shell_exec( 'command -v soffice 2>/dev/null' ) );

	return '' === $found ? null : $found;
}

/**
 * Strip the fields that legitimately change on every conversion.
 *
 * @param string $pdf Raw PDF bytes.
 * @return string
 */
function wpyeg_verify_normalize( $pdf ) {
	$pdf = preg_replace( '#/CreationDate\s*\([^)]*\)#', '/CreationDate()', $pdf );
	$pdf = preg_replace( '#/ModDate\s*\([^)]*\)#', '/ModDate()', $pdf );
	$pdf = preg_replace( '#/ID\s*\[[^\]]*\]#', '/ID[]', $pdf );

	return $pdf;
}

/**
 * Count pages in a PDF.
 *
 * @param string $pdf Raw PDF bytes.
 * @return int
 */
function wpyeg_verify_page_count( $pdf ) {
	return preg_match_all( '#/Type\s*/Page[^s]#', $pdf );
}

foreach ( array( $deck, $handout ) as $required ) {
	if ( ! is_readable( $required ) ) {
		fwrite( STDERR, 'Missing ' . basename( $required ) . ". Nothing to compare.\n" );
		exit( 1 );
	}
}

$soffice = wpyeg_verify_find_soffice();

if ( null === $soffice ) {
	// Exit non-zero rather than passing quietly. A verifier that reports success
	// when it could not verify anything is worse than one that is absent.
	fwrite( STDERR, "LibreOffice not found, so the handout cannot be verified.\n" );
	fwrite( STDERR, "Install it from libreoffice.org, then re-run `composer verify:pdf`.\n" );
	exit( 1 );
}

$work_dir = sys_get_temp_dir() . '/wpyeg-verify-pdf-' . getmypid();

if ( ! mkdir( $work_dir, 0700, true ) && ! is_dir( $work_dir ) ) {
	fwrite( STDERR, "Could not create a temporary directory for the rebuild.\n" );
	exit( 1 );
}

$command = sprintf(
	'%s --headless --convert-to pdf --outdir %s %s 2>&1',
	escapeshellarg( $soffice ),
	escapeshellarg( $work_dir ),
	escapeshellarg( $deck )
);

exec( $command, $output, $status );

$rebuilt = $work_dir . '/Better-by-Default.pdf';

/**
 * Remove the temporary rebuild and its directory.
 *
 * @param string $rebuilt  Path to the rebuilt PDF.
 * @param string $work_dir Temporary directory holding it.
 * @return void
 */
function wpyeg_verify_cleanup( $rebuilt, $work_dir ) {
	if ( is_file( $rebuilt ) ) {
		unlink( $rebuilt );
	}

	if ( is_dir( $work_dir ) ) {
		rmdir( $work_dir );
	}
}

if ( 0 !== $status || ! is_readable( $rebuilt ) ) {
	fwrite( STDERR, "The LibreOffice conversion failed:\n" . implode( "\n", $output ) . "\n" );
	wpyeg_verify_cleanup( $rebuilt, $work_dir );
	exit( 1 );
}

$committed_raw = file_get_contents( $handout );
$rebuilt_raw   = file_get_contents( $rebuilt );

wpyeg_verify_cleanup( $rebuilt, $work_dir );

$committed_pages = wpyeg_verify_page_count( $committed_raw );
$rebuilt_pages   = wpyeg_verify_page_count( $rebuilt_raw );
$committed_norm  = strlen( wpyeg_verify_normalize( $committed_raw ) );
$rebuilt_norm    = strlen( wpyeg_verify_normalize( $rebuilt_raw ) );

$problems = array();

if ( $committed_pages !== $rebuilt_pages ) {
	$problems[] = "page count differs (handout {$committed_pages}, deck rebuilds to {$rebuilt_pages})";
}

if ( $committed_norm !== $rebuilt_norm ) {
	$problems[] = "normalized size differs (handout {$committed_norm} bytes, rebuild {$rebuilt_norm})";
}

if ( ! empty( $problems ) ) {
	fwrite( STDERR, 'The PDF handout is out of date: ' . implode( '; ', $problems ) . ".\n" );
	fwrite( STDERR, "Rebuild it:\n" );
	fwrite( STDERR, '  cd workshop && ' . $soffice . " --headless --convert-to pdf Better-by-Default.pptx\n" );
	exit( 1 );
}

fwrite(
	STDOUT,
	"The PDF handout matches the committed deck ({$committed_pages} pages, {$committed_norm} bytes normalized).\n"
);
fwrite( STDOUT, "Structural comparison only — rebuild and look at it before the handout goes out.\n" );
