<?php

namespace WooCommerce_Dev;

/**
 * PostType in WordPress: shop_order
 *
 * Class WooCommerce_Order
 */
class WooCommerce_Order
{
    public function __construct()
    {

    }

    /**
     * @see https://codetrycatch.com/create-a-woocommerce-order-programatically/
     * @see https://github.com/woocommerce/woocommerce/blob/02cf0dfaed5923513de0c88add597d1560c2cfd2/includes/wc-core-functions.php#L83
     * @see https://stackoverflow.com/questions/49385700/woocommerce-wc-create-order-not-working
     */
    public static function create_order()
    {

    }

    public static function get($order_id)
    {
        // Get an instance of the WC_Order object
        $order = wc_get_order($order_id);

        // Get the meta data in an unprotected array
        $order_data = $order->get_data();

        // Get Product Data
        foreach ($order->get_items() as $item_id => $item) {

            // Get the common data in an array:
            $item_product_data_array = $item->get_data();

            // Get the special meta data in an array:
            $item_product_meta_data_array = $item->get_meta_data();

            // Get all additional meta data (formatted in an unprotected array)
            $formatted_meta_data = $item->get_formatted_meta_data(' ', true);

            // Get Product complete Data
            $product = wc_get_product($item->get_product_id());

            // Push to List
            $order_data['products_list'][$item_id] = $item_product_data_array;
            $order_data['products_list'][$item_id]['post_information'] = $product->get_data();
            $order_data['products_list'][$item_id]['meta_data'] = $item_product_meta_data_array;
            $order_data['products_list'][$item_id]['additional_meta_data'] = $formatted_meta_data;
        }


    }

    public static function change_order_status($order_id, $status, $note = '')
    {
        $order = wc_get_order($order_id);
        // get current status
        // $order->get_status()
        $order->update_status($status, $note);
    }

    public static function delete($order_id)
    {
        wp_delete_post($order_id, true);
    }

    /**
     * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
     *
     * @param array $arg
     * @return \stdClass|\WC_Order[]
     */
    public static function query($arg = array())
    {
        $default = array(
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            //'customer_id' => 12,
            //'status' => '', //on-hold
        );
        $args = wp_parse_args($arg, $default);

        return wc_get_orders($args);
    }

    /**
     * @see https://woocommerce.wp-a2z.org/oik_api/wc_get_order_statuses/
     */
    public static function get_order_status_list()
    {
        return wc_get_order_statuses();
    }

    public static function get_order_types_list()
    {
        return wc_get_order_types();
    }

}

new WooCommerce_Order;