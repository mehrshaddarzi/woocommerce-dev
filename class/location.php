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

}

new WooCommerce_Location;