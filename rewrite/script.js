jQuery(document).ready(function ($) {

    var woocommerce_methods = {
    };

    // Push To global Rewrite API Js
    if (typeof window.rewrite_api_method !== 'undefined') {
        $.extend(window.rewrite_api_method, woocommerce_methods);
    }
});