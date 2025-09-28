# NavChart WordPress Plugin

A WordPress plugin that displays navigation performance data from Excel files as interactive line charts using Apache ECharts.

## Features

- ✅ **Public Access**: Chart visible to all visitors (not just logged-in users)
- ✅ **Fixed X-axis Labels**: Properly spaced, non-overlapping date labels
- ✅ **Two-tone Color Scheme**: Professional sage green background with darker outer area
- ✅ **Excel Data Integration**: Reads data directly from Excel files
- ✅ **Responsive Design**: Works on desktop and mobile devices
- ✅ **Customizable**: Admin panel for configuration
- ✅ **Performance Optimized**: Caching and efficient data loading

## Installation

1. Upload the plugin files to `/wp-content/plugins/NavChart/`
2. Activate the plugin through the WordPress admin panel
3. Configure the Excel file path in the NavChart settings
4. Use the `[navchart]` shortcode to display charts

## Usage

### Basic Shortcode
```
[navchart]
```

### Shortcode with Options
```
[navchart id="my-chart" animation="true"]
```

## Configuration

### Excel File Format
The Excel file should contain:
- **Column A**: Date values (YYYY-MM-DD format)
- **Column D**: Numeric values (FinalNav data)

### Admin Settings
Access the NavChart settings in the WordPress admin panel to configure:
- Excel file path
- Chart title and labels
- Date range settings
- Visual appearance options

## Recent Fixes (v1.3.0)

### X-axis Label Overlapping Issue - FIXED
- Implemented intelligent label interval calculation
- Shows maximum 6 labels for optimal readability
- Labels are properly spaced and rotated 45 degrees
- No more overlapping or unreadable date labels

### Public Access Issue - FIXED
- Chart now visible to all website visitors
- Removed authentication requirements for chart data
- Maintains security while allowing public access

### Two-tone Color Scheme - IMPLEMENTED
- Darker sage green outer background (`rgba(122, 155, 122, 0.8)`)
- Lighter sage green inner plotting area (`rgba(168, 181, 160, 0.3)`)
- Professional appearance with clear visual hierarchy

## Technical Details

### Files Structure
```
NavChart/
├── navchart-plugin.php          # Main plugin file
├── ajax-handler.php             # AJAX request handler
├── simple-excel-parser.php      # Excel file parser
├── admin/
│   └── class-admin.php          # Admin interface
├── assets/
│   ├── js/
│   │   └── navchart.js          # Frontend JavaScript
│   └── css/
│       └── navchart.css         # Plugin styles
├── includes/
│   └── class-cache.php          # Cache management
└── README.md                    # This file
```

### Dependencies
- WordPress 5.0+
- PHP 7.4+
- jQuery (included with WordPress)
- ECharts 5.4.3 (loaded from CDN)

### Browser Support
- Chrome/Edge 88+
- Firefox 85+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Chart Not Loading
1. Check that the Excel file path is correct in settings
2. Verify the Excel file format (Date in column A, values in column D)
3. Check browser console for JavaScript errors
4. Clear WordPress cache if using caching plugins

### X-axis Labels Still Overlapping
1. Clear browser cache (Ctrl+F5)
2. Check that the plugin version is 1.3.0 or higher
3. Verify that the JavaScript file has been updated

### Performance Issues
1. Enable caching in the plugin settings
2. Optimize Excel file size (remove unnecessary data)
3. Consider using a CDN for faster loading

## Support

For issues or questions:
1. Check the browser console for error messages
2. Verify Excel file format and content
3. Test with a smaller dataset first
4. Check WordPress error logs

## Changelog

### Version 1.3.0 (Latest)
- **FIXED**: X-axis label overlapping issue
- **FIXED**: Public access for non-logged-in users
- **ADDED**: Two-tone color scheme
- **IMPROVED**: Cache-busting for JavaScript updates
- **IMPROVED**: Error handling and logging

### Version 1.2.4
- Basic chart functionality
- Excel file integration
- Admin panel configuration

## License

GPL v2 or later
