<?php
require '/var/www/html/wp-load.php';
flush_rewrite_rules(false);
wp_cache_flush();
foreach (['/about/','/lingerie-for-content-creators/','/returns/'] as $path) echo 'REWRITE '.$path.PHP_EOL;
echo 'DONE'.PHP_EOL;
