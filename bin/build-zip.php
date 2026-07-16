<?php
/**
 * Build dist/better-by-default.zip from plugin/better-by-default/.
 *
 * The zip is committed and is the install route the README and the workshop
 * deck both point at, so it has to be rebuilt whenever the plugin source
 * changes. Run it with `composer build`; `composer build -- --check` verifies
 * the committed zip matches the source without rewriting it.
 *
 * Entries are added in sorted order with a fixed timestamp so that rebuilding
 * an unchanged source tree produces an identical file rather than diff churn.
 *
 * @package BetterByDefault
 */

$source_dir = dirname( __DIR__ ) . '/plugin/better-by-default';
$zip_path   = dirname( __DIR__ ) . '/dist/better-by-default.zip';
$slug       = 'better-by-default';
$check_only = in_array( '--check', array_slice( $argv, 1 ), true );

// Fixed mtime keeps rebuilds byte-identical. Bump only if entry order changes.
$fixed_mtime = mktime( 0, 0, 0, 1, 1, 2020 );

$files = array_values(
	array_filter(
		scandir( $source_dir ),
		static function ( $file ) use ( $source_dir ) {
			return '.' !== $file[0] && is_file( $source_dir . '/' . $file );
		}
	)
);
sort( $files );

if ( empty( $files ) ) {
	fwrite( STDERR, "No plugin files found in {$source_dir}\n" );
	exit( 1 );
}

/**
 * Report which zip members differ from the source tree.
 *
 * @param string $zip_path   Path to the zip.
 * @param string $source_dir Plugin source directory.
 * @param string $slug       Directory prefix inside the zip.
 * @param array  $files      Expected file names.
 * @return array Names that are missing or out of date.
 */
function wpyeg_build_stale_entries( $zip_path, $source_dir, $slug, $files ) {
	if ( ! file_exists( $zip_path ) ) {
		return $files;
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path ) ) {
		return $files;
	}

	$stale = array();
	foreach ( $files as $file ) {
		$packed = $zip->getFromName( $slug . '/' . $file );
		if ( false === $packed || file_get_contents( $source_dir . '/' . $file ) !== $packed ) {
			$stale[] = $file;
		}
	}
	$zip->close();

	return $stale;
}

$stale = wpyeg_build_stale_entries( $zip_path, $source_dir, $slug, $files );

if ( $check_only ) {
	if ( empty( $stale ) ) {
		fwrite( STDOUT, "dist/better-by-default.zip matches the plugin source.\n" );
		exit( 0 );
	}
	fwrite( STDERR, 'Stale in dist/better-by-default.zip: ' . implode( ', ', $stale ) . ". Run `composer build`.\n" );
	exit( 1 );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	fwrite( STDERR, "Could not open {$zip_path} for writing.\n" );
	exit( 1 );
}

$zip->addEmptyDir( $slug );
foreach ( $files as $file ) {
	$zip->addFile( $source_dir . '/' . $file, $slug . '/' . $file );
}

// setMtimeName() needs PHP 8.0+; without it the zip still builds, just with
// current timestamps.
if ( method_exists( $zip, 'setMtimeName' ) ) {
	$zip->setMtimeName( $slug . '/', $fixed_mtime );
	foreach ( $files as $file ) {
		$zip->setMtimeName( $slug . '/' . $file, $fixed_mtime );
	}
}

$zip->close();

$remaining = wpyeg_build_stale_entries( $zip_path, $source_dir, $slug, $files );
if ( ! empty( $remaining ) ) {
	fwrite( STDERR, 'Build produced a zip that does not match source: ' . implode( ', ', $remaining ) . "\n" );
	exit( 1 );
}

fwrite( STDOUT, 'Built dist/better-by-default.zip (' . implode( ', ', $files ) . ").\n" );
