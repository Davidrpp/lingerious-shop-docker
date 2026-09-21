<?php
/**
 * Lingerious Editorial theme bootstrap.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('woocommerce');
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
});

add_action('wp_enqueue_scripts', function (): void {
    $parent_theme = wp_get_theme('twentytwentyfour');
    $child_theme = wp_get_theme();

    wp_enqueue_style(
        'twentytwentyfour-style',
        get_template_directory_uri() . '/style.css',
        [],
        $parent_theme->get('Version') ?: null
    );

    wp_enqueue_style(
        'lingerious-editorial-style',
        get_stylesheet_directory_uri() . '/assets/css/editorial.css',
        ['twentytwentyfour-style'],
        $child_theme->get('Version') ?: '0.1.0'
    );
});

add_filter('woocommerce_output_related_products_args', function (array $args): array {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
});
