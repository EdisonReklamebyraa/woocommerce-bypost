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
  // Denne blir kjørt når ordrestatusen blir endret.
  $order = new WC_Order($order_id);
}

/**
 * Dette blir kjørt når en checkout er fullført.
 * Dette gjør en POST mot min.bypost-API'et, med
 * data som trengs for å bestille en pakkesending fra Bring.
 */
add_action('woocommerce_checkout_order_processed', 'create_order_in_bypost' );

function create_order_in_bypost( $order_id ) {
  $order = new WC_Order($order_id);

  // Finn bypostnøkkelen
  $wc_methods = WC()->shipping;
  $bypost_key = null;
  foreach ($wc_methods as $method) {
    if (is_array($method)) {
      foreach ($method as $item) {
        if (is_object($item) && $item->get_option('bypost_key')) {
          $bypost_key = $item->get_option('bypost_key');
          $phone = $item->get_option('kundetelefon');
        }
      }
    }
  }

  $shipping_method = reset($order->get_shipping_methods())->get_meta('bring_id');

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
  $url = "https://minbypost.ploi.r8.is/api/createParcelOrder";
  $bearer = $bypost_key;

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

  $order->update_meta_data('packing_slip', json_decode($response)->label);
  $order->update_meta_data('tracking_url', json_decode($response)->tracking);
  $order->save();
}