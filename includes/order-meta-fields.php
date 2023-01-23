<?php
/**
 * Her er felter til ordre-siden i WP-Admin
 */

function bypost_add_custom_box() {
  $screens = ['shop_order'];
  foreach ($screens as $screen) {
    add_meta_box(
      'bypost_packing_slip', // Unique ID
      'Bypost', // Box title
      'bypost_html', // Content callback, must be of type callable
      $screen // Post type
    );
  }
}
add_action('add_meta_boxes', 'bypost_add_custom_box');

function bypost_html() {
  $order = wc_get_order(get_the_ID());
  $packageSlip = $order->get_meta('packing_slip');
  $tracking = $order->get_meta('tracking_url');
  echo '<div style="display: grid; grid-template-columns: 1fr 1fr; column-gap: 16px;">';
  if ($packageSlip) {
    echo '<a href="' . $packageSlip . '" target="_blank" class="button" style="text-align: center;">Pakkelapp</a>';
  } else {
    echo '<button onclick="return false;" class="button disabled" style="text-align: center;">Pakkelapp</button>';
  }
  if ($tracking) {
    echo '<a href="' . "https://min.bypost.no/sporing/" . $tracking . '" target="_blank" class="button" style="text-align: center;">Sporing</a>';
  } else {
    echo '<button onclick="return false;" style="text-align: center;" class="button disabled">Sporing</button>';
  }
  echo "</div>";
}