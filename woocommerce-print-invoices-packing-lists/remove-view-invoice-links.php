<?php // only copy this line if needed

/**
 * Remove the "View Invoice" links from emails and the My Account section of the site.
 */

add_filter( 'wc_pip_customers_can_view_invoices', '__return_false' );
