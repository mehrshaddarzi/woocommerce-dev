<?php

namespace WooCommerce_Dev;

class WooCommerce_Product
{

    public function __construct()
    {

        // Add Custom field To Product Data
        add_filter('woocommerce_dev_product_data', array($this, 'filter_product_data'), 10, 2);
    }

    /**
     * Filter Product Data
     *
     * @param $data
     * @param $product
     * @return mixed
     */
    public function filter_product_data($data, $product)
    {
        // Add Thumbnail ID
        $data['thumbnail_id'] = get_post_thumbnail_id($product->get_id());

        // Set Formatted Weight and Dimensions
        add_filter("option_woocommerce_weight_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'), 999, 2);
        add_filter("option_woocommerce_dimension_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'), 999, 2);
        $data['weight_rendered'] = wc_format_weight($product->get_weight());
        $data['dimensions_rendered'] = wc_format_dimensions($product->get_dimensions(false));
        remove_filter("option_woocommerce_weight_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'));
        remove_filter("option_woocommerce_weight_unit", array('\WooCommerce_Dev\WooCommerce_Helper', 'set_localize_option'));

        // Discount
        $data['discount']['is_on_sale'] = $product->is_on_sale();
        $data['discount']['price_off'] = 0;
        $data['discount']['percentage_off'] = 0;
        if ($product->is_on_sale() === true and !empty($product->get_sale_price())) {
            $data['discount']['percentage_off'] = round((($product->get_regular_price() - $product->get_sale_price()) / $product->get_regular_price()) * 100);
            $data['discount']['price_off'] = $product->get_regular_price() - $product->get_sale_price();
        }

        // Stock
        $data['get_min_purchase_quantity'] = $product->get_min_purchase_quantity();
        $data['get_max_purchase_quantity'] = $product->get_max_purchase_quantity();

        // Fix Price Html
        $data['price_html'] = strip_tags($data['price_html']);

        // Product Type Name
        $product_types = self::get_product_types();
        if (isset($product_types[$data['type']])) {
            $data['product_type'] = $product_types[$data['type']];
        }

        return $data;
    }

    /**
     * Get Product Type
     *
     * @param $product_id
     * @return false|string
     */
    public static function get_product_type($product_id)
    {
        return \WC_Product_Factory::get_product_type($product_id);
    }

    /**
     * Get Product Data By ID
     *
     * @see https://github.com/woocommerce/woocommerce/blob/0699022a46c4750e0b2574de9fccc85795e8e332/includes/legacy/api/v3/class-wc-api-products.php#L1154
     * @param $product_id
     * @return array
     */
    public static function get($product_id)
    {

        // Get Data
        $product = wc_get_product($product_id);

        // Data
        // Also We Can Use WC()->api->WC_API_Products->get_product( $product->get_id() );
        $product_data = self::get_product_data($product);

        // add variations to variable products
        if ($product->is_type('variable') && $product->has_child()) {
            $product_data['variations'] = self::get_variation_data($product);
        }

        // add the parent product data to an individual variation
        if ($product->is_type('variation') && $product->get_parent_id()) {
            $product_data['parent'] = self::get_product_data($product->get_parent_id());
        }

        // Add grouped products data
        if ($product->is_type('grouped') && $product->has_child()) {
            $product_data['grouped_products'] = self::get_grouped_products_data($product);
        }

        if ($product->is_type('simple')) {
            $parent_id = $product->get_parent_id();
            if (!empty($parent_id)) {
                $_product = wc_get_product($parent_id);
                $product_data['parent'] = self::get_product_data($_product);
            }
        }

        // Result
        return $product_data;
    }

    /**
     * Get Product Data
     *
     * @param $product ($product object)
     * @return mixed|void
     */
    public static function get_product_data($product)
    {
        $data = array(
            'title' => $product->get_name(),
            'id' => $product->get_id(),
            'created_at' => WooCommerce_Helper::format_datetime($product->get_date_created(), false, true),
            'updated_at' => WooCommerce_Helper::format_datetime($product->get_date_modified(), false, true),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'downloadable' => $product->is_downloadable(),
            'virtual' => $product->is_virtual(),
            'permalink' => $product->get_permalink(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price() ? $product->get_sale_price() : null,
            'price_html' => $product->get_price_html(),
            'taxable' => $product->is_taxable(),
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'managing_stock' => $product->managing_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'in_stock' => $product->is_in_stock(),
            'backorders_allowed' => $product->backorders_allowed(),
            'backordered' => $product->is_on_backorder(),
            'sold_individually' => $product->is_sold_individually(),
            'purchaseable' => $product->is_purchasable(),
            'featured' => $product->is_featured(),
            'visible' => $product->is_visible(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'on_sale' => $product->is_on_sale(),
            'product_url' => $product->is_type('external') ? $product->get_product_url() : '',
            'button_text' => $product->is_type('external') ? $product->get_button_text() : '',
            'weight' => $product->get_weight() ? $product->get_weight() : null,
            'dimensions' => array(
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height(),
                'unit' => get_option('woocommerce_dimension_unit'),
            ),
            'shipping_required' => $product->needs_shipping(),
            'shipping_taxable' => $product->is_shipping_taxable(),
            'shipping_class' => $product->get_shipping_class(),
            'shipping_class_id' => (0 !== $product->get_shipping_class_id()) ? $product->get_shipping_class_id() : null,
            'description' => wpautop(do_shortcode($product->get_description())),
            'short_description' => apply_filters('woocommerce_short_description', $product->get_short_description()),
            'reviews_allowed' => $product->get_reviews_allowed(),
            'average_rating' => wc_format_decimal($product->get_average_rating(), 2),
            'rating_count' => $product->get_rating_count(),
            'related_ids' => array_map('absint', array_values(wc_get_related_products($product->get_id()))),
            'upsell_ids' => array_map('absint', $product->get_upsell_ids()),
            'cross_sell_ids' => array_map('absint', $product->get_cross_sell_ids()),
            'parent_id' => $product->get_parent_id(),
            'categories' => wp_get_object_terms($product->get_id(), 'product_cat', array('fields' => 'all')),
            'tags' => wp_get_object_terms($product->get_id(), 'product_tag', array('fields' => 'all')),
            'images' => self::get_images($product),
            'featured_src' => wp_get_attachment_url(get_post_thumbnail_id($product->get_id())),
            'attributes' => self::get_attributes($product),
            'downloads' => self::get_downloads($product),
            'download_limit' => $product->get_download_limit(),
            'download_expiry' => $product->get_download_expiry(),
            'download_type' => 'standard',
            'purchase_note' => wpautop(do_shortcode(wp_kses_post($product->get_purchase_note()))),
            'total_sales' => $product->get_total_sales(),
            'variations' => array(),
            'parent' => array(),
            'grouped_products' => array(),
            'menu_order' => self::get_product_menu_order($product),
        );

        // add data that applies to every product type
        return apply_filters('woocommerce_dev_product_data', $data, $product);
    }

    /**
     * Get Variation Data
     *
     * @param $product ($product object)
     * @return array
     */
    public static function get_variation_data($product)
    {
        $variations = array();

        foreach ($product->get_children() as $child_id) {
            $variation = wc_get_product($child_id);

            if (!$variation || !$variation->exists()) {
                continue;
            }

            $variations[] = apply_filters('woocommerce_dev_variation_data', array(
                'id' => $variation->get_id(),
                'created_at' => WooCommerce_Helper::format_datetime($variation->get_date_created(), false, true),
                'updated_at' => WooCommerce_Helper::format_datetime($variation->get_date_modified(), false, true),
                'downloadable' => $variation->is_downloadable(),
                'virtual' => $variation->is_virtual(),
                'permalink' => $variation->get_permalink(),
                'sku' => $variation->get_sku(),
                'price' => $variation->get_price(),
                'regular_price' => $variation->get_regular_price(),
                'sale_price' => $variation->get_sale_price() ? $variation->get_sale_price() : null,
                'taxable' => $variation->is_taxable(),
                'tax_status' => $variation->get_tax_status(),
                'tax_class' => $variation->get_tax_class(),
                'managing_stock' => $variation->managing_stock(),
                'stock_quantity' => $variation->get_stock_quantity(),
                'in_stock' => $variation->is_in_stock(),
                'backorders_allowed' => $variation->backorders_allowed(),
                'backordered' => $variation->is_on_backorder(),
                'purchaseable' => $variation->is_purchasable(),
                'visible' => $variation->variation_is_visible(),
                'on_sale' => $variation->is_on_sale(),
                'weight' => $variation->get_weight() ? $variation->get_weight() : null,
                'dimensions' => array(
                    'length' => $variation->get_length(),
                    'width' => $variation->get_width(),
                    'height' => $variation->get_height(),
                    'unit' => get_option('woocommerce_dimension_unit'),
                ),
                'shipping_class' => $variation->get_shipping_class(),
                'shipping_class_id' => (0 !== $variation->get_shipping_class_id()) ? $variation->get_shipping_class_id() : null,
                'image' => self::get_images($variation),
                'attributes' => self::get_attributes($variation),
                'downloads' => self::get_downloads($variation),
                'download_limit' => (int)$product->get_download_limit(),
                'download_expiry' => (int)$product->get_download_expiry(),
            ));
        }

        return $variations;
    }

    /**
     * Get Grouped Product Data
     *
     * @param $product
     * @return array
     */
    public static function get_grouped_products_data($product)
    {
        $products = array();
        foreach ($product->get_children() as $child_id) {
            $_product = wc_get_product($child_id);
            if (!$_product || !$_product->exists()) {
                continue;
            }

            $products[] = self::get_product_data($_product);
        }

        return $products;
    }

    /**
     * Get List Images
     *
     * @param $product
     * @return array
     */
    public static function get_images($product)
    {
        $images = $attachment_ids = array();
        $product_image = $product->get_image_id();

        // Add featured image.
        if (!empty($product_image)) {
            $attachment_ids[] = $product_image;
        }

        // Add gallery images.
        $attachment_ids = array_merge($attachment_ids, $product->get_gallery_image_ids());

        // Build image data.
        foreach ($attachment_ids as $position => $attachment_id) {
            $attachment_post = get_post($attachment_id);
            if (is_null($attachment_post)) {
                continue;
            }
            $attachment = wp_get_attachment_image_src($attachment_id, 'full');

            if (!is_array($attachment)) {
                continue;
            }

            $images[] = array(
                'id' => (int)$attachment_id,
                'created_at' => WooCommerce_Helper::format_datetime($attachment_post->post_date_gmt),
                'updated_at' => WooCommerce_Helper::format_datetime($attachment_post->post_modified_gmt),
                'src' => current($attachment),
                'title' => get_the_title($attachment_id),
                'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                'position' => (int)$position,
            );
        }

        // Set a placeholder image if the product has no images set.
        if (empty($images)) {
            $images[] = array(
                'id' => 0,
                'created_at' => WooCommerce_Helper::format_datetime(time()), // Default to now.
                'updated_at' => WooCommerce_Helper::format_datetime(time()),
                'src' => wc_placeholder_img_src(),
                'title' => __('Placeholder', 'woocommerce'),
                'alt' => __('Placeholder', 'woocommerce'),
                'position' => 0,
            );
        }
        return $images;
    }

    /**
     * Get Attributes
     *
     * @param $product
     * @return array
     */
    public static function get_attributes($product)
    {

        $attributes = array();

        if ($product->is_type('variation')) {

            // variation attributes
            foreach ($product->get_variation_attributes() as $attribute_name => $attribute) {

                // taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`
                $attributes[] = array(
                    'name' => wc_attribute_label(str_replace('attribute_', '', $attribute_name), $product),
                    'slug' => str_replace('attribute_', '', wc_attribute_taxonomy_slug($attribute_name)),
                    'option' => $attribute,
                );
            }
        } else {

            foreach ($product->get_attributes() as $attribute) {
                $attributes[] = array(
                    'name' => wc_attribute_label($attribute['name'], $product),
                    'slug' => wc_attribute_taxonomy_slug($attribute['name']),
                    'position' => (int)$attribute['position'],
                    'visible' => (bool)$attribute['is_visible'],
                    'variation' => (bool)$attribute['is_variation'],
                    'options' => self::get_attribute_options($product->get_id(), $attribute),
                );
            }
        }

        return $attributes;
    }

    /**
     * Get Attributes Option
     *
     * @param $product_id
     * @param $attribute
     * @return array
     */
    public static function get_attribute_options($product_id, $attribute)
    {
        if (isset($attribute['is_taxonomy']) && $attribute['is_taxonomy']) {
            return wc_get_product_terms($product_id, $attribute['name'], array('fields' => 'names'));
        } elseif (isset($attribute['value'])) {
            return array_map('trim', explode('|', $attribute['value']));
        }

        return array();
    }


    /**
     * Get the downloads for a product or product variation
     *
     * @param WC_Product|WC_Product_Variation $product
     * @return array
     * @since 2.1
     */
    public static function get_downloads($product)
    {
        $downloads = array();

        if ($product->is_downloadable()) {

            foreach ($product->get_downloads() as $file_id => $file) {

                $downloads[] = array(
                    'id' => $file_id, // do not cast as int as this is a hash
                    'name' => $file['name'],
                    'file' => $file['file'],
                );
            }
        }

        return $downloads;
    }

    /**
     * Get Menu Order
     *
     * @param $product
     * @return mixed|void
     */
    public static function get_product_menu_order($product)
    {
        $menu_order = $product->get_menu_order();
        return apply_filters('woocommerce_api_product_menu_order', $menu_order, $product);
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