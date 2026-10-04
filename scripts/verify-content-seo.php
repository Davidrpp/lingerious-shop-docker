<?php
require '/var/www/html/wp-load.php';
$map=require __DIR__.'/content-map.php';$bad=[];$titles=[];$descs=[];
foreach($map as $id=>[$title,$copy,$note]){$p=get_post($id);$t=(string)get_post_meta($id,'_yoast_wpseo_title',true);$d=(string)get_post_meta($id,'_yoast_wpseo_metadesc',true);if(!$p||html_entity_decode($p->post_title,ENT_QUOTES,'UTF-8')!==$title||$p->post_excerpt!==$copy||!str_contains($p->post_content,esc_html($copy))||mb_strlen($d)<70||mb_strlen($d)>165)$bad[]=$id;$titles[]=$t;$descs[]=$d;}
echo 'PRODUCTS='.count($map).' FAIL='.count($bad).' BAD_IDS='.implode(',',$bad).' UNIQUE_TITLES='.count(array_unique($titles)).' UNIQUE_DESCS='.count(array_unique($descs)).PHP_EOL;
foreach([2405,1328,2701,2710,2705,4596] as $id){$p=get_post($id);echo 'PAGE='.$id.' '.($p?$p->post_title:'MISSING').' LENGTH='.($p?strlen($p->post_content):0).' META='.strlen(get_post_meta($id,'_yoast_wpseo_metadesc',true)).PHP_EOL;}
foreach([27,28,29,30] as $id){$m=WPSEO_Taxonomy_Meta::get_term_meta($id,'product_cat');echo 'CATEGORY='.$id.' title='.$m['wpseo_title'].' desc='.strlen($m['wpseo_desc']).PHP_EOL;}
if($bad || count(array_unique($titles))!==count($map) || count(array_unique($descs))!==count($map))exit(1);
echo 'SEO_CONTENT_DATA_PASS'.PHP_EOL;
