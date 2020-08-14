<?php

namespace WooCommerce_Dev;

class WooCommerce_Redirect_Payment
{
    public function __construct()
    {
        add_filter('woocommerce_payment_successful_result', array($this, 'redirect_without_payment_gateway_to_thanks'), 12, 2);
        add_action('woocommerce_thankyou', array($this, 'woocommerce_thankyou'), 11, 1);
        add_filter('woocommerce_get_return_url', array($this, 'return_url_after_complete_pay'), 10, 2);
    }

    /**
     * Redirect to thanks page for code gateway
     *
     * @param $result
     * @param $order_id
     * @return mixed
     */
    public function redirect_without_payment_gateway_to_thanks($result, $order_id)
    {
        $order = wc_get_order($order_id);
        // Use wc_get_payment_gateway_by_order($order_id)
        if (in_array($order->get_payment_method(), array('cod'))) {
            $result['redirect'] = add_query_arg(array('status_order' => 'success', 'order_id' => $order_id), wc_get_checkout_url);
            return $result;
        }

        return $result;
    }

    /**
     * Return Url after Success Payment
     *
     * @param $return_url
     * @param $order
     * @return string|void
     */
    public function return_url_after_complete_pay($return_url, $order)
    {
        if (!$order) {
            return $return_url;
        }

        if (is_int($order)) {
            $order = wc_get_order($order);
        }

        // No updated status for orders delivered with Bank wire, Cash on delivery and Cheque payment methods.
        if (get_post_meta($order->get_id(), '_payment_method', true) == 'bacs' || get_post_meta($order->get_id(), '_payment_method', true) == 'cod' || get_post_meta($order->get_id(), '_payment_method', true) == 'cheque') {
            return $return_url;
        }

        // "completed" updated status for paid "processing" Orders (with all others payment methods)
        if ($order->has_status('processing') || $order->has_status('completed')) {
            return add_query_arg(array('status_order' => 'success', 'order_id' => $order->get_id()), wc_get_checkout_url);
        } else {
            return add_query_arg(array('status_order' => 'failed', 'order_id' => $order->get_id()), wc_get_checkout_url);
        }
    }

    /**
     * Redirect to thanks Page
     *
     * @param $order_id
     */
    public function woocommerce_thankyou($order_id)
    {
        if (!$order_id)
            return;

        $order = new \WC_Order($order_id);

        // No updated status for orders delivered with Bank wire, Cash on delivery and Cheque payment methods.
        if (get_post_meta($order_id, '_payment_method', true) == 'bacs' || get_post_meta($order_id, '_payment_method', true) == 'cod' || get_post_meta($order_id, '_payment_method', true) == 'cheque') {
            return;
        }

        // "completed" updated status for paid "processing" Orders (with all others payment methods)
        if ($order->has_status('processing') || $order->has_status('completed')) {
            wp_redirect(add_query_arg(array('status_order' => 'success', 'order_id' => $order->get_id()), wc_get_checkout_url));
            exit;
        } else {
            wp_redirect(add_query_arg(array('status_order' => 'failed', 'order_id' => $order->get_id()), wc_get_checkout_url));
            exit;
        }
    }
}

new WooCommerce_Redirect_Payment;