<?php
/**
 * Lingerious product layout. WooCommerce hooks preserve stock, variants and cart logic.
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */
defined('ABSPATH') || exit;
global $product;
do_action('woocommerce_before_single_product');
if (post_password_required()) { echo get_the_password_form(); return; }
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('lg-pdp', $product); ?>>
  <div class="lg-pdp-top">
    <div class="lg-pdp-gallery" aria-label="Product photographs">
      <?php do_action('woocommerce_before_single_product_summary'); ?>
    </div>
    <div class="summary entry-summary lg-pdp-purchase">
      <div class="lg-pdp-panel">
        <?php do_action('woocommerce_single_product_summary'); ?>
      </div>
    </div>
  </div>
  <div class="lg-pdp-bottom">
    <?php do_action('woocommerce_after_single_product_summary'); ?>
  </div>
</div>
<?php do_action('woocommerce_after_single_product'); ?>
