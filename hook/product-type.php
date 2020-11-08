<?php

namespace WooCommerce_Dev;

class WooCommerce_Product_Type
{
    public function __construct()
    {
        // Add or Remove Product Tabs
        //add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_tabs' ), 10, 1 );

        // Modify Product Type
        //add_filter( 'product_type_selector', array( $this, 'product_type' ) );

        // Disable Option download and Virtual From WooCommerce
        //add_filter( 'product_type_options', array( $this, 'product_type_options' ) );
    }

    public function product_type_options( $options ) {
        if ( isset( $options['virtual'] ) ) {
            unset( $options['virtual'] );
        }
        if ( isset( $options['downloadable'] ) ) {
            unset( $options['downloadable'] );
        }
        return $options;
    }

    /**
     * @see https://businessbloomer.com/woocommerce-how-to-create-a-new-product-type/
     * @see https://jeroensormani.com/adding-a-custom-woocommerce-product-type/
     * @see https://www.tychesoftwares.com/how-to-add-a-new-custom-product-type-in-woocommerce/
     *
     * @param $types
     * @return mixed
     */
    public function product_type( $types ) {
        unset( $types['grouped'] );
        unset( $types['external'] );
        //unset( $types['variable'] );

        return $types;
    }

    /**
     * @see https://www.themelocation.com/how-to-remove-product-panel-tabs-admin-panel-in-woocommerce/
     * @param $tabs
     * @return mixed
     */
    public function product_tabs( $tabs ) {
        //unset( $tabs['general'] );
        //unset( $tabs['inventory'] );
        //unset( $tabs['shipping'] );
        //unset( $tabs['linked_product'] );
        //unset( $tabs['attribute'] );
        //unset( $tabs['advanced'] );
        return $tabs;
    }

}

new WooCommerce_Product_Type;