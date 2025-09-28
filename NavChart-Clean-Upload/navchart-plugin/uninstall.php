<?php
/**
 * NavChart Uninstall
 *
 * Cleans up on plugin uninstall.
 *
 * @package NavChart
 */

// Prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete options.
delete_option( 'navchart_options' );

// Clear cache.
$cache = new \NavChart\Cache();
$cache->clear_all();

// Optional: Delete uploaded files if custom, but skip to avoid data loss.