<?php
/**
 * NavChart AJAX Handler
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
 * AJAX handler for NavChart data.
 */
function navchart_ajax_get_data() {
    error_log( 'NavChart AJAX: Request received.' );

    // Verify nonce for security
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'navchart_nonce' ) ) {
        error_log( 'NavChart AJAX: Nonce verification failed.' );
        wp_send_json_error( 'Security check failed. Please refresh the page and try again.' );
    }

    $options = get_option( 'navchart_options', [] );
    $excel_path = $options['excel_path'] ?? '';

    error_log( 'NavChart AJAX: Options loaded, Excel path: ' . $excel_path );

    $days_back = intval( $_POST['days_back'] ?? 365 );
    $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
    $end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
    $smoothing = sanitize_text_field( $_POST['smoothing'] ?? 'none' );
    $smoothing_factor = intval( $_POST['smoothing_factor'] ?? 3 );

    error_log( 'NavChart AJAX: Parameters - days_back: ' . $days_back . ', smoothing: ' . $smoothing );

    if ( empty( $excel_path ) ) {
        error_log( 'NavChart AJAX: No Excel file path configured.' );
        wp_send_json_error( 'No Excel file configured. Please set the Excel file path in NavChart settings.' );
    }

    if ( ! file_exists( $excel_path ) ) {
        error_log( 'NavChart AJAX: Excel file not found at: ' . $excel_path );
        wp_send_json_error( 'Excel file not found at: ' . $excel_path . '. Please check the file path in NavChart settings.' );
    }

    try {
        error_log( 'NavChart AJAX: Loading data from Excel file using simplified parser: ' . $excel_path );
        
        $parser = new \SimpleExcelParser( $excel_path );
        $filtered_data = $parser->get_filtered_data( $days_back, $start_date, $end_date );
        
        $smoothed_data = $parser->apply_smoothing( $filtered_data, $smoothing, $smoothing_factor );
        
        if ( empty( $smoothed_data ) ) {
            error_log( 'NavChart AJAX: No data found in Excel file after filtering.' );
            wp_send_json_error( 'No data found in Excel file. Please check that the file contains valid Date and FinalNav data in columns A and D.' );
        }
        
        $dates = array_column( $smoothed_data, 'date' );
        $values = array_column( $smoothed_data, 'value' );
        
        error_log( 'NavChart AJAX: Successfully loaded ' . count( $dates ) . ' data points from Excel file.' );
        
        wp_send_json_success( [
            'dates' => $dates,
            'values' => $values,
        ] );
        
    } catch ( \Exception $e ) {
        error_log( 'NavChart AJAX: Excel parsing error: ' . $e->getMessage() );
        wp_send_json_error( 'Error reading Excel file: ' . $e->getMessage() . '. File: ' . $excel_path . '. Please check the file format and content.' );
    }
}

// Hook the AJAX actions
add_action( 'wp_ajax_navchart_data', __NAMESPACE__ . '\navchart_ajax_get_data' );
add_action( 'wp_ajax_nopriv_navchart_data', __NAMESPACE__ . '\navchart_ajax_get_data' );
