<?php
/**
 * NavChart Cache Class
 *
 * Simple wrapper for WordPress transients.
 *
 * @package NavChart
 */

namespace NavChart;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cache {
    private $prefix = 'navchart_';

    /**
     * Get cached data.
     *
     * @param string $key Cache key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get( $key, $default = null ) {
        return get_transient( $this->prefix . $key ) ?: $default;
    }

    /**
     * Set cached data.
     *
     * @param string $key Cache key.
     * @param mixed $value Value to cache.
     * @param int $expiration Expiration in seconds (default 1 hour).
     * @return bool
     */
    public function set( $key, $value, $expiration = HOUR_IN_SECONDS ) {
        return set_transient( $this->prefix . $key, $value, $expiration );
    }

    /**
     * Delete cached data.
     *
     * @param string $key Cache key.
     * @return bool
     */
    public function delete( $key ) {
        return delete_transient( $this->prefix . $key );
    }

    /**
     * Clear all cache.
     */
    public function clear_all() {
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_' . $this->prefix . '%' ) );
        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", '_transient_timeout_' . $this->prefix . '%' ) );
    }
}