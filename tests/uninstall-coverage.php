<?php
/**
 * Drift guard for the uninstaller.
 *
 * The uninstaller names the things it deletes. That is correct today and rots
 * tomorrow: a default added next month writes a new option, nobody thinks about
 * deletion, and the plugin quietly starts leaving data behind. Nothing fails,
 * because there is nothing to fail — the leftover only shows up on a site that
 * deleted the plugin and expected it gone.
 *
 * So this reads the plugin's own source for every persistent-storage key it
 * writes and asserts each one is covered. Adding a key without adding its
 * removal fails here, naming the key.
 *
 * Written after the same guard in Keel caught three keys in a single day, each
 * added by somebody who had just finished thinking about the feature and not
 * about its removal.
 *
 * Run: php tests/uninstall-coverage.php
 *
 * @package SaneDefaults
 */

$root        = dirname( __DIR__ );
$plugin_file = $root . '/plugin/sane-defaults/sane-defaults.php';
$uninstall   = $root . '/plugin/sane-defaults/uninstall.php';
$fail        = 0;

/**
 * Assert helper.
 *
 * @param bool   $cond Condition.
 * @param string $msg  Description.
 */
function wpyeg_uninstall_assert( $cond, $msg ) {
	global $fail;
	if ( ! $cond ) {
		++$fail;
		fwrite( STDERR, "Assertion failed: {$msg}\n" );
	}
}

wpyeg_uninstall_assert( is_readable( $uninstall ), 'uninstall.php exists.' );

$uninstall_src = (string) file_get_contents( $uninstall ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$plugin_src    = (string) file_get_contents( $plugin_file );    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

wpyeg_uninstall_assert(
	false !== strpos( $uninstall_src, "defined( 'WP_UNINSTALL_PLUGIN' )" ),
	'uninstall.php refuses to run outside WordPress\'s uninstall path.'
);

// Options are per site, so a network install needs every site cleared rather
// than only the one that happened to be current when the plugin was deleted.
wpyeg_uninstall_assert(
	false !== strpos( $uninstall_src, 'is_multisite()' ) && false !== strpos( $uninstall_src, 'switch_to_blog' ),
	'uninstall.php clears every site on a network, not only the current one.'
);

// --- what the plugin actually writes ---

$written = array();

// Options and transients written with a literal key.
if ( preg_match_all( "/(?:add_option|update_option|set_transient)\(\s*'([a-z0-9_]+)'/i", $plugin_src, $m ) ) {
	foreach ( $m[1] as $key ) {
		$written[ $key ] = true;
	}
}

// User meta written with a literal key.
if ( preg_match_all( "/(?:add_user_meta|update_user_meta)\(\s*[^,]+,\s*'([a-z0-9_]+)'/i", $plugin_src, $m ) ) {
	foreach ( $m[1] as $key ) {
		$written[ $key ] = true;
	}
}

// Keys built from a prefix and a variable — the shape the breach cache uses —
// caught by their literal prefix rather than the whole key, which cannot be
// known without running the plugin.
if ( preg_match_all( "/'(wpyeg_[a-z0-9_]*)'\s*\.\s*\\\$/i", $plugin_src, $m ) ) {
	foreach ( $m[1] as $key ) {
		$written[ $key ] = true;
	}
}

// The settings option is named through a constant, so the scan cannot see its
// value. Assert the constant's value rather than hard-coding it twice.
if ( preg_match( "/const WPYEG_DEFAULTS_OPTION\s*=\s*'([a-z0-9_]+)'/i", $plugin_src, $om ) ) {
	$written[ $om[1] ] = true;
}

wpyeg_uninstall_assert( ! empty( $written ), 'The scan found at least one storage key (a scan finding nothing would pass everything).' );

foreach ( array_keys( $written ) as $key ) {
	wpyeg_uninstall_assert(
		false !== strpos( $uninstall_src, $key ),
		"Storage key '{$key}' is written by the plugin but not removed by uninstall.php."
	);
}

// Every key carries the plugin's prefix. One that does not is not just untidy —
// it is a key the next person auditing "what does this leave behind?" will not
// think to look for.
foreach ( array_keys( $written ) as $key ) {
	wpyeg_uninstall_assert(
		0 === strpos( $key, 'wpyeg_' ),
		"Storage key '{$key}' does not start with wpyeg_, so it is invisible to a prefix audit."
	);
}

// The object-cache group cannot be reached by SQL, so deleting rows is not
// enough on a site with a persistent cache.
wpyeg_uninstall_assert(
	false !== strpos( $uninstall_src, 'wp_cache_flush_group' ),
	'uninstall.php flushes the object-cache group where the drop-in supports it.'
);

if ( $fail > 0 ) {
	fwrite( STDERR, "uninstall coverage: {$fail} failed\n" );
	exit( 1 );
}

fwrite( STDOUT, 'uninstall coverage: OK (' . count( $written ) . " keys checked)\n" );
