<?php

namespace WooCommerce_Dev;

class WooCommerce_Helper
{
    /**
     * Set Localize Filter for Woocommerce
     *
     * @Hook
     * @param $value
     * @param $option
     * @return string|void
     */
    public static function set_localize_option($value, $option)
    {
        return __($value, 'woocommerce');
    }

    /**
     * Get Option List Woocommerce
     *
     * @param bool $key
     * @return mixed
     */
    public static function get_woocommerce_option($key = false)
    {
        $option = array(
            'timezone' => wc_timezone_string(),
            'currency' => get_woocommerce_currency(),
            'currency_format' => get_woocommerce_currency_symbol(),
            'tax_included' => wc_prices_include_tax(),
            'shipping_enabled' => wc_shipping_enabled(),
            'tax_enabled' => wc_tax_enabled(),
            'review_ratings_enabled' => wc_review_ratings_enabled(),
            'weight_unit' => get_option('woocommerce_weight_unit'),
            'weight_unit_localize' => __(get_option('woocommerce_weight_unit'), 'woocommerce'),
            'dimension_unit_localize' => __(get_option('woocommerce_dimension_unit'), 'woocommerce'),
            'ssl_enabled' => ('yes' === get_option('woocommerce_force_ssl_checkout')),
            'permalinks_enabled' => ('' !== get_option('permalink_structure')),
            'currency_pos' => get_option('woocommerce_currency_pos'),
            'price_decimal_sep' => get_option('woocommerce_price_decimal_sep'),
            'price_num_decimals' => get_option('woocommerce_price_num_decimals'),
            'price_thousand_sep' => get_option('woocommerce_price_thousand_sep')
        );

        if (empty($key)) {
            return $option;
        }
        return $option[$key];
    }

    /**
     * Show Price With Symbol in Woocommerce
     *
     * @param $price
     * @return string
     */
    public static function wc_price($price)
    {
        return strip_tags(wc_price($price));
    }

    /**
     * Get Customer Detail
     *
     * @param $user_id
     * @return array
     * @throws \Exception
     */
    public static function get_customer($user_id)
    {
        $customer = new \WC_Customer($user_id);
        return $customer->get_data();
    }

    /**
     * Get Tree Of Terms List
     *
     * @param $arg
     * @return int|\WP_Error|\WP_Term[]
     * @example for check children user if(isset($item['children']) and !empty($item['children'])) { ..
     */
    public static function get_terms_array($arg = array())
    {

        $defaults = array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'count' //term_id
        );

        // Parse incoming $args into an array and merge it with $defaults
        $args = wp_parse_args($arg, $defaults);

        // Get Terms
        $terms = get_terms($args);

        // Sort children
        $sorted_terms = array();
        self::sort_terms_hierarchically($terms, $sorted_terms);

