<?php // only copy this line if needed

/**
 * Adds columns for product ID and variation ID to the Default - One Row per Item export format
 */

/**
 * Add the product id & variation ID to the individual line item entry
 *
 * @param array $line_item the original line item data
 * @param array $item WC order item data
 * @param WC_Product $product the product
 * @return array $line_item	the updated line item data
 */
function sv_wc_csv_export_order_line_item_id( $line_item, $item, $product ) {

	$line_item['item_id']      = $product->get_id();
	$line_item['variation_id'] = '';

	// set the variation id for variable products
	if ( $product->is_type( 'variation' ) ) {
		$line_item['item_id']      = is_callable( array( $product, 'get_parent_id') ) ? $product->get_parent_id() : $product->get_parent();
		$line_item['variation_id'] = $product->get_id();
	}

	return $line_item;
}
add_filter( 'wc_customer_order_csv_export_order_line_item', 'sv_wc_csv_export_order_line_item_id', 10, 3 );


/**
 * Add `item_id` and `variation_id` column headers to the export format
 *
 * @param array  $column_headers the original column headers
 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator the generator instance
 * @return array $column_headers the updated headers
 */
function sv_wc_csv_export_modify_column_headers_item_id( $column_headers, $csv_generator ) {

	if ( sv_wc_csv_export_is_one_row( $csv_generator ) ) {

		$new_headers = array();

		foreach ( $column_headers as $key => $value ) {

			$new_headers[ $key ] = $value;

			// Adds new headers after 'item_name' column
			if ( 'item_name' === $key ) {

				// Add columns for each piece of data
				$new_headers['item_id']      = 'item_id';
				$new_headers['variation_id'] = 'variation_id';
			}
		}

		$column_headers = $new_headers;
	}

	return $column_headers;
}
add_filter( 'wc_customer_order_csv_export_order_headers', 'sv_wc_csv_export_modify_column_headers_item_id', 10, 2 );


/**
 * Add the item_id and variation_id column data for the export format
 *
 * @param array $order_data the original order data
 * @param array $item the item data for this row
 * @return array $order_data the updated row order data
 */
function sv_wc_csv_export_order_row_one_row_per_item_ids( $order_data, $item ) {

	$order_data['item_id']      = $item['item_id'];
	$order_data['variation_id'] = $item['variation_id'];

	return $order_data;
}
add_filter( 'wc_customer_order_csv_export_order_row_one_row_per_item', 'sv_wc_csv_export_order_row_one_row_per_item_ids', 10, 2 );


if ( ! function_exists( 'sv_wc_csv_export_is_one_row' ) ) :

/**
 * Helper function to check the export format
 *
 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator the generator instance
 * @return bool - true if this is a one row per item format
 */
function sv_wc_csv_export_is_one_row( $csv_generator ) {

	$one_row_per_item = false;

	if ( version_compare( wc_customer_order_csv_export()->get_version(), '4.0.0', '<' ) ) {

		// pre 4.0 compatibility
		$one_row_per_item = ( 'default_one_row_per_item' === $csv_generator->order_format || 'legacy_one_row_per_item' === $csv_generator->order_format );

	} elseif ( isset( $csv_generator->format_definition ) ) {

		// post 4.0 (requires 4.0.3+)
		$one_row_per_item = 'item' === $csv_generator->format_definition['row_type'];
	}

	return $one_row_per_item;
}

endif;
