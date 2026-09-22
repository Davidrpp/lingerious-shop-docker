<?php
require '/var/www/html/wp-load.php';
$products=wc_get_products(['status'=>'publish','limit'=>-1]);$fail=[];$local=0;
foreach($products as $product){$d=apply_filters('woocommerce_structured_data_product',['name'=>$product->get_name(),'image'=>wp_get_attachment_url($product->get_image_id())],$product);$url=$d['image']??'';if(!str_starts_with($url,'https://lingerious.shop/wp-content/uploads/')){$fail[]=$product->get_id().' '.$url;}else $local++;}
echo 'PRODUCT_SCHEMA_IMAGE_CHECK products='.count($products).' local='.$local.' failures='.count($fail).PHP_EOL;
foreach($fail as $f)echo 'FAIL '.$f.PHP_EOL;
if($fail)exit(1);
