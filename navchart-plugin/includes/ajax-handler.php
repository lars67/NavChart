<?php
/**
 * NavChart AJAX Handler - NEW VERSION
 *
 * Handles AJAX requests for chart data using simplified Excel parser.
 *
 * @package NavChart
 */

namespace NavChart;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include the simplified Excel parser
require_once __DIR__ . '/simple-excel-parser.php';

/**
 * NEW AJAX handler for NavChart data.
 */
function navchart_ajax_get_data_new() {
    error_log( 'NavChart NEW AJAX: Request received.' );

    // Verify nonce.
    $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? $_REQUEST['nonce'] ?? '';
    if ( ! wp_verify_nonce( $nonce, 'navchart_nonce' ) ) {
        error_log( 'NavChart NEW AJAX: Invalid nonce. Received: ' . $nonce );
        wp_send_json_error( 'Invalid nonce.' );
    }

    // Capability check (for logged-in users).
    if ( ! current_user_can( 'read' ) ) {
        error_log( 'NavChart NEW AJAX: Insufficient permissions.' );
        wp_send_json_error( 'Insufficient permissions.' );
    }

    $options = get_option( 'navchart_options', [] );
    $excel_path = $options['excel_path'] ?? '';

    error_log( 'NavChart NEW AJAX: Options loaded, Excel path: ' . $excel_path );

    // Use ALL data from Excel file by default (365 days should cover all data)
    $days_back = intval( $_POST['days_back'] ?? $_GET['days_back'] ?? $_REQUEST['days_back'] ?? 365 );
    $start_date = sanitize_text_field( $_POST['start_date'] ?? $_GET['start_date'] ?? $_REQUEST['start_date'] ?? '' );
    $end_date = sanitize_text_field( $_POST['end_date'] ?? $_GET['end_date'] ?? $_REQUEST['end_date'] ?? '' );
    $smoothing = sanitize_text_field( $_POST['smoothing'] ?? $_GET['smoothing'] ?? $_REQUEST['smoothing'] ?? 'none' );

    error_log( 'NavChart NEW AJAX: Parameters - days_back: ' . $days_back . ', smoothing: ' . $smoothing );

    // ONLY use actual Excel data - NO FALLBACK DATA
    if ( empty( $excel_path ) ) {
        error_log( 'NavChart NEW AJAX: No Excel file path configured.' );
        wp_send_json_error( 'NEW VERSION: No Excel file configured. Please set the Excel file path in NavChart settings.' );
    }

    if ( ! file_exists( $excel_path ) ) {
        error_log( 'NavChart NEW AJAX: Excel file not found at: ' . $excel_path );
        wp_send_json_error( 'NEW VERSION: Excel file not found at: ' . $excel_path . '. Please check the file path in NavChart settings.' );
    }

    try {
        error_log( 'NavChart NEW AJAX: Loading data from Excel file using simplified parser: ' . $excel_path );
        
        // Use the simplified Excel parser
        $parser = new \SimpleExcelParser( $excel_path );
        $filtered_data = $parser->get_filtered_data( $days_back, $start_date, $end_date );
        $smoothed_data = $parser->apply_smoothing( $filtered_data, $smoothing );
        
        if ( empty( $smoothed_data ) ) {
            error_log( 'NavChart NEW AJAX: No data found in Excel file after filtering.' );
            wp_send_json_error( 'NEW VERSION: No data found in Excel file. Please check that the file contains valid Date and FinalNav data in columns A and D.' );
        }
        
        // Prepare for ECharts: separate dates and values.
        $dates = array_column( $smoothed_data, 'date' );
        $values = array_column( $smoothed_data, 'value' );
        
        error_log( 'NavChart NEW AJAX: Successfully loaded ' . count( $dates ) . ' data points from Excel file.' );
        error_log( 'NavChart NEW AJAX: Date range: ' . $dates[0] . ' to ' . end( $dates ) );
        error_log( 'NavChart NEW AJAX: Value range: ' . min( $values ) . ' to ' . max( $values ) );
        
        wp_send_json_success( [
            'dates' => $dates,
            'values' => $values,
        ] );
        
    } catch ( \Exception $e ) {
        error_log( 'NavChart NEW AJAX: Excel parsing error: ' . $e->getMessage() );
        wp_send_json_error( 'NEW VERSION: Error reading Excel file: ' . $e->getMessage() . '. File: ' . $excel_path . '. Please check the file format and content.' );
    }
}

/**
 * Original AJAX handler for NavChart data.
 */
function navchart_ajax_get_data() {
    // Just call the new version
    navchart_ajax_get_data_new();
}

// Hook the AJAX actions - both old and new
add_action( 'wp_ajax_navchart_data', __NAMESPACE__ . '\navchart_ajax_get_data' );
add_action( 'wp_ajax_nopriv_navchart_data', __NAMESPACE__ . '\navchart_ajax_get_data' );
add_action( 'wp_ajax_navchart_data_new', __NAMESPACE__ . '\navchart_ajax_get_data_new' );
add_action( 'wp_ajax_nopriv_navchart_data_new', __NAMESPACE__ . '\navchart_ajax_get_data_new' );