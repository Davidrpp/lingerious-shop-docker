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
        2405 => 'about',
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

    add_rewrite_rule('^shop/page/([0-9]+)/?$', 'index.php?post_type=product&paged=$matches[1]', 'top');

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
    if ($product && $product->is_type('variable')) return __('View piece', 'lingerious-editorial');
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
        $parts['title'] = 'Lingerie for Content Creators & Webcam Models | Lingerious';
        unset($parts['tagline']);
    }
    return $parts;
});

add_filter('wpseo_title', function (string $title): string {
    return is_front_page() ? 'Lingerie for Content Creators & Webcam Models | Lingerious' : $title;
});

add_filter('wpseo_metadesc', function (string $description): string {
    if (is_front_page()) {
        return 'Shop lingerie for adult content creators and webcam models: sheer sets, lace bras, strappy bodysuits and bottoms from independent boutique Lingerious.';
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
        2406 => '/about/',
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

/* Canonical URLs must match the clean public navigation. */
add_filter('wpseo_canonical', function (string $canonical): string {
    if (function_exists('is_shop') && is_shop()) {
        return is_paged() ? home_url('/shop/page/' . max(1, (int) get_query_var('paged')) . '/') : lingerious_clean_url('shop');
    }
    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term && in_array($term->slug, ['bras', 'bottoms', 'bodysuits', 'sets-two-pieces'], true)) {
            return lingerious_clean_url($term->slug);
        }
    }
    if (is_page()) {
        $map = lingerious_clean_page_map();
        $id = (int) get_queried_object_id();
        if (isset($map[$id])) {
            return lingerious_clean_url($map[$id]);
        }
    }
    return $canonical;
}, 30);

add_action('template_redirect', function (): void {
    if (!function_exists('is_shop') || !is_shop() || is_paged() || is_admin() || wp_doing_ajax()) {
        return;
    }
    $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
    if (untrailingslashit($path) !== '/shop') {
        wp_safe_redirect(lingerious_clean_url('shop'), 301);
        exit;
    }
}, 2);

/** Keep product filters server-side, queryable, and restricted to real WooCommerce terms. */
function lingerious_filter_term(string $taxonomy, string $parameter): string {
    $raw = isset($_GET[$parameter]) && is_string($_GET[$parameter]) ? sanitize_title(wp_unslash($_GET[$parameter])) : '';
    if ($raw === '' || !taxonomy_exists($taxonomy)) {
        return '';
    }
    $term = get_term_by('slug', $raw, $taxonomy);
    return $term && !is_wp_error($term) ? $term->slug : '';
}

add_action('woocommerce_product_query', function ($query): void {
    if (is_admin() && !wp_doing_ajax()) {
        return;
    }
    $tax_query = (array) $query->get('tax_query');
    foreach (['size' => 'pa_size', 'color' => 'pa_color'] as $parameter => $taxonomy) {
        $term = lingerious_filter_term($taxonomy, $parameter);
        if ($term !== '') {
            $tax_query[] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => [$term]];
        }
    }
    $query->set('tax_query', $tax_query);
}, 20);

add_action('woocommerce_before_shop_loop', function (): void {
    if (!(is_shop() || is_product_category())) {
        return;
    }
    echo '<div class="lg-catalog-toolbar"><nav class="lg-category-nav" aria-label="Product categories">';
    foreach (['shop' => 'All', 'bras' => 'Bras', 'bottoms' => 'Bottoms', 'sets-two-pieces' => 'Sets', 'bodysuits' => 'Bodysuits'] as $slug => $name) {
        $active = $slug === 'shop' ? is_shop() : is_product_category($slug);
        echo '<a' . ($active ? ' aria-current="page"' : '') . ' href="' . esc_url(lingerious_clean_url($slug)) . '">' . esc_html($name) . '</a>';
    }
    echo '</nav><form class="lg-catalog-filters" method="get">';
    foreach (['size' => ['taxonomy' => 'pa_size', 'title' => 'Size'], 'color' => ['taxonomy' => 'pa_color', 'title' => 'Color']] as $parameter => $config) {
        $chosen = lingerious_filter_term($config['taxonomy'], $parameter);
        echo '<label for="lg-filter-' . esc_attr($parameter) . '">' . esc_html($config['title']) . '</label>';
        echo '<select id="lg-filter-' . esc_attr($parameter) . '" name="' . esc_attr($parameter) . '"><option value="">All</option>';
        $terms = get_terms(['taxonomy' => $config['taxonomy'], 'hide_empty' => true]);
        if (!is_wp_error($terms)) {
            if ($parameter === 'size') {
                $order = array_flip(['xs','s','m','l','xl','xxl','xxxl','4xl','5xl','6xl']);
                usort($terms, static fn ($a, $b) => ($order[$a->slug] ?? 99) <=> ($order[$b->slug] ?? 99));
            }
            foreach ($terms as $term) {
                echo '<option value="' . esc_attr($term->slug) . '"' . selected($chosen, $term->slug, false) . '>' . esc_html($term->name) . '</option>';
            }
        }
        echo '</select>';
    }
    if (isset($_GET['orderby']) && is_string($_GET['orderby'])) {
        echo '<input type="hidden" name="orderby" value="' . esc_attr(sanitize_key(wp_unslash($_GET['orderby']))) . '">';
    }
    echo '<button type="submit">Filter</button>';
    if (lingerious_filter_term('pa_size', 'size') || lingerious_filter_term('pa_color', 'color')) {
        echo '<a class="lg-reset-filters" href="' . esc_url(remove_query_arg(['size', 'color', 'paged', 'product-page'])) . '">Clear</a>';
    }
    echo '</form></div>';
}, 12);

