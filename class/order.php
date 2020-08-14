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
     * Create Order in CheckOut
     *
     * @see https://codetrycatch.com/create-a-woocommerce-order-programatically/
     * @see https://github.com/woocommerce/woocommerce/blob/02cf0dfaed5923513de0c88add597d1560c2cfd2/includes/wc-core-functions.php#L83
     * @see https://stackoverflow.com/questions/49385700/woocommerce-wc-create-order-not-working
     * @see https://github.com/woocommerce/woocommerce/blob/29bc98816ea411c82968afe072644e6f9fc88388/includes/class-wc-checkout.php#L319
     * @see https://stackoverflow.com/questions/53853204/save-custom-cart-item-data-from-dynamic-created-cart-on-order-creation-in-woocom
     * @param $data
     * @return int
     */
    public static function create_checkout_order($data)
    {
        $data = array(
            'billing_email' => ''
        );

        // Get Billing Email
        if (is_user_logged_in() and empty($data['billing_email'])) {
            $user = get_userdata(get_current_user_id());
            if (!empty($user->user_email)) {
                $data['billing_email'] = $user->user_email;
            }
        }

        //$example = array('payment_method' => '');
        return WC()->checkout->create_order($data);
    }

    /**
     * Get Order Detail By ID
     *
     * @see https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details
     * @param $order_id
     * @param array $arg
     * @return array
     */
    public static function get($order_id, $arg = array())
    {
        // Get an instance of the WC_Order object
        $order = wc_get_order($order_id);

        // Get the meta data in an unprotected array
        $order_data = $order->get_data();

        // Order datetime
        if (!is_null($order->get_date_created())) {
            $order_data['datetime']['create'] = $order->get_date_created()->format("Y-m-d H:i:s");
        }
        if (!is_null($order->get_date_modified())) {
            $order_data['datetime']['modify'] = $order->get_date_modified()->format("Y-m-d H:i:s");
        }
        if (!is_null($order->get_date_paid())) {
            $order_data['datetime']['paid'] = $order->get_date_paid()->format("Y-m-d H:i:s");
        }
        if (!is_null($order->get_date_completed())) {
            $order_data['datetime']['complete'] = $order->get_date_completed()->format("Y-m-d H:i:s");
        }

        // Status rendered
        $order_data['status-rendered'] = esc_html(wc_get_order_status_name($order->get_status()));

        // Get Product Data
        if (isset($arg['with_product']) and $arg['with_product'] === true) {
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

        return $order_data;
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
            'type' => wc_get_order_types('view-orders'),
            'status' => array_keys(wc_get_order_statuses()), //on-hold
            //'customer_id' => 12,
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