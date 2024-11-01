<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce {

    protected $loader;

    // Plugin core class
    public function __construct() {

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_settings_hooks();
        $this->define_init_hooks();

    }

    // Load the required dependencies for this plugin
    private function load_dependencies() {

        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/functions.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'libraries/codestar-framework/codestar-framework.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-loader.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-notice.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-logger.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/abstracts/class-simpankira-woocommerce-client.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-api.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'admin/class-simpankira-woocommerce-admin.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'admin/class-simpankira-woocommerce-settings.php' );
        require_once( SIMPANKIRA_WOOCOMMERCE_PATH . 'includes/class-simpankira-woocommerce-init.php' );

        $this->loader = new Simpankira_Woocommerce_Loader();

    }

    // Register all of the hooks for admin
    private function define_admin_hooks() {

        $admin = new Simpankira_Woocommerce_Admin();
        $plugin_basename = plugin_basename( SIMPANKIRA_WOOCOMMERCE_FILE );

        $this->loader->add_action( 'plugin_action_links_' . $plugin_basename, $admin, 'register_settings_link' );
        $this->loader->add_action( 'admin_notices', $admin, 'woocommerce_notice' );

    }

    // Register all of the hooks for settings
    private function define_settings_hooks() {

        $settings = new Simpankira_Woocommerce_Settings();

        $this->loader->add_action( 'admin_enqueue_scripts', $settings, 'enqueue_scripts' );
        $this->loader->add_filter( "csf_{$settings->id}_save", $settings, 'validate_chart_of_accounts' );
        $this->loader->add_filter( "csf_{$settings->id}_save", $settings, 'validate_tax_accounts' );
        $this->loader->add_filter( "csf_{$settings->id}_save", $settings, 'exclude_manual_sync_input' );
        $this->loader->add_action( "csf_{$settings->id}_saved", $settings, 'schedule_push_transaction' );
        $this->loader->add_action( 'wp_ajax_simpankira_woocommerce_manual_sync', $settings, 'handle_ajax_manual_sync' );
        $this->loader->add_action( 'admin_notices', $settings, 'handle_manual_sync_notices' );

    }

    // Register all of the hooks for init POS and reports
    private function define_init_hooks() {

        $init = new Simpankira_Woocommerce_Init();
        $this->loader->add_action( 'init', $init, 'load_dependencies' );

    }

    //Run the loader to execute all of the hooks with WordPress
    public function run() {
        $this->loader->run();
    }

}
