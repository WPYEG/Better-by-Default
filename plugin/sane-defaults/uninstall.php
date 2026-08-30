<?php
/**
 * Remove everything Better by Default stored, when the plugin is deleted.
 *
 * Deleting a plugin should leave the database as it found it. Until this file
 * existed there was no uninstall path at all, so `wpyeg_better_by_default` and
 * every breach-cache transient survived deletion and reinstallation — the
 * settings a site had chosen came back weeks later on a fresh install, and the
 * cached breach lookups sat in the options table with nothing left to read them.
 *
 * Deactivation is untouched and non-destructive: deactivate, and every setting
 * is still there when the plugin comes back. Only deletion clears it, which is
 * what somebody asking WordPress to delete a plugin has asked for.
 *
 * @package SaneDefaults
 */

// Only ever run through WordPress's own uninstall path.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Everything this plugin persists on one site.
 *
 * Two things, and they need different handling:
 *
 * - `wpyeg_better_by_default` — one option holding every setting's value.
 * - `wpyeg_hibp_unavailable` — the last breach-screening failure, if screening
 *   has failed in the last hour. Kind and timestamp only; never the password
 *   and never the hash prefix.
 * - `wpyeg_hibp_cache_generation` — the per-installation namespace that keeps
 *   cache entries left in an external object cache unreachable after reinstall.
 * - `wpyeg_hibp_*` — the Have I Been Pwned response cache, one transient per
 *   five-hex prefix. Deleted with a LIKE query because there is no key to
 *   enumerate: which prefixes exist depends entirely on which passwords have
 *   been checked on this site.
 *
 * @return void
 */
function wpyeg_defaults_uninstall_site() {
	global $wpdb;

	delete_option( 'wpyeg_better_by_default' );

	/*
	 * The breach-screening outage record, and the cache namespace.
	 *
	 * Dropping the generation is what makes the range entries unreachable after
	 * a reinstall. On a site with a persistent object cache a transient is not a
	 * database row at all, so the query below cannot see one — a new generation
	 * means anything left behind belongs to an installation that no longer
	 * exists, and it expires on its own.
	 */
	delete_transient( 'wpyeg_hibp_unavailable' );
	delete_option( 'wpyeg_hibp_cache_generation' );

	if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
		wp_cache_flush_group( 'wpyeg_hibp' );
	}

	// A transient is two option rows, and the timeout row outlives the value if
	// only the value is deleted.
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_wpyeg_hibp_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_wpyeg_hibp_' ) . '%'
		)
	);
}

if ( is_multisite() ) {
	/*
	 * Options are per site, so every site needs clearing — including ones the
	 * plugin was never active on, because a network-activated plugin will have
	 * written on any of them somebody visited.
	 *
	 * get_sites() is capped rather than unbounded: a large network would
	 * otherwise load every site object into memory at once during an uninstall
	 * that has no progress indicator and no way to resume.
	 */
	$wpyeg_paged = 0;

	do {
		$wpyeg_sites = get_sites(
			array(
				'fields' => 'ids',
				'number' => 200,
				'offset' => $wpyeg_paged * 200,
			)
		);

		foreach ( $wpyeg_sites as $wpyeg_site_id ) {
			switch_to_blog( $wpyeg_site_id );
			wpyeg_defaults_uninstall_site();
			restore_current_blog();
		}

		$wpyeg_found = count( $wpyeg_sites );
		++$wpyeg_paged;
	} while ( 200 === $wpyeg_found );
} else {
	wpyeg_defaults_uninstall_site();
}
