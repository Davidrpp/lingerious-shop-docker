<?php
/**
 * Manual helper to restore the previous Twenty Twenty-Four theme.
 * Prefer copying this file to /tmp inside the WordPress container and running it there.
 */

require '/var/www/html/wp-load.php';

$theme = wp_get_theme('twentytwentyfour');

if (! $theme->exists()) {
    fwrite(STDERR, "Theme twentytwentyfour is not installed.\n");
    exit(1);
}

switch_theme('twentytwentyfour');

echo "Restored theme: " . wp_get_theme()->get('Name') . PHP_EOL;
