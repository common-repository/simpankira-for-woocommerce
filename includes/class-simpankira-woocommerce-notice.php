<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Notice {

    // $wrap = for plugin settings page
    public function add( $message, $class = 'success', $wrap = false ) {

        if ( $wrap ) printf( '<div class="wrap">' );
        printf( '<div class="notice notice-%s"><p>%s</p></div>', esc_attr( $class ), $message );
        if ( $wrap ) printf( '</div>' );

    }

}
