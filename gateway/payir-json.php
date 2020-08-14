<?php

// Lets add it too WooCommerce
add_filter('woocommerce_payment_gateways', 'add_WC_PayIR_Json_Gateway');
function add_WC_PayIR_Json_Gateway($methods)
{
    $methods[] = 'WC_PayIR_Json_Gateway';
    return $methods;
}

// Create Payment Gateway
class WC_PayIR_Json_Gateway extends \WC_Payment_Gateway
{

    private $allowedCurrencies = array(
        'IRR', 'IRT'
    );
    private $CALLBACK_URL = "payir_json_callback";

    public function __construct()
    {
        // The global ID for this Payment method
        $this->id = "payir-json";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __("پی آی آر", 'woocommerce');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __("درگاه پرداخت پی آی آر در بستر REST API", 'woocommerce');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = null;

        // Bool. Can be set to true if you want payment fields to show on the checkout
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = true;

        // Supports the default credit card form
        $this->supports = array('products');

        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        // Checking if valid to use
        if ($this->is_valid_for_use()) {
            $this->enabled = $this->enabled;
        } else {
            $this->enabled = 'no';
        }

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Add Payment Process
        // @see https://docs.woocommerce.com/document/wc_api-the-woocommerce-api-callback/
        add_action('woocommerce_api_' . $this->CALLBACK_URL, array($this, 'payment_verify'));
    }

    function is_valid_for_use()
    {
        return in_array(get_woocommerce_currency(), $this->allowedCurrencies);
    }

    function admin_options()
    {
        if ($this->is_valid_for_use()) {
            parent::admin_options();
        } else {
            ?>
            <div class="notice error is-dismissible">
                <p><?php _e('این درگاه پرداخت واحد پول انتخابی فروشگاه شما را قبول نمی کند', 'woocommerce'); ?></p>
            </div>
            <?php
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'label' => 'فعالسازی/غیرفعال سازی',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => '',
                'default' => 'پی آی آر',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                'desc_tip' => true,
            ),
            'api' => array(
                'title' => 'شناسه API',
                'type' => 'text',
                'placeholder' => 'شناسه API را وارد کنید'
            )
        );
    }

    public function process_payment($order)
    {
        $api = $this->api;
        $amount = $this->get_total($order, 'IRR');
        $mobile = $this->get_order_mobile();
        $factorNumber = $this->get_order_props('order_number');
        $description = 'شماره سفارش #' . $factorNumber;
        $redirect = add_query_arg(
            array(
                'wc-api' => $this->CALLBACK_URL,
                'order_id' => $order->get_id()
            ),
            home_url()
        );
        $result = $this->api_send($api, $amount, $redirect, $mobile, $factorNumber, $description);
        $result = json_decode($result);
        if ($result->status) {
            $go = "https://pay.ir/pg/$result->token";
            return array('result' => 'success', 'redirect' => $go);
        } else {
            return array('result' => 'error', 'message' => $result->errorMessage);
        }
    }

    public function payment_verify()
    {
        $api = $this->api;
        $token = $_GET['token'];
        $order_id = $_GET['order_id'];
        if (!isset($_GET['token']) || !isset($order_id)) {
            wp_redirect(wc_get_checkout_url());
            exit;
        }
        $order = wc_get_order($order_id);



        // Verify
        $result = json_decode($this->api_verify($api, $token));
        if (isset($result->status)) {
            if ($result->status == 1) {
                $order->update_status('processing');
                $order->payment_complete();
                wc_reduce_stock_levels($order->get_id());
                WC()->cart->empty_cart();
                $order->save();

                // Use apply_filters( 'woocommerce_get_return_url', $return_url, $order ) filter
                $return_url = $this->get_return_url($order);
                wp_redirect($return_url);
                exit;
            }
        }

        $order->update_status('failed');
        $order->save();

        // Use apply_filters( 'woocommerce_get_return_url', $return_url, $order ) filter
        $return_url = $this->get_return_url($order);
        wp_redirect($return_url);
        exit;
    }


    protected function get_total($order, $to_currency = 'IRR')
    {

        if (empty($order->get_id())) {
            return 0;
        }
        $price = $order->get_total();

        $currency = strtoupper($this->get_currency($order));
        $to_currency = strtoupper($to_currency);

        if (in_array($currency, array('IRHR', 'IRHT'))) {
            $currency = str_ireplace('H', '', $currency);
            $price *= 1000;
        }

        if ($currency == 'IRR' && $to_currency == 'IRT') {
            $price /= 10;
        }

        if ($currency == 'IRT' && $to_currency == 'IRR') {
            $price *= 10;
        }

        return $price;
    }

    protected function get_currency($order) {

        if ( empty( $order->get_id() ) ) {
            return '';
        }

        $currency = method_exists( $order, 'get_currency' ) ? $order->get_currency() : $order->get_order_currency();

        $irt = array( 'irt', 'toman', 'tomaan', 'iran toman', 'iranian toman', 'تومان', 'تومان ایران' );
        if ( in_array( strtolower( $currency ), $irt ) ) {
            $currency = 'IRT';
        }

        $irr = array( 'irr', 'rial', 'iran rial', 'iranian rial', 'ریال', 'ریال ایران' );
        if ( in_array( strtolower( $currency ), $irr ) ) {
            $currency = 'IRR';
        }

        return $currency;
    }


    protected function get_order($order = 0)
    {

        if (empty($order)) {
            $order = $this->order_id;
        }

        if (empty($order)) {
            return (object)array();
        }

        if (is_numeric($order)) {
            $this->order_id = $order;

            $order = new پWC_Order($order);
        }

        return $order;
    }

    protected function get_order_props($prop, $default = '')
    {

        if (empty($this->order_id)) {
            return '';
        }

        $order = $this->get_order();

        $method = 'get_' . $prop;

        if (method_exists($order, $method)) {
            $prop = $order->$method();
        } elseif (!empty($order->{$prop})) {
            $prop = $order->{$prop};
        } else {
            $prop = '';
        }

        return !empty($prop) ? $prop : $default;
    }

    protected function get_order_mobile()
    {

        $Mobile = $this->get_order_props('billing_phone');
        $Mobile = $this->get_order_props('billing_mobile', $Mobile);

        $Mobile = str_ireplace(array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'),
            array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), $Mobile); //farsi

        $Mobile = str_ireplace(array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'),
            array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), $Mobile); //arabi

        $Mobile = preg_replace('/\D/is', '', $Mobile);
        $Mobile = ltrim($Mobile, '0');
        $Mobile = substr($Mobile, 0, 2) == '98' ? substr($Mobile, 2) : $Mobile;

        return '0' . $Mobile;
    }

    protected function api_send($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null)
    {
        return $this->curl_post('https://pay.ir/pg/send', [
            'api' => $api,
            'amount' => $amount,
            'redirect' => $redirect,
            'mobile' => $mobile,
            'factorNumber' => $factorNumber,
            'description' => $description,
        ]);
    }

    protected function api_verify($api, $token)
    {
        return $this->curl_post('https://pay.ir/pg/verify', [
            'api' => $api,
            'token' => $token,
        ]);
    }

    protected function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}