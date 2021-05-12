<?php // only copy this line if needed

/**
 * Adds Product Add-ons separately in a new XML tag for each line item
 *
 * Format:
 * <OrderLineItems>
 *   <OrderLineItem>
 *     <AddOn>
 *        <Name>
 *        <Value>
 *        <Price>
 *     </AddOn>
 *   </OrderLineItem>
 * </OrderLineItems>
 */


/**
 * Adds Product Addons to the Line Item XML if available
 *
 * REQUIRES v2.0+ of XML Export; use `wc_customer_order_xml_export_suite_order_export_line_item_format` filter for earlier versions
 *
 * @param array $item_data line item XML data to write
 * @param \WC_Order $order
 * @param array|\WC_Order_Item $item the line item order data or object (WC 3.0+)
 * @return array - modified line item XML data to write
 */
function sv_wc_xml_export_line_item_addons( $item_data, $order, $item ) {

	$product = is_callable( array( $item, 'get_product' ) ) ? $item->get_product() : $order->get_product_from_item( $item );

	// bail if this line item isn't a product
	if ( ! ( $product && $product->exists() ) ) {
		return $item_data;
	}

	$addons = [];

	// get the possible add-ons for this line item to check if they're in the order
	if ( is_callable( 'WC_Product_Addons_Helper::get_product_addons' ) ) {
		$addons = WC_Product_Addons_Helper::get_product_addons( $product->get_id() );
	} elseif( is_callable( 'get_product_addons' ) ) {
		$addons = get_product_addons( $product->get_id() );
	}

	$product_addons = sv_wc_xml_export_get_line_item_addons( $item, $addons );

	if ( ! empty( $product_addons ) ) {
		$item_data['AddOn'] = $product_addons;
	}

	return $item_data;
}
add_filter( 'wc_customer_order_export_xml_order_line_item', 'sv_wc_xml_export_line_item_addons', 10, 3 );


/**
 * Gets Product Add-ons for a line item
 *
 * @param array|\WC_Order_Item $item line item data
 * @param array $addons possible addons for this line item
 * @return array - product addons ordered for the line item
 */
function sv_wc_xml_export_get_line_item_addons( $item, $addons ) {

	$product_addons = array();

	foreach ( $addons as $addon ) {

		// sanity check
		if ( ! is_array( $addon ) || empty ( $addon ) ) {
			return $product_addons;
		}

		if ( $item instanceof \WC_Order_Item ) {
			$metadata = wp_list_pluck( $item->get_formatted_meta_data(), 'value', 'key' );
		} else {
			$metadata = $item;
		}

		// loop line item data
		foreach ( $metadata as $key => $value ) {

			// check if the beginning of the meta key matches the add-on name
			if ( $addon['name'] == substr( $key, 0, strlen( $addon['name'] ) ) ) {

				// this way, the length will be 0 without a trailing paren to get a "false" $price
				$price = substr( $key, strrpos( $key, '(' ), strrpos( $key, ')' ) );

				if ( $price ) {
					preg_match( '#\((.*?)\)#', $price, $match );
				}

				$product_addons[] = array(
					'Name'  => $addon['name'],
					'Value' => $value,
					'Price' => $price ? html_entity_decode( $match[1] ) : ' - ',
				);
			}
		}
	}

	return $product_addons;
}
