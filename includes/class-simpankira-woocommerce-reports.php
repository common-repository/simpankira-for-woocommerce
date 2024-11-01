<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Simpankira_Woocommerce_Reports extends WC_Admin_Report {

    private $reports;

    public function __construct( $date ) {

        $this->start_date = strtotime( date_create_from_format( 'd/m/Y', $date )->format( 'Y-m-d' ) );
        $this->end_date = $this->start_date;

        $this->reports = new stdClass();
        $this->get_reports();

    }

    // Get data from database
    private function get_reports() {

        // Get order total, tax, shipping charge, shipping tax
        $totals = (array) $this->get_order_report_data(
            array(
                'data' => array(
                    '_order_total' => array(
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_sales',
                    ),
                    '_order_shipping' => array(
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_shipping',
                    ),
                    '_order_tax' => array(
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_tax',
                    ),
                    '_order_shipping_tax' => array(
                        'type'     => 'meta',
                        'function' => 'SUM',
                        'name'     => 'total_shipping_tax',
                    ),
                    'post_date' => array(
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ),
                ),
                'order_by'     => 'post_date ASC',
                'filter_range' => true,
                'order_types'  => wc_get_order_types( 'sales-reports' ),
                'order_status' => array( 'completed', 'processing', 'on-hold', 'refunded' ),
            )
        );

        /**
         * If an order is 100% refunded we should look at the parent's totals, but the refunds dates.
         * We also need to ensure each parent order's values are only counted/summed once.
         */
        $full_refunds = (array) $this->get_order_report_data(
            array(
                'data' => array(
                    '_order_total' => array(
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_refund',
                    ),
                    '_order_shipping' => array(
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_shipping',
                    ),
                    '_order_tax' => array(
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_tax',
                    ),
                    '_order_shipping_tax' => array(
                        'type'     => 'parent_meta',
                        'function' => '',
                        'name'     => 'total_shipping_tax',
                    ),
                    'post_date' => array(
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ),
                ),
                'group_by'            => 'posts.post_parent',
                'query_type'          => 'get_results',
                'filter_range'        => true,
                'order_status'        => false,
                'parent_order_status' => array( 'refunded' ),
            )
        );

        foreach ( $full_refunds as $key => $order ) {
            $full_refunds[ $key ]->net_refund = $order->total_refund - ( $order->total_shipping + $order->total_tax + $order->total_shipping_tax );
        }

        /**
         * Partial refunds. This includes line items, shipping and taxes. Not grouped by date.
         */
        $partial_refunds = (array) $this->get_order_report_data(
            array(
                'data' => array(
                    'ID'                  => array(
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'refund_id',
                    ),
                    '_refund_amount' => array(
                        'type'     => 'meta',
                        'function' => '',
                        'name'     => 'total_refund',
                    ),
                    'post_date' => array(
                        'type'     => 'post_data',
                        'function' => '',
                        'name'     => 'post_date',
                    ),
                    'order_item_type' => array(
                        'type'      => 'order_item',
                        'function'  => '',
                        'name'      => 'item_type',
                        'join_type' => 'LEFT',
                    ),
                    '_order_total' => array(
                        'type'     => 'meta',
                        'function' => '',
                        'name'     => 'total_sales',
                    ),
                    '_order_shipping' => array(
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_shipping',
                        'join_type' => 'LEFT',
                    ),
                    '_order_tax' => array(
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_tax',
                        'join_type' => 'LEFT',
                    ),
                    '_order_shipping_tax' => array(
                        'type'      => 'meta',
                        'function'  => '',
                        'name'      => 'total_shipping_tax',
                        'join_type' => 'LEFT',
                    ),
                    '_qty' => array(
                        'type'      => 'order_item_meta',
                        'function'  => 'SUM',
                        'name'      => 'order_item_count',
                        'join_type' => 'LEFT',
                    ),
                ),
                'group_by'            => 'refund_id',
                'order_by'            => 'post_date ASC',
                'query_type'          => 'get_results',
                'filter_range'        => true,
                'order_status'        => false,
                'parent_order_status' => array( 'completed', 'processing', 'on-hold' ),
            )
        );

        foreach ( $partial_refunds as $key => $order ) {
            $partial_refunds[ $key ]->net_refund = $order->total_refund - ( $order->total_shipping + $order->total_tax + $order->total_shipping_tax );
        }

        $total_refunds               = 0;
        $total_tax_refunded          = 0;
        $total_shipping_refunded     = 0;
        $total_shipping_tax_refunded = 0;

        $refunded_orders = array_merge( $partial_refunds, $full_refunds );

        foreach ( $refunded_orders as $key => $value ) {
            $total_refunds               += floatval( $value->total_refund );
            $total_tax_refunded          += floatval( $value->total_tax < 0 ? $value->total_tax * -1 : $value->total_tax );
            $total_shipping_refunded     += floatval( $value->total_shipping < 0 ? $value->total_shipping * -1 : $value->total_shipping );
            $total_shipping_tax_refunded += floatval( $value->total_shipping_tax < 0 ? $value->total_shipping_tax * -1 : $value->total_shipping_tax );
        }

        $this->reports = array(
            'sales'        => wc_format_decimal( $totals['total_sales'], 2 ),
            'tax'          => wc_format_decimal( $totals['total_tax'], 2 ),
            'shipping_tax' => wc_format_decimal( $totals['total_shipping_tax'], 2 ),
            'shipping'     => wc_format_decimal( $totals['total_shipping'], 2 ),
            'refund'       => array(
                'sales'        => wc_format_decimal( $total_refunds, 2 ),
                'tax'          => wc_format_decimal( $total_tax_refunded, 2 ),
                'shipping_tax' => wc_format_decimal( $total_shipping_tax_refunded, 2 ),
                'shipping'     => wc_format_decimal( $total_shipping_refunded, 2 ),
            ),
        );

    }

    // Get total sales including tax - refund
    public function get_sales_refund() {

        $sales  = $this->get_sales();
        $refund = $this->get_refund();

        $total = $sales - $refund;

        return wc_format_decimal( $total, 2 );

    }

    // Get total sales including tax
    private function get_sales() {
        return isset( $this->reports['sales'] ) ? $this->reports['sales'] : wc_format_decimal( 0.00, 2 );
    }

    // Get total sales today excluding tax
    public function get_sales_exclude_tax() {

        $sales = $this->get_sales();
        $sales_tax = $this->get_sales_tax();

        $total = $sales - $sales_tax;
        $total = $total > 0 ? $total : 0;

        return wc_format_decimal( $total, 2 );

    }

    // Get sales tax
    public function get_sales_tax() {

        $order_tax = $this->get_order_tax();
        $shipping_tax = $this->get_shipping_tax();

        $total = $order_tax + $shipping_tax;
        $total = $total > 0 ? $total : 0;

        return wc_format_decimal( $total, 2 );

    }

    // Get order tax
    private function get_order_tax() {
        return isset( $this->reports['tax'] ) ? $this->reports['tax'] : wc_format_decimal( 0.00, 2 );
    }

    // Get shipping tax
    private function get_shipping_tax() {
        return isset( $this->reports['shipping_tax'] ) ? $this->reports['shipping_tax'] : wc_format_decimal( 0.00, 2 );
    }

    // Get total refunds
    public function get_refund() {
        return isset( $this->reports['refund']['sales'] ) ? $this->reports['refund']['sales'] : wc_format_decimal( 0.00, 2 );
    }

    // Get refund tax
    public function get_refund_tax() {

        $refund_order_tax = $this->get_refund_order_tax();
        $refund_shipping_tax = $this->get_refund_shipping_tax();

        $total = $refund_order_tax + $refund_shipping_tax;
        $total = $total > 0 ? $total : 0;

        return wc_format_decimal( $total, 2 );

    }

    // Get order tax for refunded item
    private function get_refund_order_tax() {
        return isset( $this->reports['refund']['tax'] ) ? $this->reports['refund']['tax'] : wc_format_decimal( 0.00, 2 );
    }

    // Get shipping tax for refunded item
    private function get_refund_shipping_tax() {
        return isset( $this->reports['refund']['shipping_tax'] ) ? $this->reports['refund']['shipping_tax'] : wc_format_decimal( 0.00, 2 );
    }

}
