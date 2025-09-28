<?php
/**
 * NavChart Admin Class
 *
 * Handles admin functionality: settings page, menu, assets.
 *
 * @package NavChart\Admin
 */

namespace NavChart\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'settings_init' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'NavChart Settings', 'navchart' ),
            __( 'NavChart', 'navchart' ),
            'manage_options',
            'navchart',
            [ $this, 'settings_page' ]
        );
    }

    /**
     * Initialize settings.
     */
    public function settings_init() {
        register_setting( 'navchart', 'navchart_options', [ $this, 'sanitize_options' ] );

        add_settings_section(
            'navchart_section',
            __( 'Chart Configuration', 'navchart' ),
            null,
            'navchart'
        );

        // Excel File field.
        add_settings_field(
            'navchart_excel_path',
            __( 'Excel File Path/Upload', 'navchart' ),
            [ $this, 'excel_path_field' ],
            'navchart',
            'navchart_section'
        );

        // Date Range.
        add_settings_field(
            'navchart_date_range',
            __( 'Date Range', 'navchart' ),
            [ $this, 'date_range_field' ],
            'navchart',
            'navchart_section'
        );

        // Smoothing.
        add_settings_field(
            'navchart_smoothing',
            __( 'Smoothing', 'navchart' ),
            [ $this, 'smoothing_field' ],
            'navchart',
            'navchart_section'
        );

        // Animation.
        add_settings_field(
            'navchart_animation',
            __( 'Animation', 'navchart' ),
            [ $this, 'animation_field' ],
            'navchart',
            'navchart_section'
        );

        // Title and Y Label.
        add_settings_field(
            'navchart_labels',
            __( 'Labels', 'navchart' ),
            [ $this, 'labels_field' ],
            'navchart',
            'navchart_section'
        );

        // Chart Height.
        add_settings_field(
            'navchart_height',
            __( 'Chart Height (pixels)', 'navchart' ),
            [ $this, 'height_field' ],
            'navchart',
            'navchart_section'
        );

        // Cache Duration.
        add_settings_field(
            'navchart_cache_duration',
            __( 'Cache Duration (minutes)', 'navchart' ),
            [ $this, 'cache_duration_field' ],
            'navchart',
            'navchart_section'
        );
    }

    /**
     * Sanitize and process options, including file upload.
     */
    public function sanitize_options( $input ) {
        $options = get_option( 'navchart_options', [] );
        $sanitized = [];

        // Excel path.
        $sanitized['excel_path'] = sanitize_text_field( $input['excel_path'] ?? $options['excel_path'] ?? '' );

        // Handle file upload.
        if ( ! empty( $_FILES['navchart_excel_upload']['name'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            $uploaded = wp_handle_upload( $_FILES['navchart_excel_upload'], [ 'test_form' => false ] );
            if ( ! isset( $uploaded['error'] ) ) {
                $sanitized['excel_path'] = $uploaded['file'];
                // Clear cache on new upload.
                $cache = new \NavChart\Cache();
                $cache->clear_all();
            } else {
                add_settings_error( 'navchart_options', 'upload_error', $uploaded['error'], 'error' );
            }
        }

        // Other fields.
        $sanitized['range_type'] = sanitize_text_field( $input['range_type'] ?? $options['range_type'] ?? 'recent' );
        $sanitized['days_back'] = intval( $input['days_back'] ?? $options['days_back'] ?? 30 );
        $sanitized['start_date'] = sanitize_text_field( $input['start_date'] ?? $options['start_date'] ?? '' );
        $sanitized['end_date'] = sanitize_text_field( $input['end_date'] ?? $options['end_date'] ?? '' );
        $sanitized['smoothing'] = sanitize_text_field( $input['smoothing'] ?? $options['smoothing'] ?? 'none' );
        $sanitized['smoothing_factor'] = intval( $input['smoothing_factor'] ?? $options['smoothing_factor'] ?? 3 );
        $sanitized['animation'] = sanitize_text_field( $input['animation'] ?? $options['animation'] ?? 'true' );
        $sanitized['anim_type'] = sanitize_text_field( $input['anim_type'] ?? $options['anim_type'] ?? 'linear' );
        $sanitized['title'] = sanitize_text_field( $input['title'] ?? $options['title'] ?? 'Nav Performance' );
        $sanitized['y_label'] = sanitize_text_field( $input['y_label'] ?? $options['y_label'] ?? 'FinalNav' );
        $sanitized['height'] = intval( $input['height'] ?? $options['height'] ?? 400 );
        $sanitized['cache_duration'] = intval( $input['cache_duration'] ?? $options['cache_duration'] ?? 60 );

        return $sanitized;
    }

    /**
     * Excel path/upload field.
     */
    public function excel_path_field() {
        $options = get_option( 'navchart_options', [] );
        $path = $options['excel_path'] ?? '';
        $default_path = NAVCHART_PLUGIN_DIR . 'RoboNav.xlsx';
        ?>
        <input type="text" name="navchart_options[excel_path]" value="<?php echo esc_attr( $path ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $default_path ); ?>" />
        <p class="description"><?php esc_html_e( 'Full server path to the Excel file. Defaults to bundled RoboNav.xlsx in plugin root. Or upload a new one.', 'navchart' ); ?></p>
        <input type="file" name="navchart_excel_upload" accept=".xlsx" />
        <?php
    }

    /**
     * Date range field.
     */
    public function date_range_field() {
        $options = get_option( 'navchart_options', [] );
        $range_type = $options['range_type'] ?? 'recent';
        $days_back = $options['days_back'] ?? 30;
        $start_date = $options['start_date'] ?? '';
        $end_date = $options['end_date'] ?? '';
        ?>
        <label>
            <input type="radio" name="navchart_options[range_type]" value="recent" <?php checked( $range_type, 'recent' ); ?> />
            <?php esc_html_e( 'Recent Days Back', 'navchart' ); ?>
            <input type="number" name="navchart_options[days_back]" value="<?php echo esc_attr( $days_back ); ?>" min="1" max="365" class="small-text" />
        </label><br />
        <label>
            <input type="radio" name="navchart_options[range_type]" value="custom" <?php checked( $range_type, 'custom' ); ?> />
            <?php esc_html_e( 'Custom Range', 'navchart' ); ?>
            <input type="date" name="navchart_options[start_date]" value="<?php echo esc_attr( $start_date ); ?>" />
            to
            <input type="date" name="navchart_options[end_date]" value="<?php echo esc_attr( $end_date ); ?>" />
        </label>
        <?php
    }

    /**
     * Smoothing field.
     */
    public function smoothing_field() {
        $options = get_option( 'navchart_options', [] );
        $smoothing = $options['smoothing'] ?? 'none';
        $smoothing_factor = $options['smoothing_factor'] ?? 3;
        ?>
        <select name="navchart_options[smoothing]">
            <option value="none" <?php selected( $smoothing, 'none' ); ?>><?php esc_html_e( 'None', 'navchart' ); ?></option>
            <option value="linear" <?php selected( $smoothing, 'linear' ); ?>><?php esc_html_e( 'Linear (Moving Average)', 'navchart' ); ?></option>
            <option value="poly2" <?php selected( $smoothing, 'poly2' ); ?>><?php esc_html_e( '2nd Degree Polynomial', 'navchart' ); ?></option>
            <option value="poly3" <?php selected( $smoothing, 'poly3' ); ?>><?php esc_html_e( '3rd Degree Polynomial', 'navchart' ); ?></option>
            <option value="poly4" <?php selected( $smoothing, 'poly4' ); ?>><?php esc_html_e( '4th Degree Polynomial', 'navchart' ); ?></option>
        </select><br />
        <label><?php esc_html_e( 'Smoothing Factor (Window Size):', 'navchart' ); ?>
            <input type="number" name="navchart_options[smoothing_factor]" value="<?php echo esc_attr( $smoothing_factor ); ?>" min="1" max="21" step="2" class="small-text" />
        </label>
        <p class="description"><?php esc_html_e( 'Apply smoothing to the line chart. Higher smoothing factor = more smoothing (use odd numbers: 3, 5, 7, etc.).', 'navchart' ); ?></p>
        <?php
    }

    /**
     * Animation field.
     */
    public function animation_field() {
        $options = get_option( 'navchart_options', [] );
        $animation = $options['animation'] ?? 'true';
        $anim_type = $options['anim_type'] ?? 'linear';
        ?>
        <label>
            <input type="checkbox" name="navchart_options[animation]" value="true" <?php checked( $animation, 'true' ); ?> />
            <?php esc_html_e( 'Enable Animation', 'navchart' ); ?>
        </label><br />
        <select name="navchart_options[anim_type]" <?php disabled( $animation !== 'true' ); ?>>
            <option value="linear" <?php selected( $anim_type, 'linear' ); ?>><?php esc_html_e( 'Linear', 'navchart' ); ?></option>
            <option value="bounce" <?php selected( $anim_type, 'bounce' ); ?>><?php esc_html_e( 'Bounce', 'navchart' ); ?></option>
            <option value="elastic" <?php selected( $anim_type, 'elastic' ); ?>><?php esc_html_e( 'Elastic', 'navchart' ); ?></option>
            <option value="fade" <?php selected( $anim_type, 'fade' ); ?>><?php esc_html_e( 'Fade', 'navchart' ); ?></option>
        </select>
        <p class="description"><?php esc_html_e( 'Animation type for chart rendering.', 'navchart' ); ?></p>
        <?php
    }

    /**
     * Labels field.
     */
    public function labels_field() {
        $options = get_option( 'navchart_options', [] );
        $title = $options['title'] ?? 'Nav Performance';
        $y_label = $options['y_label'] ?? 'FinalNav';
        ?>
        <label><?php esc_html_e( 'Chart Title:', 'navchart' ); ?>
            <input type="text" name="navchart_options[title]" value="<?php echo esc_attr( $title ); ?>" class="regular-text" />
        </label><br />
        <label><?php esc_html_e( 'Y-Axis Label:', 'navchart' ); ?>
            <input type="text" name="navchart_options[y_label]" value="<?php echo esc_attr( $y_label ); ?>" class="regular-text" />
        </label>
        <?php
    }

    /**
     * Cache duration field.
     */
    public function cache_duration_field() {
        $options = get_option( 'navchart_options', [] );
        $cache_duration = $options['cache_duration'] ?? 60;
        ?>
        <input type="number" name="navchart_options[cache_duration]" value="<?php echo esc_attr( $cache_duration ); ?>" min="1" class="small-text" />
        <p class="description"><?php esc_html_e( 'Minutes to cache parsed data (0 to disable).', 'navchart' ); ?></p>
        <?php
    }

    /**
     * Chart height field.
     */
    public function height_field() {
        $options = get_option( 'navchart_options', [] );
        $height = $options['height'] ?? 400;
        ?>
        <input type="number" name="navchart_options[height]" value="<?php echo esc_attr( $height ); ?>" min="200" max="1000" step="50" class="small-text" />
        <p class="description"><?php esc_html_e( 'Chart height in pixels (200-1000px).', 'navchart' ); ?></p>
        <?php
    }

    /**
     * Settings page output.
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'NavChart Settings', 'navchart' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'navchart' );
                do_settings_sections( 'navchart' );
                submit_button();
                ?>
            </form>
            <h2><?php esc_html_e( 'Preview', 'navchart' ); ?></h2>
            <div id="navchart-preview" style="width: 100%; height: 400px;"></div>
            <script>
                jQuery(document).ready(function($) {
                    // Enqueue ECharts for preview.
                    if (typeof echarts === 'undefined') {
                        $.getScript('https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js', function() {
                            initPreview();
                        });
                    } else {
                        initPreview();
                    }
                });
                function initPreview() {
                    var chart = echarts.init(document.getElementById('navchart-preview'));
                    // Fetch sample data via AJAX for preview.
                    $.post(ajaxurl, {
                        action: 'navchart_data',
                        nonce: '<?php echo wp_create_nonce( 'navchart_nonce' ); ?>',
                        days_back: 30,
                        smoothing: $('select[name="navchart_options[smoothing]"]').val()
                    }, function(response) {
                        if (response.success) {
                            var option = {
                                title: { text: '<?php echo esc_js( get_option( 'navchart_options' )['title'] ?? 'Nav Performance' ); ?>' },
                                tooltip: { trigger: 'axis' },
                                legend: { data: ['FinalNav'] },
                                xAxis: { type: 'category', data: response.data.dates },
                                yAxis: { type: 'value', name: '<?php echo esc_js( get_option( 'navchart_options' )['y_label'] ?? 'FinalNav' ); ?>' },
                                series: [{
                                    name: 'FinalNav',
                                    type: 'line',
                                    data: response.data.values,
                                    smooth: $('select[name="navchart_options[smoothing]"]').val() !== 'none'
                                }]
                            };
                            chart.setOption(option);
                        }
                    });
                }
            </script>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_navchart' !== $hook ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'echarts', 'https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js', [], '5.4.3', true );
        wp_localize_script( 'echarts', 'navchart_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'navchart_nonce' ),
        ] );
    }
}