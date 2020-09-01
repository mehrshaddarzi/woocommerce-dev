jQuery(document).ready(function ($) {

    var woocommerce_methods = {
        wc_add_to_cart: function ($tag = false, $product_id = 0, $quantity = 1, $variation_id = 0) {
            // Arg
            let arg = {
                'product-id': $product_id,
                'quantity': $quantity,
                'variation-id': $variation_id
            };

            // Exist in attr
            ["product-id", "quantity", "variation-id"].forEach(function (item, index) {
                if ($tag.attr("data-" + item) && $tag.attr("data-" + item).length > 0) {
                    arg[item] = $tag.attr("data-" + item);
                }
            });

            // Check Exist Select For Quantity
            let input_quantity = $("#quantity-product-" + arg["product-id"]);
            if (input_quantity.length > 0) {
                arg['quantity'] = input_quantity.val();
            }

            // Send Request
            window.rewrite_api_method.request('wc/cart_add_product', 'GET', arg, $tag);
        },
        wc_remove_from_cart: function ($tag = false, $item_key = false, $product_id = false) {
            // Arg
            let arg = {
                'product-id': $product_id,
                'cart-item-key': $item_key
            };

            // Exist in attr
            ["product-id", "cart-item-key"].forEach(function (item, index) {
                if ($tag.attr("data-" + item) && $tag.attr("data-" + item).length > 0) {
                    arg[item] = $tag.attr("data-" + item);
                }
            });

            // Send Request
            window.rewrite_api_method.request('wc/cart_remove_product', 'GET', arg, $tag);
        },
        wc_cart_clear: function ($tag = false) {
            window.rewrite_api_method.request('wc/cart_clear', 'GET', {}, $tag);
        },
        wc_cart_set_quantity: function ($tag = false, $item_key = false, $product_id = false, $quantity = false) {
            // Arg
            let arg = {
                'product-id': $product_id,
                'cart-item-key': $item_key,
                'quantity': $quantity
            };

            // Check Exist Select Tag
            if ($tag !== false) {
                arg['quantity'] = $tag.val();
                arg['cart-item-key'] = $tag.attr('data-cart-item-key');
            }

            // Send Request
            window.rewrite_api_method.request('wc/cart_set_quantity', 'GET', arg, $tag);
        },
        wc_product_attribute_change_cart: function ($tag = false) {
            let form_area = $("#add_to_cart_area");
            let product_id = form_area.attr("data-product-id");
            let variation_input_data = $("input[name=variation-data-" + product_id + "]");
            let variations_data = window.rewrite_api_method.to_array(variation_input_data.attr("data-variation-data"));
            let variations_list = window.rewrite_api_method.to_array(variation_input_data.attr("data-attribution-fields"));

            // Get Current Data
            let current_attr_val = {};
            $("#add_to_cart_area select[data-product-attribute]").each(function (index) {
                current_attr_val[$(this).attr("data-product-attribute")] = $(this).val();
            });

            // Search For Variable ID
            let variation_id = 0;
            Object.keys(variations_data).forEach(function (product_id) {
                // Get Attr
                let $attr = variations_data[product_id]['attr'];
                if (JSON.stringify($attr) === JSON.stringify(current_attr_val)) {
                    variation_id = product_id;
                }
            });

            // Trigger
            let method = 'wc/wc_product_attribute_change_cart';
            $(document).trigger('add_action_' + method.replace("/", "_"), {
                'form_area': form_area,
                'tag': $tag,
                'variation_id': variation_id, //zero is not found
                'current_attr_val': current_attr_val,
                'product_id': product_id,
                'variations_data': variations_data,
                'variations_list': variations_list
            });
        },
        wc_add_reviews: function($tag = false) {

            // arg
            let arg = {
                'product_id': 0,
                'comment_parent_id': 0,
                'comment_email': '',
                'comment_author': '',
                'comment_rating': '',
                'comment_content': '',
            };

            // extra parameters
            if ($tag !== false) {
                arg = $.extend(arg, window.rewrite_api_method.get_form_inputs($tag));
            }

            // Send Data
            window.rewrite_api_method.request('wc/review_add', 'POST', arg, $tag);
        }
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, woocommerce_methods);
    }
});