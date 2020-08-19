jQuery(document).ready(function ($) {

    var woocommerce_compare_methods = {
        wc_add_product_to_compare: function ($tag = false, $product_id = 0) {
            // Sanitize Params
            if ($tag !== false) {
                $product_id = $tag.attr('data-product-id');
            }
            window.rewrite_api_method.request('wc_compare/add', 'GET', {
                'product_id': $product_id
            });
        },
        wc_remove_product_to_compare: function ($tag = false, $product_id = 0) {
            // Sanitize Params
            if ($tag !== false) {
                $product_id = $tag.attr('data-product-id');
            }
            window.rewrite_api_method.request('wc_compare/remove', 'GET', {
                'product_id': $product_id
            });
        },
        wc_clear_compare_list: function ($tag = false) {
            window.rewrite_api_method.request('wc_compare/clear', 'GET');
        },
        wc_get_compare_list: function ($tag = false) {
            window.rewrite_api_method.request('wc_compare/get', 'GET');
        },
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, woocommerce_compare_methods);
    }
});