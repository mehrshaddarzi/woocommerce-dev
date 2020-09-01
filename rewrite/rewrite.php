<?php

namespace WordPress_Rewrite_API_Request;

use WooCommerce_Dev\WooCommerce_Cart;
use WooCommerce_Dev\WooCommerce_Helper;
use WooCommerce_Dev\WooCommerce_Product;
use WooCommerce_Dev\WooCommerce_Review;

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
        if (!isset($_REQUEST['product-id'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare params
        $product_id = sanitize_text_field($_REQUEST['product-id']);

        // Check Exist Product
        if (!WooCommerce_Product::exist($_REQUEST['product-id'])) {
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

    public static function _return_cart_list()
    {
        return apply_filters('woocommerce_dev_cart_list_return', array(
            'html' => '',
            'link' => WooCommerce_Helper::get_page_url('cart'),
            'items' => WooCommerce_Cart::get(),
            'count' => WooCommerce_Cart::get_sum_product_quantity_product(),
        ));
    }

    public static function cart_add_product()
    {
        // Check Product ID
        $product_id = self::_check_product_id();

        // Get Product
        $product = WooCommerce_Product::get($product_id);
        $max_quantity = $product['get_max_purchase_quantity'];

        // Quantity
        $quantity = 1;
        if (isset($_REQUEST['quantity']) and is_numeric($_REQUEST['quantity']) and $_REQUEST['quantity'] > 0) {
            $quantity = sanitize_text_field($_REQUEST['quantity']);

            // Check Max Number Of Product
            if ($max_quantity != "-1" and $quantity > $max_quantity) {
                wp_send_json_error(array(
                    'code' => 'invalid_max_quantity',
                    'message' => __('The stock of this product in stock is less than your request.', 'woocommerce-dev')
                ), 400);
            }
        }

        // Variation ID
        $variation_id = 0;
        if (isset($_REQUEST['variation-id']) and is_numeric($_REQUEST['variation-id']) and $_REQUEST['variation-id'] > 0) {
            $variation_id = sanitize_text_field($_REQUEST['variation-id']);

            // Check Exist Variation ID
            $variation_ids = WooCommerce_Product::get_variation_ids($product);
            if (empty($variation_ids)) {
                wp_send_json_error(array(
                    'code' => 'invalid_variation_product',
                    'message' => __('This is not a variable product.', 'woocommerce-dev')
                ), 400);
            }
            if (!in_array($variation_id, $variation_ids)) {
                wp_send_json_error(array(
                    'code' => 'invalid_variation_id',
                    'message' => __('The product ID of the variable is invalid.', 'woocommerce-dev')
                ), 400);
            }
        }

        // Add to Cart
        $cart_item_key = false;
        try {
            $cart_item_key = WooCommerce_Cart::add_to_cart($product_id, $quantity, $variation_id);
        } catch (\Exception $e) {
            WordPress_Rewrite_API_Request::not_success_action();
        }

        if ($cart_item_key != false) {
            wp_send_json_success(array_merge(self::_return_cart_list(), array('cart_item_key' => $cart_item_key)), 200);
        }

        WordPress_Rewrite_API_Request::not_success_action();
    }

    public static function cart_remove_product()
    {
        // Get Item Cart Key
        if (!isset($_REQUEST['cart-item-key'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        //@TODO Check item_key exist in Cart or Use $_REQUEST['product_id'] as item_key

        // Remove
        WooCommerce_Cart::remove_item_cart(sanitize_text_field($_REQUEST['cart-item-key']));

        // Result
        wp_send_json_success(self::_return_cart_list(), 200);
    }

    public static function cart_clear()
    {
        // Check Is Empty
        if (WooCommerce_Cart::is_empty()) {
            WordPress_Rewrite_API_Request::not_success_action();
        }

        // Clear
        WooCommerce_Cart::clear();

        // Result
        wp_send_json_success(self::_return_cart_list(), 200);
    }

    public static function cart_set_quantity()
    {
        if (!isset($_REQUEST['cart-item-key']) || !isset($_REQUEST['quantity'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare Item
        $item_key = sanitize_text_field($_REQUEST['cart-item-key']);
        $quantity = sanitize_text_field($_REQUEST['quantity']);

        //@TODO Check item_key exist in Cart or Use $_REQUEST['product_id'] as item_key, Check Quantity Max Number

        // Set Quantity
        WooCommerce_Cart::set_quantity($item_key, $quantity, true);

        // Result
        wp_send_json_success(self::_return_cart_list(), 200);
    }

    /**
     * CheckOut System
     */


    /**
     * Reviews
     */
    public static function review_add()
    {
        // Require Params
        if (!isset($_REQUEST['product_id']) || !isset($_REQUEST['comment_content']) || !isset($_REQUEST['comment_author'])) {
            WordPress_Rewrite_API_Request::missing_params();
        }

        // Prepare Params
        $product_id = $_REQUEST['product_id'];
        $comment_parent_id = $_REQUEST['comment_parent_id'];
        if (empty($comment_parent_id)) {
            $comment_parent_id = 0;
        }

        // Check Empty Params
        $comment_content = $_REQUEST['comment_content'];
        $comment_author = $_REQUEST['comment_author'];
        $comment_email = $_REQUEST['comment_email'];
        if (empty($comment_content)) {
            WordPress_Rewrite_API_Request::empty_param(__('متن نظر', 'woocommerce-dev'));
        }
        if (empty($comment_author)) {
            WordPress_Rewrite_API_Request::empty_param(__('نام و نام خانوادگی', 'woocommerce-dev'));
        }

        // Check Email
        if (!empty($comment_email)) {
            if (is_email($comment_email) === false) {
                wp_send_json_error(array(
                    'message' => 'لطفا ایمیل را به شکل صحیح وارد نمایید'
                ), 400);
            }
        }

        // Rating
        $rating = null;
        if (isset($_REQUEST['comment_rating']) and !empty($_REQUEST['comment_rating']) and is_numeric($_REQUEST['comment_rating'])) {
            $rating = $_REQUEST['comment_rating'];
        }

        // do action
        do_action('woocommerce_dev_add_reviews_error', $product_id);

        // Add Review
        $_comment = WooCommerce_Review::add(array(
            'comment_post_ID' => $product_id,
            'comment_author' => $comment_author,
            'comment_author_email' => $comment_email,
            'comment_content' => nl2br($comment_content),
            'comment_parent' => $comment_parent_id
        ), $rating);

        if ($_comment['id'] === false) {
            wp_send_json_error(array(
                'message' => 'ارسال نظر موفقیت آمیز نبود لطفا دوباره تلاش کنید'
            ), 400);
        }

        // Response
        $text_success = 'نظر شما با موفقیت ثبت گردید و پس از تایید نمایش داده می شود';
        if ($_comment['arg']['comment_approved'] == 1) {
            $text_success = 'نظر شما با موفقیت ثبت گردید';
        }
        wp_send_json_success(array(
            'message' => $text_success,
        ), 200);
    }

}

new wc;
