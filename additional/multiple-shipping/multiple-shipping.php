<?php

namespace WooCommerce_Dev;

/**
 * @Require:
 * 1) Advance Custom Field Pro
 * 2) https://github.com/mehrshaddarzi/acf-unique-id-field
 * 3) Persian WooCommerce
 */
class WooCommerce_User_Multiple_Shipping
{
    public static $acf_user_field_name = 'customer-shipping-list';

    public function __construct()
    {
        // Create ACF Field
        add_action('acf/init', array($this, 'my_acf_add_local_field_groups'));

        // Add Choice Country
        add_filter('acf/load_field/key=field_5f30de92184a7', array($this, 'acf_load_country_field_choices'));
        add_filter('acf/load_field/key=field_5f30e7f0c6bce', array($this, 'acf_load_state_field_choices'));

        // Add Ajax Add Shipping
        $ajax_list_method = array(
            'ajax_woocommerce_dev_add_shipping',
            'ajax_woocommerce_dev_remove_shipping',
            'ajax_woocommerce_dev_edit_shipping',
        );
        foreach ($ajax_list_method as $method) {
            add_action('wp_ajax_' . $method, array($this, $method));
            add_action('wp_ajax_nopriv_' . $method, array($this, $method));
        }
    }

    /**
     * Add Choice Country
     *
     * @Hook
     * @param $field
     * @return mixed
     */
    public function acf_load_country_field_choices($field)
    {
        $field['choices'] = WooCommerce_Location::get_list_country();
        $field['default_value'] = WooCommerce_Location::$IRAN_Country_Key;
        return $field;
    }

    /**
     * Add Choice States
     *
     * @Hook
     * @param $field
     * @return mixed
     */
    public function acf_load_state_field_choices($field)
    {
        $field['choices'] = WooCommerce_Location::get_list_states_from_country('IR');
        return $field;
    }

    /**
     * Get List Custom Address
     *
     * @param $customer_id
     * @param string $ID
     * @return mixed
     */
    public static function get_customer_address_list($customer_id, $ID = '')
    {
        //["ID"]
        //["address-name"]
        //["shipping_country"]
        //["shipping_state"]
        //["shipping_city"]
        //["shipping_address_1"]
        //["shipping_postcode"]
        //["shipping_phone"]
        //["shipping_company"]
        //["shipping_first_name"]
        //["shipping_last_name"]
        //["default"] //true or false
        $array = get_field(self::$acf_user_field_name, 'user_' . $customer_id);
        if (empty($array)) {
            return false;
        }

        // Get List
        $list = array();
        foreach ($array as $item) {
            $list[$item['ID']] = $item;
        }

        // Get Custom ID
        if (!empty($ID)) {
            if (isset($list[$ID])) {
                return $list[$ID];
            }

            return false;
        }

        return $list;
    }

    /**
     * Get Customer Default Shipping
     *
     * @param $customer_id
     * @return array|bool|mixed
     */
    public static function get_customer_default_shipping($customer_id)
    {
        $shipping_list = self::get_customer_address_list($customer_id);

        // Check Empty
        if (empty($shipping_list)) {
            return false;
        }

        // Get Default From List
        $item = array();
        foreach ($shipping_list as $k => $v) {
            if (isset($v['default']) and $v['default'] === true) {
                $item = $v;
                break;
            }
        }

        // Check Not Default
        if (!empty($item)) {
            return $item;
        }

        // Show first Address
        return reset($shipping_list);
    }

    /**
     * Add Shipping
     *
     * @param $customer_id
     * @param array $arg
     * @return array|object|string
     */
    public static function add_shipping($customer_id, $arg = array())
    {
        $default = array(
            "ID" => uniqid(),
            "address-name" => '',
            "shipping_country" => 'IR',
            "shipping_state" => 'THR',
            "shipping_city" => '',
            "shipping_address_1" => '',
            "shipping_postcode" => '',
            "shipping_phone" => '',
            "shipping_company" => '',
            "shipping_first_name" => '',
            "shipping_last_name" => '',
            "default" => false
        );
        $args = wp_parse_args($arg, $default);

        // Require Params
        if (empty($args['shipping_city']) || empty($args['shipping_address_1']) || empty($args['shipping_postcode'])) {
            return array('status' => false, 'message' => 'لطفا فیلد های الزامی را پر کنید');
        }

        $new_value = get_field(self::$acf_user_field_name, 'user_' . $customer_id);
        $new_value[] = $args;
        update_field(self::$acf_user_field_name, $new_value, 'user_' . $customer_id);

        return array('status' => true, 'data' => $args);
    }

