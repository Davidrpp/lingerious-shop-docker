<?php
// Local restoration only: suppress email, payments, and checkout submissions.
add_filter('pre_wp_mail', '__return_true', PHP_INT_MAX);
add_filter('woocommerce_available_payment_gateways', '__return_empty_array', PHP_INT_MAX);
add_filter('redirect_canonical', '__return_false', PHP_INT_MAX);
add_filter('option_active_plugins', function ($plugins) {
    return array_values(array_filter($plugins, function ($name) {
        return strpos($name, 'hostinger/') !== 0
            && strpos($name, 'hostinger-easy-onboarding/') !== 0;
    }));
}, PHP_INT_MAX);
add_action('woocommerce_checkout_process', function () {
    wc_add_notice('Checkout is disabled in this preview.', 'error');
}, 1);
add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    $route = $request->get_route();
    $method = $request->get_method();
    if ($route === '/wc/store/v1/checkout' && in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
        return new WP_Error(
            'lingerious_preview_checkout_disabled',
            'Checkout is disabled in this preview.',
            array('status' => 403)
        );
    }
    return $result;
}, 10, 3);
add_action('shutdown', function () {
    if (!function_exists('wc_get_orders') || !function_exists('wc_get_order')) {
        return;
    }

    $draft_order_ids = wc_get_orders(array(
        'status' => 'checkout-draft',
        'limit' => 50,
        'return' => 'ids',
    ));

    foreach ($draft_order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->delete(true);
        }
    }
}, PHP_INT_MAX);
add_filter('wp_robots', function ($robots) {
    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    return $robots;
});
add_action('send_headers', function () {
    header('X-Robots-Tag: noindex, nofollow', true);
});
