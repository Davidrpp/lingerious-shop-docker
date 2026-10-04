<?php
/** One-off corrective migration: factual contact details, statutory rights and index hygiene. */
require '/var/www/html/wp-load.php';
$apply = in_array('--apply', $argv, true);
$email = 'lingerious.bcn@gmail.com';
$contact = '<!--email_off--><a href="mailto:'.$email.'">'.$email.'</a><!--/email_off-->';
$patterns = [
 '~<li><strong>(?:CIF|Address|Phone number)</strong>:\s*_+</li>~i' => '',
 '~<li><strong>Email</strong>:\s*_+</li>~i' => '<li><strong>Email</strong>: '.$contact.'</li>',
];
$privacy = get_post(2698)->post_content;
$terms = get_post(2702)->post_content;
foreach ($patterns as $pattern => $replacement) {
 $privacy = preg_replace($pattern, $replacement, $privacy, -1, $c); echo 'PRIVACY_PATTERN='.$c.PHP_EOL;
 $terms = preg_replace($pattern, $replacement, $terms, -1, $c); echo 'TERMS_PATTERN='.$c.PHP_EOL;
}
$privacy = preg_replace('~via email at _+~i', 'via email at '.$contact, $privacy, -1, $c); echo 'PRIVACY_RIGHTS_EMAIL='.$c.PHP_EOL;
$terms = preg_replace('~via email at _+~i', 'via email at '.$contact, $terms, -1, $c); echo 'TERMS_RETURN_EMAIL='.$c.PHP_EOL;
$privacy = preg_replace('~<!-- wp:paragraph -->\s*<p><strong> de Prot[^<]*</strong>\.</p>\s*<!-- /wp:paragraph -->~u', '', $privacy, -1, $c); echo 'PRIVACY_STRAY_SPANISH='.$c.PHP_EOL;
$privacy = str_replace('our our cookie information, which is being reviewed', 'information about cookies available upon request via our contact page', $privacy);
$terms = str_replace('If you receive a defective product, you may request its return or replacement within 14 days from the delivery date.', 'If an item is faulty, incorrect or does not conform to the order, contact us. Your statutory guarantee and remedies are not limited to 14 or 15 days; any remedy and return transport must be provided without costs that the seller is legally required to bear.', $terms);
$terms = preg_replace('~<p>These Terms and Conditions are governed by Spanish law\. In the event of a dispute, the parties submit to the jurisdiction of the Courts of \[jurisdiction to be confirmed\], expressly waiving any other jurisdiction that may apply\.</p>~', '<p>Applicable consumer protection rights and mandatory legal rules on jurisdiction remain unaffected by these terms.</p>', $terms, -1, $c); echo 'REMOVE_JURISDICTION_WAIVER='.$c.PHP_EOL;
$terms = str_replace('you have the right to withdraw from your purchase within 14 calendar days from receipt of the product, without providing justification.', 'you may request an eligible return or exchange within 15 calendar days of receipt. For consumers covered by EU law, the statutory right of withdrawal for most distance purchases lasts at least 14 calendar days; mandatory legal exceptions apply. The voluntary 15-day request window does not restrict statutory rights.', $terms);
$terms = str_replace('Return the product in its original condition and packaging.', 'Send the goods back within the applicable return period, taking reasonable care of the goods. Any statutory rights concerning handling and packaging remain unaffected.', $terms);
$terms = str_replace('Cover the return shipping costs unless the product received is not the one ordered.', 'For eligible non-defective returns, you pay direct return postage where this was disclosed before purchase. Return of incorrect, defective or non-conforming goods is free of charges that legally belong to the seller.', $terms);
$terms = str_replace('<p><strong><strong>Last updated</strong>: December 5, 2024</strong></p>', '<p><strong>Last updated: September 22, 2026</strong></p>', $terms);
$privacy = str_replace('<p><strong><strong>Last updated</strong>: December 5, 2024</strong></p>', '<p><strong>Last reviewed for contact details: September 22, 2026</strong></p>', $privacy);
$returns = require __DIR__.'/returns-en.php';
if (strpos($returns,'</h')===false || strpos($returns,'15 calendar days')===false) throw new RuntimeException('Return policy missing required content');
foreach ([2698=>$privacy,2702=>$terms,2705=>$returns] as $id=>$content) {
 if (preg_match('/_{10,}|\[jurisdiction to be confirmed\]/',$content)) throw new RuntimeException('Residual placeholder in page '.$id);
 echo 'PAGE_PREVIEW='.$id.' bytes='.strlen($content).PHP_EOL;
 if ($apply) { $result=wp_update_post(['ID'=>$id,'post_content'=>$content],true); if (is_wp_error($result)) throw new RuntimeException($result->get_error_message()); }
}
foreach ([7,8,9] as $id) if ($apply) update_post_meta($id,'_yoast_wpseo_meta-robots-noindex','1');
if ($apply) { wp_cache_flush(); echo 'APPLIED 3 LEGAL CONTENT PAGES; NOINDEX UTILITY PAGES; REVIEW LEGAL IDENTITY STILL REQUIRED'.PHP_EOL; }
else echo 'DRY RUN; NOTHING MODIFIED'.PHP_EOL;
