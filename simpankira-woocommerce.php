<?php
/**
 * Plugin Name: SimpanKira for WooCommerce
 * Plugin URI:  https://www.simpankira.com/
 * Description: SimpanKira integration for WooCommerce.
 * Version:     1.0.1
 * Author:      Global Digital Technologies Sdn. Bhd.
 */

if ( !defined( 'ABSPATH' ) ) exit;

define( 'SIMPANKIRA_WOOCOMMERCE_FILE', __FILE__ );
define( 'SIMPANKIRA_WOOCOMMERCE_URL', plugin_dir_url( SIMPANKIRA_WOOCOMMERCE_FILE ) );
define( 'SIMPANKIRA_WOOCOMMERCE_PATH', plugin_dir_path( SIMPANKIRA_WOOCOMMERCE_FILE ) );
define( 'SIMPANKIRA_WOOCOMMERCE_VERSION', '1.0.1' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks
 */
require( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce.php' );

function run_simpankira_woocommerce() {
	$plugin = new Simpankira_Woocommerce();
	$plugin->run();
}
run_simpankira_woocommerce();
