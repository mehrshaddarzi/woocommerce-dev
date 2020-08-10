<?php

namespace WooCommerce_Dev;

/**
 * @see https://github.com/woocommerce/woocommerce/blob/master/includes/class-wc-cart.php
 * @see https://www.businessbloomer.com/woocommerce-custom-add-cart-urls-ultimate-guide/
 *
 * Class WooCommerce_Cart
 * @package WP_MVC\WooCommerce
 */
class WooCommerce_Cart
{
    public function __construct()
    {

    }

    /**
     * Use For Create Order
     *
     * @return string
     */
    public static function get_cart_hash()
    {
        return WC()->cart->get_cart_hash();
    }

    /**
     * Check Cart is Empty
     *
     * @return bool
     */
    public static function is_empty_cart()
    {
        return WC()->cart->is_empty();
    }

    /**
     * Empty Cart complete
     */
    public static function empty_cart()
    {
        WC()->cart->empty_cart();
    }

    /**
     * Get Sum Price with Tax Item in Cart
     *
     * @return string
     */
    public static function get_cart_total()
    {
        return WC()->cart->get_cart_total();
    }

    /**
     * Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
     *
     * @return string
     */
    public static function get_cart_total_only_products_with_discount()
    {
        return WC()->cart->get_cart_contents_total();
    }

    /**
     * Get Number Item Product in Cart
     *
     * @return int
     */
    public static function get_number_product_in_cart()
    {
        return WC()->cart->get_cart_contents_count();
    }

    /**
     * @see https://github.com/woocommerce/woocommerce/blob/02cf0dfaed5923513de0c88add597d1560c2cfd2/includes/class-wc-cart.php#L1007
     *
     * @param int $product_id
     * @param int $quantity
     * @param int $variation_id
     * @param array $variation
     * @param array $cart_item_data
     * @return bool|string
     * @throws \Exception
     */
    public static function add_to_cart($product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array())
    {
        return WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);
        //return false or cart_key_items
    }

    /**
     * Remove From Cart
     *
     * @param $cart_item_key
     */
    public static function remove_item_cart($cart_item_key)
    {
        WC()->cart->remove_cart_item($cart_item_key);
    }

    public static function get_product_key_by_id_in_cart($product_id)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($product_id == $cart_item['product_id']) {
                return array('q' => $cart_item['quantity'], 'key' => $cart_item_key);
            }
        }
        return array('q' => 0, 'key' => false);
    }

    public static function set_quantity($cart_item_key, $quantity = 1, $refresh_totals = true)
    {
        WC()->cart->set_quantity($cart_item_key, $quantity, $refresh_totals);
    }

    public static function find_product_in_cart($product_id)
    {
        $product_cart_id = WC()->cart->generate_cart_id($product_id);
        $in_cart = WC()->cart->find_product_in_cart($product_cart_id);

        if ($in_cart) {
            return true;
        }

        return false;
    }

    public static function calculate_cart()
    {
        WC()->cart->calculate_totals();
    }

    public static function get_carts_items()
    {
        return WC()->cart->get_cart();
        //foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        //$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        //$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
        //if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
    }

    /**
     * Exist Coupon in Cart
     *
     * @param $coupon
     * @return bool
     */
    public static function exist_coupon_in_cart($coupon)
    {
        return WC()->cart->has_discount($coupon);
    }

    /**
     * Remove Coupon From Cart
     *
     * @param $applied_coupon
     */
    public static function remove_coupon_from_cart($applied_coupon)
    {
        WC()->cart->remove_coupon($applied_coupon);
    }

    /**
     * GET All Coupon that applied in Cart
     *
     * @return array
     */
    public static function get_all_coupon_applied_in_cart()
    {
        return WC()->cart->get_applied_coupons();
    }

    /**
     * @see https://www.businessbloomer.com/woocommerce-apply-coupon-programmatically-product-cart/
     * @param $coupon_code
     * @return bool
     */
    public static function add_coupon_to_cart($coupon_code)
    {
        $coupon = WC()->cart->apply_coupon($coupon_code);
        if ($coupon === true) {
            return true;
        }

        // Woocommerce Added a New A notice Must Show in Front
        // https://github.com/woocommerce/woocommerce/blob/02cf0dfaed5923513de0c88add597d1560c2cfd2/includes/class-wc-coupon.php#L914
        return false;
    }

}

new WooCommerce_Cart;