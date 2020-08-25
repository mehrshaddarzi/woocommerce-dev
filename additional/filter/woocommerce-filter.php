<?php

namespace WooCommerce_Dev;

class WooCommerce_Filter
{
    public static $filter_params = 'wc-filter';
    public static $filter_term_params = 'term';
    public static $filter_meta_params = 'meta';

    public function __construct()
    {

        // Redirect Filter Product Cat if One Item
        add_action('wp', array($this, '_action_redirect_product_cat'));
    }

    /**
     * @Helper
     * @return array|mixed
     */
    public static function get_filters()
    {
        if (isset($_REQUEST[self::$filter_params])) {
            return $_REQUEST[self::$filter_params];
        }

        return array();
    }

    /**
     * Generate Url
     *
     * @Helper
     * @param null $filter
     * @param string $url
     * @return string
     */
    public static function generate_url($filter = null, $url = '')
    {
        $array[self::$filter_params] = array_filter($filter);
        return add_query_arg($array, $url);
    }

    /**
     * Get Param
     *
     * @param $params | a.b.c
     * @return array|mixed
     * @see https://stackoverflow.com/questions/27929875/how-to-access-and-manipulate-multi-dimensional-array-by-key-names-path
     */
    public static function get_param($params)
    {
        $filter = self::get_filters();
        $path = explode('.', $params);
        $temp =& $filter;

        foreach ($path as $key) {
            $temp =& $temp[$key];
        }
        return $temp; // Return Null if not exist
    }

    /**
     * Generate Input Name
     *
     * @param $params
     * @param bool $append
     * @return string
     */
    public static function generate_input_name($params, $append = false)
    {
        $input_name = self::$filter_params;
        foreach (explode(".", $params) as $parameter) {
            $input_name .= '[' . $parameter . ']';
        }
        if ($append) {
            $input_name .= '[]';
        }

        return $input_name;
    }

    /**
     * Redirect To Product Category If One Item Found
     *
     * @Hook
     */
    public function _action_redirect_product_cat()
    {
        $filter = self::get_filters();
        if (isset($filter[self::$filter_term_params]['product_cat']) and !empty($filter[self::$filter_term_params]['product_cat'])) {
            // Check exist
            $terms = $filter[self::$filter_term_params]['product_cat'];
            if (count($terms) == 1) {
                $term_id = $terms[0];
                $term = get_term($term_id);
                if (!is_null($term)) {
                    // unset Item
                    unset($filter[self::$filter_term_params]['product_cat']);

                    // redirect
                    wp_safe_redirect(self::generate_url($filter, get_term_link($term)));
                    exit;
                }
            }
        }
    }
}

new WooCommerce_Filter;
