<?php
/**
 * Register our custom shipping method
 */

/**
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

  function bypost_shipping_method_init()
  {
    if (!class_exists('WC_bypost_Shipping_Method')) {
      class WC_bypost_Shipping_Method extends WC_Shipping_Method
      {
        /**
         * Constructor for bypost shipping class. Noen påkrevde felter, og noen opsjoner.
         *
         * @access public
         * @return void
         */
        public function __construct($instance_id = 0)
        {
          $this->id                 = 'bypost_shipping_method'; // Id for bypost shipping method. Should be uunique.
          $this->instance_id        = absint($instance_id);
          $this->method_title       = __('Bypost', 'bypost-woo');  // Title shown in admin
          $this->method_description = __('Frakt med Bypost', 'bypost-woo'); // Description shown in admin
          $this->supports           = array(
            'shipping-zones',
            'settings',
          );
          $this->init();
          $this->title              = __("Bypost", 'bypost-woo'); // This can be added as an setting but for this example its forced.
        }

        /**
         * Init bypost settings
         *
         * @access public
         * @return void
         */
        function init()
        {
          // Load the settings API
          $this->init_form_fields(); // This is part of the settings API. Override the method to add bypost own settings
          $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

          // Save settings in admin
          add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        public function get_key() {
          return $this->get_option('bypost_key');
        }

        /**
         * These are all the form fields shown in Shipping -> Bypost
         * @return void
         */
        function init_form_fields() {
          // Add settings here
          $this->form_fields = [
            'bypost_key' => [
              "title"             => "Bypost API-nøkkel",
              "type"              => "text",
              'description'       => 'Denne opprettes på <a href="https://min.bypost.no/">min.bypost.no</a>'
            ],
            'kundetelefon' => [
              "title"             => "Telefonnummer",
              "type"              => "text",
              "description"       => __('Dette trengs for å bestille frakt med Bring', 'bypost-woo')
            ],
            'weight_based_shipping' => [
              'title'             => 'Vektbasert frakt',
              'type'              => 'checkbox',
              'id'                => 'weight_based_shipping',
              'label'             => 'Slå på vektbasert frakt'
            ],
            'pickup_point_label' => [
              'title'             => __('Navn i kassen', 'bypost-woo'),
              'type'              => 'text',
              'description'       => __('Her kan du overstyre hva fraktklassen heter i kassen.', 'bypost-woo'),
              'default'           => __('Bypost: Hentested', 'bypost-woo'),
              'class'             => 'group-start group-heading--pickup',
            ],
            'pickup_point' => [
              'title'             => __('Fastpris', 'bypost-woo'),
              'id'                => 'price_pickup',
              'type'              => 'text',
              'description'       => __('Aktiver levering til hentested ved å fylle inn pris.', 'bypost-woo'),
            ],
            'pickup_point_s' => [
              'title'             => 'Pris per størrelse',
              'description'       => "0-3kg (25x35x25cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class'
            ],
            'pickup_point_m' => [
              'description'       => "3-10kg (38x28x30cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'pickup_point_l' => [
              'description'       => "10-20kg (45x30x30cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'pickup_point_xl' => [
              'description'       => "20-35kg (60x40x40cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'pickup_point_fallback' => [
              'title'             => 'Bruk denne fraktklassen dersom ordren ikke har vekt',
              'type'              => 'select',
              'options' => [
                'door_delivery_s'   => '0-3kg',
                'door_delivery_m'   => '3-10kg',
                'door_delivery_l'   => '10-20kg',
                'door_delivery_xl'  => '20-35kg',
              ],
            ],
            'pickup_point_free_shipping' => [
              'title'             => 'Gratis frakt over gitt beløp',
              'description'       => 'Fyll inn beløp for å aktivere gratis frakt',
              'type'              => 'text',
            ],

            'door_delivery_label' => [
              'title'             => __('Navn', 'bypost-woo'),
              'description'       => __('Her kan du overstyre hva fraktklassen heter i kassen.', 'bypost-woo'),
              'type'              => 'text',
              'default'           => 'Bypost: Hjemlevering',
              'class'             => 'group-start group-heading--door'
            ],
            'door_delivery' => [
              'title'             => 'Fastpris',
              'type'              => 'text',
              'description'       => 'Aktiver hjemlevering ved å fylle inn pris.',
              'id'                => "price_door",
            ],
            'door_delivery_s' => [
              'title'             => 'Priser pr vektklasse',
              'description'       => "0-3kg (25x35x25cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'door_delivery_m' => [
              'description'       => "3-10kg (38x28x30cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'door_delivery_l' => [
              'description'       => "10-20kg (45x30x30cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'door_delivery_xl' => [
              'description'       => "20-35kg (60x40x40cm)",
              'type'              => 'text',
              'css'               => 'width: 10ch',
              'class'             => 'weight-class',
            ],
            'door_delivery_fallback' => [
              'title'             => 'Bruk denne fraktklassen dersom ordren ikke har vekt',
              'type'              => 'select',
              'id'                => 'pickup_point_fallback',
              'options' => [
                'door_delivery_s'   => '0-3kg',
                'door_delivery_m'   => '3-10kg',
                'door_delivery_l'   => '10-20kg',
                'door_delivery_xl'  => '20-35kg',
              ],
            ],
            'door_delivery_free_shipping' => [
              'title'             => 'Gratis frakt over gitt beløp',
              'description'       => 'Fyll inn beløp for å aktivere gratis frakt',
              'type'              => 'text',
            ],

            'mailbox_delivery_label' => [
              'title'             => "Navn",
              'description'       => __('Her kan du velge hva fraktklassen skal hete i kassen.', 'bypost-woo'),
              'type'              => 'text',
              'default'           => 'Bypost: Postkasse',
              'class'             => 'group-start group-heading--mailbox'
            ],
            'mailbox_delivery' => [
              'title'             => 'Fastpris',
              'type'              => 'text',
              'description'       => 'Aktiver postkasselevering ved å fylle inn pris.',
              'css'               => 'width: 10ch',
            ],
            'mailbox_free_shipping' => [
              'title'             => 'Gratis frakt over gitt beløp',
              'description'       => 'Fyll inn beløp for å aktivere gratis frakt',
              'type'              => 'text',
            ]
          ];
        }

        /**
         * These are the products displayed on the checkout page
         *
         * @access public
         * @param array $package
         * @return void
         */
        public function calculate_shipping($package = array())
        {
          define('MAX_ALLOWED_KG', 35);
          define('MAX_ALLOWED_LETTER_KG', 2);
          global $woocommerce;
          $weight = $woocommerce->cart->cart_contents_weight;
          if ($weight >= MAX_ALLOWED_KG) return;

          $free_shipping_rates = $this->get_free_shipping_rates($package['cart_subtotal']);

          /**
           * Pickup rate
           */
          $pickup_label = $this->get_option('pickup_point_label') !== ""
            ? $this->get_option('pickup_point_label')
            : "Bypost (til hentested)";
          if (in_array('pickup_point', $free_shipping_rates)) {
            $pickup_label = __('Gratis frakt: ') . ($this->get_option('pickup_point_label')
            ? $this->get_option('pickup_point_label')
            : 'Bypost (til hentested)');
          }

          $pickup_cost = $this->get_cost(in_array('pickup_point', $free_shipping_rates), $weight, 'pickup_point');

          $pickup_rate = array(
            'id' => 'pickup_point',
            'label' => $pickup_label,
            'cost' => $pickup_cost,
            'meta_data' => [
              'bring_product_id' => 5800,
              'weight' => $weight,
              'weight_unit' => 'kg',
              'pickup_point_fallback' => $this->get_option('pickup_point_fallback'),
            ],
          );
          $this->add_rate($pickup_rate);

          /**
           * Door delivery rate
           */
          $door_label = $this->get_option('door_delivery_label') !== ""
            ? $this->get_option('door_delivery_label')
            : "Bypost (på døra)";
          if (in_array('door_delivery', $free_shipping_rates)) {
            $door_label = __('Gratis frakt: ') . ($this->get_option('door_delivery_label')
              ? $this->get_option('door_delivery_label')
              : 'Bypost (på døra)');
          }

          $door_cost = $this->get_cost(in_array('door_delivery', $free_shipping_rates), $weight, 'door_delivery');

          $door_rate = array(
            'id' => "door_delivery",
            'label' => $door_label,
            'cost' => $door_cost,
            'meta_data' => [
              'bring_product_id' => 5600,
              'weight' => $weight,
              'weight_unit' => 'kg',
              'door_delivery_fallback' => $this->get_option('door_delivery_fallback'),
            ]
          );
          $this->add_rate($door_rate);

          /**
           * Mailbox delivery rate
           */
          $mailbox_label = $this->get_option('mailbox_label') !== ""
            ? $this->get_option('mailbox_label')
            : "Bypost (til postkasse)";
          if (in_array('mailbox', $free_shipping_rates)) {
            $mailbox_label = __('Gratis frakt: ') . ($this->get_option('mailbox_label')
              ? $this->get_option('mailbox_label')
              : 'Bypost (til postkasse)');
          }

          $mailbox_cost = in_array('mailbox', $free_shipping_rates) ? 0 : $this->get_option('mailbox_delivery');

          $mailbox_rate = array(
            'id' => "mailbox",
            'label' => $mailbox_label,
            'cost' => $mailbox_cost,
              'bring_product_id' => 3584,
              'weight' => null,
              'weight_unit' => null
          );

          if ($weight < MAX_ALLOWED_LETTER_KG) {
            $this->add_rate($mailbox_rate);
          }

        }

        /**
         * Get an id (string) of shipping products that qualifies for free shipping
         */
        private function get_free_shipping_rates($total_price) {
          $free_shipping = [];
          if ($this->get_option('door_delivery_free_shipping')
            && $total_price >= $this->get_option('door_delivery_free_shipping')) {
              $free_shipping[] = 'door_delivery';
          }
          if ($this->get_option('pickup_point_free_shipping')
            && $total_price >= $this->get_option('pickup_point_free_shipping')) {
              $free_shipping[] = 'pickup_point';
          }
          if ($this->get_option('mailbox_free_shipping')
            && $total_price >= $this->get_option('mailbox_free_shipping')) {
              $free_shipping[] = 'mailbox';
          }
          return $free_shipping;
        }

        /**
         * Get cost of shipping product by returning the value from settings.
         * Checks if the shipping product qualifies for free shipping,
         * and calculates price based on weight, if applicable.
         */
        private function get_cost($is_free, $weight, $handle = "") {
          if ($is_free) return 0;
          if ($this->get_option('weight_based_shipping')) {

            if ($weight == 0) {
              // The inner option returns a field-id, and then we get that value
              return $this->get_option($this->get_option($handle . '_fallback'));
            }
            if ($weight > 0 && $this->has_weight_products($handle)) {
              // We're getting the field by handle, and adding a field suffix for each size
              if ($weight < 3) return $this->get_option($handle . '_s');
              if ($weight < 10) return $this->get_option($handle . '_m');
              if ($weight < 20) return $this->get_option($handle . '_l');
              if ($weight < 35) return $this->get_option($handle . '_xl');
            }
          }
          if ($this->get_option($handle)) return $this->get_option($handle);
        }

        /**
         * This checks if the handle has any prices set for all weight classes
         */
        private function has_weight_products($handle) {
          return $this->get_option($handle . '_s')
            || $this->get_option($handle . '_m')
            || $this->get_option($handle) . '_l'
            || $this->get_option($handle) . '_xl';
        }

      }
    }
  }

  add_action('woocommerce_shipping_init', 'bypost_shipping_method_init');

  // Registrering av selve shipping-metoden
  function add_bypost_shipping_method($methods)
  {
    $methods['bypost_shipping_method'] = 'WC_bypost_Shipping_Method';
    return $methods;
  }

  add_filter('woocommerce_shipping_methods', 'add_bypost_shipping_method');
}