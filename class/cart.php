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

        // Disable All Message Cart
        // @see https://stackoverflow.com/questions/37126658/hide-added-to-cart-message-in-woocommerce
        add_filter('wc_add_to_cart_message_html', '__return_false');

    }

    /**
     * Clear Cart
     */
    public static function clear()
    {
        WC()->cart->empty_cart();
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
    public static function is_empty()
    {
        return WC()->cart->is_empty();
    }

    /**
     * Get Cart Detail
     *
     * @return mixed|void
     */
    public static function get()
    {
        $cart = array(
            'cart_contents' => WC()->cart->get_cart(), // Legacy WC()->cart->cart_contents
            'removed_cart_contents' => WC()->cart->removed_cart_contents,
            'shipping_methods' => WC()->cart->shipping_methods,
            'coupon_discount_totals' => WC()->cart->coupon_discount_totals,
            'coupon_discount_tax_totals' => WC()->cart->coupon_discount_tax_totals,
            'applied_coupons' => WC()->cart->applied_coupons,
            'totals' => WC()->cart->totals,
            'sum' => array(
                'quantity' => WC()->cart->get_cart_contents_count(),
                'weight' => WC()->cart->get_cart_contents_weight(),
                'regular_price' => self::get_total_product_regular_price(),
                'discount' => self::get_total_product_discount(),
                'subtotal' => WC()->cart->get_subtotal()
            )
        );

        return apply_filters('woocommerce_dev_cart_data', $cart);
    }

    /**
     * Get Cart Items
     *
     * @return array
     */
    public static function get_items()
    {
        return WC()->cart->get_cart();
        //foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        //$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        //$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
        //if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
        //$cart_item['quantity']
        //echo wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key) . '&nbsp;')
        //apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key )
        //apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key )
        // esc_url( wc_get_cart_remove_url( $cart_item_key ) )
    }

    /**
     * Calculate Again Cart
     */
    public static function calculate()
    {
        WC()->cart->calculate_totals();
    }

    /**
     * Get Number Item (sum) Product in Cart
     *
     * @return int
     */
    public static function get_sum_product_quantity_product()
    {
        return WC()->cart->get_cart_contents_count();
    }

    /**
     * Get Sum Price with Tax Item in Cart
     *
     * @return string
     */
    public static function get_cart_total()
    {
        return (wc_prices_include_tax() ? WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() : WC()->cart->get_cart_contents_total());
        //return WC()->cart->get_cart_total();
    }

    /**
     * Gets cart total after calculation.
     *
     * @return float
     */
    public static function get_total()
    {
        return WC()->cart->total;
    }

    /**
     * Gets cart Subtotal only Product
     *
     * @return float
     */
    public static function get_sub_total()
    {
        return WC()->cart->subtotal;
    }

    /**
     * Get Shipping Total
     *
     * @return bool|int|mixed|string|\WC_Tax
     */
    public static function get_shipping_total()
    {
        return WC()->cart->shipping_total;
    }

    /**
     * Cart Need Shipping
     *
     * @return bool
     */
    public static function cart_need_shipping()
    {
        return (WC()->cart->needs_shipping() && WC()->cart->show_shipping());
    }

    /**
     * Enable Tax
     *
     * @return bool
     */
    public static function enable_tax()
    {
        return wc_prices_include_tax();
    }

    /**
     * Get Sum Total Tax
     *
     * @return bool|int|mixed|string|\WC_Tax
     */
    public static function get_total_tax()
    {
        return WC()->cart->cart_contents_tax;
    }

    /**
     * get total Discount For Product has on sale
     *
     * @return float|int
     */
    public static function get_total_product_discount()
    {
        $discount_total = 0;
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
            $_product = $values['data'];
            if ($_product->is_on_sale()) {
                $regular_price = $_product->get_regular_price();
                $sale_price = $_product->get_sale_price();
                $discount = ($regular_price - $sale_price) * $values['quantity'];
                $discount_total += $discount;
            }
        }

        return $discount_total;
    }

    /**
     * get total regular Price For Product
     *
     * @return float|int
     */
    public static function get_total_product_regular_price()
    {
        $regular_total = 0;
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
            $_product = $values['data'];
            $regular_price = $_product->get_regular_price();
            $regular = $regular_price * $values['quantity'];
            $regular_total += $regular;
        }

        return $regular_total;
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
        // return true or False
    }

    /**
     * Get Product Key By ID in Cart Woocommerce
     *
     * @param $product_id
     * @return mixed
     */
    public static function get_product_key_by_id_in_cart($product_id)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($product_id == $cart_item['product_id']) {
                return array('quantity' => $cart_item['quantity'], 'key' => $cart_item_key);
            }
        }

        return false;
    }

    /**
     * Set New Quantity for Product
     *
     * @param $cart_item_key
     * @param int $quantity
     * @param bool $refresh_totals
     */
    public static function set_quantity($cart_item_key, $quantity = 1, $refresh_totals = true)
    {
        WC()->cart->set_quantity($cart_item_key, $quantity, $refresh_totals);
    }

    /**
     * check Product Exist in Cart
     *
     * @param $product_id
     * @return bool
     */
    public static function find_product_in_cart($product_id)
    {
        $product_cart_id = WC()->cart->generate_cart_id($product_id);
        $in_cart = WC()->cart->find_product_in_cart($product_cart_id);

        if ($in_cart) {
            return true;
        }

        return false;
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