<?php
/** One-time cleanup: block theme already renders the page title as H1. */
require '/var/www/html/wp-load.php';
foreach ([2405 => 'About Lingerious', 2705 => 'Shipping & Returns'] as $id => $heading) {
    $post = get_post($id);
    if (!$post || $post->post_status !== 'publish') throw new RuntimeException("Missing published page $id");
    $prefix = '<h1>' . esc_html($heading) . '</h1>';
    $content = ltrim($post->post_content);
    if (!str_starts_with($content, $prefix)) {
        echo "UNCHANGED $id\n";
        continue;
    }
    $content = ltrim(substr($content, strlen($prefix)));
    $result = wp_update_post(['ID' => $id, 'post_content' => $content], true);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    echo "REMOVED_DUPLICATE_H1 $id\n";
}
flush_rewrite_rules(false);
echo "REWRITE_RULES_REFRESHED\n";
