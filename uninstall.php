<?php
// If uninstall not called from WordPress, then exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Clear all pending scheduled hooks
$hook = 'simpankira_woocommerce_push_transaction';
if ( wp_next_scheduled( $hook ) ) {
    wp_unschedule_hook( $hook );
}
