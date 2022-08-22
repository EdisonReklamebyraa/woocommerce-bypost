<?php
/*
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

/**
 * Dette gjør en POST mot min.bypost-API'et, med
 * data som trengs for å bestille en pakkesending fra Bring.
 */
function create_order_in_bypost( $order_id ) {
  $order = new WC_Order($order_id);

  // Finn bypostnøkkelen
  $wc_methods = WC()->shipping->get_shipping_methods();
  $bypost_key = null;
  foreach ($wc_methods as $method) {
    if ($method->id === 'bypost_shipping_method') {
      $bypost_key = $method->get_option('bypost_key');
      $phone = $method->get_option('kundetelefon');
    }
  }

  $shipping_method = reset($order->get_shipping_methods())->get_meta('bring_id');
  error_log('Current shipping method: ' . $shipping_method);
  $data = [
    "customer_name"        => get_option('woocommerce_email_from_name') ?? '',
    "customer_address_1"   => get_option('woocommerce_store_address') ?? '',
    "customer_address_2"   => get_option('woocommerce_store_address_2')  ?? '',
    "customer_postcode"    => get_option('woocommerce_store_postcode') ?? '',
    "customer_city"        => get_option('woocommerce_store_city') ?? '',
    "customer_email"       => get_option('woocommerce_email_from_address') ?? '',
    "customer_phone"       => $phone,
    "recipient_name"       => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
    "recipient_address_1"  => $order->get_shipping_address_1() ?? '',
    "recipient_address_2"  => $order->get_shipping_address_2() ?? '',
    "recipient_postcode"   => $order->get_shipping_postcode() ?? '',
    "recipient_city"       => $order->get_shipping_city() ?? '',
    "recipient_email"      => $order->get_billing_email() ?? '',
    "recipient_phone"      => $order->get_billing_phone() ?? '',
    "delivery_method"      => $shipping_method,
  ];
  $payload = json_encode(['order' => $data]);
  error_log('Data prepared for min.bypost: ' . print_r(json_decode($payload), true));
  $url = "https://min.bypost.no/api/createParcelOrder";
  error_log('Using endpoint: ' . $url);
  $bearer = $bypost_key;
  error_log('Current API-Key: ' . $bearer);

  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => array(
      'Authorization: Bearer ' . $bearer,
      'Content-Type: application/json',
    ),
  ));

  $response = curl_exec($curl);
  curl_close($curl);
  if ($response) {
    error_log('Response from API: ' . print_r($response, true));
    $order->update_meta_data('packing_slip', json_decode($response)->label);
    $order->update_meta_data('tracking_url', json_decode($response)->tracking);
    $order->save();
    error_log('Added packing slip and tracking url to order metadata');
  } else {
    error_log('Error: ' . print_r($response, true));
  }
}

/**
 * Validate API-key
 */
// add_action('added_option', 'validate_bypost_key', 10, 2);
add_action('updated_option', 'validate_bypost_key', 10, 3);

function validate_bypost_key($option, $old_value, $value) {
  // error_log($option);
}