    /**
     * Check Ajax Nonce
     */
    public static function check_ajax_nonce()
    {
        if (!isset($_REQUEST['_nonce'])) {
            exit;
        }

        if (!isset($_REQUEST['_nonce']) || !wp_verify_nonce(trim($_REQUEST['_nonce']), 'woocommerce-dev-nonce')) {
            wp_send_json(array(
                'message' => 'درخواست نامعتبر هست'
            ), 400);
        }
    }

    /**
     * WooCommerce Add Shipping Ajax
     *
     * @Hook
     */
    public function ajax_woocommerce_dev_add_shipping()
    {
        // Check Require Params
        if (!is_user_logged_in()) {
            exit;
        }

        // Check Nonce
        self::check_ajax_nonce();

        // Add Shipping
        $add_shipping = self::add_shipping(get_current_user_id(), $_REQUEST);
        if ($add_shipping['status'] === false) {
            wp_send_json(array(
                'message' => $add_shipping['message']
            ), 400);
        }

        // Complete
        wp_send_json(array(
            'message' => 'آدرس اضافه شد',
            'data' => $add_shipping['message']
        ), 200);
    }

    /**
     * Get Array index Number By ID for example 'fblirt69' => [1] in array
     *
     * @param $customer_id
     * @param $ID
     * @return bool|int|string
     */
    public static function get_array_key_by_shipping_id($customer_id, $ID)
    {
        $get_lists = get_field(self::$acf_user_field_name, 'user_' . $customer_id);
        $key = false;
        foreach ($get_lists as $k => $item) {
            if (isset($item['ID']) and $item['ID'] == $ID) {
                $key = $k;
                break;
            }
        }

        return $key;
    }

    /**
     * Remove Shipping
     *
     * @param $customer_id
     * @param $ID
     * @return array
     */
    public static function remove_shipping($customer_id, $ID)
    {
        // Get Array key by ID
        $key = self::get_array_key_by_shipping_id($customer_id, $ID);

        // Check Not Exist
        if ($key === false) {
            return array('status' => false, 'message' => 'شناسه یافت نشد');
        }

        // Remove
        // @see https://www.advancedcustomfields.com/resources/delete_row/
        add_filter('acf/settings/row_index_offset', '__return_zero');
        delete_row(self::$acf_user_field_name, $key, 'user_' . $customer_id);

        // Return True
        return array('status' => true, 'data' => self::get_customer_address_list($customer_id));
    }

    /**
     * WooCommerce Remove Shipping Ajax
     *
     * @Hook
     */
    public function ajax_woocommerce_dev_remove_shipping()
    {
        // Check Require Params
        if (!is_user_logged_in() || !isset($_REQUEST['shipping_id'])) {
            exit;
        }

        // Check Nonce
        self::check_ajax_nonce();

        // Remove Shipping
        $remove_shipping = self::remove_shipping(get_current_user_id(), sanitize_text_field($_REQUEST['shipping_id']));
        if ($remove_shipping['status'] === false) {
            wp_send_json(array(
                'message' => $remove_shipping['message']
            ), 400);
        }

        // Complete
        wp_send_json(array(
            'message' => 'آدرس حذف شد',
            'data' => $remove_shipping['message']
        ), 200);
    }

    /**
     * Edit Shipping
     *
     * @param $customer_id
     * @param $ID
     * @param array $args
     * @return array
     */
    public static function edit_shipping($customer_id, $ID, $args = array())
    {
        // Get Array key by ID
        $key = self::get_array_key_by_shipping_id($customer_id, $ID);

        // Check Not Exist
        if ($key === false) {
            return array('status' => false, 'message' => 'شناسه یافت نشد');
        }

        // Update Row
        // @see https://www.advancedcustomfields.com/resources/update_row/
        add_filter('acf/settings/row_index_offset', '__return_zero');
        update_row(self::$acf_user_field_name, $key, $args, 'user_' . $customer_id);

        // Return Data
        return array(
            'status' => true,
            'message' => 'آدرس ویرایش شد',
            'data' => self::get_customer_address_list($customer_id)
        );
    }

