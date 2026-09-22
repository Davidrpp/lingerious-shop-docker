<?php
/** Boutique merchandising: factual product data and progressive WooCommerce hooks. */
if (!defined('ABSPATH')) exit;

add_action('woocommerce_single_product_summary', function (): void {
    $product = wc_get_product(get_the_ID());
    if (!$product) return;
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if (!$terms || is_wp_error($terms)) return;
    $terms = array_values(array_filter($terms, static fn($term) => $term->slug !== 'uncategorized' && $term->slug !== 'all-products-category'));
    if (!$terms) return;
    $term = $terms[0];
    echo '<a class="lg-pdp-eyebrow" href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
}, 4);

add_action('wp', function (): void {
    if (!function_exists('is_product') || !is_product()) return;
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
});

add_action('woocommerce_after_single_product_summary', function (): void {
    $product = wc_get_product(get_the_ID());
    if (!$product) return;
    echo '<section class="lg-product-information" aria-label="Product information">';
    echo '<div class="lg-product-information__intro"><p class="lg-eyebrow">The details</p><h2>About this piece</h2></div>';
    $description = trim($product->get_description());
    if ($description !== '') {
        echo '<details class="lg-product-disclosure"><summary>Description</summary><div class="lg-product-disclosure__body">';
        echo wp_kses_post(apply_filters('the_content', $description));
        echo '</div></details>';
    }
    $rows = [];
    foreach ($product->get_attributes() as $attribute) {
        if (!$attribute->get_visible()) continue;
        $name = $attribute->get_name();
        if (!in_array($name, ['pa_size','pa_cup-size','pa_color','pa_material','pa_fabric','pa_composition'], true)) continue;
        if ($attribute->is_taxonomy()) {
            $values = wc_get_product_terms($product->get_id(), $name, ['fields'=>'names']);
        } else {
            $values = $attribute->get_options();
        }
        if (is_wp_error($values) || !$values) continue;
        $rows[] = [wc_attribute_label($name), implode(', ', array_map('strval', $values))];
    }
    if ($rows) {
        echo '<details class="lg-product-disclosure"><summary>Product specifications</summary><div class="lg-product-disclosure__body"><dl class="lg-specification-list">';
        foreach ($rows as [$label,$value]) echo '<div><dt>'.esc_html($label).'</dt><dd>'.esc_html($value).'</dd></div>';
        echo '</dl></div></details>';
    }
    echo '<details class="lg-product-disclosure"><summary>Size &amp; fit</summary><div class="lg-product-disclosure__body"><p>Check the measurements before choosing your size.</p><a href="'.esc_url(lingerious_clean_url('size-guide')).'">Open size guide</a></div></details>';
    echo '<details class="lg-product-disclosure"><summary>Delivery &amp; returns</summary><div class="lg-product-disclosure__body"><p>Consult our published delivery and returns information before ordering.</p><a href="'.esc_url(lingerious_clean_url('returns')).'">Shipping and returns information</a></div></details>';
    echo '</section>';
}, 10);

add_filter('woocommerce_product_related_products_heading', static fn(): string => 'Discover more');
add_action('woocommerce_after_shop_loop_item_title', function (): void {
    global $product;
    if (!$product || !$product->is_type('variable')) return;
    $attributes = $product->get_variation_attributes();
    $colors = $attributes['pa_color'] ?? $attributes['Color'] ?? [];
    $count = count(array_unique(array_filter($colors)));
    if ($count > 1) echo '<span class="lg-product-colour-count">'.esc_html(sprintf('%d colours', $count)).'</span>';
}, 12);
