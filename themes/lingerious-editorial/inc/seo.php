<?php
/** Serve verified taxonomy SEO data even while Yoast's indexed records are stale. */
if (!defined('ABSPATH')) exit;
function lingerious_public_category_seo(string $key,string $fallback): string {
 if (!function_exists('is_product_category') || !is_product_category() || !class_exists('WPSEO_Taxonomy_Meta')) return $fallback;
 $term=get_queried_object();
 if (!$term || !isset($term->term_id)) return $fallback;
 $meta=WPSEO_Taxonomy_Meta::get_term_meta((int)$term->term_id,'product_cat');
 $value=is_array($meta)?trim((string)($meta[$key]??'')):'';
 return $value!==''?$value:$fallback;
}
add_filter('wpseo_title',static fn(string $title):string=>lingerious_public_category_seo('wpseo_title',$title),100);
add_filter('wpseo_metadesc',static fn(string $desc):string=>lingerious_public_category_seo('wpseo_desc',$desc),100);
