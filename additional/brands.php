<?php

namespace WooCommerce_Dev;


class WooCommerce_Brands_Taxonomy
{
    public static $taxonomy_slug = 'brands';

    public function __construct()
    {
        add_action('init', array($this, 'register_taxonomy'));
    }

    function register_taxonomy()
    {
        $args = apply_filters('woocommerce_dev_brands_taxonomy', array(
            'label' => __('Brands', 'woocommerce-dev'),
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'hierarchical' => true
        ));

        register_taxonomy(self::$taxonomy_slug, array('product'), $args);
        register_taxonomy_for_object_type(self::$taxonomy_slug, 'product');
    }

}

new WooCommerce_Brands_Taxonomy;
