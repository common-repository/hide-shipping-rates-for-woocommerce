<?php

/**
 * Plugin Name: Hide Shipping Rates for WooCommerce
 * Description: Easily hide WooCommerce shipping rates based on cart total, weight, quantity, product class, address, and user roles to enhance checkout flexibility.
 * Version: 1.0.2
 * Author: Repon Hossain
 * Author URI: https://workwithrepon.com
 * Text Domain: hide-shipping-rates-for-woocommerce
 * 
 * Requires Plugins: woocommerce
 * Requires at least: 6.2.0
 * Requires PHP: 7.4.3
 * Tested up to: 6.6.2
 * 
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
	exit;
}

define('HIDE_SHIPPING_RATES_FILE', __FILE__);
define('HIDE_SHIPPING_RATES_VERSION', '1.0.2');
define('HIDE_SHIPPING_RATES_BASENAME', plugin_basename(__FILE__));
define('HIDE_SHIPPING_RATES_URI', trailingslashit(plugins_url('/', __FILE__)));
define('HIDE_SHIPPING_RATES_PATH', trailingslashit(plugin_dir_path(__FILE__)));
define('HIDE_SHIPPING_RATES_PHP_MIN', '7.4.3');

define('HIDE_SHIPPING_RATES_API_URI', 'https://codiepress.com');
define('HIDE_SHIPPING_RATES_PLUGIN_ID', 925);

/**
 * Check PHP version. Show notice if version of PHP less than our 7.4.3 
 * 
 * @since 1.0.0
 * @return void
 */
function hide_shipping_rates_php_missing_notice() {
	$notice = sprintf(
		/* translators: 1 for plugin name, 2 for PHP, 3 for PHP version */
		esc_html__('%1$s need %2$s version %3$s or greater.', 'hide-shipping-rates-for-woocommerce'),
		'<strong>Hide Shipping Rates for WooCommerce</strong>',
		'<strong>PHP</strong>',
		HIDE_SHIPPING_RATES_PHP_MIN
	);

	printf('<div class="notice notice-warning"><p>%1$s</p></div>', wp_kses_post($notice));
}

if (version_compare(PHP_VERSION, HIDE_SHIPPING_RATES_PHP_MIN, '<')) {
	return add_action('admin_notices', 'hide_shipping_rates_php_missing_notice');
}

require_once HIDE_SHIPPING_RATES_PATH . 'inc/class-main.php';
