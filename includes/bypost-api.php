<?php
/*
 * For å gjøre et testkall:
 * Gå til Woocommerce - Settings - Payments - Skru på Check payments
 * Gå igjennom vanlig kjøpsløp og bruk check som payment.
 * Gå til Orders og sett ordren til fullført.
*/

add_action('woocommerce_order_status_changed', 'bypost_status_change', 10, 3);

/**
 * @param order_id
 * @param from er hvilken status ordren endres fra
 * @param to er hvilken status ordren har blitt endret til
 */
function bypost_status_change($order_id, $from, $to) {
  if ($to === "completed") {
    error_log('Order is set to complete. Creating order with API.');
    create_order_in_bypost($order_id);
  }
}


// Finn ut hvordan vi får weight, bring_id
// og deretter lag ferdig metoden:

function get_product_by($bring_id, $weight) {
  // 0 = s
  // 1 = m
  // 2 = l
  // 3 = xl
  // 4 = postkasse
  error_log("Bring ID: " . $bring_id);
  error_log("Weight: " . $weight);
}

/**
 * Dette gjør en POST mot min.bypost-API'et, med
 * data som trengs for å bestille en pakkesending fra Bring.
 */
function create_order_in_bypost( $order_id ) {
  $weight = get_total_weight($order_id);
  $order = new WC_Order($order_id);

  // Finn bypostnøkkelen
  $bypost_key = null;

  $wc_methods = WC()->shipping->get_shipping_methods();
  foreach ($wc_methods as $method) {
    if ($method->id === 'bypost_shipping_method') {
      $bypost_key = $method->get_option('bypost_key');
      $phone = $method->get_option('kundetelefon');
      $fallback = $method->get_option('pickup_point_fallback');
    }
  }
  $shipping_method = reset($order->get_shipping_methods())->get_meta('bring_product_id');
  error_log(print_r($shipping_method, true));


  $data = [
    "customer_name"        => get_option('woocommerce_email_from_name') ?? '',
    "customer_address_1"   => get_option('woocommerce_store_address') ?? '',
    "customer_address_2"   => get_option('woocommerce_store_address_2')  ?? '',
    "customer_postcode"    => get_option('woocommerce_store_postcode') ?? '',
    "customer_city"        => get_option('woocommerce_store_city') ?? '',
    "customer_email"       => get_option('woocommerce_email_from_address') ?? '',
    // "customer_phone"       => $phone,
    "recipient_name"       => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
    "recipient_address_1"  => $order->get_shipping_address_1() ?? '',
    "recipient_address_2"  => $order->get_shipping_address_2() ?? '',
    "recipient_postcode"   => $order->get_shipping_postcode() ?? '',
    "recipient_city"       => $order->get_shipping_city() ?? '',
    "recipient_email"      => $order->get_billing_email() ?? '',
    "recipient_phone"      => $order->get_billing_phone() ?? '',
    "bring_id"             => "",
    "bypost_product_id"    => ""
  ];
  $payload = json_encode(['order' => $data]);

  // error_log('Data prepared for min.bypost: ' . print_r(json_decode($payload), true));
  // $url = "https://min.bypost.no/api/createparcelorder";
  // $url = "https://minbypost.ploi.r8.is/api/createparcelorder";
  // error_log('Using endpoint: ' . $url);
  $bearer = $bypost_key;
  // error_log('Current API-Key: ' . $bearer);

  // $curl = curl_init();
  // curl_setopt_array($curl, array(
  //   CURLOPT_URL => $url,
  //   CURLOPT_RETURNTRANSFER => true,
  //   CURLOPT_ENCODING => '',
  //   CURLOPT_MAXREDIRS => 10,
  //   CURLOPT_TIMEOUT => 0,
  //   CURLOPT_FOLLOWLOCATION => true,
  //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  //   CURLOPT_CUSTOMREQUEST => 'POST',
  //   CURLOPT_POSTFIELDS => $payload,
  //   CURLOPT_HTTPHEADER => array(
  //     'Authorization: Bearer ' . $bearer,
  //     'Content-Type: application/json',
  //   ),
  // ));

  // $response = curl_exec($curl);
  // curl_close($curl);
  // if ($response) {
  //   error_log('Response from API: ' . print_r($response, true));
  //   $order->update_meta_data('packing_slip', json_decode($response)->label);
  //   $order->update_meta_data('tracking_url', json_decode($response)->tracking);
  //   $order->save();
  //   error_log('Added packing slip and tracking url to order metadata');
  // } else {
  //   error_log('Error: ' . print_r($response, true));
  // }
}

/**
 * Validate API-key
 */
// add_action('added_option', 'validate_bypost_key', 10, 2);
add_action('updated_option', 'validate_bypost_key', 10, 3);

function validate_bypost_key($option, $old_value, $value) {
  // error_log($option);
}

/**
 * Get weight from order (since cart is not always available)
 */
function get_total_weight($order_id) {
  $order = wc_get_order($order_id);
  $total_weight = 0;
  foreach( $order->get_items() as $item_id => $product_item ){
    $quantity = $product_item->get_quantity(); // get quantity
    $product = $product_item->get_product(); // get the WC_Product object
    $product_weight = $product->get_weight(); // get the product weight
    // Add the line item weight to the total weight calculation
    $total_weight += floatval( (int)$product_weight * $quantity );
  }
  return $total_weight;
}

add_action('woocommerce_checkout_create_order', 'save_the_most_day_as_order_meta_data', 20, 2);
function save_the_most_day_as_order_meta_data( $order, $data ) {
    if( $most_day = WC()->session->get( 'most_day' ) ){
        $order->update_meta_data( '_most_day', $most_day );
    }
}