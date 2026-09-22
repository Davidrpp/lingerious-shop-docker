<?php
/** One-time, idempotent WordPress editorial migration. Run only after SQL backup. */
require '/var/www/html/wp-load.php';
$apply = in_array('--apply', $argv ?? [], true);
$map = require __DIR__.'/content-map.php';
$editorialPages = require __DIR__.'/editorial-pages.php';
$returnPolicy = require __DIR__.'/returns-en.php';
$posts = get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1]);
$ids = array_map(static fn($p)=>(int)$p->ID,$posts);
if (count($posts)!==count($map) || array_diff(array_keys($map),$ids) || array_diff($ids,array_keys($map))) {
 fwrite(STDERR,'Catalogue mismatch. Products='.count($posts).' curated='.count($map).PHP_EOL);exit(1);
}
function seo_description(string $copy,string $note): string {
 $text=trim(wp_strip_all_tags($copy.' '.$note));
 if(mb_strlen($text)<=157)return $text;
 $cut=mb_substr($text,0,157);$cut=mb_substr($cut,0,mb_strrpos($cut,' '));
 return rtrim($cut,',;: ').'.';
}
function update_page_editorial(int $id,string $title,string $html,string $desc,bool $apply): void {
 $p=get_post($id);if(!$p || $p->post_type!=='page')throw new RuntimeException('Missing page '.$id);
 $html=preg_replace('~^<h1>.*?</h1>~s','',trim($html),1);
 echo 'PAGE '.$id.' '.$title.' '.mb_strlen($html).' chars'.PHP_EOL;
 if(!$apply)return;
 $result=wp_update_post(['ID'=>$id,'post_title'=>$title,'post_content'=>$html,'post_name'=>$p->post_name],true);
 if(is_wp_error($result))throw new RuntimeException($result->get_error_message());
 update_post_meta($id,'_yoast_wpseo_title',$title.' | Lingerious');
 update_post_meta($id,'_yoast_wpseo_metadesc',$desc);
}
$sizeNotes=[
4432=>'<p><strong>Original product measurements:</strong> S: length 53 cm, bust 66â€“76 cm, waist 62â€“72 cm, hip 74â€“84 cm. M: length 54 cm, bust 70â€“80 cm, waist 66â€“76 cm, hip 78â€“88 cm. L: length 55 cm, bust 74â€“84 cm, waist 70â€“80 cm, hip 82â€“92 cm. Refer to the size selector for all offered sizes.</p>',
4221=>'<p><strong>Original sizing notes:</strong> S: bust 75â€“85 cm / underbust 68â€“72 cm; M: 80â€“90 / 73â€“77 cm; L: 83â€“95 / 78â€“82 cm; XL: 90â€“100 / 83â€“87 cm.</p>',
4181=>'<p><strong>Original sizing notes:</strong> S: bust 75â€“85 cm / underbust 68â€“72 cm; M: 80â€“90 / 73â€“77 cm; L: 83â€“95 / 78â€“82 cm; XL: 90â€“100 / 83â€“87 cm.</p>',
3997=>'<p><strong>Original set sizing:</strong> One bralette and one thong. S corresponds to 32 (70) BC; M to 34 (75) BCD; L to 36 (80) BCD; XL to 38 (85) CD. Check the selectable cup and band variation before ordering.</p>',
];
$changed=0;$alts=0; $pageOnly=in_array('--pages-only', $argv ?? [], true);
foreach($map as $id=>[$title,$copy,$note]) { if($pageOnly)break;
 $p=get_post($id);$original=$p->post_content;
 $html='<p>'.esc_html($copy).'</p><p>'.esc_html($note).'</p>';
 if(isset($sizeNotes[$id]))$html.=$sizeNotes[$id];
 if($id===4420 && preg_match('~<table\b[^>]*>.*?</table>~si',$original,$match))$html.='<h3>Original size chart</h3>'.wp_kses_post($match[0]);
 $meta=seo_description($copy,$note);
 if(mb_strlen($meta)<70 || mb_strlen($meta)>165)throw new RuntimeException('Bad meta length: '.$id);
 echo 'PRODUCT '.$id.' '.$title.' meta='.mb_strlen($meta).PHP_EOL;
 if(!$apply)continue;
 $result=wp_update_post(['ID'=>$id,'post_title'=>$title,'post_excerpt'=>$copy,'post_content'=>$html,'post_name'=>$p->post_name],true);
 if(is_wp_error($result))throw new RuntimeException('Product '.$id.': '.$result->get_error_message());
 update_post_meta($id,'_yoast_wpseo_title',$title.' | Lingerious');
 update_post_meta($id,'_yoast_wpseo_metadesc',$meta);
 $prod=wc_get_product($id);$imageId=$prod?(int)$prod->get_image_id():0;
 if($imageId && get_post_type($imageId)==='attachment' && trim((string)get_post_meta($imageId,'_wp_attachment_image_alt',true))===''){
 update_post_meta($imageId,'_wp_attachment_image_alt',$title);$alts++;
 }
 $changed++;
}
echo 'PRODUCTS_UPDATED='.$changed.' ALT_UPDATED='.$alts.PHP_EOL;
update_page_editorial(2405,'About Lingerious',$editorialPages['about'],'Meet Lingerious, an independent lingerie boutique with an edit of lace, sheer sets and statement pieces for adult content creators and webcam models.',$apply);
update_page_editorial(1328,'Contact Lingerious',$editorialPages['contact'],'Contact Lingerious for lingerie product questions, sizes, orders and returns. Find the store contact email and shopping information.',$apply);
update_page_editorial(2701,'Lingerie Shopping FAQs',$editorialPages['faq'],'Answers about Lingerious lingerie sizes, product colours, items included in sets, checkout and returns for adult content creators and other shoppers.',$apply);
update_page_editorial(2710,'Lingerie Size Guide',$editorialPages['size'],'Measure bust, underbust, waist and hips and compare each item’s measurements before choosing a bra, bodysuit, set or thong from Lingerious.',$apply);
update_page_editorial(2705,'Shipping & Returns',$returnPolicy,'Read Lingerious shipping and return terms, delivery estimates, withdrawal information, hygiene exceptions and how to contact the store.',$apply);
update_page_editorial(6,'Shop Lingerie','', 'Shop lace bras, sheer lingerie sets, thongs and cut-out bodysuits. Browse the independent Lingerious collection for adult content creators and other shoppers.',$apply);
$home=get_post(2);
if($apply){wp_update_post(['ID'=>2,'post_title'=>'Lingerious | Lingerie for Content Creators','post_content'=>'<!-- wp:paragraph --><p>Explore lace bras, sheer sets, bodysuits and bottoms for adult creators and independent lingerie shoppers.</p><!-- /wp:paragraph -->','post_name'=>$home->post_name]);update_post_meta(2,'_yoast_wpseo_title','Lingerie for Content Creators & Webcam Models | Lingerious');update_post_meta(2,'_yoast_wpseo_metadesc','Shop lingerie for adult content creators and webcam models: sheer sets, lace bras, strappy bodysuits and bottoms from independent boutique Lingerious.');update_option('blogdescription','Lingerie for adult content creators and webcam models.');}
echo 'HOME 2 editorial/Yoast '.($apply?'updated':'planned').PHP_EOL;
$existing=get_page_by_path('lingerie-for-content-creators',OBJECT,'page');
if($existing){$creatorId=(int)$existing->ID;}else{$creatorId=0;}
if($apply && !$creatorId){$creatorId=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Lingerie for Content Creators & Webcam Models','post_name'=>'lingerie-for-content-creators','post_parent'=>0,'post_content'=>''],true);if(is_wp_error($creatorId))throw new RuntimeException($creatorId->get_error_message());}
echo 'CREATOR_PAGE id='.$creatorId.' '.($apply?'apply':'plan').PHP_EOL;
if($apply){$content=preg_replace('~^<h1>.*?</h1>~s','',trim($editorialPages['creator']),1);wp_update_post(['ID'=>$creatorId,'post_title'=>'Lingerie for Content Creators & Webcam Models','post_content'=>$content,'post_name'=>'lingerie-for-content-creators']);update_post_meta($creatorId,'_yoast_wpseo_title','Lingerie for Adult Content Creators & Webcam Models | Lingerious');update_post_meta($creatorId,'_yoast_wpseo_metadesc','Practical lingerie choices for adult creators: sheer sets, lace bras, bodysuits and sizing tips for photography, OnlyFans and webcam styling. Independent boutique.');}
$categories=[
27=>['Bras','Lace bras and corset styles with fine straps, embroidery and sheer details. Explore options for adult creator shoots or individual lingerie styling.','Lace Bras & Corsets for Content Creators | Lingerious','Browse lace bras, fine-strap designs and strapless corsets. Compare available sizes and details for photo shoots, webcam looks and individual styling.'],
28=>['Bottoms','Sheer briefs, embroidered thongs and strap-detail bottoms. Browse individual pieces for a coordinated outfit or mix-and-match lingerie wardrobe.','Sheer Thongs & Lace Bottoms | Lingerious','Shop Brazilian briefs, lace-back thongs and embroidered lingerie bottoms. Explore sheer and strappy styles for mix-and-match creator outfits.'],
29=>['Sets & Two-Piece Lingerie','Coordinated bra-and-thong sets, floral embroidery and multi-piece garter looks. Check each product for exact inclusions, available colours and size combinations.','Lingerie Sets & Garter Looks for Creators | Lingerious','Discover sheer lingerie sets, embroidered bra-and-thong looks and garter outfits. Check the included pieces, colours and sizes before ordering.'],
30=>['Bodysuits','Single-piece lingerie with cut-outs, mesh panels, chain accents and strappy silhouettes. Compare each bodysuit’s fit details before choosing your size.','Sheer & Cut-Out Bodysuits for Creators | Lingerious','Browse black mesh and cut-out bodysuits with strap and ring details. Statement lingerie for adult creator shoots and webcam outfit styling.'],
];
foreach($categories as $id=>[$name,$description,$title,$meta]){
 $t=get_term($id,'product_cat');if(!$t || is_wp_error($t))throw new RuntimeException('Missing category '.$id);
 echo 'CATEGORY '.$id.' '.$name.PHP_EOL;
 if(!$apply)continue;
 $r=wp_update_term($id,'product_cat',['name'=>$name,'description'=>$description]);if(is_wp_error($r))throw new RuntimeException($r->get_error_message());
 if(class_exists('WPSEO_Taxonomy_Meta')){WPSEO_Taxonomy_Meta::set_value($id,'product_cat','title',$title);WPSEO_Taxonomy_Meta::set_value($id,'product_cat','desc',$meta);}
}
foreach([1324,1326,2250,2252,2254,2266,2408,2406,10,18,2270,2756,2752,2749,2592] as $id){if(!get_post($id))continue;echo 'LEGACY_NOINDEX '.$id.PHP_EOL;if($apply)update_post_meta($id,'_yoast_wpseo_meta-robots-noindex','1');}
if($apply){$terms=get_post(2702);$new=str_replace(['Información sobre Lingerious','Adress:','Adress:________________________________','[___________Insert location____________]'],['Information about Lingerious','Address:','Address:________________________________','[jurisdiction to be confirmed]'],$terms->post_content);if($new!==$terms->post_content)wp_update_post(['ID'=>2702,'post_content'=>$new]);update_post_meta(2702,'_yoast_wpseo_title','Terms & Conditions | Lingerious');update_post_meta(2702,'_yoast_wpseo_metadesc','Read the published terms for the Lingerious online store, including purchase conditions, prices, deliveries, returns and contact information.');}
if($apply){$privacy=get_post(2698);$fixed=str_replace(['How jong do we keep your data?','zar el nivel de protección exigido por el RGPD.','de Protcción de Datos (AEPD).','[Cookie Policy]'],['How long do we keep your data?','','','our cookie information, which is being reviewed'],$privacy->post_content);if($fixed!==$privacy->post_content)wp_update_post(['ID'=>2698,'post_content'=>$fixed]);update_post_meta(2698,'_yoast_wpseo_title','Privacy Policy | Lingerious');update_post_meta(2698,'_yoast_wpseo_metadesc','Read the Lingerious privacy policy covering personal data collected through the online store, processing, retention and your privacy rights.');}
echo 'LEGAL Privacy typo cleanup; legal identifiers and practices still require owner review'.PHP_EOL;
if($apply){wp_cache_flush();echo 'APPLIED. Verify public Yoast title/meta/canonical, page content and checkout. NO orders placed.'.PHP_EOL;}else{echo 'DRY RUN ONLY: no WordPress data changed.'.PHP_EOL;}
