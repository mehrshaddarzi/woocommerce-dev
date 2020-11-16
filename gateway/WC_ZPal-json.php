<?php

if (!defined('ABSPATH')) {
    exit;
}

function Load_ZarinPal_Gateway()
{
    if (!function_exists('Woocommerce_Add_ZarinPal_Gateway') && class_exists('WC_Payment_Gateway') && !class_exists('WC_ZPal')) {
        add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_ZarinPal_Gateway');

        function Woocommerce_Add_ZarinPal_Gateway($methods)
        {
            $methods[] = 'WC_ZPal';
            return $methods;
        }

        add_filter('woocommerce_currencies', 'add_IR_currency');

        function add_IR_currency($currencies)
        {
            $currencies['IRR'] = __('ریال', 'woocommerce');
            $currencies['IRT'] = __('تومان', 'woocommerce');
            $currencies['IRHR'] = __('هزار ریال', 'woocommerce');
            $currencies['IRHT'] = __('هزار تومان', 'woocommerce');

            return $currencies;
        }

        add_filter('woocommerce_currency_symbol', 'add_IR_currency_symbol', 10, 2);
        function add_IR_currency_symbol($currency_symbol, $currency)
        {
            switch ($currency) {
                case 'IRR':
                    $currency_symbol = 'ریال';
                    break;
                case 'IRT':
                    $currency_symbol = 'تومان';
                    break;
                case 'IRHR':
                    $currency_symbol = 'هزار ریال';
                    break;
                case 'IRHT':
                    $currency_symbol = 'هزار تومان';
                    break;
            }
            return $currency_symbol;
        }

        class WC_ZPal extends WC_Payment_Gateway
        {
            private $merchantCode;
            private $failedMassage;
            private $successMassage;

            public function __construct()
            {

                $this->id = 'WC_ZPal';
                $this->method_title = __('پرداخت امن زرین پال', 'woocommerce');
                $this->method_description = __('تنظیمات درگاه پرداخت زرین پال برای افزونه فروشگاه ساز ووکامرس', 'woocommerce');
                $this->icon = apply_filters('WC_ZPal_logo', WP_PLUGIN_URL . '/' . plugin_basename(__DIR__) . '/assets/images/logo.png');
                $this->has_fields = false;

                $this->init_form_fields();
                $this->init_settings();

                $this->title = $this->settings['title'];
                $this->description = $this->settings['description'];

                $this->merchantCode = $this->settings['merchantcode'];

                $this->successMassage = $this->settings['success_massage'];
                $this->failedMassage = $this->settings['failed_massage'];

                if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                } else {
                    add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
                }

                add_action('woocommerce_receipt_' . $this->id . '', array($this, 'Send_to_ZarinPal_Gateway'));
                add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'payment_verify'));
            }

            public function init_form_fields()
            {
                $this->form_fields = apply_filters('WC_ZPal_Config', array(
                        'base_config' => array(
                            'title' => __('تنظیمات پایه ای', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'enabled' => array(
                            'title' => __('فعالسازی/غیرفعالسازی', 'woocommerce'),
                            'type' => 'checkbox',
                            'label' => __('فعالسازی درگاه زرین پال', 'woocommerce'),
                            'description' => __('برای فعالسازی درگاه پرداخت زرین پال باید چک باکس را تیک بزنید', 'woocommerce'),
                            'default' => 'yes',
                            'desc_tip' => true,
                        ),
                        'title' => array(
                            'title' => __('عنوان درگاه', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('عنوان درگاه که در طی خرید به مشتری نمایش داده میشود', 'woocommerce'),
                            'default' => __('پرداخت امن زرین پال', 'woocommerce'),
                            'desc_tip' => true,
                        ),
                        'description' => array(
                            'title' => __('توضیحات درگاه', 'woocommerce'),
                            'type' => 'text',
                            'desc_tip' => true,
                            'description' => __('توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce'),
                            'default' => __('پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه زرین پال', 'woocommerce')
                        ),
                        'account_config' => array(
                            'title' => __('تنظیمات حساب زرین پال', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'merchantcode' => array(
                            'title' => __('مرچنت کد', 'woocommerce'),
                            'type' => 'text',
                            'description' => __('مرچنت کد درگاه زرین پال', 'woocommerce'),
                            'default' => '',
                            'desc_tip' => true
                        ),
                        'zarinwebgate' => array(
                            'title' => __('فعالسازی زرین گیت', 'woocommerce'),
                            'type' => 'checkbox',
                            'label' => __('برای فعالسازی درگاه مستقیم (زرین گیت) باید چک باکس را تیک بزنید', 'woocommerce'),
                            'description' => __('درگاه مستقیم زرین پال', 'woocommerce'),
                            'default' => '',
                            'desc_tip' => true,
                        ),
                        'payment_config' => array(
                            'title' => __('تنظیمات عملیات پرداخت', 'woocommerce'),
                            'type' => 'title',
                            'description' => '',
                        ),
                        'success_massage' => array(
                            'title' => __('پیام پرداخت موفق', 'woocommerce'),
                            'type' => 'textarea',
                            'description' => __('متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری (توکن) زرین پال استفاده نمایید .', 'woocommerce'),
                            'default' => __('با تشکر از شما . سفارش شما با موفقیت پرداخت شد .', 'woocommerce'),
                        ),
                        'failed_massage' => array(
                            'title' => __('پیام پرداخت ناموفق', 'woocommerce'),
                            'type' => 'textarea',
                            'description' => __('متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت زرین پال ارسال میگردد .', 'woocommerce'),
                            'default' => __('پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .', 'woocommerce'),
                        ),
                    )
                );
            }

            public function SendRequestToZarinPal($action, $params)
            {
                // https://www.zarinpal.com/lab/%d9%86%d9%85%d9%88%d9%86%d9%87-%d8%b2%d8%b1%db%8c%d9%86-%d9%be%d8%a7%d9%84-%d8%b2%d8%a8%d8%a7%d9%86-php-rest/
                try {
                    //$ch = curl_init('https://sandbox.zarinpal.com/pg/rest/WebGate/' . $action . '.json');
                    $ch = curl_init('https://www.zarinpal.com/pg/rest/WebGate/' . $action . '.json');
                    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($params)
                    ));
                    $result = curl_exec($ch);
                    return json_decode($result, true);
                } catch (Exception $ex) {
                    return false;
                }
            }

            public function process_payment($order)
            {
                global $woocommerce;
                $order_id = $order->get_id();
                $woocommerce->session->order_id_zarinpal = $order_id;
                $order = new WC_Order($order_id);
                $currency = $order->get_currency();
                $currency = apply_filters('WC_ZPal_Currency', $currency, $order_id);

                $Amount = (int)$order->order_total;
                $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
                $strToLowerCurrency = strtolower($currency);
                if (
                    ($strToLowerCurrency === strtolower('IRT')) ||
                    ($strToLowerCurrency === strtolower('TOMAN')) ||
                    $strToLowerCurrency === strtolower('Iran TOMAN') ||
                    $strToLowerCurrency === strtolower('Iranian TOMAN') ||
                    $strToLowerCurrency === strtolower('Iran-TOMAN') ||
                    $strToLowerCurrency === strtolower('Iranian-TOMAN') ||
                    $strToLowerCurrency === strtolower('Iran_TOMAN') ||
                    $strToLowerCurrency === strtolower('Iranian_TOMAN') ||
                    $strToLowerCurrency === strtolower('تومان') ||
                    $strToLowerCurrency === strtolower('تومان ایران'
                    )
                ) {
                    $Amount *= 1;
                } else if (strtolower($currency) === strtolower('IRHT')) {
                    $Amount *= 1000;
                } else if (strtolower($currency) === strtolower('IRHR')) {
                    $Amount *= 100;
                } else if (strtolower($currency) === strtolower('IRR')) {
                    $Amount /= 10;
                }

                $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency);
                $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $Amount, $currency);
                $Amount = apply_filters('woocommerce_order_amount_total_ZarinPal_gateway', $Amount, $currency);

                $CallbackUrl = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_ZPal'));

                $products = array();
                $order_items = $order->get_items();
                foreach ($order_items as $product) {
                    $products[] = $product['name'] . ' (' . $product['qty'] . ') ';
                }
                $products = implode(' - ', $products);

                $Description = 'خرید به شماره سفارش : ' . $order->get_order_number() . ' | خریدار : ' . $order->billing_first_name . ' ' . $order->billing_last_name;
                $Mobile = get_post_meta($order_id, '_billing_phone', true) ?: '-';
                $Email = $order->billing_email;
                $Payer = $order->billing_first_name . ' ' . $order->billing_last_name;
                $ResNumber = (int)$order->get_order_number();

                //Hooks for iranian developer
                $Description = apply_filters('WC_ZPal_Description', $Description, $order_id);
                $Mobile = apply_filters('WC_ZPal_Mobile', $Mobile, $order_id);
                $Email = apply_filters('WC_ZPal_Email', $Email, $order_id);
                $Payer = apply_filters('WC_ZPal_Paymenter', $Payer, $order_id);
                $ResNumber = apply_filters('WC_ZPal_ResNumber', $ResNumber, $order_id);
                $Email = !filter_var($Email, FILTER_VALIDATE_EMAIL) === false ? $Email : '';
                $Mobile = preg_match('/^09[0-9]{9}/i', $Mobile) ? $Mobile : '';

                $acczarin = ($this->settings['zarinwebgate'] === 'no') ? 'https://www.zarinpal.com/pg/StartPay/%s/' : 'https://www.zarinpal.com/pg/StartPay/%s/ZarinGate';
                //$acczarin =  'https://sandbox.zarinpal.com/pg/StartPay/%s/';

                $data = array('MerchantID' => $this->merchantCode, 'Amount' => $Amount, 'CallbackURL' => $CallbackUrl, 'Description' => $Description);
                $result = $this->SendRequestToZarinPal('PaymentRequest', json_encode($data));
                if ($result === false) {
                    return array('result' => 'error', 'message' => 'خطا در انتقال به درگاه پرداخت');
                } else if ($result['Status'] === 100) {
                    $go = sprintf($acczarin, $result['Authority']);
                    return array('result' => 'success', 'redirect' => $go);
                } else {
                    return array('result' => 'error', 'message' => 'خطا در انتقال به درگاه پرداخت');
                }
            }

            public function payment_verify()
            {
                $InvoiceNumber = isset($_POST['InvoiceNumber']) ? $_POST['InvoiceNumber'] : '';

                global $woocommerce;
                if (isset($_GET['wc_order'])) {
                    $order_id = $_GET['wc_order'];
                } else if ($InvoiceNumber) {
                    $order_id = $InvoiceNumber;
                } else {
                    $order_id = $woocommerce->session->order_id_zarinpal;
                    unset($woocommerce->session->order_id_zarinpal);
                }

                if ($order_id) {
                    $order = new WC_Order($order_id);
                    $currency = $order->get_currency();
                    $currency = apply_filters('WC_ZPal_Currency', $currency, $order_id);

                    if ($order->status !== 'completed') {
                        $MerchantCode = $this->merchantCode;

                        if ($_GET['Status'] === 'OK') {

                            $MerchantID = $this->merchantCode;
                            $Amount = (int)$order->order_total;
                            $Amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency);
                            $strToLowerCurrency = strtolower($currency);
                            if (
                                ($strToLowerCurrency === strtolower('IRT')) ||
                                ($strToLowerCurrency === strtolower('TOMAN')) ||
                                $strToLowerCurrency === strtolower('Iran TOMAN') ||
                                $strToLowerCurrency === strtolower('Iranian TOMAN') ||
                                $strToLowerCurrency === strtolower('Iran-TOMAN') ||
                                $strToLowerCurrency === strtolower('Iranian-TOMAN') ||
                                $strToLowerCurrency === strtolower('Iran_TOMAN') ||
                                $strToLowerCurrency === strtolower('Iranian_TOMAN') ||
                                $strToLowerCurrency === strtolower('تومان') ||
                                $strToLowerCurrency === strtolower('تومان ایران'
                                )
                            ) {
                                $Amount *= 1;
                            } else if (strtolower($currency) === strtolower('IRHT')) {
                                $Amount *= 1000;
                            } else if (strtolower($currency) === strtolower('IRHR')) {
                                $Amount *= 100;
                            } else if (strtolower($currency) === strtolower('IRR')) {
                                $Amount /= 10;
                            }

                            $Authority = $_GET['Authority'];

                            $data = array('MerchantID' => $MerchantID, 'Authority' => $Authority, 'Amount' => $Amount);
                            $result = $this->SendRequestToZarinPal('PaymentVerification', json_encode($data));

                            if ($result['Status'] === 100) {
                                $Transaction_ID = $result['RefID'];

                                // Add Post Meta
                                update_post_meta($order->get_id(), 'transaction-id', $Transaction_ID);

                                // The text for the note
                                $note = 'شناسه پرداخت: ';
                                $note .= $Transaction_ID;
                                $order->add_order_note($note);
                                $order->update_status('completed');
                                $order->payment_complete($Transaction_ID);
                                wc_reduce_stock_levels($order->get_id());
                                WC()->cart->empty_cart();
                                $order->save();

                                // Action
                                do_action('woocommerce_payment_complete', $order_id);

                                // Use apply_filters( 'woocommerce_get_return_url', $return_url, $order ) filter
                                $return_url = $this->get_return_url($order);
                                wp_redirect($return_url);
                                exit;

                            } else {

                                $order->update_status('failed');
                                $order->save();

                                // Use apply_filters( 'woocommerce_get_return_url', $return_url, $order ) filter
                                $return_url = $this->get_return_url($order);
                                wp_redirect($return_url);
                                exit;
                            }
                        } else {

                            $order->update_status('failed');
                            $order->save();

                            // Use apply_filters( 'woocommerce_get_return_url', $return_url, $order ) filter
                            $return_url = $this->get_return_url($order);
                            wp_redirect($return_url);
                            exit;
                        }
                    }
                }
            }
        }
    }
}

add_action('plugins_loaded', 'Load_ZarinPal_Gateway', 99);
