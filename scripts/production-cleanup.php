<?php
/**
 * One-off production cleanup for Lingerious after activating the editorial theme.
 */
require '/var/www/html/wp-load.php';

update_option('blogname', 'LINGERIOUS');
update_option('blogdescription', 'Premium lingerie and intimates');
update_option('blog_public', '1');
update_option('wp_page_for_privacy_policy', 2698);
update_option('woocommerce_terms_page_id', 2702);
update_option('woocommerce_default_customer_address', 'base');

$term = get_term_by('slug', 'all-prodcuts', 'product_cat');
if ($term && ! is_wp_error($term)) {
    wp_update_term((int) $term->term_id, 'product_cat', [
        'name' => 'All Products',
        'slug' => 'all-products-category',
    ]);
}

$replacements = [
    'asjustable' => 'adjustable',
    'Botoms' => 'Bottoms',
    'Bras that seduces' => 'Bras with presence',
    'Architectural Solutions' => 'Lingerious',
    'Only Fans (coming Soon)' => 'Editorial content coming soon',
    'All prodcuts' => 'All products',
];

$posts = get_posts([
    'post_type' => ['page', 'product'],
    'post_status' => ['publish', 'private', 'draft'],
    'posts_per_page' => -1,
]);

foreach ($posts as $post) {
    $changed = false;
    $fields = [
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
    ];

    foreach ($fields as $key => $value) {
        $new_value = str_replace(array_keys($replacements), array_values($replacements), $value);
        if ($new_value !== $value) {
            $fields[$key] = $new_value;
            $changed = true;
        }
    }

    if ($changed) {
        wp_update_post([
            'ID' => $post->ID,
            'post_title' => $fields['post_title'],
            'post_content' => $fields['post_content'],
            'post_excerpt' => $fields['post_excerpt'],
        ]);
    }
}

flush_rewrite_rules(false);
if (has_action('litespeed_purge_all')) {
    do_action('litespeed_purge_all');
}

echo "Production cleanup completed" . PHP_EOL;

