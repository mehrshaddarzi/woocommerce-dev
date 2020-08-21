<?php

namespace WordPress_Rewrite_API_Request;

use WooCommerce_Dev\WooCommerce_Product;

class wc
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 7);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('woocommerce-rewrite', \WOOCOMMERCE_DEV::$plugin_url . '/rewrite/script.js', array('jquery', 'wp-rewrite-api'), \WOOCOMMERCE_DEV::$plugin_version, true);
    }

    /**
     * Helper
     */

    public static function _check_product_id()
    {
        // Check Require Params
        if (!isset($_REQUEST['product_id'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare params
        $product_id = sanitize_text_field($_REQUEST['product_id']);

        // Check Exist Product
        if (!WooCommerce_Product::exist($_REQUEST['product_id'])) {
            wp_send_json_error(array(
                'code' => 'invalid_product_id',
                'message' => __('Invalid product ID', 'woocommerce-dev'),
            ), 400);
        }

        return $product_id;
    }

    /**
     * Cart System
     */

    public static function cart_add_product()
    {
        // Check Product ID
        $product_id = self::_check_product_id();


    }

    public static function cart_remove_product()
    {

    }

    public static function cart_clear()
    {

    }

    public static function cart_set_quantity()
    {

    }

    /**
     * CheckOut System
     */

}

new wc;
