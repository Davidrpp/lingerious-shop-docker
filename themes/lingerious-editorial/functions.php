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
    add_theme_support('responsive-embeds');
});

add_action('wp_enqueue_scripts', function (): void {
    $parent = wp_get_theme('twentytwentyfour');
    $child = wp_get_theme();

    wp_enqueue_style(
        'twentytwentyfour-style',
        get_template_directory_uri() . '/style.css',
        [],
        $parent->get('Version') ?: null
    );
    wp_enqueue_style(
        'lingerious-editorial-style',
        get_stylesheet_directory_uri() . '/assets/css/editorial.css',
        ['twentytwentyfour-style'],
        $child->get('Version') ?: '0.2.0'
    );
});
function lingerious_clean_page_map(): array {
    $map = [
        (int) get_option('woocommerce_shop_page_id') => 'shop',
        (int) get_option('woocommerce_cart_page_id') => 'cart',
        (int) get_option('woocommerce_checkout_page_id') => 'checkout',
        (int) get_option('woocommerce_myaccount_page_id') => 'my-account',
        2698 => 'privacy-policy',
        2702 => 'terms-and-conditions',
        2705 => 'returns',
        2701 => 'faq',
        2710 => 'size-guide',
        1328 => 'contact',
    ];
    return array_filter($map, static fn ($slug, $id) => $id > 0 && $slug, ARRAY_FILTER_USE_BOTH);
}

function lingerious_route_page_id(string $slug): int {
    $id = array_search($slug, lingerious_clean_page_map(), true);
    return $id === false ? 0 : (int) $id;
}

function lingerious_clean_url(string $slug): string {
    return home_url('/' . trim($slug, '/') . '/');
}
add_action('init', function (): void {
    foreach (lingerious_clean_page_map() as $id => $slug) {
        add_rewrite_rule('^' . preg_quote($slug, '/') . '/?$', 'index.php?page_id=' . $id, 'top');
    }

    $shop_id = (int) get_option('woocommerce_shop_page_id');
    if ($shop_id) {
        add_rewrite_rule('^all-products/?$', 'index.php?page_id=' . $shop_id, 'top');
    }

    foreach (['bras', 'bottoms', 'bodysuits', 'sets-two-pieces'] as $slug) {
        add_rewrite_rule('^' . $slug . '/?$', 'index.php?product_cat=' . $slug, 'top');
    }

    add_rewrite_rule('^terminos-y-condiciones/?$', 'index.php?page_id=2702', 'top');
    add_rewrite_rule('^politica-de-devoluciones-y-envios/?$', 'index.php?page_id=2705', 'top');
    add_rewrite_rule('^guia-de-tallas/?$', 'index.php?page_id=2710', 'top');
}, 20);

add_action('after_switch_theme', function (): void {
    flush_rewrite_rules(false);
});

add_filter('page_link', function (string $url, int $post_id): string {
    $map = lingerious_clean_page_map();
    return isset($map[$post_id]) ? lingerious_clean_url($map[$post_id]) : $url;
}, 20, 2);
add_filter('term_link', function (string $url, $term, string $taxonomy): string {
    if ($taxonomy === 'product_cat' && in_array($term->slug, ['bras', 'bottoms', 'bodysuits', 'sets-two-pieces'], true)) {
        return lingerious_clean_url($term->slug);
    }
    return $url;
}, 20, 3);

add_action('template_redirect', function (): void {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $requested = wp_parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    if (is_page()) {
        $id = (int) get_queried_object_id();
        $map = lingerious_clean_page_map();
        if (isset($map[$id])) {
            $target = wp_parse_url(lingerious_clean_url($map[$id]), PHP_URL_PATH);
            if ($target && untrailingslashit($requested) !== untrailingslashit($target)) {
                wp_safe_redirect(lingerious_clean_url($map[$id]), 301);
                exit;
            }
        }
    }

    if (is_product_category()) {
        $term = get_queried_object();
        if ($term && in_array($term->slug, ['bras', 'bottoms', 'bodysuits', 'sets-two-pieces'], true)) {
            $target = '/' . $term->slug . '/';
            if (untrailingslashit($requested) !== untrailingslashit($target)) {
                wp_safe_redirect(home_url($target), 301);
                exit;
            }
        }
    }
}, 1);
add_filter('woocommerce_output_related_products_args', function (array $args): array {
    $args['posts_per_page'] = 4;
    $args['columns'] = 4;
    return $args;
});

add_filter('loop_shop_per_page', static fn (): int => 16, 20);
add_filter('woocommerce_product_add_to_cart_text', function (string $text, $product): string {
    if ($product && $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()) {
        return __('Add to bag', 'lingerious-editorial');
    }
    return $text;
}, 20, 2);
add_filter('woocommerce_product_single_add_to_cart_text', static fn (): string => __('Add to bag', 'lingerious-editorial'));

add_filter('woocommerce_page_title', function (string $title): string {
    if (is_shop()) {
        return 'Shop all';
    }
    return $title;
});

add_filter('wp_get_attachment_image_attributes', function (array $attr, $attachment): array {
    if (! empty($attr['alt'])) {
        return $attr;
    }
    $parent_id = (int) ($attachment->post_parent ?? 0);
    if ($parent_id && get_post_type($parent_id) === 'product') {
        $attr['alt'] = get_the_title($parent_id);
    } elseif (! empty($attachment->post_title)) {
        $attr['alt'] = wp_strip_all_tags($attachment->post_title);
    }
    return $attr;
}, 20, 2);
add_filter('document_title_parts', function (array $parts): array {
    if (is_front_page()) {
        $parts['title'] = 'Lingerious — Premium Lingerie & Intimates';
        unset($parts['tagline']);
    }
    return $parts;
});

add_filter('wpseo_title', function (string $title): string {
    return is_front_page() ? 'Lingerious — Premium Lingerie & Intimates' : $title;
});

add_filter('wpseo_metadesc', function (string $description): string {
    if (is_front_page()) {
        return 'Discover Lingerious lingerie, bras, bottoms, bodysuits and matching sets in a refined, modern edit.';
    }
    return $description;
});

add_filter('body_class', function (array $classes): array {
    $classes[] = 'lingerious-editorial';
    return $classes;
});

add_action('template_redirect', function (): void {
    if (is_admin() || wp_doing_ajax() || ! is_page()) {
        return;
    }

    $legacy = [
        1324 => '/shop/',
        1326 => '/bras/',
        2250 => '/bottoms/',
        2252 => '/sets-two-pieces/',
        2254 => '/bodysuits/',
        2408 => '/contact/',
        2266 => '/contact/',
        10   => '/returns/',
    ];

    $id = (int) get_queried_object_id();
    if (isset($legacy[$id])) {
        wp_safe_redirect(home_url($legacy[$id]), 301);
        exit;
    }
}, 0);

add_action('woocommerce_after_add_to_cart_form', function (): void {
    echo '<div class="lg-purchase-help">';
    echo '<a href="' . esc_url(lingerious_clean_url('size-guide')) . '">Size guide</a>';
    echo '<span aria-hidden="true">·</span>';
    echo '<a href="' . esc_url(lingerious_clean_url('returns')) . '">Returns policy</a>';
    echo '</div>';
});
