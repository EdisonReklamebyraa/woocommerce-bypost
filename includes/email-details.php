<?php
/**
 * Ikke påbegynt - her kan vi legge til sporingsnummer i eposten som sendes til kunde
 */
add_action('woocommerce_email_order_details', 'add_tracking_number', 25, 4);

function add_tracking_number() {
  // Get order, get_post_meta('tracking_url');
}