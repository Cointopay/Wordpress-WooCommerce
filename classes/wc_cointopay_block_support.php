<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class WC_Cointopay_Block_support extends AbstractPaymentMethodType
{

    private $gateway;

    protected $name = 'cointopay';

    public function initialize()
    {
        $this->settings = get_option( 'woocommerce_cointopay_settings', [] );
        $this->gateway  = new WC_Cointopay_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        $script_path       = '/assets/js/frontend/blocks.js';
        $script_asset_path = WC_Cointopay_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
        $script_asset      = file_exists( $script_asset_path )
            ? require( $script_asset_path )
            : array(
                'dependencies' => array(),
                'version'      => '1.3.0'
            );
        $script_url        = WC_Cointopay_Payments::plugin_url() . $script_path;
        wp_register_script(
            'wc-cointopay-payments-blocks',
            $script_url,
            $script_asset[ 'dependencies' ],
            $script_asset[ 'version' ],
            true
        );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'wc-cointopay-payments-blocks', 'woocommerce-gateway-cointopay', WC_Cointopay_Payments::plugin_abspath() . 'languages/' );
        }
        return [ 'wc-cointopay-payments-blocks' ];
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->get_setting( 'title' ),
            'description' => $this->get_setting( 'description' ),
            'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }
}