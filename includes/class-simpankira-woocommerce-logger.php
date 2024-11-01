<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Logger {

    private $id;

    public function __construct( $id = NULL ) {
        $this->id = $id ? 'simpankira-' . $id : 'simpankira';
    }

    // Errors logging
    public function log( $message ) {

        if ( class_exists( 'WC_Logger' ) ) {
            $logger = new WC_Logger();
            $logger->add( $this->id, $message );
        }

    }

}
