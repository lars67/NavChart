<?php
/**
 * Simple Excel Parser for NavChart - FINAL VERSION
 *
 * Reads Excel files and extracts Date and FinalNav data.
 *
 * @package NavChart
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'NAVCHART_PLUGIN_DIR' ) ) {
    define( 'NAVCHART_PLUGIN_DIR', dirname( __DIR__ ) );
}
// Include PhpSpreadsheet
require_once NAVCHART_PLUGIN_DIR . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class SimpleExcelParser {
    private $file_path;
    private $data = [];

    public function __construct( $file_path ) {
        $this->file_path = $file_path;
        $this->load_data();
    }

    private function load_data() {
        try {
            error_log( 'SimpleExcelParser: Loading file: ' . $this->file_path );
            
            $spreadsheet = IOFactory::load( $this->file_path );
            $worksheet = $spreadsheet->getActiveSheet();
            $raw_data = $worksheet->toArray();
            
            error_log( 'SimpleExcelParser: Raw data rows: ' . count( $raw_data ) );
            
            // Process each row starting from row 2 (skip header)
            for ( $i = 1; $i < count( $raw_data ); $i++ ) {
                $row = $raw_data[$i];
                
                // Column A = Date, Column D = FinalNav (index 3)
                $date_value = $row[0] ?? null;
                $final_nav_value = $row[3] ?? null;
                
                // Skip empty rows
                if ( empty( $date_value ) || empty( $final_nav_value ) ) {
                    continue;
                }
                
                // Parse date - handle multiple formats
                $date = $this->parse_date( $date_value );
                if ( ! $date ) {
                    continue;
                }
                
                // Parse FinalNav value - remove commas and convert to float
                $value = $this->parse_value( $final_nav_value );
                if ( $value === null ) {
                    continue;
                }
                
                $this->data[] = [
                    'date' => $date,
                    'value' => $value,
                ];
            }
            
            error_log( 'SimpleExcelParser: Processed ' . count( $this->data ) . ' valid data points' );
            
            if ( ! empty( $this->data ) ) {
                error_log( 'SimpleExcelParser: Date range: ' . $this->data[0]['date'] . ' to ' . end( $this->data )['date'] );
                $values = array_column( $this->data, 'value' );
                error_log( 'SimpleExcelParser: Value range: ' . min( $values ) . ' to ' . max( $values ) );
            }
            
        } catch ( Exception $e ) {
            error_log( 'SimpleExcelParser: Error loading Excel file: ' . $e->getMessage() );
            throw new Exception( 'Failed to load Excel file: ' . $e->getMessage() );
        }
    }

    private function parse_date( $date_value ) {
        // Handle different date formats
        if ( is_numeric( $date_value ) ) {
            // Excel serial date
            $unix_date = ( $date_value - 25569 ) * 86400;
            return date( 'Y-m-d', $unix_date );
        }
        
        if ( is_string( $date_value ) ) {
            // Try different string formats
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d',
                'Y/m/d',
                'm/d/Y',
                'd/m/Y',
                'Y-m-d\TH:i:s',
            ];
            
            foreach ( $formats as $format ) {
                $parsed = DateTime::createFromFormat( $format, $date_value );
                if ( $parsed ) {
                    return $parsed->format( 'Y-m-d' );
                }
            }
            
            // Try strtotime as fallback
            $timestamp = strtotime( $date_value );
            if ( $timestamp ) {
                return date( 'Y-m-d', $timestamp );
            }
        }
        
        return null;
    }

    private function parse_value( $value ) {
        if ( is_numeric( $value ) ) {
            return (float) $value;
        }
        
        if ( is_string( $value ) ) {
            // Remove commas and convert to float
            $cleaned = str_replace( ',', '', $value );
            if ( is_numeric( $cleaned ) ) {
                return (float) $cleaned;
            }
        }
        
        return null;
    }

    public function get_filtered_data( $days_back = 365, $start_date = '', $end_date = '' ) {
        $filtered = $this->data;
        
        // Apply date filtering if specified
        if ( ! empty( $start_date ) || ! empty( $end_date ) || $days_back > 0 ) {
            $filtered = array_filter( $filtered, function( $item ) use ( $days_back, $start_date, $end_date ) {
                $item_date = $item['date'];
                
                // Filter by start date
                if ( ! empty( $start_date ) && $item_date < $start_date ) {
                    return false;
                }
                
                // Filter by end date
                if ( ! empty( $end_date ) && $item_date > $end_date ) {
                    return false;
                }
                
                // Filter by days back
                if ( $days_back > 0 ) {
                    $cutoff_date = date( 'Y-m-d', strtotime( "-{$days_back} days" ) );
                    if ( $item_date < $cutoff_date ) {
                        return false;
                    }
                }
                
                return true;
            } );
        }
        
        return array_values( $filtered );
    }

    public function apply_smoothing( $data, $smoothing = 'none' ) {
        if ( $smoothing === 'none' || empty( $data ) ) {
            return $data;
        }
        
        // Simple moving average smoothing
        if ( $smoothing === 'smooth' ) {
            $window = 3;
            $smoothed = [];
            
            for ( $i = 0; $i < count( $data ); $i++ ) {
                $sum = 0;
                $count = 0;
                
                for ( $j = max( 0, $i - $window + 1 ); $j <= min( count( $data ) - 1, $i + $window - 1 ); $j++ ) {
                    $sum += $data[$j]['value'];
                    $count++;
                }
                
                $smoothed[] = [
                    'date' => $data[$i]['date'],
                    'value' => $sum / $count,
                ];
            }
            
            return $smoothed;
        }
        
        return $data;
    }
}