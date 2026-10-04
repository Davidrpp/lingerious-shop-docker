<?php
/** Correct demonstrably inaccurate store facts; leave unknown company fields untouched. */
require '/var/www/html/wp-load.php';
$p=get_post(2702);$content=$p->post_content;
$fixes=['Información sobre Lingerious'=>'Information about Lingerious','Adress'=>'Address','CIF:'=>'Tax ID:','To make a purchase on Lingerious, you must register as a user.'=>'You can purchase as a guest when guest checkout is available. You may also register for an account.','All prices are listed in euros and include VAT.'=>'Prices are displayed in the currency shown on the product and checkout pages. Review any applicable taxes and delivery charges at checkout before confirming an order.','We accept various forms of payment, including credit cards and other secure methods.'=>'Available payment methods are shown at checkout.'];
foreach($fixes as $before=>$after){$count=substr_count($content,$before);$content=str_replace($before,$after,$content);echo 'TERM_FIX='.mb_substr($before,0,50).' matches='.$count.PHP_EOL;}
if($content!==$p->post_content){$r=wp_update_post(['ID'=>2702,'post_content'=>$content],true);if(is_wp_error($r))throw new RuntimeException($r->get_error_message());}else echo 'NO_TERM_CHANGE'.PHP_EOL;
$pp=get_post(2698);$pr=str_replace(['CIF:','How jong do we keep your data?','de Protcción de Datos (AEPD).'],['Tax ID:','How long do we keep your data?',''],$pp->post_content);if($pr!==$pp->post_content){wp_update_post(['ID'=>2698,'post_content'=>$pr]);echo 'PRIVACY_TYPO_FIXED'.PHP_EOL;}
echo 'LEGAL_FIELDS_UNVERIFIED: legal business identity, address, data processing and actual delivery conditions.'.PHP_EOL;
