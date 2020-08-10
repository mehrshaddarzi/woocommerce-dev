<?php

/**
 * Plugin Name: WooCommerce For Developer
 * Description: A Plugin For Developing WooCommerce Project
 * Plugin URI:  https://realwp.net
 * Version:     1.0.0
 * Author:      Mehrshad Darzi
 * Author URI:  https://realwp.net
 * License:     MIT
 * Text Domain: woocommerce-dev
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class WOOCOMMERCE_DEV
{
    /**
     * @var string
     */
    public static $ENVIRONMENT = 'development';

    /**
     * Minimum PHP version required
     *
     * @var string
     */
    private $min_php = '5.4.0';

    /**
     * Use plugin's translated strings
     *
     * @var string
     * @default true
     */
    public static $use_i18n = true;

    /**
     * URL to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_url;

    /**
     * Path to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_path;

    /**
     * Path to this plugin's directory.
     *
     * @type string
     * @status Core
     */
    public static $plugin_version;

    /**
     * Plugin instance.
     *
     * @see get_instance()
     * @status Core
     */
    protected static $_instance = null;

    /**
     * Access this plugin’s working instance
     *
     * @wp-hook plugins_loaded
     * @return  object of this class
     * @since   2012.09.13
     */
    public static function instance()
    {
        null === self::$_instance and self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * WP_MVC constructor.
     */
    public function __construct()
    {
        /*
         * Check Require Php Version
         */
        if (version_compare(PHP_VERSION, $this->min_php, '<=')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return;
        }

        /*
         * Define Variable
         */
        $this->define_constants();

        /*
         * include files
         */
        $this->includes();

        /*
         * init Wordpress hook
         */
        $this->init_hooks();

        /*
         * Plugin Loaded Action
         */
        do_action('woocommerce_dev_loaded');
    }

    /**
     * Define Constant
     */
    public function define_constants()
    {
        /*
         * Get Plugin Data
         */
        if (!function_exists('get_plugin_data')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        $plugin_data = get_plugin_data(__FILE__);

        /*
         * Set Plugin Version
         */
        self::$plugin_version = $plugin_data['Version'];

        /*
         * Set Plugin Url
         */
        self::$plugin_url = plugins_url('', __FILE__);

        /*
         * Set Plugin Path
         */
        self::$plugin_path = plugin_dir_path(__FILE__);
    }

    /**
     * include Plugin Require File
     */
    public function includes()
    {
        /**
         * Load WooCommerce
         */
        include_once dirname(__FILE__) . '/class/config.php';
        include_once dirname(__FILE__) . '/class/helper.php';
        include_once dirname(__FILE__) . '/class/cart.php';
        include_once dirname(__FILE__) . '/class/coupon.php';
        include_once dirname(__FILE__) . '/class/order.php';
    }

    /**
     * Used for regular plugin work.
     *
     * @wp-hook init Hook
     * @return  void
     */
    public function init_hooks()
    {
        //register_activation_hook(__FILE__, array('\WP_MVC\config\install', 'run_install'));
        //register_deactivation_hook(__FILE__, array('\WP_MVC\config\uninstall', 'run_uninstall'));
    }

    /**
     * Show notice about PHP version
     *
     * @return void
     */
    function php_version_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $error = __('Your installed PHP Version is:', 'woocommerce-dev') . PHP_VERSION . '. ';
        $error .= __('The <strong>WooCommerce Dev</strong> plugin requires PHP version <strong>', 'woocommerce-dev') . $this->min_php . __('</strong> or greater.', 'woocommerce-dev');
        ?>
        <div class="error">
            <p><?php printf($error); ?></p>
        </div>
        <?php
    }
}

/**
 * Main instance of WP_Plugin.
 *
 * @since  1.1.0
 */
function woocommerce_dev()
{
    return WOOCOMMERCE_DEV::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce-dev'] = woocommerce_dev();
