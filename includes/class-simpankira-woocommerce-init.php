<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Init {

    private $loader;

    public function __construct() {
        $this->loader = new Simpankira_Woocommerce_Loader();
    }

    // Load the required dependencies
    public function load_dependencies() {

        if ( !$this->is_woocommerce_activated() ) {
            return;
        }

        include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );

        if ( !class_exists( 'WC_Admin_Report' ) ) {
            return;
        }

        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-reports.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-pos.php' );

        $this->hooks();

    }

    private function is_woocommerce_activated() {
        return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
    }

    // Registering and running all hooks
    private function hooks() {

        $this->define_pos_hooks();
        $this->loader->run();

    }

    // Register all of the hooks for POS
    private function define_pos_hooks() {

        $pos = new Simpankira_Woocommerce_Pos();
        $this->loader->add_action( 'simpankira_woocommerce_push_transaction', $pos, 'push_transaction', 10, 2 );

    }

}
