<?php

namespace WooCommerce_Dev;

class WooCommerce_ACF
{
    public function __construct()
    {
        //Add ACF Rule For Woocommerce Attribute
        //@see https://hirejordansmith.com/how-to-add-advanced-custom-fields-to-woocommerce-attributes/
        add_filter('acf/location/rule_types', array($this, 'add_rule_types'));
        // Adds custom rule values.
        add_filter('acf/location/rule_values/wc_prod_attr', array($this, 'add_rule_value'));
        // Matching the custom rule.
        add_filter('acf/location/rule_match/wc_prod_attr', array($this, 'add_rule_match'), 10, 3);
    }

    public function add_rule_types($choices)
    {
        $choices[__("Other", 'acf')]['wc_prod_attr'] = 'WC Product Attribute';
        return $choices;
    }

    public function add_rule_value($choices)
    {
        foreach (wc_get_attribute_taxonomies() as $attr) {
            $pa_name = wc_attribute_taxonomy_name($attr->attribute_name);
            $choices[$pa_name] = $attr->attribute_label;
        }
        return $choices;
    }

    public function add_rule_match($match, $rule, $options)
    {
        if (isset($options['taxonomy'])) {
            if ('==' === $rule['operator']) {
                $match = $rule['value'] === $options['taxonomy'];
            } elseif ('!=' === $rule['operator']) {
                $match = $rule['value'] !== $options['taxonomy'];
            }
        }
        return $match;
    }

}

new WooCommerce_ACF;