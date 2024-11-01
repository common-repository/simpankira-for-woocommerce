<?php if ( !defined( 'ABSPATH' ) ) exit; ?>
<input type="text" id="manual_sync_date" class="datepicker">
<button id="manual_sync_submit" class="button button-primary"><?php _e( 'Synchronize', 'simpankira-woocommerce' ); ?></button>
<div class="csf-desc-text"><?php _e( 'Date is required to run manual synchronization.', 'simpankira-woocommerce' ); ?></div>
<script type="text/javascript">
    jQuery( document ).ready( function( $ ) {
        $( '#manual_sync_date' ).datepicker( {
            dateFormat : 'dd/mm/yy',
            defaultDate: -1,
            maxDate: -1
        } );

        $( '#manual_sync_date' ).datepicker( 'setDate', ( new Date() ).getDate() - 1 ); 

        $( '#manual_sync_submit' ).on( 'click', function( event ) {
            event.preventDefault();

            var date = $( "#manual_sync_date" ).val();
            location.href = "<?php echo admin_url( 'admin-ajax.php?action=simpankira_woocommerce_manual_sync' ); ?>&_wpnonce=<?php echo wp_create_nonce( 'simpankira_woocommerce_manual_sync' ) ?>&date=" + date;
        } );
    } );
</script>
