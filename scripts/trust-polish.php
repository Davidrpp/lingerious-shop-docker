<?php
require '/var/www/html/wp-load.php';
$p=get_post(1328);$old='<a href="mailto:lingerious.bcn@gmail.com">lingerious.bcn@gmail.com</a>';
if (substr_count($p->post_content,$old)!==1) throw new RuntimeException('Contact email not unique');
$new='<!--email_off-->'.$old.'<!--/email_off-->';
$r=wp_update_post(['ID'=>1328,'post_content'=>str_replace($old,$new,$p->post_content)],true);
if(is_wp_error($r)) throw new RuntimeException($r->get_error_message());
$p=get_post(2705);$title='<h1>Shipping &amp; Returns</h1>';
if(substr_count($p->post_content,$title)!==1)throw new RuntimeException('Return policy title unexpected');
$r=wp_update_post(['ID'=>2705,'post_content'=>str_replace($title,'',$p->post_content)],true);
if(is_wp_error($r))throw new RuntimeException($r->get_error_message());
update_post_meta(2705,'_yoast_wpseo_metadesc','Read Lingerious delivery options, the 15-day return request window, customer-paid return postage and free returns for defective or incorrect items.');
wp_cache_flush();echo 'CONTACT_EMAIL_PUBLIC_AND_SINGLE_H1_RETURNS_META_UPDATED'.PHP_EOL;
