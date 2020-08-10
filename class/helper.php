<?php

namespace WooCommerce_Dev;

class WooCommerce_Helper
{

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
        return wc_get_page_permalink($type);
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
}

new WooCommerce_Helper;