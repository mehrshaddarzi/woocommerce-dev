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
        WC()->cart->calculate_shipping();
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
        return WC()->cart->get_coupons();
        /*
        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td data-title="<?php echo esc_attr( wc_cart_totals_coupon_label( $coupon, false ) ); ?>"><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>
        */
        //return WC()->cart->get_applied_coupons(); get index array of coupon code => array('mehr', 't2');
    }

    /**
     * GEt custom Price Coupon in Cart
     *
     * @param $coupon
     * @return float|string|void
     */
    public static function get_price_coupon_in_cart($coupon)
    {
        if (is_string($coupon)) {
            $coupon = new WC_Coupon($coupon);
        }
        $discount_amount_html = '';
        $amount = WC()->cart->get_coupon_discount_amount($coupon->get_code(), WC()->cart->display_cart_ex_tax);
        $discount_amount_html = $amount;

        if ($coupon->get_free_shipping() && empty($amount)) {
            $discount_amount_html = __('Free shipping coupon', 'woocommerce');
        }

        // Get From wc_cart_totals_coupon_html()
        return $discount_amount_html;
        //return apply_filters('woocommerce_coupon_discount_amount_html', $discount_amount_html, $coupon);
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
        // https://stackoverflow.com/questions/41593442/woocommerce-ajax-apply-coupon-code-to-basket
        // https://github.com/woocommerce/woocommerce/blob/02cf0dfaed5923513de0c88add597d1560c2cfd2/includes/class-wc-coupon.php#L914
        return false;
    }

    /**
     * Remove All Coupons From Cart
     */
    public static function remove_all_coupon()
    {
        WC()->cart->remove_coupons();
    }

    /**
     * Get Cart Fees List
     *
     * @return array
     */
    public static function get_cart_fees()
    {
        return WC()->cart->get_fees();
        // Name: $fee->name
        // Price: $cart_totals_fee_html = WC()->cart->display_prices_including_tax() ? WooCommerce_Helper::wc_price($fee->total + $fee->tax) : WooCommerce_Helper::wc_price($fee->total);
    }

    /**
     * Add Fee
     *
     * @param $title
     * @param $price
     */
    public static function add_fee($title, $price)
    {
        WC()->cart->add_fee($title, $price);
    }

    /**
     * Get List shipping Item
     *
     * @return array
     */
    public static function get_list_shipping_items()
    {
        $packages = WC()->shipping()->get_packages();
        foreach ($packages as $i => $package) {
            $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
            $product_names = array();

            if (count($packages) > 1) {
                foreach ($package['contents'] as $item_id => $values) {
                    $product_names[$item_id] = $values['data']->get_name() . ' &times;' . $values['quantity'];
                }
                $product_names = apply_filters('woocommerce_shipping_package_details_array', $product_names, $package);
            }

            return array(
                'package' => $package,
                'available_methods' => $package['rates'],
                'show_package_details' => count($packages) > 1,
                'show_shipping_calculator' => is_cart() && apply_filters('woocommerce_shipping_show_shipping_calculator', true, $i, $package),
                'package_details' => implode(', ', $product_names),
                /* translators: %d: shipping package number */
                'package_name' => apply_filters('woocommerce_shipping_package_name', (($i + 1) > 1) ? sprintf(_x('Shipping %d', 'shipping packages', 'woocommerce'), ($i + 1)) : _x('Shipping', 'shipping packages', 'woocommerce'), $i, $package),
                'index' => $i,
                'chosen_method' => $chosen_method,
                'formatted_destination' => WC()->countries->get_formatted_address($package['destination'], ', '),
                'has_calculated_shipping' => WC()->customer->has_calculated_shipping(),
            );
        }
    }

    public static function get_cart_zone_id()
    {
        $shipping_packages = WC()->cart->get_shipping_packages();
        $shipping_zone = wc_get_shipping_zone(reset($shipping_packages));
        $zone_id = $shipping_zone->get_id();
        $zone_name = $shipping_zone->get_zone_name();
        return array('id' => $zone_id, 'name' => $zone_name);
    }

    public static function reset_shipping()
    {
        // Reset Shipping Method
        // @see https://stackoverflow.com/questions/55272146/reset-previous-chosen-shipping-method-in-woocommerce-checkout-page
        WC()->shipping()->reset_shipping();
    }

    public static function set_shipping_method($method)
    {
        // Reset Shipping Method
        // @see https://stackoverflow.com/questions/55272146/reset-previous-chosen-shipping-method-in-woocommerce-checkout-page
        $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
        $posted_shipping_methods = array($method);

        if (is_array($posted_shipping_methods)) {
            foreach ($posted_shipping_methods as $i => $value) {
                $chosen_shipping_methods[$i] = $value;
            }
        }

        WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
    }

    public static function get_shipping_method()
    {
        // is a index array [0] => method name
        return WC()->session->chosen_shipping_methods;
    }
}

new WooCommerce_Cart;