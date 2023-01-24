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

function get_product_size($bring_id, $weight, $order) {
  define('MAILBOX_DELIVERY', 3584);
  define('DOOR_DELIVERY', 5600);
  define('PICKUP_POINT', 5800);

  $bring_id = (int)$bring_id;
  $weight = (int)$weight;

  if ($bring_id === MAILBOX_DELIVERY) return 4;

  if ($weight === 0) {
    $fallback = "";
    if ($bring_id === DOOR_DELIVERY) {
      $fallback = reset($order->get_shipping_methods())->get_meta('door_delivery_fallback');
    }
    if ($bring_id === PICKUP_POINT) {
      $fallback = reset($order->get_shipping_methods())->get_meta('pickup_point_fallback');
    }
    $size_suffix = substr($fallback,strrpos($fallback, '_') + 1);
    if ($size_suffix ===  "s") return 0;
    if ($size_suffix ===  "m") return 1;
    if ($size_suffix ===  "l") return 2;
    if ($size_suffix === "xl") return 3;
  }

  if ($weight > 0 && ($bring_id === DOOR_DELIVERY || $bring_id === PICKUP_POINT)) {
    if ($weight <  3) return 0; // S
    if ($weight < 10) return 1; // M
    if ($weight < 20) return 2; // L
    if ($weight < 35) return 3; // XL
  }
  return "Unknown Size";
}

/**
 * Dette gjør en POST mot min.bypost-API'et, med
 * data som trengs for å bestille en pakkesending fra Bring.
 */
function create_order_in_bypost( $order_id ) {
  $order = new WC_Order($order_id);

  $bypost_key = reset($order->get_shipping_methods())->get_meta('bypost_key');
  $phone = reset($order->get_shipping_methods())->get_meta('kundetelefon');
  $bring_id = reset($order->get_shipping_methods())->get_meta('bring_product_id');
  $weight = reset($order->get_shipping_methods())->get_meta('weight');

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
    "bring_id"             => $bring_id,
    "weight"               => $weight,
    "bypost_size"          => get_product_size($bring_id, $weight, $order),
  ];
  $payload = json_encode(['order' => $data]);
  error_log('Data prepared for min.bypost: ' . print_r(json_decode($payload), true));
  $url = "https://min.bypost.no/api/createparcelorder";
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