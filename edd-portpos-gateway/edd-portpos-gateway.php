<?php
/**
 * Plugin Name:       EDD PortPos Gateway
 * Plugin URI:        https://github.com/tanvirisraq/wp-edd-portpos-gateway
 * Description:       Integrates PortPos payment gateway with Easy Digital Downloads.
 * Version:           1.0.2
 * Author:            Tanvir Israq
 * Author URI:        https://www.github.com/TanvirIsraq
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       edd-portpos-gateway
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to:     6.9
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('EDD_PORTPOS_VERSION', '1.0.2');
define('EDD_PORTPOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EDD_PORTPOS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EDD_PORTPOS_ID', 'portpos');

/**
 * Initialize the plugin
 *
 * @return void
 */
function edd_portpos_init()
{
	if (!class_exists('Easy_Digital_Downloads')) {
		return;
	}

	// Include required files
	require_once EDD_PORTPOS_PLUGIN_DIR . 'includes/class-portpos-api.php';
	require_once EDD_PORTPOS_PLUGIN_DIR . 'includes/class-portpos-gateway.php';

	// Instantiate the gateway
	new EDD_PortPos_Gateway();
}
add_action('plugins_loaded', 'edd_portpos_init');

/**
 * Register the gateway
 *
 * @param array $gateways Registered gateways.
 * @return array
 */
function edd_portpos_register_gateway($gateways)
{
	$gateways[EDD_PORTPOS_ID] = array(
		'admin_label' => __('PortPos', 'edd-portpos-gateway'),
		'checkout_label' => __('PortPos', 'edd-portpos-gateway'),
		'supports' => array('buy_now'),
	);

	return $gateways;
}
add_filter('edd_payment_gateways', 'edd_portpos_register_gateway');