add_action('woocommerce_no_products_found', function (): void {
    if (lingerious_filter_term('pa_size', 'size') || lingerious_filter_term('pa_color', 'color')) {
        echo '<p class="lg-empty-filter-reset"><a href="' . esc_url(remove_query_arg(['size','color','paged','product-page'])) . '">Clear size and color filters</a></p>';
    }
}, 5);

/** Prioritize the featured image on product pages; keep secondary images lazy. */
add_filter('wp_get_attachment_image_attributes', function (array $attributes, $attachment): array {
    if (function_exists('is_product') && is_product()
        && (int) $attachment->ID === (int) get_post_thumbnail_id(get_queried_object_id())) {
        $attributes['loading'] = 'eager';
        $attributes['fetchpriority'] = 'high';
        $attributes['sizes'] = '(max-width: 899px) calc(100vw - 32px), (max-width: 1600px) 48vw, 740px';
    }
    return $attributes;
}, 30, 2);

/** Product page: preserve WooCommerce variation logic, enhance native inputs only with JS. */
add_action('wp_enqueue_scripts', function (): void {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    wp_enqueue_script(
        'lingerious-product-options',
        get_stylesheet_directory_uri() . '/assets/js/product-options.js',
        ['jquery', 'wc-add-to-cart-variation'],
        wp_get_theme()->get('Version'),
        true
    );
}, 30);

add_action('wp', function (): void {
    if (function_exists('is_product') && is_product()) {
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    }
});

require_once __DIR__ . '/inc/media.php';
require_once __DIR__ . '/inc/brand.php';
require_once __DIR__ . '/inc/seo.php';

/** Leave informative product specifications, but do not show empty review tabs. */
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    $product = function_exists('wc_get_product') ? wc_get_product(get_the_ID()) : false;
    if ($product && (int) $product->get_review_count() === 0) unset($tabs['reviews']);
    if (isset($tabs['description'])) $tabs['description']['title'] = 'Product details';
    if (isset($tabs['additional_information'])) $tabs['additional_information']['title'] = 'Specifications';
    return $tabs;
}, 40);

/** Load the consolidated boutique design system after the baseline WooCommerce layer. */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('lingerious-atelier-v2', get_stylesheet_directory_uri() . '/assets/css/atelier-v2.css', ['lingerious-editorial-style'], wp_get_theme()->get('Version'));
}, 40);

/** Product layout has its own final design layer; WooCommerce form/variation logic remains native. */
add_action('wp_enqueue_scripts', function (): void {
    if (!function_exists('is_product') || !is_product()) return;
    wp_enqueue_style(
        'lingerious-pdp-v3',
        get_stylesheet_directory_uri() . '/assets/css/product-v3.css',
        ['lingerious-atelier-v2'],
        wp_get_theme()->get('Version')
    );
}, 50);
/** Final isolated fashion selector layer, independent from legacy product grids. */
add_action('wp_enqueue_scripts', function (): void {
    if (!function_exists('is_product') || !is_product()) return;
    wp_enqueue_style(
        'lingerious-selectors-v4',
        get_stylesheet_directory_uri() . '/assets/css/selectors-v4.css',
        ['lingerious-pdp-v3'],
        wp_get_theme()->get('Version')
    );
}, 60);

/** Imported products may use alpha sizes in the cup-size attribute; label by product. */
add_filter('woocommerce_attribute_label', function (string $label, string $name, $product): string {
    if ($name !== 'pa_cup-size') return $label;
    if (!($product instanceof WC_Product)) return 'Size';
    $id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    if (in_array((int) $id, [4181, 4221], true)) return 'Size';
    if ((int) $id === 3997) return 'Bra size';
    return $label;
}, 20, 3);

/** Avoid exposing large supplier stock counts as merchandising copy on PDPs. */
add_filter('woocommerce_get_availability_text', function (string $text, $product): string {
    if (!function_exists('is_product') || !is_product() || !($product instanceof WC_Product)) return $text;
    $quantity = $product->get_stock_quantity();
    if ($product->is_in_stock() && !$product->is_on_backorder(1)
        && is_numeric($quantity) && (int) $quantity > 9) return 'In stock';
    return $text;
}, 20, 2);
