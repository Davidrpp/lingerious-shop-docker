<?php
/** Reuse actual migrated AVIF files instead of dead supplier-hosted attachments on PDPs. */
if (!defined('ABSPATH')) { exit; }
function lingerious_local_vendor_file(int $id): string {
    static $cache = [];
    if (array_key_exists($id, $cache)) return $cache[$id];
    $original = (string) get_post_meta($id, '_wp_attached_file', true);
    $path = wp_parse_url($original, PHP_URL_PATH);
    if (!str_starts_with($original, 'https://') || !is_string($path) ||
        !preg_match('~/kf/([A-Za-z0-9]{20,90})(?:/|\.)~', $path, $match)) return $cache[$id] = '';
    $uploads = wp_upload_dir();
    $month = get_post($id) ? get_post_time('Y/m', false, $id) : '';
    $file = $uploads['basedir'] . '/' . $month . '/' . $match[1] . '.avif';
    if (!is_file($file)) {
        $matches = glob($uploads['basedir'] . '/*/*/' . $match[1] . '.avif');
        $file = $matches[0] ?? '';
    }
    return $cache[$id] = ($file && is_file($file) ? $file : '');
}
function lingerious_local_vendor_image(int $id, $size = 'full') {
    $file = lingerious_local_vendor_file($id);
    if (!$file) return false;
    $suffixes = ['thumbnail'=>['-150x150','-100x100'],
        'woocommerce_gallery_thumbnail'=>['-100x100','-150x150'],
        'woocommerce_thumbnail'=>['-300x457','-225x300'],
        'medium'=>['-300x457','-225x300'],
        'woocommerce_single'=>['-600x800','-600x600'],
        'large'=>['-768x1024','-600x800']];
    $requested = is_string($size) ? ($suffixes[$size] ?? []) : [];
    if (is_array($size) && isset($size[0])) {
        $width = (int) $size[0];
        $requested = $width <= 150 ? ['-100x100', '-150x150']
            : ($width <= 450 ? ['-300x457', '-225x300'] : ['-600x800', '-600x600']);
    }
    foreach ($requested as $suffix) {
        $candidate = substr($file, 0, -5) . $suffix . '.avif';
        if (is_file($candidate)) { $file = $candidate; break; }
    }
    $uploads = wp_upload_dir();
    $dimensions = @getimagesize($file);
    return ['url'=>$uploads['baseurl'] . str_replace('\\','/',substr($file,strlen($uploads['basedir']))),
        'width'=>$dimensions[0] ?? 600, 'height'=>$dimensions[1] ?? 800];
}

add_filter('wp_get_attachment_url', function ($url, $id) {
    if (!function_exists('is_product') || !is_product()) return $url;
    $local = lingerious_local_vendor_image((int) $id);
    return $local ? $local['url'] : $url;
}, 20, 2);
add_filter('image_downsize', function ($result, $id, $size) {
    if ($result !== false || !function_exists('is_product') || !is_product()) return $result;
    $local = lingerious_local_vendor_image((int) $id, $size);
    return $local ? [$local['url'], $local['width'], $local['height'], $size !== 'full'] : false;
}, 20, 3);
add_filter('wp_calculate_image_srcset', function ($sources, $size_array, $src, $meta, $id) {
    if (function_exists('is_product') && is_product() && lingerious_local_vendor_file((int) $id)) return false;
    return $sources;
}, 20, 5);

/** Find the correct local photograph for a selected color when supplier media disappeared. */
function lingerious_gallery_color_image($product, string $color): array {
    $raw = sanitize_title($color);
    $exact = $raw;
    $broad = preg_replace('/^(deep-|light-)/', '', preg_replace('/-set$/', '', $raw));
    $best = ['id'=>0, 'score'=>-999, 'approx'=>false];
    foreach ($product->get_gallery_image_ids() as $id) {
        if (!lingerious_local_vendor_file((int) $id) && !is_file((string) get_attached_file($id))) continue;
        $alt = sanitize_title((string) get_post_meta($id, '_wp_attachment_image_alt', true) . ' ' . get_the_title($id));
        $exact_hit = $exact !== '' && preg_match('~(?:^|-)' . preg_quote($exact, '~') . '(?:-|$)~', $alt);
        $broad_hit = $broad !== '' && preg_match('~(?:^|-)' . preg_quote($broad, '~') . '(?:-|$)~', $alt);
        if (!$exact_hit && !$broad_hit) continue;
        $score = $exact_hit ? 100 : 60;
        if (str_contains($alt, 'lingerie-set')) $score += 12;
        if (str_contains($alt, 'front-view')) $score += 8;
        if (str_contains($alt, 'back-view')) $score -= 9;
        if (str_contains($alt, 'close-up')) $score -= 12;
        if (str_contains($alt, 'thong')) $score -= 12;
        if (str_contains($alt, 'bra')) $score -= 4;
        if ($score > $best['score']) $best = ['id'=>(int) $id, 'score'=>$score, 'approx'=>!$exact_hit];
    }
    return $best;
}

add_filter('woocommerce_available_variation', function ($data, $product, $variation) {
    if (!function_exists('is_product') || !is_product()) return $data;
    $image_id = (int) $variation->get_image_id();
    if ($image_id && (lingerious_local_vendor_file($image_id) || is_file((string) get_attached_file($image_id)))) return $data;
    $color = '';
    foreach ($variation->get_variation_attributes() as $name => $value) {
        if (stripos($name, 'color') !== false && $value !== '') { $color = (string) $value; break; }
    }
    $match = $color !== '' ? lingerious_gallery_color_image($product, $color) : ['id'=>0, 'approx'=>false];
    $replacement = $match['id'] ?: (int) $product->get_image_id();
    if ($replacement && ($match['id'] || $color === '' || is_file((string) get_attached_file($replacement)))) {
        $props = wc_get_product_attachment_props($replacement, $product);
        if (!empty($props['src'])) {
            $data['image'] = $props;
            $data['image_id'] = $replacement;
        }
    }
    if ($color !== '' && (!$match['id'] || $match['approx'])) {
        $data['lg_photo_note'] = !$match['id']
            ? 'An image of this exact color is not yet available. Your color selection is saved.'
            : 'The image shows a similar shade; refer to the selected color above.';
    }
    return $data;
}, 25, 3);

/** Hide gallery slides that point exclusively to inaccessible external originals. */
add_filter('woocommerce_product_get_gallery_image_ids', function ($ids) {
    if (!function_exists('is_product') || !is_product() || !is_array($ids)) return $ids;
    return array_values(array_filter($ids, static function ($id) {
        return (bool) lingerious_local_vendor_file((int) $id)
            || is_file((string) get_attached_file((int) $id));
    }));
}, 20);
