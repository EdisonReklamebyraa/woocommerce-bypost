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

require_once __DIR__ . '/classes/class-bypost.php';
require_once __DIR__ . '/includes/bypost-api.php';
require_once __DIR__ . '/includes/woo-admin.php';

add_action('plugins_loaded', 'Bypost::init');
register_deactivation_hook(__FILE__, 'Bypost::plugin_deactivate');