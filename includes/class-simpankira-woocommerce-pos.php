<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Pos {

    // API credentials
    private $authorization_token;
    private $x_organisation_token;

    // Accounts
    private $bank_account;
    private $sales_account;
    private $sales_return_allowance_account;
    private $sales_tax_account;
    private $refund_tax_account;

    private $simpankira;
    private $logger;

    public function __construct() {

        // API credentials
        $this->authorization_token  = simpankira_woocommerce_get_setting( 'authorization_token' );
        $this->x_organisation_token = simpankira_woocommerce_get_setting( 'x_organisation_token' );

        // Accounts
        $this->bank_account                   = simpankira_woocommerce_get_setting( 'bank_account' );
        $this->sales_account                  = simpankira_woocommerce_get_setting( 'sales_account' );
        $this->sales_return_allowance_account = simpankira_woocommerce_get_setting( 'sales_return_allowance_account' );
        $this->sales_tax_account              = simpankira_woocommerce_get_setting( 'sales_tax_account' );
        $this->refund_tax_account             = simpankira_woocommerce_get_setting( 'refund_tax_account' );

        $this->init_api();

        $this->logger = new Simpankira_Woocommerce_Logger( 'pos' );

    }

    private function init_api() {

        if ( !$this->check_token() ) {
            return;
        }

        $this->simpankira = new Simpankira_Woocommerce_Api(
            $this->authorization_token,
            $this->x_organisation_token
        );

        return $this->simpankira;

    }

    // Push WooCommerce order transaction data to SimpanKira
    // Pass date where the transaction should be push and current attempt
    public function push_transaction( $date = NULL, $attempts = 0 ) {

        if ( !$this->check_token() || !$this->check_accounts() ) {
            return;
        }

        // Get yesterday date, since we run cron after 12am
        $date = $date ?: date( 'd/m/Y', strtotime( "-1 day", current_time( 'timestamp' ) ) );

        $this->logger->log( 'Processing transaction. Transaction Date: ' . $date );

        $reports = new Simpankira_Woocommerce_Reports( $date );

        $sales_amount                  = $reports->get_sales_refund();
        $sales_exclude_tax_amount      = $reports->get_sales_exclude_tax();
        $sales_return_allowance_amount = $reports->get_refund();
        $sales_tax_amount              = $reports->get_sales_tax();
        $refund_tax_amount             = $reports->get_refund_tax();

        // Required data
        $data = array(
            'description'     => __( 'Sales from website: ', 'simpankira-woocommerce' ) . get_site_url(),
            'cash_account_id' => $this->bank_account,
            'total_cash'      => $sales_amount,
            'date'            => $date,
        );

        // If has sales
        if ( $sales_exclude_tax_amount !== '0.00' ) {
            $data['sales'][] = array(
                'account_id' => $this->sales_account,
                'amount'     => $sales_exclude_tax_amount,
            );
        }

        // If has refund
        if ( $sales_return_allowance_amount > 0 ) {
            $data['return_account_id'] = $this->sales_return_allowance_account;
            $data['refund'] = $sales_return_allowance_amount;
        }

        // If sales tax account set and has sales tax
        if ( $this->sales_tax_account && $sales_tax_amount > 0 ) {
            $data['taxes'][] = array(
                'tax_id' => $this->sales_tax_account,
                'amount' => $sales_tax_amount,
            );
        }

        // If refund tax account set and has refund tax
        if ( $this->refund_tax_account && $refund_tax_amount > 0 ) {
            $data['taxes'][] = array(
                'tax_id' => $this->refund_tax_account,
                'amount' => $refund_tax_amount,
            );
        }

        // If no transaction to push
        if ( !isset( $data['sales'] ) && !isset( $data['refund'] ) ) {
            $this->logger->log( 'No transaction to push. Transaction Date: ' . $date );
            return;
        }

        $this->logger->log( 'Pushing transaction. Transaction Date: ' . $date );

        // Push transaction
        list( $code, $response ) = $this->simpankira->store_pos( $data );

        // If failed
        if ( $code !== 200 ) {
            $this->logger->log( 'Failed pushing transaction. Transaction Date: ' . $date );
            $this->reschedule_push_transaction( $date, ++$attempts );
            return;
        }

        $this->logger->log( 'Pushed transaction. Transaction Date: ' . $date );

    }

    // Re-schedule push transaction for a specific attempts (set by user) for unsuccessful push
    private function reschedule_push_transaction( $date, $attempts = 0 ) {

        $resync_max_attempts = simpankira_woocommerce_get_setting( 'resync_max_attempts' );
        $resync_interval     = simpankira_woocommerce_get_setting( 'resync_interval' );

        // Max re-sync attempts is 5
        if ( $resync_max_attempts > 5 ) {
            $resync_max_attempts = 5;
        }

        // Max re-sync interval is 24 hours
        if ( $resync_interval > 24 ) {
            $resync_interval = 24;
        }

        // Get today's timestamp - the end of the day
        $time = strtotime( current_datetime()->format( 'Y-m-d 23:59:59' ) );

        // 1 hour = 3600 seconds
        $interval = $resync_interval * 3600;

        $resync_interval_label = $resync_interval > 1 ? 'hours' : 'hour';

        // Make sure current attempt not reached maximum re-sync maximum attempts before re-schedule the task
        if ( $attempts <= $resync_max_attempts ) {
            wp_schedule_single_event( $time + $interval, 'simpankira_woocommerce_push_transaction', array( $date, $attempts ) );
            $this->logger->log( "Re-schedule to push transaction in $resync_interval $resync_interval_label. Transaction Date: $date | Attempts: $attempts" );
        } else {
            $this->logger->log( "Push transaction reached max attempts. Transaction Date: $date" );
        }

    }

    // Check if authorization and x-organisation token is filled
    private function check_token() {
        return $this->authorization_token && $this->x_organisation_token;
    }

    // Check if all account settings is selected by user
    private function check_accounts() {

        return $this->bank_account
            && $this->sales_account
            && $this->sales_return_allowance_account;

    }

}
