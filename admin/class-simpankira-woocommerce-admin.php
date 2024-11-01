<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Admin {

    private $id = 'simpankira';
    private $notice;

    public function __construct() {
        $this->notice = new Simpankira_Woocommerce_Notice();
    }

    // Register plugin settings link
    public function register_settings_link( $links ) {

        $url = get_admin_url() . 'admin.php?page=' . $this->id;
        $label = __( 'Settings', 'simpankira-woocommerce' );

        $settings_link = sprintf( '<a href="%s">%s</a>', $url, $label );
        array_unshift( $links, $settings_link );

        return $links;

    }

    // Show notice if WooCommerce is not installed and activated
    public function woocommerce_notice() {

        if ( !$this->is_woocommerce_activated() ) {
            $message = __( '<strong>SimpanKira for WooCommerce:</strong> WooCommerce needs to be installed and activated.', 'simpankira-woocommerce' );
            $this->notice->add( $message, 'error' );
        }

    }

    // Check if WooCommerce is installed and activated
    private function is_woocommerce_activated() {
        return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

}
