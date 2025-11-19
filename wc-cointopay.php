<?php
/**
 * Plugin Name: Cointopay — Crypto and Fiat Payments for WooCommerce
 * Description: Extends WooCommerce with crypto payments gateway.
 * Version: 1.4.4
 * Author: Cointopay
 *
 * @author   Cointopay <info@cointopay.com>
 * @link     cointopay.com
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path( __FILE__ ) . 'hooks/get_merchant_coins.php';

add_filter( 'woocommerce_payment_gateways', 'wc_cointopay_gateway_class' );
function wc_cointopay_gateway_class( $gateways ) {
	$gateways[] = 'WC_Cointopay_Gateway'; // your class name is here
	return $gateways;
}

add_action( 'plugins_loaded', 'woocommerce_cointopay_init' );

function woocommerce_cointopay_init() {
	require_once plugin_dir_path( __FILE__ ) . 'classes/wc_cointopay_gateway.php';
}

class WC_Cointopay_Payments {

    /**
     * Plugin bootstrapping.
     */
    public static function init() {
        // Registers WooCommerce Blocks integration.
        add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_cointopay_woocommerce_block_support' ) );

    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath() {
        return trailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function woocommerce_gateway_cointopay_woocommerce_block_support() {
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            require_once 'classes/wc_cointopay_block_support.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new WC_Cointopay_Block_support() );
                }
            );
        }
    }
}

WC_Cointopay_Payments::init();
?>