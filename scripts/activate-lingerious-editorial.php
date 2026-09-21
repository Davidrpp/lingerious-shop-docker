<?php
/**
 * Activates the Lingerious Editorial theme and normalizes safe store settings.
 * Copy to /tmp inside the WordPress container, then run with PHP.
 */
require '/var/www/html/wp-load.php';

$theme = wp_get_theme('lingerious-editorial');
if (! $theme->exists()) {
    fwrite(STDERR, "Theme lingerious-editorial is not installed.\n");
    exit(1);
}

update_option('wp_page_for_privacy_policy', 2698);
update_option('woocommerce_terms_page_id', 2702);
$term = get_term_by('slug', 'all-prodcuts', 'product_cat');
if ($term && ! is_wp_error($term)) {
    wp_update_term((int) $term->term_id, 'product_cat', [
        'name' => 'All Products',
        'slug' => 'all-products-category',
    ]);
}

switch_theme('lingerious-editorial');
flush_rewrite_rules(false);

echo "Activated theme: " . wp_get_theme()->get('Name') . PHP_EOL;
echo "Privacy page: " . get_option('wp_page_for_privacy_policy') . PHP_EOL;
echo "Terms page: " . get_option('woocommerce_terms_page_id') . PHP_EOL;

$product = get_post(2890);
if ($product && $product->post_type === 'product') {
    $content = str_ireplace('asjustable', 'adjustable', $product->post_content);
    $excerpt = str_ireplace('asjustable', 'adjustable', $product->post_excerpt);
    if ($content !== $product->post_content || $excerpt !== $product->post_excerpt) {
        wp_update_post([
            'ID' => 2890,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
        ]);
    }
}
