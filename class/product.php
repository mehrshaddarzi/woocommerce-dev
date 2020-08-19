<?php

namespace WooCommerce_Dev;

class WooCommerce_Product
{

    /**
     * Get Product Data By ID
     *
     * @param $product_id
     * @param array $arg
     * @return array
     */
    public static function get($product_id, $arg = array())
    {
        // Get Product Type
        $product_type = \WC_Product_Factory::get_product_type($product_id);

        // Check If variable
        if ($product_type == "variable") {
            $get = new \WC_Product_Variable($product_id);
            $array = $get->get_data();
            // Get Price https://stackoverflow.com/questions/44675192/display-woocommerce-variable-product-price
        } else {
            $get = wc_get_product($product_id);
            $array = $get->get_data();
        }

        // Additional Data
        // @see https://gist.github.com/mehrshaddarzi/4d49f7475172c7c5cc235290940d6203
        $array['product_type'] = $get->get_type();
        if (!empty($array['image_id']) and isset($arg['full_thumbnail'])) {
            $array['image_full_src'] = wp_get_attachment_url($array['image_id']);
        }
        if (isset($arg['thumbnail_size']) and !empty($array['image_id'])) {
            $attachment_src = wp_get_attachment_image_src($array['image_id'], $arg['thumbnail_size']);
            $array['image_thumbnail_src'] = $attachment_src[0];
        }

        // Set Formatted Weight and Dimensions
        add_filter("option_woocommerce_weight_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'), 999, 2);
        add_filter("option_woocommerce_dimension_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'), 999, 2);
        $array['weight-rendered'] = wc_format_weight($array['weight']);
        $array['dimensions-rendered'] = wc_format_dimensions($get->get_dimensions(false));
        remove_filter("option_woocommerce_weight_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'));
        remove_filter("option_woocommerce_weight_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'));

        // Discount
        $array['is_on_sale'] = $get->is_on_sale();
        $array['price_off'] = 0;
        $array['percentage_off'] = 0;
        if ($get->is_on_sale() === true and !empty($get->get_sale_price())) {
            $array['percentage_off'] = round((($get->get_regular_price() - $get->get_sale_price()) / $get->get_regular_price()) * 100);
            $array['price_off'] = $get->get_regular_price() - $get->get_sale_price();
        }

        // Stock
        $array['is_in_stock'] = $get->is_in_stock();
        $array['get_min_purchase_quantity'] = $get->get_min_purchase_quantity();
        $array['get_max_purchase_quantity'] = $get->get_max_purchase_quantity();

        // Permalink
        $array['permalink'] = get_the_permalink($product_id);

        // Get Children variable Product
        if ($array['product_type'] == "variable") {
            $array['children_ids'] = $get->get_children();
            if (isset($arg['get_children']) and $arg['get_children'] === true) {
                if (count($array['children_ids']) > 0) {
                    $array['children'] = array();
                    foreach ($array['children_ids'] as $children_id) {
                        $children_product = wc_get_product($children_id);
                        $array['children'][$children_id] = $children_product->get_data();
                    }
                }
            }
        }

        // Return Data
        return $array;
    }

    /**
     * Get Product Gallery Images
     *
     * @param $product_id
     * @param string $thumbnail_size
     * @return array
     */
    public static function get_product_gallery_images($product_id, $thumbnail_size = 'thumbnail')
    {
        // Get Product Data
        $array = self::get($product_id);

        // Create Gallery image List
        $gallery_images = array();
        if (!empty($array['image_id'])) {
            $thumbnail = wp_get_attachment_image_src($array['image_id'], $thumbnail_size);
            $gallery_images[$array['image_id']] = array(
                'thumb' => $thumbnail[0],
                'full' => wp_get_attachment_url($array['image_id']),
                'alt' => get_post_meta($array['image_id'], '_wp_attachment_image_alt', true),
            );
        }
        if (!empty($array['gallery_image_ids'])) {
            foreach ($array['gallery_image_ids'] as $attachment_id) {
                $thumbnail = wp_get_attachment_image_src($attachment_id, $thumbnail_size);
                $gallery_images[$attachment_id] = array(
                    'thumb' => $thumbnail[0],
                    'full' => wp_get_attachment_url($attachment_id),
                    'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                );
            }
        }

        return $gallery_images;
    }

    /**
     * Get Products List
     *
     * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
     * @param array $arg
     * @return array|\stdClass
     */
    public static function get_list($arg = array())
    {
        $default = array(
            //'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'stock_status' => 'instock',
        );
        $args = wp_parse_args($default, $arg);
        return wc_get_products($args);
    }

    /**
     * Get Product Types
     *
     * @return array
     */
    public static function get_product_types()
    {
        return wc_get_product_types();
    }

    /**
     * Get Product ID by SKU
     *
     * @param $sku
     * @return int
     */
    public static function get_product_id_by_sku($sku)
    {
        return wc_get_product_id_by_sku($sku);
    }

    /**
     * Check Exist Product
     *
     * @param $product_id
     * @param string $post_status
     * @param string $post_type
     * @return bool
     */
    public static function exist($product_id, $post_status = 'publish', $post_type = 'product')
    {
        global $wpdb;
        $query = $wpdb->get_var("SELECT count(*) FROM `$wpdb->posts` WHERE `ID` = $product_id AND `post_type` = '$post_type' AND `post_status` = '$post_status'");
        return (int)$query > 0;
    }
}

new WooCommerce_Product;