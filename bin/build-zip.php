<?php
/**
 * Build dist/sane-defaults.zip from plugin/sane-defaults/.
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

$source_dir = dirname( __DIR__ ) . '/plugin/sane-defaults';
$zip_path   = dirname( __DIR__ ) . '/dist/sane-defaults.zip';
$slug       = 'sane-defaults';
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
 * @return array Names that are missing, out of date, or unexpected.
 */
function wpyeg_build_stale_entries( $zip_path, $source_dir, $slug, $files ) {
	if ( ! file_exists( $zip_path ) ) {
		return $files;
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path ) ) {
		return $files;
	}

	$stale            = array();
	$expected_entries = array( $slug . '/' );
	foreach ( $files as $file ) {
		$entry              = $slug . '/' . $file;
		$expected_entries[] = $entry;
		$packed             = $zip->getFromName( $entry );
		if ( false === $packed || file_get_contents( $source_dir . '/' . $file ) !== $packed ) {
			$stale[] = $file;
		}
	}

	$actual_entries = array();
	$entry_count    = count( $zip );
	for ( $index = 0; $index < $entry_count; $index++ ) {
		$entry = $zip->getNameIndex( $index );
		if ( false !== $entry ) {
			$actual_entries[] = $entry;
		}
	}

	$unexpected_entries = $actual_entries;
	foreach ( $expected_entries as $expected_entry ) {
		$index = array_search( $expected_entry, $unexpected_entries, true );
		if ( false !== $index ) {
			unset( $unexpected_entries[ $index ] );
		}
	}

	foreach ( $unexpected_entries as $entry ) {
		$stale[] = $entry . ' (unexpected)';
	}
	$zip->close();

	return $stale;
}

$stale = wpyeg_build_stale_entries( $zip_path, $source_dir, $slug, $files );

if ( $check_only ) {
	if ( empty( $stale ) ) {
		fwrite( STDOUT, "dist/sane-defaults.zip matches the plugin source.\n" );
		exit( 0 );
	}
	fwrite( STDERR, 'Stale in dist/sane-defaults.zip: ' . implode( ', ', $stale ) . ". Run `composer build`.\n" );
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

fwrite( STDOUT, 'Built dist/sane-defaults.zip (' . implode( ', ', $files ) . ").\n" );
