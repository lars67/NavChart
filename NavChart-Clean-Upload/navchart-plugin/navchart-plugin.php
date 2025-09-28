<?php
/**
 * Plugin Name: NavChart
 * Plugin URI: https://example.com/navchart
 * Description: Displays navigation performance data from Excel as an interactive line chart using Apache ECharts.
 * Version: 1.3.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: navchart
 * Domain Path: /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'NAVCHART_VERSION', '1.3.0-' . time() ); // Cache-busting version
define( 'NAVCHART_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NAVCHART_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NAVCHART_TEXTDOMAIN', 'navchart' );

// Composer autoload is optional for this version
if ( file_exists( NAVCHART_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once NAVCHART_PLUGIN_DIR . 'vendor/autoload.php';
}

// Activation hook.
register_activation_hook( __FILE__, 'navchart_activate' );
function navchart_activate() {
    // Schedule cron if needed for data refresh.
    if ( ! wp_next_scheduled( 'navchart_cron_refresh' ) ) {
        wp_schedule_event( time(), 'hourly', 'navchart_cron_refresh' );
    }
    // Flush rewrite rules if needed.
    flush_rewrite_rules();
}

// Deactivation hook.
register_deactivation_hook( __FILE__, 'navchart_deactivate' );
function navchart_deactivate() {
    wp_clear_scheduled_hook( 'navchart_cron_refresh' );
}

// Init plugin.
add_action( 'plugins_loaded', 'navchart_init' );
function navchart_init() {
    // Load text domain.
    load_plugin_textdomain( NAVCHART_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    require_once NAVCHART_PLUGIN_DIR . 'includes/class-cache.php';
    require_once NAVCHART_PLUGIN_DIR . 'simple-excel-parser.php';
    require_once NAVCHART_PLUGIN_DIR . 'ajax-handler.php';
    require_once NAVCHART_PLUGIN_DIR . 'admin/class-admin.php';

    // Set default Excel path if not configured.
    $options = get_option( 'navchart_options', [] );
    if ( empty( $options['excel_path'] ) ) {
        $default_path = NAVCHART_PLUGIN_DIR . 'data/RoboNav.xlsx';
        if ( file_exists( $default_path ) ) {
            $options['excel_path'] = $default_path;
            update_option( 'navchart_options', $options );
            error_log( 'NavChart: Set default Excel path to ' . $default_path );
        }
    }

    // Init admin if in admin.
    if ( is_admin() ) {
        new \NavChart\Admin\Admin();
    }

    // Enqueue assets for frontend.
    add_action( 'wp_enqueue_scripts', 'navchart_enqueue_assets' );
}

// Enqueue frontend assets.
function navchart_enqueue_assets() {
    // ECharts from CDN.
    wp_enqueue_script( 'echarts', 'https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js', [], '5.4.3', true );

    // Custom JS with cache-busting version.
    wp_enqueue_script( 'navchart-js', NAVCHART_PLUGIN_URL . 'assets/js/navchart.js', [ 'jquery', 'echarts' ], NAVCHART_VERSION, true );

    // Localize script for AJAX.
    wp_localize_script( 'navchart-js', 'navchart_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'navchart_nonce' ),
    ] );

    // Custom CSS.
    wp_enqueue_style( 'navchart-css', NAVCHART_PLUGIN_URL . 'assets/css/navchart.css', [], NAVCHART_VERSION );
}

// Shortcode for chart.
add_shortcode( 'navchart', 'navchart_shortcode' );
function navchart_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'id'        => 'navchart-' . wp_rand( 1000, 9999 ),
        'animation' => 'true',
    ], $atts, 'navchart' );

    $chart_id = sanitize_html_class( $atts['id'] );
    $options = get_option( 'navchart_options', [] );

    // Determine date range based on admin settings
    $range_type = $options['range_type'] ?? 'recent';
    
    if ( $range_type === 'custom' ) {
        $start_date = $options['start_date'] ?? '';
        $end_date = $options['end_date'] ?? '';
        $days_back = null;
    } else {
        $days_back = intval( $options['days_back'] ?? 365 );
        $start_date = '';
        $end_date = '';
    }

    $settings_json = wp_json_encode( [
        'chartId'   => $chart_id,
        'excelPath' => $options['excel_path'] ?? '',
        'title'     => $options['title'] ?? 'Nav Performance',
        'yLabel'    => $options['y_label'] ?? 'FinalNav',
        'smoothing' => $options['smoothing'] ?? 'none',
        'smoothing_factor' => intval( $options['smoothing_factor'] ?? 3 ),
        'animation' => sanitize_text_field( $atts['animation'] ),
        'use_custom_range' => $range_type === 'custom',
        'days_back' => $days_back,
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'height'    => intval( $options['height'] ?? 400 ),
        'yMin'      => intval( $options['y_min'] ?? 90000 ),
        'yMax'      => intval( $options['y_max'] ?? 140000 ),
    ] );

    error_log( 'NavChart Shortcode: Rendering chart ID ' . $chart_id . ' with settings: ' . $settings_json );

    $height = intval( $options['height'] ?? 400 );
    return sprintf(
        '<div id="%s" class="navchart-container" data-settings=\'%s\' style="width: 100%%; height: %dpx;"></div>',
        esc_attr( $chart_id ),
        esc_attr( $settings_json ),
        $height
    );
}

// Cron hook for refresh (stub).
add_action( 'navchart_cron_refresh', 'navchart_refresh_cache' );
function navchart_refresh_cache() {
    // Clear transient.
    delete_transient( 'navchart_data' );
}
