<?php
/**
 * NavChart Cache Class
 *
 * Simple cache management for NavChart plugin.
 *
 * @package NavChart
 */

namespace NavChart;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cache management class.
 */
class Cache {
    
    /**
     * Cache key prefix.
     */
    const CACHE_PREFIX = 'navchart_';
    
    /**
     * Default cache expiration (1 hour).
     */
    const DEFAULT_EXPIRATION = 3600;
    
    /**
     * Get cached data.
     *
     * @param string $key Cache key.
     * @return mixed|false Cached data or false if not found.
     */
    public static function get( $key ) {
        return get_transient( self::CACHE_PREFIX . $key );
    }
    
    /**
     * Set cached data.
     *
     * @param string $key Cache key.
     * @param mixed $data Data to cache.
     * @param int $expiration Cache expiration in seconds.
     * @return bool True on success, false on failure.
     */
    public static function set( $key, $data, $expiration = self::DEFAULT_EXPIRATION ) {
        return set_transient( self::CACHE_PREFIX . $key, $data, $expiration );
    }
    
    /**
     * Delete cached data.
     *
     * @param string $key Cache key.
     * @return bool True on success, false on failure.
     */
    public static function delete( $key ) {
        return delete_transient( self::CACHE_PREFIX . $key );
    }
    
    /**
     * Clear all NavChart cache.
     *
     * @return void
     */
    public static function clear_all() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . '%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . self::CACHE_PREFIX . '%'
            )
        );
    }
}
