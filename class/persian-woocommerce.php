<?php

namespace WooCommerce_Dev;

class Persian_WooCommerce
{
    public function __construct()
    {
        // Remove submenu Page
        add_action('admin_menu', array($this, 'remove_submenu_page'), 999);

        // Fix Date in Post [WP-ParsiDate]
        add_action('save_post', array($this, 'fix_post_date'), 99, 3);

        // Remove Pay.Ir Gateway
        add_filter('woocommerce_payment_gateways', array($this, 'woo_add_gateway_class'), 999);

        // Remove Dashboard Widget
        add_action('wp_dashboard_setup', array($this, 'remove_dashboard_meta_boxes'), 9999 );
    }

    /**
     * Remove Widget Dashboard
     */
    function remove_dashboard_meta_boxes()
    {
        remove_meta_box( 'persian_woocommerce_feed', 'dashboard', 'normal' );
    }

    /**
     * Remove PayIR From Persian Woocommerce
     *
     * @see https://growdevelopment.com/remove-default-woocommerce-payment-gateways/
     * @param $methods
     * @return mixed
     */
    function woo_add_gateway_class($methods)
    {
        $remove_gateways = array(
            'Woocommerce_Ir_Gateway_PayIr'
        );
        foreach ($methods as $key => $value) {
            if (in_array($value, $remove_gateways)) {
                unset($methods[$key]);
            }
        }
        return $methods;
    }

    /**
     * Fix Post Date time
     *
     * @param $post_ID
     * @param $post
     * @param $update
     */
    public function fix_post_date($post_ID, $post, $update)
    {
        global $wpdb;

        if (!is_admin()) {
            return;
        }

        $post_date = $wpdb->get_var("SELECT `post_date` FROM `{$wpdb->posts}` WHERE `ID` = " . $post_ID);
        $explode = explode(" ", $post_date);
        $explode_date = explode("-", $explode[0]);
        $current_year = $explode_date[0];
        $current_jalali_year = date_i18n("Y");
        if ($current_year == $current_jalali_year) {
            // Change Post_date
            $post_date_explode = explode(" ", $post_date);
            $new_post_date = gregdate("Y-m-d", $post_date_explode[0]);

            // Change post_date_gmt
            $post_date_gmt = $wpdb->get_var("SELECT `post_date_gmt` FROM `{$wpdb->posts}` WHERE `ID` = " . $post_ID);
            $post_date_gmt_explode = explode(" ", $post_date_gmt);
            $new_post_date_gmt = gregdate("Y-m-d", $post_date_gmt_explode[0]);

            // Update
            $wpdb->update($wpdb->posts, array(
                'post_date_gmt' => $new_post_date_gmt . ' ' . $post_date_gmt_explode[1],
                'post_date' => $new_post_date . ' ' . $post_date_explode[1],
            ), array(
                'ID' => $post_ID
            ));
        }
    }

    /**
     * Remove submenu Page
     */
    public function remove_submenu_page()
    {
        remove_submenu_page('woocommerce', 'wc-persian-themes');
        remove_submenu_page('woocommerce', 'wc-persian-plugins');
        remove_submenu_page('persian-wc', 'persian-wc-app');
        remove_submenu_page('persian-wc', 'persian-wc-plugins');
        remove_submenu_page('persian-wc', 'persian-wc-themes');
        remove_submenu_page('persian-wc', 'persian-wc-about');
    }
}

new Persian_WooCommerce;