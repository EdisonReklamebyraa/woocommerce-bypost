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
         * Construcotr for bypost shipping class. Noen påkrevde felter, og noen opsjoner.
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
            'instance-settings',
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
          global $woocommerce;
          // Add settings here
          $this->form_fields = [
            'enabled' => [
              'title' => "Slå på",
              'type' => 'checkbox',
              'label' => 'Slå på Bypost fraktalternativer',
              'default' => 'no'
            ],
            'bypost_key' => [
              "title" => "Bypost API-nøkkel",
              "type" => "text",
            ],
            'kundetelefon' => [
              "title" => "Telefonnummer",
              "type" => "text",
            ],
            'hentested_label' => [
              'title' => 'Fraktnavn, hentested',
              'type' => 'text',
              'description' => __('Her kan du velge hva produktet "til hentested" skal hete i kassen.', 'bypost-woo'),
              'default'  => __('Bypost: Til hentested', 'bypost'),
            ],
            'fraktpris' => [
              'title'             => __('Fraktpris, til hentested', 'bypost-woo'),
              'type'              => 'number',
              'description'       => __('Fastpris på frakt', 'bypost-woo'),
              'css'               => 'width: 8em;',
              'default'           => '',
            ],
            'heltfrem_label' => [
              'title' => 'Fraktnavn, til døren',
              'description' => 'Her kan du velge hva produktet "til døren" skal hete i kassen.',
              'type' => 'text',
              'default' => 'Bypost: På døra'
            ],
            'heltfrem' => [
              'title'             => 'Fraktpris, til døren',
              'type'              => 'number',
              'desc_tip'          => 'Fastpris på frakt. Brukes bare hvis den er fyllt inn',
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
          if ($this->get_option('fraktpris')) {
            $flat_rate = array(
              'id' => 'arnold',
              'label' => $this->get_option('hentested_label') ?? "",
              'cost' => $this->get_option('fraktpris'),
              'meta_data' => ['bring_id' => 5800],
            );
            $this->add_rate($flat_rate);
          }

          if ($this->get_option('heltfrem')) {
            $door_rate = array(
              'id' => 'hey',
              'label' => $this->get_option('heltfrem_label'),
              'cost' => $this->get_option('heltfrem'),
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