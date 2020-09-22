<?php

namespace WooCommerce_Dev;

use WordPress_ACL\Helper;

class WooCommerce_Affiliate
{

    // Global Option
    public static $theme_option_page_slug = 'wc-affiliates';

    // User Meta Option
    public static $user_meta_affiliate_id = 'affiliate_id';
    public static $user_meta_affiliate_ban = 'ban_affiliate'; //yes|no
    public static $user_meta_affiliate_rate = 'affiliate_rate';

    // Post Type Option
    public static $commission_post_type = 'wca-commission';
    public static $checkout_commission_post_type = 'wca-checkout';

    public function __construct()
    {
        // Create ACF Setting Page
        add_action('init', array($this, 'create_option_page'));

        // Set Affiliate Cookie
        add_action('init', array($this, 'set_affiliate_cookie'));

        // Set Affiliate Id in User Register
        add_action('user_register', array($this, 'set_affiliate_id'), 999, 1);

        // Set Affiliate ID in Create New Order
        add_action('woocommerce_new_order', array($this, 'woocommerce_new_order_set_affiliate'), 999, 1);

        // Add Post Type For Commission List
        add_action('init', array($this, 'create_post_type'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('manage_' . self::$commission_post_type . '_posts_columns', array($this, 'wca_commission_column'));
        add_action('manage_' . self::$commission_post_type . '_posts_custom_column', array($this, 'wca_commission_column_value'), 10, 2);
        add_filter('manage_' . self::$checkout_commission_post_type . '_posts_columns', array($this, 'wca_checkout_column'));
        add_action('manage_' . self::$checkout_commission_post_type . '_posts_custom_column', array($this, 'wca_checkout_column_value'), 10, 2);
        add_filter('post_row_actions', array($this, 'my_action_row'), 10, 2);
        add_filter('bulk_actions-edit-' . self::$commission_post_type, array($this, 'commission_bulk_actions'));

        // Set User Must VIP for Affiliate
        add_filter('woocommerce_dev_user_can_affiliate', function ($boolean, $user_id) {
            if ($boolean === false) {
                return false;
            }

            if ($boolean === true and \WP_Vip_User::has($user_id) === false) {
                return false;
            }

            return true;
        }, 999, 2);

        // Set Commission after Order Complete
        add_action('woocommerce_order_status_completed', array($this, 'wc_calculate_commission'), 999);
    }

    public function wc_calculate_commission($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $user_id = $order->get_customer_id();
        if ($user_id < 1) {
            return;
        }

        $run_before = get_post_meta($order_id, 'set_wc_affiliate_commission', true);
        if (!empty($run_before) and $run_before == "1") {
            return;
        }

        // Check User Has Affiliate
        $affiliate_id = self::get_affiliate_id($user_id);
        if ($affiliate_id === false) {
            return;
        }

        // Check User Affiliate has can
        if (self::user_can_affiliate($affiliate_id) === false) {
            return;
        }

        // Calculate Affiliate
        $total = $order->get_total();
        $rate = self::get_user_affiliate_rate($affiliate_id);
        $commission = ($total * $rate) / 100;

        // insert To database
        $post_id = wp_insert_post(array(
            'post_type' => self::$commission_post_type,
            'post_status' => 'publish',
            'post_date' => current_time('mysql'),
            'post_author' => 1,
            'meta_input' => array(
                "order_id" => $order_id,
                "order_date" => $order->get_date_created(),
                "user_id" => $user_id,
                "order_total" => $total,
                "affiliate_id" => $affiliate_id,
                "rate" => $rate,
                "commission" => $commission,
                "wp_lang" => WPLANG,
            ),
        ));

        // Set After Complete
        update_post_meta($order_id, 'set_wc_affiliate_commission', '1');
    }

    public function wca_checkout_column_value($column, $post_id)
    {
        global $post;

        switch ($column) {
            case "date_time":
                echo date_i18n("Y-m-d H:i", strtotime($post->post_date));
                break;
            case "user_id":
                $user_id = get_post_meta($post_id, 'user_id', true);
                echo Helper::get_user_email($user_id) . '<br />' . Helper::get_user_full_name($user_id);
                break;
            case "price":
                $price = get_post_meta($post_id, 'price', true);
                echo \WP_App::wc_price_language($price, get_post_meta($post_id, 'wp_lang', true));
                break;
            case "status":
                echo get_post_meta($post_id, 'checkout_status', true);
                break;
            case "bank_number":
                echo get_post_meta($post_id, 'bank_number', true);
                break;
            case "additional_desc":
                echo get_post_meta($post_id, 'additional_desc', true);
                break;
            case "lang":
                echo get_post_meta($post_id, 'wp_lang', true);
                break;
        }
    }

    public function wca_checkout_column($columns)
    {
        unset($columns['date']);
        unset($columns['title']);
        unset($columns['views']);

        $columns['date_time'] = __('Date', 'woocommerce-dev');
        $columns['user_id'] = __('User', 'woocommerce-dev');
        $columns['price'] = __('Price', 'woocommerce-dev');
        $columns['bank_number'] = __('Bank Number', 'woocommerce-dev');
        $columns['additional_desc'] = __('Description', 'woocommerce-dev');
        $columns['status'] = __('Checkout status', 'woocommerce-dev');
        $columns['lang'] = __('Language', 'woocommerce-dev');
        return $columns;
    }

    public function wca_commission_column_value($column, $post_id)
    {
        switch ($column) {
            case "order_id":
                echo '<a href="' . get_edit_post_link($post_id) . '">' . get_post_meta($post_id, 'order_id', true) . '</a>';
                break;
            case "order_date":
                $order_date = get_post_meta($post_id, 'order_date', true);
                echo date_i18n("Y-m-d H:i", strtotime($order_date));
                break;
            case "user_id":
                $user_id = get_post_meta($post_id, 'user_id', true);
                echo Helper::get_user_email($user_id) . '<br />' . Helper::get_user_full_name($user_id);
                break;
            case "order_total":
                $order_total = get_post_meta($post_id, 'order_total', true);
                echo \WP_App::wc_price_language($order_total, get_post_meta($post_id, 'wp_lang', true));
                break;
            case "affiliate_id":
                $affiliate_id = get_post_meta($post_id, 'affiliate_id', true);
                echo Helper::get_user_email($affiliate_id) . '<br />' . Helper::get_user_full_name($affiliate_id);
                break;
            case "rate":
                echo get_post_meta($post_id, 'rate', true);
                break;
            case "commission":
                $commission = get_post_meta($post_id, 'commission', true);
                echo \WP_App::wc_price_language($commission, get_post_meta($post_id, 'wp_lang', true));
                break;
            case "lang":
                echo get_post_meta($post_id, 'wp_lang', true);
                break;
        }
    }

    public function wca_commission_column($columns)
    {
        unset($columns['date']);
        unset($columns['title']);
        unset($columns['views']);

        $columns['order_id'] = __('Order ID', 'woocommerce-dev');
        $columns['order_date'] = __('Order Date', 'woocommerce-dev');
        $columns['user_id'] = __('User', 'woocommerce-dev');
        $columns['order_total'] = __('Total Price', 'woocommerce-dev');
        $columns['affiliate_id'] = __('Affiliate user', 'woocommerce-dev');
        $columns['rate'] = __('Rate', 'woocommerce-dev');
        $columns['commission'] = __('Commission', 'woocommerce-dev');
        $columns['lang'] = __('Language', 'woocommerce-dev');
        return $columns;
    }

    public function create_post_type()
    {
        $list = array(
            self::$commission_post_type => array('title' => __('Commission', 'woocommerce-dev')),
            self::$checkout_commission_post_type => array('title' => __('Checkout', 'woocommerce-dev')),
        );
        foreach ($list as $key => $val) {
            $labels = array(
                'name' => $val['title'],
                'singular_name' => $val['title'],
                'menu_name' => $val['title'],
                'name_admin_bar' => $val['title'],
                'add_new' => __('Add', 'woocommerce-dev'),
                'add_new_item' => __('Add', 'woocommerce-dev'),
                'new_item' => __('Create', 'woocommerce-dev'),
                'edit_item' => __('Edit', 'woocommerce-dev'),
                'view_item' => __('Show', 'woocommerce-dev'),
                'all_items' => __('All', 'woocommerce-dev'),
                'search_items' => __('Search', 'woocommerce-dev'),
                'parent_item_colon' => __('Parent:', 'woocommerce-dev'),
                'not_found' => __('No items.', 'woocommerce-dev'),
                'not_found_in_trash' => __('No items in trash.', 'woocommerce-dev')
            );
            $args = array(
                'labels' => $labels,
                'description' => $val['title'],
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'query_var' => false,
                'menu_icon' => 'dashicons-menu-alt',
                'has_archive' => true,
                'hierarchical' => false,
                'menu_position' => 8,
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'supports' => array(
                    //'title',
                    //'excerpt',
                    //'author',
                    //'editor',
                    'custom-fields' // For Active Post Meta in REST API
                ),
                //'rewrite'               => array( 'slug' => $key ),
                'show_in_rest' => false,
                'rest_base' => $key,
                'rest_controller_class' => 'WP_REST_Posts_Controller',
            );

            // Disable Edit in Commission
            if ($key == self::$commission_post_type) {
                $args['capabilities'] = array(
                    'create_posts' => 'do_not_allow', // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
                );
            }

            register_post_type($key, $args);
        }
    }

    function my_action_row($actions, $post)
    {
        if ($post->post_type == self::$commission_post_type) {
            unset($actions['edit']);
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    function commission_bulk_actions($actions)
    {
        unset($actions['edit']);
        return $actions;
    }

    public function admin_menu()
    {
        add_submenu_page(self::$theme_option_page_slug, __('Settings', 'woocommerce-dev'), __('Settings', 'woocommerce-dev'), 'manage_options', self::$theme_option_page_slug, null);
        add_submenu_page(self::$theme_option_page_slug, __('Commission', 'woocommerce-dev'), __('Commission', 'woocommerce-dev'), 'manage_options', 'edit.php?post_type=' . self::$commission_post_type, false);
        add_submenu_page(self::$theme_option_page_slug, __('Checkout', 'woocommerce-dev'), __('Checkout', 'woocommerce-dev'), 'manage_options', 'edit.php?post_type=' . self::$checkout_commission_post_type, false);
    }

    public function woocommerce_new_order_set_affiliate($order_id)
    {
        $order = wc_get_order($order_id);
        $current_user_id = $order->get_user_id();
        if (!empty($current_user_id) and is_numeric($current_user_id) and $current_user_id > 0) {
            self::set_affiliate_id($current_user_id);
        }
    }

    public function set_affiliate_id($current_user_id)
    {
        // Check Has Cookie
        if (isset($_COOKIE[self::get_cookie_name()]) and !empty($_COOKIE[self::get_cookie_name()]) and is_numeric($_COOKIE[self::get_cookie_name()])) {
            // Sanitize User ID
            $user_id = sanitize_text_field($_COOKIE[self::get_cookie_name()]);

            // Check User ID Exist
            if (self::user_can_affiliate($user_id) === false) {
                return;
            }

            // Same User Affiliate
            if ($user_id == $current_user_id) {
                return;
            }

            // Set User Meta
            update_user_meta($current_user_id, self::$user_meta_affiliate_id, $user_id);

            // Remove Cookie
            setcookie(self::get_cookie_name(), '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    public function set_affiliate_cookie()
    {
        if (isset($_GET[self::get_ref_param()]) and !empty($_GET[self::get_ref_param()]) and is_numeric($_GET[self::get_ref_param()])) {
            // Sanitize User ID
            $user_id = sanitize_text_field($_GET[self::get_ref_param()]);

            // Check User Can Affiliate
            if (self::user_can_affiliate($user_id) === false) {
                return;
            }

            // Same User Affiliate
            if (is_user_logged_in()) {
                if ($user_id == get_current_user_id()) {
                    return;
                }
            }

            // Set Cookie For Current User
            // @see https://stackoverflow.com/questions/11788355/how-to-set-a-cookie-in-wordpress
            setcookie(self::get_cookie_name(), $user_id, time() + self::get_cookie_time(), COOKIEPATH, COOKIE_DOMAIN);
        }
    }

    public function create_option_page()
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'page_title' => __('Affiliates', 'woocommerce-dev'),
                'menu_title' => __('Affiliates', 'woocommerce-dev'),
                'menu_slug' => self::$theme_option_page_slug,
                'position' => '25',
                'capability' => 'manage_options',
                'redirect' => false,
                'icon_url' => 'dashicons-image-filter',
                'update_button' => __('Update', 'acf'),
                'updated_message' => __("Options Updated", 'acf')
            ));
        }
    }

    public static function exist_user($user_id)
    {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE ID = %d", $user_id));
        return empty($count) || 1 > $count ? false : true;
    }

    public static function user_can_affiliate($user_id)
    {
        // Check User ID Exist
        if (self::exist_user($user_id) === false) {
            return false;
        }

        // Check User is Ban for affiliate
        $user_meta = get_user_meta($user_id, self::$user_meta_affiliate_ban, true);
        if ($user_meta == "yes") {
            return false;
        }

        return apply_filters('woocommerce_dev_user_can_affiliate', true, $user_id);
    }

    public static function get_affiliate_id($user_id)
    {
        $affiliate_id = get_user_meta($user_id, self::$user_meta_affiliate_id, true);
        if (!empty($affiliate_id) and is_numeric($affiliate_id) and $affiliate_id > 0) {
            return $affiliate_id;
        }

        return false;
    }

    public static function get_user_affiliate_rate($user_id)
    {
        $user_meta = get_user_meta($user_id, self::$user_meta_affiliate_rate, true);
        if (!empty($user_meta) and is_numeric($user_meta) and $user_meta > 0) {
            return $user_meta;
        }

        return self::get_commission_rate();
    }

    public static function get_ref_param()
    {
        $value = get_field('wc-affiliates-get-ref-id', 'option');
        if (empty($value) || is_null($value)) {
            $value = 'affiliate_id';
        }
        return apply_filters('woocommerce_dev_affiliate_ref_param', $value);
    }

    public static function get_cookie_name()
    {
        $value = get_field('wc-affiliates-get-cookie-name', 'option');
        if (empty($value) || is_null($value)) {
            $value = 'wc_dev_affiliate';
        }
        return apply_filters('woocommerce_dev_affiliate_cookie_name', $value);
    }

    public static function get_cookie_time()
    {
        $value = get_field('wc-affiliates-get-cookie-days', 'option');
        if (empty($value) || is_null($value)) {
            $value = 30;
        }
        $cookie_time = $value * 24 * 60 * 60;
        return apply_filters('woocommerce_dev_affiliate_cookie_time', $cookie_time);
    }

    public static function get_commission_rate()
    {
        $value = get_field(\WP_App::get_field_prefix() . 'wc-affiliates-get-commission-rate', 'option');
        if (empty($value) || is_null($value)) {
            $value = 1;
        }
        return apply_filters('woocommerce_dev_affiliate_commission_rate', $value);
    }

    public static function get_minimum_checkout_price()
    {
        $value = get_field(\WP_App::get_field_prefix() . 'wc-affiliates-get-minimum-checkout-price', 'option');
        if (empty($value) || is_null($value)) {
            $value = 0;
        }
        return apply_filters('woocommerce_dev_affiliate_minimum_checkout_price', $value);
    }

    public static function get_user_affiliates_link($user_id)
    {
        return add_query_arg(
            array(
                self::get_ref_param() => $user_id
            ),
            get_site_url(null, "/")
        );
    }

    public static function get_subset_of_users($user_id)
    {
        $users_ids = get_users(array(
                'fields' => array('id'),
                'meta_query' => array(
                    array(
                        'key' => self::$user_meta_affiliate_id,
                        'value' => $user_id,
                        'compare' => '='
                    )
                )
            )
        );
        if (empty($users_ids)) {
            return array();
        }
        $list = array();
        foreach ($users_ids as $user_id) {
            $list[] = $user_id->id;
        }

        return $list;
    }

    public static function get_user_commission_list($user_id)
    {
        $args = array(
            'post_type' => self::$commission_post_type,
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'order' => 'DESC',
            'fields' => 'ids',
            'cache_results' => false,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => array(
                array(
                    'key' => 'affiliate_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
        );
        $query = new \WP_Query($args);
        if (empty($query->posts)) {
            return array();
        }

        return $query->posts;
    }

    public static function get_user_commission_checkout_list($user_id)
    {
        $args = array(
            'post_type' => self::$checkout_commission_post_type,
            'post_status' => 'publish',
            'posts_per_page' => '-1',
            'order' => 'DESC',
            'fields' => 'ids',
            'cache_results' => false,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => array(
                array(
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
        );
        $query = new \WP_Query($args);
        if (empty($query->posts)) {
            return array();
        }

        return $query->posts;
    }

    public static function get_user_sum_commission_price($user_id)
    {
        $sum_commission = 0;
        $my_commission = self::get_user_commission_list($user_id);
        foreach ($my_commission as $post_id) {
            $commission = get_post_meta($post_id, 'commission', true);
            if (!empty($commission) and is_numeric($commission) and $commission > 0) {
                $sum_commission = $sum_commission + $commission;
            }
        }
        return $sum_commission;
    }

    public static function get_user_sum_checkout_commission_price($user_id)
    {
        $sum_checkout = 0;
        $my_checkout = self::get_user_commission_checkout_list($user_id);
        foreach ($my_checkout as $post_id) {
            $price = get_post_meta($post_id, 'price', true);
            if (!empty($price) and is_numeric($price) and $price > 0) {
                $sum_checkout = $sum_checkout + $price;
            }
        }

        return $sum_checkout;
    }

    public static function insert_commission_checkout($args)
    {
        /*$args = array(
            'user_id' => get_current_user_id(),
            'price' => 0,
            'checkout_status' => 'no',
            'bank_number' => '',
            'additional_desc' => '',
            'wp_lang' => WPLANG,
        );*/
        return wp_insert_post(array(
            'post_type' => self::$checkout_commission_post_type,
            'post_status' => 'publish',
            'post_date' => current_time('mysql'),
            'post_author' => 1,
            'meta_input' => $args,
        ));
    }
}

new WooCommerce_Affiliate;
