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

// Include PhpSpreadsheet
require_once __DIR__ . '/vendor/autoload.php';

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

    public function apply_smoothing( $data, $smoothing = 'none', $smoothing_factor = 3 ) {
        if ( $smoothing === 'none' || empty( $data ) ) {
            return $data;
        }
        
        $count = count( $data );
        if ( $count < 2 ) {
            return $data;
        }
        
        switch ( $smoothing ) {
            case 'linear':
                return $this->apply_moving_average( $data, $smoothing_factor );
                
            case 'poly2':
                return $this->apply_polynomial_smoothing( $data, 2, $smoothing_factor );
                
            case 'poly3':
                return $this->apply_polynomial_smoothing( $data, 3, $smoothing_factor );
                
            case 'poly4':
                return $this->apply_polynomial_smoothing( $data, 4, $smoothing_factor );
                
            default:
                return $data;
        }
    }
    
    private function apply_moving_average( $data, $window_size ) {
        $smoothed = [];
        $count = count( $data );
        
        for ( $i = 0; $i < $count; $i++ ) {
            $sum = 0;
            $window_count = 0;
            
            // Calculate window bounds
            $start = max( 0, $i - floor( $window_size / 2 ) );
            $end = min( $count - 1, $i + floor( $window_size / 2 ) );
            
            // Sum values in window
            for ( $j = $start; $j <= $end; $j++ ) {
                $sum += $data[$j]['value'];
                $window_count++;
            }
            
            $smoothed[] = [
                'date' => $data[$i]['date'],
                'value' => $sum / $window_count,
            ];
        }
        
        return $smoothed;
    }
    
    private function apply_polynomial_smoothing( $data, $degree, $window_size ) {
        $smoothed = [];
        $count = count( $data );
        
        for ( $i = 0; $i < $count; $i++ ) {
            // For polynomial smoothing, use a local window around each point
            $start = max( 0, $i - floor( $window_size / 2 ) );
            $end = min( $count - 1, $i + floor( $window_size / 2 ) );
            
            $window_data = [];
            for ( $j = $start; $j <= $end; $j++ ) {
                $window_data[] = $data[$j]['value'];
            }
            
            // Apply polynomial fit (simplified - using weighted average with polynomial weights)
            $smoothed_value = $this->polynomial_fit( $window_data, $degree );
            
            $smoothed[] = [
                'date' => $data[$i]['date'],
                'value' => $smoothed_value,
            ];
        }
        
        return $smoothed;
    }
    
    private function polynomial_fit( $values, $degree ) {
        $count = count( $values );
        if ( $count === 1 ) {
            return $values[0];
        }
        
        // Improved polynomial smoothing using Gaussian-like weights
        // Higher degree = more aggressive smoothing
        $weights = [];
        $center = floor( $count / 2 );
        $sigma = max( 1, $count / (2 + $degree) ); // Adaptive sigma based on degree
        
        for ( $i = 0; $i < $count; $i++ ) {
            $distance = abs( $i - $center );
            // Gaussian-like weight function that creates better smoothing
            $weight = exp( -pow( $distance / $sigma, 2 ) );
            $weights[] = $weight;
        }
        
        $weighted_sum = 0;
        $weight_sum = 0;
        
        for ( $i = 0; $i < $count; $i++ ) {
            $weighted_sum += $values[$i] * $weights[$i];
            $weight_sum += $weights[$i];
        }
        
        return $weight_sum > 0 ? $weighted_sum / $weight_sum : $values[$center];
    }
}
