<?php

namespace WooCommerce_Dev;

/**
 * Class WooCommerce_Yith_Compare
 * @package WooCommerce_Dev
 *
 * @Plugin:
 * https://fa.wordpress.org/plugins/yith-woocommerce-compare/
 */
class WooCommerce_Yith_Compare
{

    public function __construct()
    {
        // Unregister Widget
        add_action('widgets_init', array($this, 'wp_remove_widget'), 9999);

        // Remove Image Size
        add_action('init', array($this, 'remove_image_size'), 9999);

        // Remove all Front File
        add_action('wp_enqueue_scripts', array($this, 'remove_asset'), 9999);
        add_action('wp_print_styles', array($this, 'remove_inline_css'), 9999);

        // Remove Dashboard Widget
        add_action('wp_dashboard_setup', array($this, 'remove_dashboard_meta_boxes'), 9999);

        // Remove Menu Admin
        add_action('admin_init', array($this, 'remove_menu'), 999);
    }

    /**
     * Remove Menu
     */
    public function remove_menu()
    {
        remove_menu_page('yith_plugin_panel');
    }

    /**
     * Remove Widget Dashboard
     */
    function remove_dashboard_meta_boxes()
    {
        remove_meta_box('yith_dashboard_products_news', 'dashboard', 'normal');
        remove_meta_box('yith_dashboard_blog_news', 'dashboard', 'normal');
    }

    public function remove_inline_css()
    {
        wp_styles()->add_data( 'font-awesome', 'after', '' );
    }

    public function remove_asset()
    {
        wp_dequeue_script('yith-woocompare-main');
        wp_dequeue_script('jquery-colorbox');
        wp_dequeue_style('jquery-colorbox');
    }

    public function remove_image_size()
    {
        remove_image_size('yith-woocompare-image');
    }

    public function wp_remove_widget()
    {
        unregister_widget('YITH_Woocompare_Widget');
    }

    /**
     * Get Compare Cookie List Name
     *
     * @return mixed
     */
    public static function get_cookie_name()
    {
        global $yith_woocompare;
        return $yith_woocompare->obj->cookie_name;
    }

    /**
     * Clear All Products in Compare
     */
    public static function clear_compare_list()
    {
        global $yith_woocompare;
        setcookie($yith_woocompare->obj->cookie_name, json_encode(array_values(array())), 0, COOKIEPATH, COOKIE_DOMAIN, false, false);
    }

    /**
     * Get Number List Product in Compare
     *
     * @return int
     */
    public static function get_number_product_in_compare()
    {
        return count(self::get_list_products_compare());
    }

    /**
     * Check Has Product in Compare List
     *
     * @param $product_id
     * @return bool
     */
    public static function has_product_in_compare($product_id)
    {
        return in_array($product_id, self::get_list_products_compare());
    }

    /**
     * Get Remove Link From Compare List
     *
     * @param $product_id
     * @return mixed
     */
    public static function get_remove_link_product_from_compare($product_id)
    {
        global $yith_woocompare;
        return $yith_woocompare->obj->remove_product_url($product_id);
    }

    /**
     * Remove Product From Compare List
     *
     * @param $product_id
     */
    public static function remove_product_from_compare($product_id)
    {
        global $yith_woocompare;
        $yith_woocompare->obj->remove_product_from_compare($product_id);
    }

    /**
     * Add Product To Compare List
     *
     * @param $product_id
     * @return bool
     */
    public static function add_product_to_compare($product_id)
    {
        global $yith_woocompare;

        $product_id = intval($product_id);
        $product = wc_get_product($product_id);

        // don't add the product if doesn't exist
        if ($product != false && !in_array($product_id, $yith_woocompare->obj->products_list)) {
            $yith_woocompare->obj->add_product_to_compare($product_id);
            return true;
        }

        return false;
    }

    /**
     * Use: list_products_html method from YITH_Woocompare_Frontend Class
     *
     * @Helper
     */
    public static function get_list_products_compare()
    {
        global $yith_woocompare;

        $products_list = $yith_woocompare->obj->products_list;
        $ids = array();

        // If Empty
        if (empty($products_list)) {
            return array();
        }

        foreach ($products_list as $product_id) {
            /**
             * @type object $product /WC_Product
             */
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            // Add yo List
            $ids[] = $product_id;
        }

        return $ids;
    }

    /**
     * Get Compare Default Fields
     *
     * @param bool $with_attr
     * @return mixed
     */
    public static function get_default_compare_fields($with_attr = true)
    {
        //@TODO Create Standard Again For REST API and Check product attribute if not all in Product compare list
        return \YITH_Woocompare_Helper::standard_fields($with_attr);
        // use apply_filters( 'yith_woocompare_standard_fields_array', $fields ); for adding Item
    }

    /**
     * Get Table List array
     *
     * @param array $products
     * @return mixed
     */
    public static function get_compare_list_array($products = array())
    {
        global $yith_woocompare;
        return $yith_woocompare->obj->get_products_list($products);
    }

}

new WooCommerce_Yith_Compare;