    /**
     * WooCommerce Editing Shipping Ajax
     *
     * @Hook
     */
    public function ajax_woocommerce_dev_edit_shipping()
    {
        // Check Require Params
        if (!is_user_logged_in() || !isset($_REQUEST['shipping_id'])) {
            exit;
        }

        // Check Nonce
        self::check_ajax_nonce();

        // Edit Shipping
        $edit_shipping = self::edit_shipping(get_current_user_id(), sanitize_text_field($_REQUEST['shipping_id']), $_REQUEST);
        if ($edit_shipping['status'] === false) {
            wp_send_json(array(
                'message' => $edit_shipping['message']
            ), 400);
        }

        // Complete
        wp_send_json(array(
            'data' => $edit_shipping['message']
        ), 200);
    }

    /**
     * Create ACF Field
     *
     * @Hook
     */
    public function my_acf_add_local_field_groups()
    {
        if (function_exists('acf_add_local_field_group')):
            acf_add_local_field_group(array(
                'key' => 'group_5f30dcdea5a62',
                'title' => 'آدرس های مشتریان',
                'fields' => array(
                    array(
                        'key' => 'field_5f30dce912b21',
                        'label' => 'لیست آدرس ها',
                        'name' => 'customer-shipping-list',
                        'type' => 'repeater',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'collapsed' => '',
                        'min' => 0,
                        'max' => 0,
                        'layout' => 'row', //block
                        'button_label' => '',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_5f30dcff12b22',
                                'label' => 'شناسه',
                                'name' => 'ID',
                                'type' => 'unique_id',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                            ),
                            array(
                                'key' => 'field_5f30e71cc6bcd',
                                'label' => 'اسم شاخص',
                                'name' => 'address-name',
                                'type' => 'text',
                                'instructions' => 'یک نام دلخواه مثلا : شرکت ، خانه ، باشگاه و ...',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30de92184a7',
                                'label' => 'کشور',
                                'name' => 'shipping_country',
                                'type' => 'select',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'choices' => array(),
                                'default_value' => false,
                                'allow_null' => 0,
                                'multiple' => 0,
                                'ui' => 1,
                                'return_format' => 'value',
                                'ajax' => 0,
                                'placeholder' => '',
                            ),
                            array(
                                'key' => 'field_5f30e7f0c6bce',
                                'label' => 'استان',
                                'name' => 'shipping_state',
                                'type' => 'select',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'choices' => array(),
                                'default_value' => false,
                                'allow_null' => 0,
                                'multiple' => 0,
                                'ui' => 1,
                                'return_format' => 'value',
                                'ajax' => 0,
                                'placeholder' => '',
                            ),
                            array(
                                'key' => 'field_5f30e802c6bcf',
                                'label' => 'شهر',
                                'name' => 'shipping_city',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30e810c6bd0',
                                'label' => 'آدرس کامل',
                                'name' => 'shipping_address_1',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30e850c6bd4',
                                'label' => 'کد پستی',
                                'name' => 'shipping_postcode',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30e850c6bp8',
                                'label' => 'شماره تماس',
                                'name' => 'shipping_phone',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30e81cc6bd1',
                                'label' => 'نام شرکت',
                                'name' => 'shipping_company',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30e829c6bd2',
                                'label' => 'نام',
                                'name' => 'shipping_first_name',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30e832c6bd3',
                                'label' => 'نام خانوادگی',
                                'name' => 'shipping_last_name',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'maxlength' => '',
                            ),
                            array(
                                'key' => 'field_5f30f0e710b16',
                                'label' => 'آدرس پیش فرض',
                                'name' => 'default',
                                'type' => 'true_false',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'message' => '',
                                'default_value' => 0,
                                'ui' => 1,
                                'ui_on_text' => '',
                                'ui_off_text' => '',
                            )
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'user_form',
                            'operator' => '==',
                            'value' => 'all',
                        ),
                    )
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));
        endif;
    }
}

new WooCommerce_User_Multiple_Shipping;
