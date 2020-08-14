<?php

namespace WooCommerce_Dev;

class WooCommerce_Payment
{

    public function __construct()
    {
        // Load List Of Payment for ACF
        add_filter('acf/load_field/key=field_5f33f5144e404', array($this, 'acf_load_gateway_field_choices'));
        add_filter('acf/load_field/key=field_5f33f58e4e406', array($this, 'acf_load_gateway_field_choices'));
    }

    /**
     * Add Choice Gateway
     *
     * @Hook
     * @param $field
     * @return mixed
     */
    public function acf_load_gateway_field_choices($field)
    {
        $field['choices'] = array();
        foreach (self::get_list() as $key => $item) {
            $field['choices'][$key] = $item['title'];
        }

        return $field;
    }

    /**
     * Get All List Payment Method
     *
     * @param bool $enabled
     * @return array
     */
    public static function get_list($enabled = true)
    {
        // Use WC()->payment_gateways->payment_gateways() for get All Payment Method
        // Use WC()->payment_gateways->get_available_payment_gateways()
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $enabled_gateways = array();
        if ($gateways) {
            foreach ($gateways as $gateway_key => $gateway) {
                if ($gateway->enabled == 'yes') {

                    // Get Order
                    $order = (array)get_option('woocommerce_gateway_order');

                    // Setting
                    $settings = array();
                    $gateway->init_form_fields();
                    foreach ($gateway->form_fields as $id => $field) {
                        // Make sure we at least have a title and type.
                        if (empty($field['title']) || empty($field['type'])) {
                            continue;
                        }
                        // Ignore 'title' settings/fields -- they are UI only.
                        if ('title' === $field['type']) {
                            continue;
                        }
                        // Ignore 'enabled' and 'description' which get included elsewhere.
                        if (in_array($id, array('enabled', 'description'), true)) {
                            continue;
                        }
                        $data = array(
                            'id' => $id,
                            'label' => empty($field['label']) ? $field['title'] : $field['label'],
                            'description' => empty($field['description']) ? '' : $field['description'],
                            'type' => $field['type'],
                            'value' => empty($gateway->settings[$id]) ? '' : $gateway->settings[$id],
                            'default' => empty($field['default']) ? '' : $field['default'],
                            'tip' => empty($field['description']) ? '' : $field['description'],
                            'placeholder' => empty($field['placeholder']) ? '' : $field['placeholder'],
                        );
                        if (!empty($field['options'])) {
                            $data['options'] = $field['options'];
                        }
                        $settings[$id] = $data;
                    }

                    // Prepare Item
                    $item = array(
                        'id' => $gateway->id,
                        'title' => $gateway->title,
                        'description' => $gateway->description,
                        'order' => isset($order[$gateway->id]) ? $order[$gateway->id] : '',
                        'enabled' => ('yes' === $gateway->enabled),
                        'method_title' => $gateway->get_method_title(),
                        'method_description' => $gateway->get_method_description(),
                        'settings' => $settings,
                    );

                    $enabled_gateways[$gateway_key] = $item;
                }
            }
        }

        if ($enabled) {
            return $enabled_gateways; // Return Only enabled in Setting
        }
        return $gateways; // Return All
    }

    /**
     * Process Payment Order
     *
     * @param $order_id
     * @return mixed|void
     */
    public static function process_order_payment($order_id)
    {
        // Get Order
        $order = wc_get_order($order_id);

        // Start Payment
        // @see https://stackoverflow.com/questions/31787244/woocommerce-create-an-order-programmatically-and-redirect-to-payment
        // @see https://stackoverflow.com/questions/36969532/change-order-status-just-after-payment-in-woocommerce
        //update_post_meta( $order_id, '_payment_method', 'ideal' );
        //update_post_meta( $order_id, '_payment_method_title', 'iDeal' );

        // Store Order ID in session so it can be re-used after payment failure
        WC()->session->set('order_awaiting_payment', $order_id);

        // Process Payment
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $result = $available_gateways[$order->get_payment_method()]->process_payment($order);

        // Redirect to success/confirmation/payment page
        if ($result['result'] == 'success') {
            $result = apply_filters('woocommerce_payment_successful_result', $result, $order_id);
            return $result;
            /**
             * $result is and array contain:
             * ['redirect'] is Url
             * ['result'] is status | success, error
             */
        }

        return $result;
    }
}

new WooCommerce_Payment;