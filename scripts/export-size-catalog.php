<?php
require '/var/www/html/wp-load.php';
$rows=[];
foreach(get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1]) as $post){
 $product=wc_get_product($post->ID);if(!$product || !$product->is_type('variable'))continue;
 $chosen=null;
 foreach($product->get_available_variations() as $variation){
  if(empty($variation['is_in_stock'])||empty($variation['is_purchasable']))continue;
  if(!array_filter(array_keys($variation['attributes']),static fn($key)=>stripos($key,'size')!==false))continue;
  if(!empty(array_filter($variation['attributes'],static fn($value)=>$value==='')))continue;
  $chosen=['id'=>$variation['variation_id'],'attrs'=>$variation['attributes']];break;
 }
 $rows[]=['id'=>$post->ID,'url'=>get_permalink($post->ID),'choice'=>$chosen];
}
echo wp_json_encode($rows,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
