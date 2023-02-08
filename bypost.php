<?php
/**
 * Bypost
 *
 * @package     Bypost
 * @author      Edge Branding
 * @copyright   2022 Edge Branding
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Bypost
 * Description: Plugin for frakt med Bypost
 * Version:     1.0.0
 * Author:      Edge Branding
 * Author URI:  https://edgebranding.no
 * Text Domain: bypost
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

 // Her settes pluginet opp - magien skjer i andre filer
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

define('BYPOST_ROOT', plugin_dir_url(__FILE__));

add_action('admin_enqueue_scripts', function() {
  wp_register_style( 'bypost_admin_css', BYPOST_ROOT . 'assets/css/bypost-admin.css', false, '1.0.0' );
  wp_enqueue_style( 'bypost_admin_css' );
  wp_register_script( 'bypost_admin_js', BYPOST_ROOT . 'assets/js/bypost-admin.js', false, '1.0.0' );
  wp_enqueue_script( 'bypost_admin_js' );
});

require_once __DIR__ . '/classes/class-bypost.php';
require_once __DIR__ . '/includes/bypost-api.php';
require_once __DIR__ . '/includes/order-meta-fields.php';

add_action('plugins_loaded', 'Bypost::init');
register_deactivation_hook(__FILE__, 'Bypost::plugin_deactivate');