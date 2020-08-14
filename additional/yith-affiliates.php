<?php

namespace WooCommerce_Dev;

class WooCommerce_Yith_Affiliates
{
    public static $acf_user_field_name = 'customer-shipping-list';

    public function __construct()
    {
        // Add Automatic User To Affiliate
        add_action('user_register', array($this, 'add_user_affiliate'), 10, 1);

    }

    /**
     * Add User To Affiliate
     *
     * @where class=> YITH_WCAF_Affiliate_Handler Method=> add_affiliate
     * @param $user_id
     * @return void
     */
    public function add_user_affiliate($user_id)
    {
        global $wpdb;
        $defaults = array(
            'token' => $user_id,
            'user_id' => $user_id,
            'enabled' => 1,
            'rate' => 'NULL',
            'earnings' => 0,
            'refunds' => 0,
            'paid' => 0,
            'click' => 0,
            'conversion' => 0,
            'banned' => 0,
            'payment_email' => ''
        );
        $res = $wpdb->insert($wpdb->prefix . 'yith_wcaf_affiliates', $defaults);
        $affiliate_id = $wpdb->insert_id;
        $user = get_user_by('id', $user_id);
        $user->add_role('yith_affiliate');
    }

    public static function get_affiliate_link($user_id)
    {
        return add_query_arg(array(get_option('yith_wcaf_referral_var_name', 'ref') => $user_id), home_url());
    }

}

new WooCommerce_Yith_Affiliates;
