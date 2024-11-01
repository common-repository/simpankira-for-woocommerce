<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Api extends Simpankira_Woocommerce_Client {

    public function __construct( $authorization_token, $x_organisation_token ) {

        $this->authorization_token  = $authorization_token;
        $this->x_organisation_token = $x_organisation_token;

    }

    // Getting chart of account list
    public function get_chart_of_account() {
        return $this->get( 'ver1/chart-of-account/get' );
    }

    // Finding chart of account
    public function find_chart_of_account( $account_id ) {
        return $this->post( 'ver1/chart-of-account/find', array( 'account_id' => $account_id ) );
    }

    // Getting taxes list
    public function get_tax() {
        return $this->get( 'ver1/tax/get' );
    }

    // Finding tax
    public function find_tax( $tax_id ) {
        return $this->post( 'ver1/tax/find', array( 'tax_id' => $tax_id ) );
    }

    // Store POS session accounting
    public function store_pos( array $params ) {
        return $this->post( 'ver1/pos/store', $params );
    }

}
