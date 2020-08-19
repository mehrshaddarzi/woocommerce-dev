<?php

namespace WordPress_Rewrite_API_Request;

use WooCommerce_Dev\WooCommerce_Product;
use WooCommerce_Dev\WooCommerce_Yith_Compare;

class wc_compare
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 7);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('woocommerce-compare-rewrite', \WOOCOMMERCE_DEV::$plugin_url . '/additional/compare/script.js', array('jquery', 'wp-rewrite-api'), \WOOCOMMERCE_DEV::$plugin_version, true);
    }

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

    public static function _return_list()
    {
        $list = WooCommerce_Yith_Compare::get_list_products_compare();
        return apply_filters('woocommerce_dev_compare_list_return', array(
            'html' => '',
            'ids' => $list,
            'count' => count($list),
        ));
    }

    public static function add()
    {
        // Product_id
        $product_id = self::_check_product_id();

        // Check Has in Compare List
        if (WooCommerce_Yith_Compare::has_product_in_compare($product_id)) {
            wp_send_json_error(array(
                'code' => 'in_compare_list',
                'message' => __('This product is in the product comparison list', 'woocommerce-dev'),
            ), 400);
        }

        // Add to Compare List
        WooCommerce_Yith_Compare::add_product_to_compare($product_id);

        // Result
        wp_send_json_success(self::_return_list(), 200);
    }

    public static function remove()
    {
        // Product_id
        $product_id = self::_check_product_id();

        // Check Has in Compare List
        if (!WooCommerce_Yith_Compare::has_product_in_compare($product_id)) {
            wp_send_json_error(array(
                'code' => 'not_in_compare_list',
                'message' => __('This product is not in the product comparison list', 'woocommerce-dev'),
            ), 400);
        }

        // Remove Compare List
        WooCommerce_Yith_Compare::remove_product_from_compare($product_id);

        // Result
        wp_send_json_success(self::_return_list(), 200);
    }

    public static function get()
    {
        wp_send_json_success(self::_return_list(), 200);
    }

    public static function clear()
    {
        // Clear All
        WooCommerce_Yith_Compare::clear_compare_list();

        // Return
        wp_send_json_success(self::_return_list(), 200);
    }
}

new wc_compare;
