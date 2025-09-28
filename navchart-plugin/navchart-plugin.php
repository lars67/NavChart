<?php
/**
 * Plugin Name: NavChart
 * Plugin URI: https://example.com/navchart
 * Description: Displays navigation performance data from Excel as an interactive line chart using Apache ECharts.
 * Version: 1.2.0
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
define( 'NAVCHART_VERSION', '1.2.0' );
define( 'NAVCHART_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NAVCHART_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NAVCHART_TEXTDOMAIN', 'navchart' );

// Require Composer autoload.
if ( file_exists( NAVCHART_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once NAVCHART_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'NavChart requires Composer dependencies. Please run composer install.', 'navchart' ) . '</p></div>';
    } );
    return;
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
    require_once NAVCHART_PLUGIN_DIR . 'includes/simple-excel-parser.php';
    require_once NAVCHART_PLUGIN_DIR . 'includes/ajax-handler.php';
    require_once NAVCHART_PLUGIN_DIR . 'admin/Admin.php';

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

    // Custom JS.
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
        'days_back' => 9999,
        'smoothing' => 'none',
        'animation' => 'true',
    ], $atts, 'navchart' );

    // Get options.
    $options = get_option( 'navchart_options', [] );
    $excel_path = $options['excel_path'] ?? '';

    error_log( 'NavChart Shortcode: Options loaded, Excel path: ' . $excel_path );

    if ( empty( $excel_path ) ) {
        $default_path = NAVCHART_PLUGIN_DIR . 'RoboNav.xlsx';
        if ( file_exists( $default_path ) ) {
            $excel_path = $default_path;
            error_log( 'NavChart Shortcode: Using default Excel path: ' . $default_path );
        } else {
            error_log( 'NavChart Shortcode: No Excel path configured and default not found.' );
            return '<p>' . esc_html__( 'No Excel file configured. Please set in settings.', 'navchart' ) . '</p>';
        }
    }

    if ( ! file_exists( $excel_path ) ) {
        error_log( 'NavChart Shortcode: Excel file does not exist at path: ' . $excel_path );
        return '<p>' . esc_html__( 'Excel file not found. Check the path in settings.', 'navchart' ) . '</p>';
    }

    // Output chart div with data attributes.
    $chart_id = 'navchart-' . uniqid();
    $settings_json = json_encode( [
        'title'     => $options['title'] ?? 'Robofunds Global A/S',
        'y_label'   => $options['y_label'] ?? 'FinalNav',
        'smoothing' => sanitize_text_field( $atts['smoothing'] ),
        'animation' => sanitize_text_field( $atts['animation'] ),
        'days_back' => intval( $atts['days_back'] ),
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

// AJAX handler - ONLY use real Excel data, NO fallback
add_action( 'wp_ajax_navchart_data', 'navchart_ajax_get_data' );
add_action( 'wp_ajax_nopriv_navchart_data', 'navchart_ajax_get_data' );
function navchart_ajax_get_data() {
    // Load the AJAX handler that reads real Excel data
    require_once NAVCHART_PLUGIN_DIR . 'includes/ajax-handler.php';
    
    // Call the real AJAX handler function with correct namespace
    \NavChart\navchart_ajax_get_data();
}

// Cron hook for refresh (stub).
add_action( 'navchart_cron_refresh', 'navchart_refresh_cache' );
function navchart_refresh_cache() {
    // Clear transient.
    delete_transient( 'navchart_data' );
}