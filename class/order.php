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

        // Disable All Email Order Notification
        //@see https://docs.woocommerce.com/document/unhookremove-woocommerce-emails/
        add_action('woocommerce_email', array($this, 'unhook_those_pesky_emails'), 999);
        add_filter('woocommerce_email_actions', function () {
            return array();
        }, 999);

    }

    /**
     * Get Order Detail By ID
     *
     * @see https://stackoverflow.com/questions/39401393/how-to-get-woocommerce-order-details
     * @param $order_id
     * @param array $filter
     * @return array
     */
    public static function get($order_id, $filter = array())
    {
        //@TODO Use wp_set_cache similar product in woocommerce dev
        // Get an instance of the WC_Order object
        $order = wc_get_order($order_id);

        // Get the decimal precession.
        $dp = apply_filters('woocommerce_dev_order_dp', 0);
        $expand = array();
        if (isset($filter['expand'])) {
            $expand = $filter['expand'];
        }

        /**
         * Expand List:
         * $filter['expand'] = array('products');
         * \WooCommerce_Dev\WooCommerce_Order::get(448, array('expand' => array('products')));
         */

        // Get Order Data
        $order_data = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'order_key' => $order->get_order_key(),
            'created_at' => WooCommerce_Helper::format_datetime($order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0, false, false), // API gives UTC times.
            'updated_at' => WooCommerce_Helper::format_datetime($order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : 0, false, false), // API gives UTC times.
            'completed_at' => WooCommerce_Helper::format_datetime($order->get_date_completed() ? $order->get_date_completed()->getTimestamp() : 0, false, false), // API gives UTC times.
            'status' => $order->get_status(),
            'status-rendered' => esc_html(wc_get_order_status_name($order->get_status())),
            'currency' => $order->get_currency(),
            'total' => wc_format_decimal($order->get_total(), $dp),
            'subtotal' => wc_format_decimal($order->get_subtotal(), $dp),
            'total_line_items_quantity' => $order->get_item_count(),
            'total_tax' => wc_format_decimal($order->get_total_tax(), $dp),
            'total_shipping' => wc_format_decimal($order->get_shipping_total(), $dp),
            'cart_tax' => wc_format_decimal($order->get_cart_tax(), $dp),
            'shipping_tax' => wc_format_decimal($order->get_shipping_tax(), $dp),
            'total_discount' => wc_format_decimal($order->get_total_discount(), $dp),
            'shipping_methods' => $order->get_shipping_method(),
            'payment_details' => array(
                'method_id' => $order->get_payment_method(),
                'method_title' => $order->get_payment_method_title(),
                'paid' => !is_null($order->get_date_paid()),
            ),
            'billing_address' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
            ),
            'shipping_address' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
            ),
            'note' => $order->get_customer_note(),
            'customer_ip' => $order->get_customer_ip_address(),
            'customer_user_agent' => $order->get_customer_user_agent(),
            'customer_id' => $order->get_user_id(),
            'view_order_url' => $order->get_view_order_url(),
            'line_items' => array(),
            'shipping_lines' => array(),
            'tax_lines' => array(),
            'fee_lines' => array(),
            'coupon_lines' => array(),
        );

        // Add line items.
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $hideprefix = null;
            $item_meta = $item->get_formatted_meta_data($hideprefix);

            foreach ($item_meta as $key => $values) {
                $item_meta[$key]->label = $values->display_key;
                unset($item_meta[$key]->display_key);
                unset($item_meta[$key]->display_value);
            }

            $line_item = array(
                'id' => $item_id,
                'subtotal' => wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
                'subtotal_tax' => wc_format_decimal($item->get_subtotal_tax(), $dp),
                'total' => wc_format_decimal($order->get_line_total($item, false, false), $dp),
                'total_tax' => wc_format_decimal($item->get_total_tax(), $dp),
                'price' => wc_format_decimal($order->get_item_total($item, false, false), $dp),
                'quantity' => $item->get_quantity(),
                'tax_class' => $item->get_tax_class(),
                'name' => $item->get_name(),
                'product_id' => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
                'sku' => is_object($product) ? $product->get_sku() : null,
                'meta' => array_values($item_meta),
            );

            if (in_array('products', $expand) && is_object($product)) {
                $line_item['product_data'] = WooCommerce_Product::get($product->get_id());
            }

            $order_data['line_items'][] = $line_item;
        }

        // Add shipping.
        foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
            $order_data['shipping_lines'][] = array(
                'id' => $shipping_item_id,
                'method_id' => $shipping_item->get_method_id(),
                'method_title' => $shipping_item->get_name(),
                'total' => wc_format_decimal($shipping_item->get_total(), $dp),
            );
        }

        // Add taxes.
        foreach ($order->get_tax_totals() as $tax_code => $tax) {
            $tax_line = array(
                'id' => $tax->id,
                'rate_id' => $tax->rate_id,
                'code' => $tax_code,
                'title' => $tax->label,
                'total' => wc_format_decimal($tax->amount, $dp),
                'compound' => (bool)$tax->is_compound,
            );

            if (in_array('taxes', $expand)) {
                $_rate_data = WC()->api->WC_API_Taxes->get_tax($tax->rate_id);

                if (isset($_rate_data['tax'])) {
                    $tax_line['rate_data'] = $_rate_data['tax'];
                }
            }

            $order_data['tax_lines'][] = $tax_line;
        }

        // Add fees.
        foreach ($order->get_fees() as $fee_item_id => $fee_item) {
            $order_data['fee_lines'][] = array(
                'id' => $fee_item_id,
                'title' => $fee_item->get_name(),
                'tax_class' => $fee_item->get_tax_class(),
                'total' => wc_format_decimal($order->get_line_total($fee_item), $dp),
                'total_tax' => wc_format_decimal($order->get_line_tax($fee_item), $dp),
            );
        }

        // Add coupons.
        foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
            $coupon_line = array(
                'id' => $coupon_item_id,
                'code' => $coupon_item->get_code(),
                'amount' => wc_format_decimal($coupon_item->get_discount(), $dp),
            );

            if (in_array('coupons', $expand)) {
                $_coupon_data = WC()->api->WC_API_Coupons->get_coupon_by_code($coupon_item->get_code());

                if (!is_wp_error($_coupon_data) && isset($_coupon_data['coupon'])) {
                    $coupon_line['coupon_data'] = $_coupon_data['coupon'];
                }
            }

            $order_data['coupon_lines'][] = $coupon_line;
        }

        // Apply Filter
        $order_data = apply_filters('woocommerce_dev_order_data', $order_data, $order_id);

        // Return Data
        return $order_data;
    }

    /**
     * Create Order in CheckOut
     *
     * @see https://codetrycatch.com/create-a-woocommerce-order-programatically/
     * @see https://github.com/woocommerce/woocommerce/blob/02cf0dfaed5923513de0c88add597d1560c2cfd2/includes/wc-core-functions.php#L83
     * @see https://stackoverflow.com/questions/49385700/woocommerce-wc-create-order-not-working
     * @see https://github.com/woocommerce/woocommerce/blob/29bc98816ea411c82968afe072644e6f9fc88388/includes/class-wc-checkout.php#L319
     * @see https://stackoverflow.com/questions/53853204/save-custom-cart-item-data-from-dynamic-created-cart-on-order-creation-in-woocom
     * @param $args
     * @return int
     */
    public static function create_checkout_order($args)
    {
        $default = array(
            'billing_email' => '',
            'payment_method' => '',
            'user_shipping' => array(
                'first_name' => '',
                'last_name' => '',
                'company' => '',
                'email' => '',
                'phone' => '',
                'address_1' => '',
                'address_2' => '',
                'city' => '',
                'state' => '',
                'postcode' => '',
                'country' => ''
            )
        );
        $data = wp_parse_args($args, $default);

        // Get Billing Email
        if (is_user_logged_in() and empty($data['billing_email'])) {
            $user = get_userdata(get_current_user_id());
            if (!empty($user->user_email)) {
                $data['billing_email'] = $user->user_email;
            }
        }

        // Create Check Out Order
        $order_id = WC()->checkout->create_order($data); // return $order_id

        // Set User Address
        $order = wc_get_order($order_id);
        $order->set_address($data['user_shipping'], 'billing');
        $order->set_address($data['user_shipping'], 'shipping');
        $order->calculate_totals();
        $order->save();

        // Return $order ID
        return $order_id;
    }

    /**
     * Change Order Status
     *
     * @param $order_id
     * @param $status
     * @param string $note
     */
    public static function change_order_status($order_id, $status, $note = '')
    {
        $order = wc_get_order($order_id);
        // get current status
        // $order->get_status()
        $order->update_status($status, $note);
    }

    /**
     * Delete Order
     *
     * @param $order_id
     */
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

    /**
     * Get Order Type List
     *
     * @return array
     */
    public static function get_order_types_list()
    {
        return wc_get_order_types();
    }

    /**
     * Disable All Email Notification
     *
     * @HOOk
     * @param $email_class
     */
    function unhook_those_pesky_emails($email_class)
    {

        /**
         * Hooks for sending emails during store events
         **/
        remove_action('woocommerce_low_stock_notification', array($email_class, 'low_stock'));
        remove_action('woocommerce_no_stock_notification', array($email_class, 'no_stock'));
        remove_action('woocommerce_product_on_backorder_notification', array($email_class, 'backorder'));

        // New order emails
        remove_action('woocommerce_order_status_pending_to_processing_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_on-hold_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_failed_to_processing_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_failed_to_completed_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));
        remove_action('woocommerce_order_status_failed_to_on-hold_notification', array($email_class->emails['WC_Email_New_Order'], 'trigger'));

        // Processing order emails
        remove_action('woocommerce_order_status_pending_to_processing_notification', array($email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));
        remove_action('woocommerce_order_status_pending_to_on-hold_notification', array($email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger'));

        // Completed order emails
        remove_action('woocommerce_order_status_completed_notification', array($email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger'));

        // Note emails
        remove_action('woocommerce_new_customer_note_notification', array($email_class->emails['WC_Email_Customer_Note'], 'trigger'));
    }
}

new WooCommerce_Order;