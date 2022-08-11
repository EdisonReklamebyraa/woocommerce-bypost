<?php
/* Oppsett av plugin */
class Bypost {

  public static function init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
      return;
    }

    require_once __DIR__ . '/class-wc-shipping-method-bypost.php';
  }

  /**
   * Add action to call when the plugin is deactivated
   */
  public static function plugin_deactivate() {
    do_action( 'bypost_fraktguiden_plugin_deactivate' );
	}

}