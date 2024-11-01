<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Settings {

    public $id = 'simpankira';

    private $simpankira;
    private $notice;

    // API credentials
    private $authorization_token;
    private $x_organisation_token;

    // Account settings
    private $chart_of_accounts;
    private $tax_accounts;

    public function __construct() {

        $this->notice = new Simpankira_Woocommerce_Notice();

        $this->authorization_token  = simpankira_woocommerce_get_setting( 'authorization_token' );
        $this->x_organisation_token = simpankira_woocommerce_get_setting( 'x_organisation_token' );

        $this->build();

    }

    // Register and enqueue style for plugin settings page
    public function enqueue_scripts() {

        global $pagenow;

        if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == $this->id ) {
            wp_enqueue_style( 'simpankira-woocommerce', SIMPANKIRA_WOOCOMMERCE_URL . 'assets/css/styles.min.css', array(), SIMPANKIRA_WOOCOMMERCE_VERSION );

            // jQuery UI
            wp_register_style( 'jquery-ui', SIMPANKIRA_WOOCOMMERCE_URL . 'assets/css/jquery-ui.min.css', array(), '1.12.1' );
            wp_enqueue_style( 'jquery-ui' );

            // jQuery Datepicker
            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

    }

    private function init_api() {

        $this->simpankira = new Simpankira_Woocommerce_Api(
            $this->authorization_token,
            $this->x_organisation_token
        );

        return $this->simpankira;

    }

    // Build settings page
    public function build() {

        if ( !class_exists( 'CSF' ) ) {
            return;
        }

        CSF::createOptions( $this->id, $this->args() );

        // Sections
        foreach ( $this->sections() as $section ) {
            CSF::createSection( $this->id, $section );
        }

    }

    // Settings page configuration
    private function args() {

        $title = __( 'SimpanKira for WooCommerce', 'simpankira-woocommerce' );

        $logo_img = SIMPANKIRA_WOOCOMMERCE_URL . 'assets/images/simpankira.png';
        $logo = sprintf( '<img class="simpankira-logo" src="%s" alt="%s">', $logo_img, $title );

        return array(
            'framework_title' => $logo . $title,
            'framework_class' => 'simpankira-woocommerce-settings',
            'menu_title'      => __( 'SimpanKira', 'simpankira-woocommerce' ),
            'menu_slug'       => $this->id,
            'menu_icon'       => SIMPANKIRA_WOOCOMMERCE_URL . 'assets/images/simpankira_icon.png',
            'menu_position'   => 58,
            'show_bar_menu'   => false,
            'ajax_save'       => false,
        );

    }

    // Settings sections
    private function sections() {

        global $pagenow;

        // Get accounts only if we are on settings page
        if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == $this->id ) {
            $this->get_chart_of_accounts();
            $this->get_tax_accounts();
        }

        $api_credentials_filled_conditions = array(
            array( 'authorization_token', '!=', '', true ),
            array( 'x_organisation_token', '!=', '', true ),
        );

        $sections = array(
            array(
                'id'          => 'api_credentials',
                'title'       => __( 'API Credentials', 'simpankira-woocommerce' ),
                'description' => __( 'You can obtain the API credentials from SimpanKira Organisation Settings page, under API section.', 'simpankira-woocommerce' ),
                'fields' => array(
                    array(
                        'id'    => 'authorization_token',
                        'type'  => 'text',
                        'title' => __( 'Authorization Token', 'simpankira-woocommerce' ),
                    ),
                    array(
                        'id'    => 'x_organisation_token',
                        'type'  => 'text',
                        'title' => __( 'X-organisation Token', 'simpankira-woocommerce' ),
                    ),
                ),
            ),
        );

        // If chart of accounts not found, we know that either user enter invalid API credentials
        // or they does not have any account stored in SimpanKira
        if ( !$this->chart_of_accounts ) {
            $api_credentials_invalid_notice = array(
                'type'    => 'notice',
                'style'   => 'warning',
                'content' => __( 'API credentials invalid. Please fill in valid API credentials.<br>If your API credentials is valid, please make sure you have at least one account in SimpanKira.', 'simpankira-woocommerce' ),
            );

            array_unshift( $sections[0]['fields'], $api_credentials_invalid_notice );
            $account_settings_fields = array( $api_credentials_invalid_notice );
        } else {
            $account_select_placeholder   = __( 'Select an account', 'simpankira-woocommerce' );
            $account_select_empty_message = __( 'No data found', 'simpankira-woocommerce' );

            $account_settings_fields = array(
                array(
                    'type'       => 'notice',
                    'style'      => 'warning',
                    'content'    => __( 'Fill in your API credentials first.', 'simpankira-woocommerce' ),
                    'dependency' => array(
                        array( 'authorization_token', '==', '', true ),
                        array( 'x_organisation_token', '==', '', true ),
                    ),
                ),
                array(
                    'id'            => 'bank_account',
                    'type'          => 'select',
                    'title'         => __( 'Bank Account', 'simpankira-woocommerce' ),
                    'options'       => $this->get_chart_of_account( 'Asset' ),
                    'dependency'    => $api_credentials_filled_conditions,
                    'chosen'        => true,
                    'placeholder'   => $account_select_placeholder,
                    'empty_message' => $account_select_empty_message,
                    'settings'      => array( 'width' => '100%' ),
                ),
                array(
                    'id'            => 'sales_account',
                    'type'          => 'select',
                    'title'         => __( 'Sales Account', 'simpankira-woocommerce' ),
                    'options'       => $this->get_chart_of_account( 'Revenue' ),
                    'dependency'    => $api_credentials_filled_conditions,
                    'chosen'        => true,
                    'placeholder'   => $account_select_placeholder,
                    'empty_message' => $account_select_empty_message,
                    'settings'      => array( 'width' => '100%' ),
                ),
                array(
                    'id'            => 'sales_return_allowance_account',
                    'type'          => 'select',
                    'title'         => __( 'Sales Return and Allowance Account', 'simpankira-woocommerce' ),
                    'options'       => $this->get_chart_of_account( 'Revenue' ),
                    'dependency'    => $api_credentials_filled_conditions,
                    'chosen'        => true,
                    'placeholder'   => $account_select_placeholder,
                    'empty_message' => $account_select_empty_message,
                    'settings'      => array( 'width' => '100%' ),
                ),
                array(
                    'id'            => 'sales_tax_account',
                    'type'          => 'select',
                    'title'         => __( 'Sales Tax Account', 'simpankira-woocommerce' ),
                    'options'       => $this->get_tax_account( 'Supply' ),
                    'dependency'    => $api_credentials_filled_conditions,
                    'chosen'        => true,
                    'placeholder'   => $account_select_placeholder,
                    'empty_message' => $account_select_empty_message,
                    'settings'      => array( 'width' => '100%' ),
                ),
                array(
                    'id'            => 'refund_tax_account',
                    'type'          => 'select',
                    'title'         => __( 'Refund Tax Account', 'simpankira-woocommerce' ),
                    'options'       => $this->get_tax_account( 'Supply' ),
                    'dependency'    => $api_credentials_filled_conditions,
                    'chosen'        => true,
                    'placeholder'   => $account_select_placeholder,
                    'empty_message' => $account_select_empty_message,
                    'settings'      => array( 'width' => '100%' ),
                ),
            );
        }

        $sections[] = array(
            'id'          => 'account_settings',
            'title'       => __( 'Account Settings', 'simpankira-woocommerce' ),
            'description' => __( 'All account settings is required except sales and refund tax account.<br>Sales and refund tax account is required only if WooCommerce tax calculation is enabled.<br><br>If you enable tax calculation in WooCommerce settings and not specify sales and refund tax account in our plugin settings, plugin are unable to synchronize transaction from WooCommerce to SimpanKira.', 'simpankira-woocommerce' ),
            'fields'      => $account_settings_fields,
        );

        $sections[] = array(
            'id'          => 'synchronization',
            'title'       => __( 'Synchronization', 'simpankira-woocommerce' ),
            'description' => __( 'If SimpanKira does not return successful response, plugin will re-schedule to run the synchronization task for a specified attempts.<br>Set maximum attempts and interval between the attempts.<br><br>If manual synchronization is not working, please check error log. Failed synchronization will be re-sync based on re-sync maximum attempt(s) and re-sync interval set.', 'simpankira-woocommerce' ),
            'fields' => array(
                array(
                    'id'      => 'resync_max_attempts',
                    'type'    => 'spinner',
                    'title'   => __( 'Re-sync Maximum Attempt(s)', 'simpankira-woocommerce' ),
                    'desc'    => __( 'Minimum is 1 attempt, maximum is 5 attempts. Default is 2 attempts.', 'simpankira-woocommerce' ),
                    'default' => 2,
                    'min'     => 1,
                    'max'     => 5,
                ),
                array(
                    'id'      => 'resync_interval',
                    'type'    => 'spinner',
                    'title'   => __( 'Re-sync Interval', 'simpankira-woocommerce' ),
                    'desc'    => __( 'Minimum is 1 hour, maximum is 24 hours. Default is 1 hour.', 'simpankira-woocommerce' ),
                    'default' => 1,
                    'min'     => 1,
                    'max'     => 24,
                ),
                array(
                    'id'       => 'manual_sync',
                    'type'     => 'callback',
                    'title'    => __( 'Manual Sync', 'simpankira-woocommerce' ),
                    'function' => 'simpankira_woocommerce_manual_sync_form',
                ),
            ),
        );

        // Content for logs section
        $logs_content = __( 'View API request and transaction synchronization logs: ', 'simpankira-woocommerce' )
                            . '<a href="' . admin_url( 'admin.php?page=wc-status&tab=logs' ) . '">'
                            . __( 'WooCommerce Logs', 'simpankira-woocommerce' )
                            . '</a>'
                            . '<br><br>'
                            . __( '<strong>simpankira</strong> - SimpanKira API request log', 'simpankira-woocommerce' )
                            . '<br>'
                            . __( '<strong>simpankira-pos</strong> - transaction synchronization log', 'simpankira-woocommerce' );

        $sections[] = array(
            'id'     => 'logs',
            'title'  => __( 'Logs', 'simpankira-woocommerce' ),
            'fields' => array(
                array(
                    'type'    => 'notice',
                    'style'   => 'info',
                    'content' => $logs_content,
                ),
            ),
        );

        return $sections;

    }

    // Get chart of accounts from SimpanKira
    private function get_chart_of_accounts() {

        // If $_POST data is passed, update API credentials value
        if ( isset( $_POST[ $this->id ]['authorization_token'] ) && isset( $_POST[ $this->id ]['x_organisation_token'] ) ) {
            $this->authorization_token  = sanitize_text_field( $_POST[ $this->id ]['authorization_token'] );
            $this->x_organisation_token = sanitize_text_field( $_POST[ $this->id ]['x_organisation_token'] );
        }

        if ( !$this->authorization_token || !$this->x_organisation_token ) {
            return false;
        }

        $this->init_api();

        list( $code, $response ) = $this->simpankira->get_chart_of_account();

        if ( $code == 200 && isset( $response['data'] ) ) {
            $this->chart_of_accounts = $response['data'];
        }

        return $this->chart_of_accounts;

    }

    // Format chart of accounts for dropdown selection
    private function get_chart_of_account( $type ) {

        // If specified chart of account not found, return empty array
        if ( !isset( $this->chart_of_accounts[ $type ] ) ) {
            return array();
        }

        // Format the data for <select>
        $items = array();
        foreach ( $this->chart_of_accounts[ $type ] as $value ) {
            $items[ $value['account_id'] ] = $value['code'] . ' - ' . $value['name'];
        }

        return $items;

    }

    // Validate chart of accounts selected by user
    public function validate_chart_of_accounts( $data ) {

        // Update API credentials value
        if ( isset( $data['authorization_token'] ) && isset( $data['x_organisation_token'] ) ) {
            $this->authorization_token  = $data['authorization_token'];
            $this->x_organisation_token = $data['x_organisation_token'];
        }

        if ( !$this->authorization_token || !$this->x_organisation_token ) {
            return $data;
        }

        $this->init_api();

        // Accounts need to be validated with SimpanKira
        $accounts = array(
            'bank_account',
            'sales_account',
            'sales_return_allowance_account',
        );

        // Validate all account ID selected by user
        foreach ( $accounts as $account ) {
            // Skip if field not present
            if ( !isset( $data[ $account ] ) ) {
                continue;
            }

            list( $code, $response ) = $this->simpankira->find_chart_of_account( $data[ $account ] );

            // If account ID is not found, set $data for that account ID as NULL
            if ( $code !== 200 || !isset( $response['data'] ) ) {
                $data[ $account ] = NULL;
            }
        }


        return $data;

    }

    // Get tax accounts from SimpanKira
    private function get_tax_accounts() {

        // If $_POST data is passed, update API credentials value
        if ( isset( $_POST[ $this->id ]['authorization_token'] ) && isset( $_POST[ $this->id ]['x_organisation_token'] ) ) {
            $this->authorization_token  = sanitize_text_field( $_POST[ $this->id ]['authorization_token'] );
            $this->x_organisation_token = sanitize_text_field( $_POST[ $this->id ]['x_organisation_token'] );
        }

        if ( !$this->authorization_token || !$this->x_organisation_token ) {
            return false;
        }

        $this->init_api();

        list( $code, $response ) = $this->simpankira->get_tax();

        if ( $code == 200 && isset( $response['data']['Supply'] ) ) {
            $this->tax_accounts = $response['data'];
        }

        return $this->tax_accounts;

    }

    // Format tax accounts for dropdown selection
    private function get_tax_account( $type ) {

        // If specified tax account not found, return empty array
        if ( !isset( $this->tax_accounts[ $type ] ) ) {
            return array();
        }

        // Format the data for <select>
        $items = array();
        foreach ( $this->tax_accounts[ $type ] as $value ) {
            $items[ $value['tax_id'] ] = $value['code'] . ' - ' . $value['name'];
        }

        return $items;

    }

    // Validate tax accounts selected by user
    public function validate_tax_accounts( $data ) {

        // Update API credentials value
        if ( isset( $data['authorization_token'] ) && isset( $data['x_organisation_token'] ) ) {
            $this->authorization_token  = $data['authorization_token'];
            $this->x_organisation_token = $data['x_organisation_token'];
        }

        if ( !$this->authorization_token || !$this->x_organisation_token ) {
            return $data;
        }

        $this->init_api();

        $tax_accounts = array(
            'sales_tax_account',
            'refund_tax_account',
        );

        // Validate all account ID selected by user
        foreach ( $tax_accounts as $tax_account ) {
            // Skip if field not present
            if ( !isset( $data[ $tax_account ] ) ) {
                continue;
            }

            list( $code, $response ) = $this->simpankira->find_tax( $data[ $tax_account ] );

            // If account ID is not found, set $data for that account ID as NULL
            if ( $code !== 200 || !isset( $response['data'] ) ) {
                $data[ $tax_account ] = NULL;
            }
        }

        return $data;

    }

    // Create scheduling task to push WooCommerce transaction to SimpanKira
    public function schedule_push_transaction() {

        $hook = 'simpankira_woocommerce_push_transaction';

        if ( wp_next_scheduled( $hook ) ) {
            wp_clear_scheduled_hook( $hook );
        }

        // Run cron on tomorrow date, at 12am (beginning of the day)
        // This is too prevent WordPress cron to pass tomorrow date when no one visits the site today
        $time = strtotime( "+1 day", strtotime( current_time( 'Y-m-d 00:00:00' ) ) );

        wp_schedule_event( $time, 'daily', $hook );

    }

    // Exclude manual sync date input value from being saved
    public function exclude_manual_sync_input( $data ) {

        if ( isset( $data['manual_sync'] ) ) {
            unset( $data['manual_sync'] );
        }

        return $data;

    }

    // Handle AJAX manual sync
    public function handle_ajax_manual_sync() {

        if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'simpankira_woocommerce_manual_sync' ) ) {
            return;
        }

        $url = add_query_arg( 'page', $this->id, admin_url( 'admin.php' ) );

        // Check if date input is empty
        if ( !$_REQUEST['date'] ) {
            wp_safe_redirect( add_query_arg( 'manual_sync', 'empty_date', $url ) );
            exit();
        }

        $date = sanitize_text_field( $_REQUEST['date'] );

        // Validate date
        if ( !$this->validate_date( $date ) ) {
            wp_safe_redirect( add_query_arg( 'manual_sync', 'wrong_date', $url ) );
            exit();
        }

        do_action( 'simpankira_woocommerce_push_transaction', $date );

        wp_safe_redirect( add_query_arg( array(
            'manual_sync' => 'success',
            'date' => $data,
        ), $url ) );

        exit();

    }

    // Validate date for AJAX manual sync
    private function validate_date( $date, $format = 'd/m/Y' ) {

        $d = DateTime::createFromFormat( $format, $date );
        return $d && $d->format( $format ) === $date;

    }

    // Handle manual sync notices
    public function handle_manual_sync_notices() {

        global $pagenow;

        if (
            $pagenow == 'admin.php'
            && isset( $_GET['page'] )
            && $_GET['page'] == $this->id
            && isset( $_GET['manual_sync'] )
        ) {
            $manual_sync = $_GET['manual_sync'];

            if ( $manual_sync == 'success' && isset( $_GET['date'] ) ) {
                $this->notice->add(
                    esc_html__( 'Manual sync successfully run. Date: ', 'simpankira-woocommerce' ) . $_GET['date'],
                    'success',
                    true
                );
            } elseif ( $manual_sync == 'empty_date' ) {
                $this->notice->add(
                    __( 'Date is required to run manual sync.', 'simpankira-woocommerce' ),
                    'error',
                    true
                );
            } elseif ( $manual_sync == 'wrong_date' ) {
                $this->notice->add(
                    __( 'Wrong date format to run manual sync.', 'simpankira-woocommerce' ),
                    'error',
                    true
                );
            }
        }

    }

}
