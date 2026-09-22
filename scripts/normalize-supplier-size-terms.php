<?php
/** Humanise one imported product's supplier labels; preserve taxonomy slugs and variation IDs. */
require '/var/www/html/wp-load.php';
$apply = in_array('--apply', $argv, true);
$map = [
 200 => ['s32or70abc','S(32or70ABC)','S (32 / 70 ABC)'],
 201 => ['m34or75abc','M(34or75ABC)','M (34 / 75 ABC)'],
 203 => ['l36or80abc','L(36or80ABC)','L (36 / 80 ABC)'],
 202 => ['xl38or85abc','XL(38or85ABC)','XL (38 / 85 ABC)'],
];
foreach ($map as $id => [$slug,$old,$new]) {
 $term=get_term($id,'pa_cup-size');
 if (!$term || is_wp_error($term) || $term->slug!==$slug || !in_array($term->name,[$old,$new],true)) throw new RuntimeException('Unexpected term '.$id);
 $products=array_map('intval',get_objects_in_term($id,'pa_cup-size'));
 if ($products!==[4181]) throw new RuntimeException('Unexpected product usage '.wp_json_encode($products));
 echo $id.' '.$term->name.' -> '.$new.' (slug '.$slug.')'.PHP_EOL;
 if ($apply && $term->name!==$new) {
  $updated=wp_update_term($id,'pa_cup-size',['name'=>$new,'slug'=>$slug]);
  if (is_wp_error($updated)) throw new RuntimeException($updated->get_error_message());
 }
}
if ($apply) { wc_delete_product_transients(4181); wp_cache_flush(); echo 'APPLIED; check variation IDs and published selectors.'.PHP_EOL; }
else echo 'DRY RUN; no data modified.'.PHP_EOL;
