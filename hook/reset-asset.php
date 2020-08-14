<?php

namespace WooCommerce_Dev;

use WP_MVC\ACF;

class WooCommerce_Reset_Asset
{
    public function __construct()
    {
        // Change Woocommerce PlaceHolder Image
        // @see https://docs.woocommerce.com/document/change-the-placeholder-image/
        // @see https://woocommerce.wp-a2z.org/oik_api/wc_placeholder_img/
        add_filter('woocommerce_placeholder_img_src', array($this, 'custom_woocommerce_placeholder_img_src'));

        // Disable All Woocommerce Css
        // @see https://docs.woocommerce.com/document/disable-the-default-stylesheet/
        add_filter('woocommerce_enqueue_styles', '__return_empty_array');

        // Remove All Js
        // @see https://gist.github.com/DevinWalker/7621777
        add_action('wp_enqueue_scripts', array($this, 'child_manage_woocommerce_styles'), 99);

        // Disable Woocommerce image Size
        add_filter( 'intermediate_image_sizes_advanced', array($this, 'remove_default_image_sizes'), 99 );

        // Remove Inline Css
        add_action('init', array($this, 'remove_inline'));
    }

    function remove_default_image_sizes( $sizes ) {
        unset( $sizes[ 'woocommerce_thumbnail' ]);
        unset( $sizes[ 'woocommerce_single' ]);
        unset( $sizes[ 'woocommerce_gallery_thumbnail' ]);
        unset( $sizes[ 'shop_thumbnail' ]);  // Remove Shop thumbnail (180 x 180 hard cropped)
        unset( $sizes[ 'shop_catalog' ]);    // Remove Shop catalog (300 x 300 hard cropped)
        unset( $sizes[ 'shop_single' ]);     // Shop single (600 x 600 hard cropped)
        return $sizes;
    }

    public function remove_inline()
    {
        // Remove From Body Class and footer Script
        add_filter('body_class', function ($classes) {
            remove_action('wp_footer', 'wc_no_js');
            $classes = array_diff($classes, array('woocommerce-no-js'));
            return array_values($classes);
        }, 99, 1);

        add_action('wp_print_styles', function () {
            wp_style_add_data('woocommerce-inline', 'after', '');
        });
        remove_action('wp_head', 'wc_gallery_noscript', 10);
        add_action('wp_enqueue_scripts', function () {
            wp_deregister_style('woocommerce-inline');
        }, 11);
    }

    public function child_manage_woocommerce_styles()
    {
        //first check that woo exists to prevent fatal errors
        if (function_exists('is_woocommerce')) {
            //dequeue scripts and styles
            //if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
            wp_dequeue_style('woocommerce_frontend_styles');
            wp_dequeue_style('woocommerce_fancybox_styles');
            wp_dequeue_style('woocommerce_chosen_styles');
            wp_dequeue_style('woocommerce_prettyPhoto_css');
            wp_dequeue_script('wc_price_slider');
            wp_dequeue_script('wc-single-product');
            wp_dequeue_script('wc-add-to-cart');
            wp_dequeue_script('wc-cart-fragments');
            wp_dequeue_script('wc-checkout');
            wp_dequeue_script('wc-add-to-cart-variation');
            wp_dequeue_script('wc-single-product');
            wp_dequeue_script('wc-cart');
            wp_dequeue_script('wc-chosen');
            wp_dequeue_script('woocommerce');
            wp_dequeue_script('prettyPhoto');
            wp_dequeue_script('prettyPhoto-init');
            wp_dequeue_script('jquery-blockui');
            wp_dequeue_script('jquery-placeholder');
            wp_dequeue_script('fancybox');
            wp_dequeue_script('jqueryui');
            //}
        }
    }

    function custom_woocommerce_placeholder_img_src($src)
    {
        if (function_exists('get_field')) {
            $placeholder_image = ACF::get_option('placeholder', 'woo');
            if (!empty($placeholder_image)) {
                $src = $placeholder_image;
            }
        }

        return $src;
    }


}

new WooCommerce_Reset_Asset;