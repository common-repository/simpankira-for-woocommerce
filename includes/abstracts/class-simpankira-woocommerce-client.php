<?php
if ( !defined( 'ABSPATH' ) ) exit;

abstract class Simpankira_Woocommerce_Client {

    const API_URL = 'https://app.simpankira.com/api/';

    protected $authorization_token;
    protected $x_organisation_token;

    private $logger;

    private function get_headers() {
        return array(
            'Accept'               => 'application/json',
            'Content-Type'         => 'application/json',
            'Authorization'        => 'Bearer ' . $this->authorization_token,
            'x-organisation-token' => $this->x_organisation_token,
        );
    }

    // Send GET request to SimpanKira
    protected function get( $route, $params = array() ) {
        return $this->request( $route, $params, 'GET' );
    }

    // Send POST request to SimpanKira
    protected function post( $route, $params = array() ) {
        return $this->request( $route, $params );
    }

    // Send request to SimpanKira
    protected function request( $route, $params = array(), $method = 'POST' ) {

        $url = self::API_URL . $route;
        $args['headers'] = $this->get_headers();

        $this->log( 'URL: ' . $url );
        $this->log( 'Headers: ' . wp_json_encode( $args['headers'] ) );

        if ( $params ) {
            $args['body'] = $method !== 'POST' ? $params : wp_json_encode( $params );
            $this->log( 'Body: ' . wp_json_encode( $params ) );
        }

        switch ( $method ) {
            case 'GET':
                $response = wp_remote_get( $url, $args );
                break;

            case 'POST':
                $response = wp_remote_post( $url, $args );
                break;

            default:
                $args['method'] = $method;
                $response = wp_remote_request( $url, $args );
        }

        if ( is_wp_error( $response ) ) {
            throw new Exception( $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        $this->log( 'Response: ' . wp_json_encode( $body ) );

        return array( $code, $body );

    }

    // Errors logging
    private function log( $message ) {

        if ( !$this->logger ) {
            $this->logger = new Simpankira_Woocommerce_Logger();
        }

        if ( $this->logger ) {
            $this->logger->log( $message );
        }

    }
}
