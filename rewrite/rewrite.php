<?php

namespace WordPress_Rewrite_API_Request;

class wc
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, '_register_js_script'), 7);
    }

    public function _register_js_script()
    {
        wp_enqueue_script('woocommerce-rewrite', \WOOCOMMERCE_DEV::$plugin_url . '/rewrite/script.js', array('jquery', 'wp-rewrite-api'), \WOOCOMMERCE_DEV::$plugin_version, true);
    }



}

new wc;