        // return data
        return $sorted_terms;
    }

    /**
     * @param $type
     * @return false|string
     * @see https://wpcrumbs.com/how-to-get-woocommerce-page-urls-in-woocommerce-3-x/
     *
     * -- List: --
     * myaccount
     * shop
     * cart
     * checkout
     */
    public static function get_page_url($type = 'cart')
    {
        if ($type == "my-account") {
            $type = "myaccount";
        }
        return wc_get_page_permalink($type);
    }

    /**
     * Get Page From Account Url
     *
     * @param string $type
     * @return string
     *
     * -- Default list --
     * dashboard
     * orders
     * downloads
     * edit-address
     * edit-account
     * customer-logout
     */
    public static function get_page_account_url($type = 'view-order')
    {
        return wc_get_account_endpoint_url('customer-logout');
    }

    /**
     * Get Navigation Account List Menu
     */
    public static function get_navigation_account()
    {
        $list = array();
        foreach (wc_get_account_menu_items() as $endpoint => $label) {
            $list[] = array(
                'class' => wc_get_account_menu_item_classes($endpoint),
                'url' => esc_url(wc_get_account_endpoint_url($endpoint)),
                'endpoint' => $endpoint,
                'label' => esc_html($label)
            );
        }

        return $list;
    }

    /**
     * List of Notice Type apply_filters( 'woocommerce_notice_types', array( 'error', 'success', 'notice' ) );
     * @param string $notice_type
     * @return array[]
     */
    public static function get_wc_notices($notice_type = '')
    {
        return wc_get_notices();
    }

    /**
     * Remove All Notice in WooCommerce
     */
    public static function clear_all_wc_notice()
    {
        wc_clear_notices();
    }

    /**
     * Add New WC Session
     *
     * @param $key
     * @param $value
     */
    public static function add_wc_session($key, $value)
    {
        WC()->session->set($key, $value);
    }

    /**
     * Get WC Session
     *
     * @param $key
     * @return array|string|null
     */
    public static function get_wc_session($key)
    {
        return WC()->session->get($key);
    }

    /**
     * Remove WC Session
     *
     * @param $key
     */
    public static function remove_wc_session($key)
    {
        WC()->session->__unset($key);
    }

    /**
     * Get List Post From Post Type
     *
     * @param array $arg
     * @param bool $title
     * @return array
     */
    public static function wp_query($arg = array(), $title = true)
    {
        // Create Empty List
        $list = array();

        // Prepare Params
        $default = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'order' => 'ASC',
            'fields' => 'ids',
            'cache_results' => false,
            'no_found_rows' => true, //@see https://10up.github.io/Engineering-Best-Practices/php/#performance
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        $args = wp_parse_args($arg, $default);

        // Get Data
        $query = new \WP_Query($args);

        // Get SQL
        //echo $query->request;
        //exit;

        // Added To List
        foreach ($query->posts as $ID) {
            if ($title) {
                $list[$ID] = get_the_title($ID);
            } else {
                $list[] = $ID;
            }
        }

        return $list;
    }

    /**
     * Sort Woocommerce Term
     *
     * @param array $terms
     * @param array $into
     * @param int $parent_id
     */
    public static function sort_terms_hierarchically(array &$terms, array &$into, $parent_id = 0)
    {
        foreach ($terms as $i => $term) {
            if ($term->parent == $parent_id) {
                $into[$term->term_id] = $term;
                unset($terms[$i]);
            }
        }

        foreach ($into as $top_term) {
            $top_term->children = array();
            self::sort_terms_hierarchically($terms, $top_term->children, $top_term->term_id);
        }

        // Example
        //$terms = get_the_terms( 'taxslug', $post );
        //$sorted_terms = array();
        //sort_terms_hierarchically( $terms, $sorted_terms );
    }

    /**
     * Format a unix timestamp or MySQL datetime into an RFC3339 datetime
     *
     * @param $timestamp
     * @param bool $convert_to_utc
     * @param bool $convert_to_gmt
     * @return string
     */
    public static function format_datetime($timestamp, $convert_to_utc = false, $convert_to_gmt = false)
    {
        if ($convert_to_gmt) {
            if (is_numeric($timestamp)) {
                $timestamp = date('Y-m-d H:i:s', $timestamp);
            }

            $timestamp = get_gmt_from_date($timestamp);
        }

        if ($convert_to_utc) {
            $timezone = new \DateTimeZone(wc_timezone_string());
        } else {
            $timezone = new \DateTimeZone('UTC');
        }

        try {

            if (is_numeric($timestamp)) {
                $date = new \DateTime("@{$timestamp}");
            } else {
                $date = new \DateTime($timestamp, $timezone);
            }

            // convert to UTC by adjusting the time based on the offset of the site's timezone
            if ($convert_to_utc) {
                $date->modify(-1 * $date->getOffset() . ' seconds');
            }
        } catch (\Exception $e) {

            $date = new \DateTime('@0');
        }

        return $date->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Object To Array
     *
     * @param $r
     * @return array
     */
    public static function object_to_array($r)
    {
        if (is_object($r)) {
            if (method_exists($r, 'toArray')) {
                return $r->toArray(); // returns result directly
            } else {
                $r = get_object_vars($r);
            }
        }

        if (is_array($r)) {
            $r = array_map(array(__CLASS__, __METHOD__), $r); // recursive function call
        }

        return $r;
    }
}

new WooCommerce_Helper;