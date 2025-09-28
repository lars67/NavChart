=== NavChart ===
Contributors: yourname
Tags: chart, echarts, excel, performance, navigation
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display navigation performance data from Excel files as interactive line charts using Apache ECharts.

== Description ==

NavChart is a WordPress plugin that reads data from an Excel file (columns: Date, FinalNav) and renders an interactive line chart using Apache ECharts. Features include:

* Polynomial smoothing (up to 4th degree)
* Various animations (linear, bounce, elastic, fade)
* Date range filtering (start/end dates or n days back)
* Responsive design with tooltips, legends, zoom/pan
* Export to PNG
* Admin settings for configuration and file upload
* Caching for performance

Upload your Excel file via settings, embed [navchart] shortcode in posts/pages.

== Installation ==

1. Upload the `navchart-plugin` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > NavChart to configure: upload Excel, set options.
4. Use shortcode `[navchart]` or `[navchart days_back="60" smoothing="poly4"]` to display.

Requires PHP 7.4+ with extensions: zip, gd, xml, curl.

== Frequently Asked Questions ==

= How do I add my Excel data? =
Upload via settings or place in /wp-content/uploads/ and enter path.

= What smoothing options are available? =
None, linear (moving average), 2nd-4th degree polynomial.

= Can I customize the chart? =
Yes, via settings: title, y-label, animation type, cache duration.

== Screenshots ==

1. Admin settings page.
2. Frontend line chart example.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial version. Backup your settings.