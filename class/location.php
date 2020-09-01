<?php

namespace WooCommerce_Dev;

/**
 * @see https://github.com/woocommerce/woocommerce/blob/master/includes/class-wc-countries.php
 */
class WooCommerce_Location
{
    public static $IRAN_Country_Key = 'IR';

    public function __construct()
    {
    }

    /**
     * Get List Of Country
     */
    public static function get_list_country()
    {
        $countries_obj = new \WC_Countries(); //WC()->countries->{method}
        return $countries_obj->__get('countries'); // assoc array @example IR => 'ایران'
    }

    /**
     * Get Default Country in Woocommerce
     *
     * @return string
     */
    public static function get_default_country()
    {
        $countries_obj = new \WC_Countries();
        return $countries_obj->get_base_country(); //IR or GB
    }

    /**
     * GET List of state from Country
     *
     * @param string $country
     * @return array|bool|false
     */
    public static function get_list_states_from_country($country = '')
    {
        $countries_obj = new \WC_Countries();
        if (empty($country)) {
            $country = self::get_default_country();
        }
        return $countries_obj->get_states($country);
    }

    /**
     * Get State Name By Key
     *
     * @param $country
     * @param $state
     * @return bool|mixed
     */
    public static function get_state_name_by_key($country, $state)
    {
        $state_list = self::get_list_states_from_country($country);
        if (isset($state_list[$state])) {
            return $state_list[$state];
        }

        return false;
    }

    /**
     * Get custom Shipping in Cart or checkOut Page
     *
     * @see https://stackoverflow.com/questions/52245997/woocommerce-set-billing-and-shipping-information
     * @see https://stackoverflow.com/questions/52245997/woocommerce-set-billing-and-shipping-information
     * @see https://stackoverflow.com/questions/47409734/display-shipping-cost-on-product-page-woocommerce
     * @param array $args
     * @return array|string|null
     */
    public static function set_customer_shipping($args = array())
    {
        // Use class-wc-ajax -> update_order_review()
        WC()->customer->set_props(
            array(
                'billing_country' => $args['billing_country'],
                'billing_state' => $args['billing_state'],
                'billing_postcode' => $args['billing_postcode'],
                'billing_city' => $args['billing_city'],
                'billing_address_1' => $args['billing_address_1'],
                'billing_address_2' => null,
            )
        );
        WC()->customer->set_props(
            array(
                'shipping_country' => $args['billing_country'],
                'shipping_state' => $args['billing_state'],
                'shipping_postcode' => $args['billing_postcode'],
                'shipping_city' => $args['billing_city'],
                'shipping_address_1' => $args['billing_address_1'],
                'shipping_address_2' => null,
            )
        );
        WC()->customer->set_calculated_shipping(true);
        WC()->customer->save();

        // Save User Meta
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'shipping_country', $args['billing_country']);
            update_user_meta($user_id, 'shipping_state', $args['billing_state']);
            update_user_meta($user_id, 'shipping_postcode', $args['billing_postcode']);
            update_user_meta($user_id, 'shipping_city', $args['billing_city']);
            update_user_meta($user_id, 'shipping_address_1', $args['billing_address_1']);

            update_user_meta($user_id, 'billing_country', $args['billing_country']);
            update_user_meta($user_id, 'billing_state', $args['billing_state']);
            update_user_meta($user_id, 'billing_postcode', $args['billing_postcode']);
            update_user_meta($user_id, 'billing_city', $args['billing_city']);
            update_user_meta($user_id, 'billing_address_1', $args['billing_address_1']);
        }

        // Calculate Again
        WooCommerce_Cart::calculate();

        /*
        Array
        $customer_session = WC()->session->get('customer');
        (
            [id] => 2
            [date_modified] => 2020-08-27T09:47:31+04:30
            [postcode] =>
            [city] =>
            [address_1] =>
            [address] =>
            [address_2] =>
            [state] => //MZN
            [country] => IR
            [shipping_postcode] =>
            [shipping_city] =>
            [shipping_address_1] =>
            [shipping_address] =>
            [shipping_address_2] =>
            [shipping_state] =>
            [shipping_country] => GB
            [is_vat_exempt] =>
            [calculated_shipping] =>
            [first_name] => مهدی
            [last_name] => حسینی
            [company] =>
            [phone] => 09358510091
            [email] => mehrshad.198@gmail.com
            [shipping_first_name] =>
            [shipping_last_name] =>
            [shipping_company] =>
        )*/
    }

    /**
     * Get Shipping Method
     *
     * @param bool $only_enable
     * @return mixed
     */
    public static function get_shipping_methods($only_enable = true)
    {
        return WC()->shipping()->get_shipping_methods();
    }
}

new WooCommerce_Location;