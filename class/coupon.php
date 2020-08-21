<?php

namespace WooCommerce_Dev;

class WooCommerce_Coupon
{
    public function __construct()
    {

    }

    /**
     * Check Enable Coupon system in WooCommerce
     *
     * @return bool
     */
    public static function is_enabled()
    {
        return wc_coupons_enabled();
    }

    /**
     * Get Coupon Types
     *
     * @return array
     */
    public static function get_coupon_types()
    {
        return wc_get_coupon_types();
    }

    /**
     * False if Not Found and int for found
     *
     * @param string $code
     * @return int
     */
    public static function get_coupon_id($code = '')
    {
        return wc_get_coupon_id_by_code($code);
    }

    /**
     * Get Coupon Used [WC_API_Coupons Class]
     *
     * @param string $code_or_id
     * @return array|bool
     */
    public static function get_coupon($code_or_id = '')
    {
        $coupon = new \WC_Coupon($code_or_id);

        if (0 === $coupon->get_id()) {
            return false;
        }

        return array(
            'id' => $coupon->get_id(),
            'code' => $coupon->get_code(),
            'type' => $coupon->get_discount_type(),
            'created_at' => $coupon->get_date_created() ? $coupon->get_date_created()->getTimestamp() : 0, // API gives UTC times.
            'updated_at' => $coupon->get_date_modified() ? $coupon->get_date_modified()->getTimestamp() : 0, // API gives UTC times.
            'amount' => wc_format_decimal($coupon->get_amount(), 2),
            'individual_use' => $coupon->get_individual_use(),
            'product_ids' => array_map('absint', (array)$coupon->get_product_ids()),
            'exclude_product_ids' => array_map('absint', (array)$coupon->get_excluded_product_ids()),
            'usage_limit' => $coupon->get_usage_limit() ? $coupon->get_usage_limit() : null,
            'usage_limit_per_user' => $coupon->get_usage_limit_per_user() ? $coupon->get_usage_limit_per_user() : null,
            'limit_usage_to_x_items' => (int)$coupon->get_limit_usage_to_x_items(),
            'usage_count' => (int)$coupon->get_usage_count(),
            'expiry_date' => $coupon->get_date_expires() ? $coupon->get_date_expires()->getTimestamp() : null, // API gives UTC times.
            'enable_free_shipping' => $coupon->get_free_shipping(),
            'product_category_ids' => array_map('absint', (array)$coupon->get_product_categories()),
            'exclude_product_category_ids' => array_map('absint', (array)$coupon->get_excluded_product_categories()),
            'exclude_sale_items' => $coupon->get_exclude_sale_items(),
            'minimum_amount' => wc_format_decimal($coupon->get_minimum_amount(), 2),
            'maximum_amount' => wc_format_decimal($coupon->get_maximum_amount(), 2),
            'customer_emails' => $coupon->get_email_restrictions(),
            'description' => $coupon->get_description(),
        );
    }

    /**
     * Get Coupon List
     *
     * @param array $arg
     * @param string $field
     * @return array
     */
    public static function get_coupon_list($arg = array(), $field = 'all')
    {
        // Set base query arguments
        $query_args = array(
            'fields' => 'ids',
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
        );
        $args = wp_parse_args($arg, $query_args);

        // Get Data
        $Query = WooCommerce_Helper::wp_query($args, false);
        $list = array();
        foreach ($Query as $id) {
            $coupon = self::get_coupon($id);
            if ($coupon === false) {
                continue;
            }

            if ($field == "code") {
                $list[$id] = $coupon['code'];
            } else {
                $list[$id] = $coupon;
            }
        }

        return $list;
    }


}

new WooCommerce_Coupon;