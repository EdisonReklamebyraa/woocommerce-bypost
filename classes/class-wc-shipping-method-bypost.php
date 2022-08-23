<?php
/**
 * Her blir selve shipping-metoden registrert i klassen WC_bypost_Shipping_Method
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

          // Save settings in admin if you have any defined
          add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        public function get_key() {
          return $this->get_option('bypost_key');
        }

        /**
         * Her legger vi til alle settings-feltene som skal vises under Shipping -> Bypost
         * @return void
         */
        function init_form_fields() {
          // Add settings here
          $this->form_fields = [
            'bypost_key' => [
              "title" => "Bypost API-nøkkel",
              "type" => "text",
            ],
            'kundetelefon' => [
              "title" => "Telefonnummer",
              "type" => "text",
              "description" => __('Dette trengs for å bestille frakt med Bring', 'bypost-woo')
            ],
            'pickup_point_label' => [
              'title' => __('Fraktnavn, hentested', 'bypost-woo'),
              'type' => 'text',
              'description' => __('Her kan du velge hva fraktalternativet "hentested" skal hete i kassen.', 'bypost-woo'),
              'default'  => __('Bypost: Hentested', 'bypost-woo'),
            ],
            'pickup_point' => [
              'title'             => __('Fraktpris, hentested', 'bypost-woo'),
              'type'              => 'decimal',
              'description'       => __('Aktiver levering til hentested ved å fylle inn pris.', 'bypost-woo'),
              'css'               => 'width: 8em;',
              'default'           => '',
            ],
            'door_delivery_label' => [
              'title' => __('Fraktnavn, hjemlevering', 'bypost-woo'),
              'description' => __('Her kan du velge hva fraktalternativet "hjemlevering" skal hete i kassen.', 'bypost-woo'),
              'type' => 'text',
              'default' => 'Bypost: Hjemlevering'
            ],
            'door_delivery' => [
              'title'             => 'Fraktpris, hjemlevering',
              'type'              => 'decimal',
              'description'          => 'Aktiver hjemlevering ved å fylle inn pris.',
              'css'               => 'width: 8em;',
              'default'           => '',
            ],
            'free_shipping' => [
              'title'             => 'Gratis frakt over gitt beløp',
              'type'              => 'number',
              'description'          => 'Fyll inn beløp for å aktivere gratis frakt',
              'css'               => 'width: 8em;',
              'default'           => '',
            ],
          ];
        }

        /**
         * Her legger vi til de forskjellige fraktproduktene
         *
         * @access public
         * @param array $package
         * @return void
         */
        public function calculate_shipping($package = array())
        {
          if ($this->get_option('free_shipping') && $package['cart_subtotal'] >= $this->get_option('free_shipping')) {
            if ($this->get_option('door_delivery')) {
              $door_rate = array(
                'id' => 'hey',
                'label' => $this->get_option('door_delivery_label') ? __('Gratis frakt: ') . $this->get_option('door_delivery_label') : "",
                'cost' => 0,
                'meta_data' => ['bring_id' => 5600],
              );
              $this->add_rate($door_rate);
            }
            if ($this->get_option('pickup_point')) {
              $pickup_rate = array(
                'id' => 'arnold',
                'label' => $this->get_option('pickup_point_label') ? __('Gratis frakt: ') . $this->get_option('pickup_point_label') : "",
                'cost' => 0,
                'meta_data' => ['bring_id' => 5800],
              );
              $this->add_rate($pickup_rate);
            }
            return;
          }
            if ($this->get_option('pickup_point')) {
              $pickup_rate = array(
                'id' => 'arnold',
                'label' => $this->get_option('pickup_point_label') ?? "",
                'cost' => $this->get_option('pickup_point'),
                'meta_data' => ['bring_id' => 5800],
              );
              $this->add_rate($pickup_rate);
            }

            if ($this->get_option('door_delivery')) {
              $door_rate = array(
                'id' => 'hey',
                'label' => $this->get_option('door_delivery_label'),
                'cost' => $this->get_option('door_delivery'),
                'meta_data' => ['bring_id' => 5600],
              );
              $this->add_rate($door_rate);
            }
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