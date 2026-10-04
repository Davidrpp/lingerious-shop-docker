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

/** Keep duplicate thin taxonomy archives out of discovery; real product categories stay indexed. */
add_filter('wpseo_sitemap_exclude_taxonomy', static function ($excluded, $taxonomy) {
 return in_array($taxonomy, ['product_tag', 'pa_cup-size'], true) ? true : $excluded;
}, 20, 2);
add_filter('wpseo_robots', static function ($robots) {
 return is_tax(['product_tag', 'pa_cup-size']) ? 'noindex, follow' : $robots;
}, 20);
/** Correct the actual logo and add ONLY the publicly confirmed customer-care email. */
add_filter('wpseo_schema_organization', static function (array $data): array {
 $logo = wp_get_attachment_image_url(2828, 'full');
 if ($logo && isset($data['logo']) && is_array($data['logo'])) {
  $logo = set_url_scheme($logo, 'https');
  $data['logo']['url'] = $logo;
  $data['logo']['contentUrl'] = $logo;
  $data['logo']['width'] = 500;
  $data['logo']['height'] = 500;
 }
 $data['name'] = 'Lingerious';
 $data['email'] = 'lingerious.bcn@gmail.com';
 $data['contactPoint'] = [['@type'=>'ContactPoint','contactType'=>'customer service','email'=>'lingerious.bcn@gmail.com','availableLanguage'=>'English']];
 return $data;
}, 20);
add_filter('wpseo_schema_website', static function (array $data): array {
 $data['name'] = 'Lingerious';
 return $data;
}, 20);

/** WooCommerce JSON-LD should point at real first-party product photographs. */
add_filter('woocommerce_structured_data_product', static function (array $data, $product): array {
 if (!$product instanceof WC_Product) return $data;
 $uploads = wp_upload_dir();
 $candidate_ids = array_values(array_unique(array_filter(array_merge(
  [(int) $product->get_image_id()], array_map('intval', $product->get_gallery_image_ids())
 ))));
 foreach ($candidate_ids as $image_id) {
  $image = function_exists('lingerious_local_vendor_image') ? lingerious_local_vendor_image($image_id, 'full') : false;
  if ($image && !empty($image['url'])) { $data['image'] = set_url_scheme($image['url'], 'https'); break; }
  $url = wp_get_attachment_url($image_id);
  if (!$url || !str_starts_with($url, $uploads['baseurl'].'/')) continue;
  $relative = substr($url, strlen($uploads['baseurl']) + 1);
  if (!$relative || str_contains($relative, '..') || !is_file($uploads['basedir'].'/'.$relative)) continue;
  $data['image'] = set_url_scheme($url, 'https');
  break;
 }
 if (isset($data['name'])) {
  $name = (string) $data['name'];
  for ($i=0; $i<4; $i++) { $decoded=html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'); if ($decoded===$name) break; $name=$decoded; }
  $data['name'] = wp_strip_all_tags($name);
 }
 if (isset($data['offers']) && is_array($data['offers'])) foreach ($data['offers'] as &$offer) {
  if (isset($offer['seller']['name'])) $offer['seller']['name'] = 'Lingerious';
 }
 unset($offer);
 return $data;
}, 30, 2);
