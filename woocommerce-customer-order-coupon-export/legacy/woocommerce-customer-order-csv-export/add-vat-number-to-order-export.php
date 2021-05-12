<?php // only copy this line if needed

/**
 * NOTE: This is not needed for the AvaTax plugin, as it will add VAT ID automatically to order / customer exports.
 */


/**
 * Add `vat_number` column header
 *
 * @param array $column_headers the original column headers
 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator the generator instance
 * @return array the updated column headers
 */
function sv_wc_csv_export_modify_column_headers_vat_number( $column_headers, $csv_generator ) {

	$new_headers = array();

	foreach( $column_headers as $key => $header ) {

		$new_headers[ $key ] = $header;

		if ( 'billing_company' === $key ) {
			$new_headers['vat_number'] = 'vat_number';
		}
	}

	return $new_headers;
}
add_filter( 'wc_customer_order_csv_export_order_headers', 'sv_wc_csv_export_modify_column_headers_vat_number', 10, 2 );


/**
 * Add `vat_number` column data
 *
 * this function searches for a VAT number order meta key used by the more
 * popular Tax/VAT plugins
 *
 * @param array $order_data the original column data
 * @param \WC_Order $order the order object
 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator the generator instance
 * @return array the updated column data
 */
function sv_wc_csv_export_modify_row_data_vat_number( $order_data, $order, $csv_generator ) {

	$vat_number     = '';
	$new_order_data = array();

	// compat for pre / post WC 3.0
	$order_id = is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id;

	// find VAT number if one exists for the order
	$vat_number_meta_keys = array(
		'_vat_number',               // EU VAT number
		'VAT Number',                // Legacy EU VAT number
		'vat_number',                // Taxamo
	);

	foreach ( $vat_number_meta_keys as $meta_key ) {

		if ( metadata_exists( 'post', $order_id, $meta_key ) ) {
			$vat_number = get_post_meta( $order_id, $meta_key, true );
			break;
		}
	}

	$custom_data = array(
		'vat_number' => $vat_number,
	);

	if ( sv_wc_csv_export_is_one_row( $csv_generator ) ) {

		foreach ( $order_data as $data ) {
			$new_order_data[] = array_merge( (array) $data, $custom_data );
		}

	} else {
		$new_order_data = array_merge( $order_data, $custom_data );
	}

	return $new_order_data;
}
add_filter( 'wc_customer_order_csv_export_order_row', 'sv_wc_csv_export_modify_row_data_vat_number', 10, 3 );


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
