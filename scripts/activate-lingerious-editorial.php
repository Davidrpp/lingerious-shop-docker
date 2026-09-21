<?php
/**
 * Manual helper to activate the experimental Lingerious Editorial child theme.
 * Usage inside the WordPress container:
 * php /var/www/html/wp-content/themes/lingerious-editorial/../../../../scripts/activate-lingerious-editorial.php
 * Prefer copying this file to /tmp and running: php /tmp/activate-lingerious-editorial.php
 */

require '/var/www/html/wp-load.php';

$theme = wp_get_theme('lingerious-editorial');

if (! $theme->exists()) {
    fwrite(STDERR, "Theme lingerious-editorial is not installed.\n");
    exit(1);
}

switch_theme('lingerious-editorial');

echo "Activated theme: " . wp_get_theme()->get('Name') . PHP_EOL;
