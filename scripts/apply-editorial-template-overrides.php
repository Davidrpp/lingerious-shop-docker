<?php
/** Apply recoverable overrides for WooCommerce database-managed block templates. */
require '/var/www/html/wp-load.php';
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--apply') {
    fwrite(STDERR, "Run this script via PHP CLI with --apply.\n");
    exit(2);
}
$theme = get_stylesheet();
if ($theme !== 'lingerious-editorial') {
    fwrite(STDERR, "Unexpected active theme: {$theme}\n");
    exit(2);
}
$items = ['archive-product' => 2490, 'single-product' => 3530, 'page-cart' => 2414];
$records = [];
foreach ($items as $slug => $id) {
    $post = get_post($id);
    $terms = wp_get_post_terms($id, 'wp_theme', ['fields' => 'names']);
    $file = get_stylesheet_directory() . '/templates/' . $slug . '.html';
    if (!$post || $post->post_type !== 'wp_template' || $post->post_name !== $slug || !in_array('woocommerce/woocommerce', $terms, true) || !is_readable($file)) {
        fwrite(STDERR, "Precondition failed for {$slug}; nothing changed.\n");
        exit(3);
    }
    $records[$slug] = ['id' => $id, 'title' => $post->post_title, 'content' => $post->post_content, 'terms' => $terms];
}
$backup = '/tmp/lingerious-wc-template-backup-' . gmdate('Ymd-His') . '.json';
$bytes = file_put_contents($backup, wp_json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
if (!$bytes || filesize($backup) < 1000) {
    fwrite(STDERR, "Backup not created or unexpectedly small.\n");
    exit(4);
}
echo "BACKUP={$backup} BYTES={$bytes}\n";
foreach ($items as $slug => $id) {
    $content = file_get_contents(get_stylesheet_directory() . '/templates/' . $slug . '.html');
    $content = str_replace('"slug":"header"', '"slug":"header","theme":"lingerious-editorial"', $content);
    $content = str_replace('"slug":"footer"', '"slug":"footer","theme":"lingerious-editorial"', $content);
    if (strpos($content, '"theme":"lingerious-editorial"') === false) {
        fwrite(STDERR, "Missing explicit theme reference for {$slug}. Backup: {$backup}\n");
        exit(5);
    }
    $updated = wp_update_post(['ID' => $id, 'post_content' => $content], true);
    if (is_wp_error($updated) || $updated !== $id) {
        fwrite(STDERR, "Failed updating {$slug}; restore from backup {$backup}.\n");
        exit(6);
    }
    clean_post_cache($id);
    echo "UPDATED={$slug} ID={$id} BYTES=" . strlen(get_post($id)->post_content) . "\n";
}
wp_cache_flush();
do_action('litespeed_purge_all');
echo "FINISHED_BACKUP={$backup}\n";
