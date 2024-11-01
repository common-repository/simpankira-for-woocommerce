<?php
// Get setting value by key
function simpankira_woocommerce_get_setting( $key ) {
    return isset( get_option( 'simpankira' )[ $key ] ) ? get_option( 'simpankira' )[ $key ] : NULL;
}

// Render manual sync form
function simpankira_woocommerce_manual_sync_form() {

    ob_start();
    include( SIMPANKIRA_WOOCOMMERCE_PATH . 'admin/views/manual-sync-form.php' );
    echo ob_get_clean();

}
