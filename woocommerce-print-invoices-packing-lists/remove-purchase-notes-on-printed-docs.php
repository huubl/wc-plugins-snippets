<?php // only copy this line if needed

/**
 * Remove WooCommerce's Product Purchase Note from printed documents. 
 */

add_filter( 'wc_pip_order_item_meta_show_purchase_note', '__return_false' );
