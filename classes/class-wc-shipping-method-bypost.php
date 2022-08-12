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
          $this->method_title       = __('Bypost');  // Title shown in admin
          $this->method_description = __('Frakt med Bypost'); // Description shown in admin
          $this->supports           = array(
            'shipping-zones',
            'settings',
            'instance-settings',
          );
          $this->init();

          $this->title              = "Bypost"; // This can be added as an setting but for this example its forced.
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

            /**
             * Andre felter
             */
            'title'                         => [
              'title'    => 'Fraktnavn',
              'type'     => 'text',
              'desc_tip' => 'Dette styrer hvilken tittel sluttbruker ser i kassen.',
              'default'  => __('Bypost frakt', 'bypost'),
            ],
            'fraktpris'                  => [
              'title'             => 'Fraktpris, til hentested',
              'type'              => 'number',
              'desc_tip'          => 'Fastpris på frakt',
              'css'               => 'width: 8em;',
              'default'           => '',
            ],
            'heltfrem'            => [
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
              'id' => 'fastpris',
              'label' => "Bypost: Til hentested",
              'cost' => $this->get_option('fraktpris'),
            );
            $this->add_rate($flat_rate);
          }

          if ($this->get_option('heltfrem')) {
            $door_rate = array(
              'id' => 'heltfrem',
              'label' => "Bypost: På døren",
              'cost' => $this->get_option('heltfrem'),
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