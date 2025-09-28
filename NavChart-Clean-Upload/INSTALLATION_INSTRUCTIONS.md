# NavChart Plugin Fix - Installation Instructions

## Files Included
- `navchart-plugin.php` - Main plugin file (fixed)
- `ajax-handler.php` - AJAX handler (fixed)
- `navchart_fixes_summary.md` - Summary of all changes made

## Installation Steps

### 1. Backup Your Current Plugin
Before making any changes, backup your current NavChart plugin directory.

### 2. Replace Files
Copy the following files to your NavChart plugin directory:
- Replace `navchart-plugin.php` with the fixed version
- Replace `ajax-handler.php` with the fixed version

### 3. Directory Structure Changes
**IMPORTANT:** You need to make these directory changes:

1. **Rename the `includes` directory** to `includes_old`
   ```
   mv includes includes_old
   ```

2. **Create a new `includes` directory**
   ```
   mkdir includes
   ```

3. **Copy only the cache class** from the old includes:
   ```
   cp includes_old/class-cache.php includes/
   ```

### 4. File Permissions
Ensure the files have proper permissions:
```bash
chmod 644 navchart-plugin.php
chmod 644 ajax-handler.php
chmod 755 includes/
chmod 644 includes/class-cache.php
```

### 5. Test the Plugin
1. Go to your WordPress admin
2. Navigate to the NavChart page: `/navchart/`
3. Verify that the chart displays all historical data from April 2024 onwards

## Key Fixes Applied

1. **Fixed Parser Conflicts** - Resolved conflicts between old and new Excel parsers
2. **Removed Data Limitations** - Changed default from 30 days to 9999 days to show all data
3. **Fixed AJAX Issues** - Corrected nonce validation and parameter handling
4. **Resolved Function Conflicts** - Prevented function redeclaration errors

## Troubleshooting

If you encounter issues:
1. Check WordPress error logs
2. Ensure Excel file path is correct in NavChart settings
3. Verify file permissions are correct
4. Make sure the directory structure changes were applied correctly

## Support
If you need help, refer to the `navchart_fixes_summary.md` file for detailed information about what was changed and why.