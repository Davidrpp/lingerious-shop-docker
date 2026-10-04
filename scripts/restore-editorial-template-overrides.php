<?php
/** Restore the three WooCommerce custom templates from an apply-script JSON backup. */
require '/var/www/html/wp-load.php';
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '--restore' || !is_readable($argv[2] ?? '')) {
    fwrite(STDERR, "Usage: php restore-editorial-template-overrides.php --restore /tmp/backup.json\n");
    exit(2);
}
$items = json_decode(file_get_contents($argv[2]), true);
$ids = ['archive-product' => 2490, 'single-product' => 3530, 'page-cart' => 2414];
if (!is_array($items) || count($items) !== count($ids)) {
    fwrite(STDERR, "Unexpected backup content.\n");
    exit(3);
}
foreach ($ids as $slug => $id) {
    $record = $items[$slug] ?? null;
    $post = get_post($id);
    if (!$record || ($record['id'] ?? 0) !== $id || !is_string($record['content'] ?? null) || !$post || $post->post_name !== $slug) {
        fwrite(STDERR, "Validation failed for {$slug}; no changes made.\n");
        exit(4);
    }
}
foreach ($ids as $slug => $id) {
    $result = wp_update_post(['ID' => $id, 'post_content' => $items[$slug]['content']], true);
    if (is_wp_error($result) || $result !== $id) {
        fwrite(STDERR, "Restore failed for {$slug}.\n");
        exit(5);
    }
    clean_post_cache($id);
    echo "RESTORED={$slug}\n";
}
wp_cache_flush();
do_action('litespeed_purge_all');
