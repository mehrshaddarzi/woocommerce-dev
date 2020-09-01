<?php

namespace WooCommerce_Dev;

class WooCommerce_Filter
{
    // http://localhost/orgame.ir/shop/?min_price=7120&max_price=8730&orderby=menu_order&filter_color=blue
    // https://docs.woocommerce.com/document/woocommerce-shortcodes/#section-6
    // woocommerce_catalog_ordering Function
    // http://localhost/orgame.ir/?s=%D9%BE%D8%B1%D8%AA%D9%82%D8%A7%D9%84&post_type=product
    // addons\woocommerce\includes\class-wc-query.php
    // ?orderby=relevance&paged=1&s=ماهی&post_type=product
    // ?product_cat=' + jQuery(this).val();
    // ?query_type_color=or&filter_color=blue,red

    // custom Taxonomy
    //www.example.com/events/?location=nsw&industry=web
    //https://thereforei.am/2011/10/28/advanced-taxonomy-queries-with-pretty-urls/

    public function __construct()
    {
        // Set Tax Query 'OR' Relation
        //add_filter( 'pre_get_posts', array($this, 'set_product_cat_or_relation') );
//        add_action('admin_footer', function(){
//           global $wp_query;
//           echo '<pre>';
//           print_r($wp_query);
//           exit;
//        });

        // https://rudrastyh.com/woocommerce/sorting-options.html
        // WooCommerce Catalog Query
        add_filter('woocommerce_catalog_orderby', array($this, 'misha_rename_default_sorting_options'));
        add_filter('woocommerce_get_catalog_ordering_args', array($this, 'misha_custom_product_sorting'));

        // category_ids in Product Shop
        add_action('pre_get_posts',  array($this, 'woocommerce_pre_get_query'));
    }

    function woocommerce_pre_get_query($query)
    {

        if ($query->is_main_query() && !is_admin()) {

            // Product Category IDS
            if (isset($_GET['product_cat_ids']) and !empty($_GET['product_cat_ids'])) {
                $product_ids = explode(",", trim($_GET['product_cat_ids']));
                if (is_product_category()) {
                    $product_ids[] = get_queried_object_id();
                }

                if (!empty($product_ids)) {
                    $tax_query = array();

                    // Set Relation
                    $tax_relation = 'AND';
                    if (isset($_GET['query_type_product_cat_ids']) and !empty($_GET['query_type_product_cat_ids'])) {
                        $tax_relation = strtoupper($_GET['query_type_product_cat_ids']);
                    }
                    $tax_query['relation'] = $tax_relation;

                    // Add Custom
                    $tax_query = $query->get('tax_query') ?: array();
                    $tax_query[] = array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $product_ids,
                        'operator' => 'IN'
                    );
                    $query->set('tax_query', $tax_query);
                }
            }

            // Product Tag IDS
            if (isset($_GET['tag_ids']) and !empty($_GET['tag_ids'])) {
                $product_ids = explode(",", trim($_GET['tag_ids']));
                if (is_product_tag()) {
                    $product_ids[] = get_queried_object_id();
                }

                if (!empty($product_ids)) {
                    $tax_query = array();

                    // Set Relation
                    $tax_relation = 'AND';
                    if (isset($_GET['query_type_product_tag_ids']) and !empty($_GET['query_type_product_tag_ids'])) {
                        $tax_relation = strtoupper($_GET['query_type_product_tag_ids']);
                    }
                    $tax_query['relation'] = $tax_relation;

                    // Add Custom
                    $tax_query = $query->get('tax_query') ?: array();
                    $tax_query[] = array(
                        'taxonomy' => 'product_tag',
                        'field' => 'term_id',
                        'terms' => $product_ids,
                        'operator' => 'IN'
                    );
                    $query->set('tax_query', $tax_query);
                }
            }
        }
        return $query;
    }


    function misha_rename_default_sorting_options($options)
    {

        // Rename And Order
        $options = array(
            'menu_order' => __('پیش فرض', 'woocommerce'), // you can change the order of this element too
            'price' => __('قیمت از کم به زیاد', 'woocommerce'), // I need sorting by price to be the first
            'price-desc' => __('قیمت از زیاد به کم', 'woocommerce'),
            'date' => __('جدیدترین محصولات', 'woocommerce'), // Let's make "Sort by latest" the second one
            'popularity' => __('محبوبیت محصولات', 'woocommerce'),
            'rating' => __('میانگین امتیاز محصولات', 'woocommerce'),

            'title' => 'بر اساس حروف الفبا',
            'in-stock' => 'محصولات موجود در انبار',
            'views' => 'بیش ترین بازدید از کالا',
        );
        return $options;
    }

    function misha_custom_product_sorting($args)
    {

        // Sort alphabetically
        if (isset($_GET['orderby']) && 'title' === $_GET['orderby']) {
            $args['orderby'] = 'title';
            $args['order'] = 'asc';
        }

        // Show products in stock first
        if (isset($_GET['orderby']) && 'in-stock' === $_GET['orderby']) {
            $args['meta_key'] = '_stock_status';
            $args['orderby'] = array('meta_value' => 'ASC');
        }

        // Show products By View
        if (isset($_GET['orderby']) && 'views' === $_GET['orderby']) {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'views';
            $args['order'] = 'desc';
        }

        return $args;
    }


    function set_product_cat_or_relation($query)
    {
        if (!is_admin() && $query->is_main_query() and isset($_GET['query_type_product_cat'])) {
            $query->tax_query->relation = strtoupper($_GET['query_type_product_cat']);
            // puts the item at the beginning of the array instead of the end.
//            array_unshift( $tax_query, array(
//                'taxonomy' => 'category',
//                'field' => 'slug',
//                'terms' => 'intl',
//                'operator' => 'IN'
//            );
        }
        return $query;
    }

    public static function get_filters()
    {
        if (isset($_REQUEST[self::$filter_params])) {
            return $_REQUEST[self::$filter_params];
        }

        return array();
    }

    public static function generate_url($filter = null, $url = '')
    {
        $array[self::$filter_params] = array_filter($filter);
        return add_query_arg($array, $url);
    }

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
}

new WooCommerce_Filter;
