<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Cointopay_Block_support extends AbstractPaymentMethodType {

    private $gateway;

    protected $name = 'cointopay';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_cointopay_settings', [] );

        $gateways = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $gateways['cointopay'] ?? null;
    }

    public function is_active() {
        return $this->gateway && $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        $script_path = '/assets/js/frontend/blocks.js';

        $script_asset_path = WC_Cointopay_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';

        $script_asset = file_exists( $script_asset_path )
		? require $script_asset_path
		: [
			'dependencies' => [
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-i18n'
			],
			'version' => '1.0.0'
		];

        $script_url = WC_Cointopay_Payments::plugin_url() . $script_path;

        wp_register_script(
            'wc-cointopay-payments-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations(
                'wc-cointopay-payments-blocks',
                'wc-cointopay-com',
                WC_Cointopay_Payments::plugin_abspath() . 'languages/'
            );
        }

        return [ 'wc-cointopay-payments-blocks' ];
    }

    public function get_payment_method_data() {

		if ( ! $this->gateway ) {
			return [];
		}

		$merchant_id = $this->gateway->merchantid ?? '';
		$coins = [];

		if ( $merchant_id ) {
			$coins = $this->gateway->cointopay_get_merchantCoins( $merchant_id );
		}
		$nonce = wp_create_nonce('cointopay_coints_nonce');

		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'ctp_nonce' => $nonce,
			'coins'       => $coins, // 👈 IMPORTANT
			'supports'    => array_values(
				array_filter(
					$this->gateway->supports,
					[ $this->gateway, 'supports' ]
				)
			),
		];
	}
	
	
	public function cointopay_get_merchantCoins($merchantId)
	{
		$params = array(
			'body' => 'MerchantID=' . $merchantId . '&output=json',
		);
		$url = 'https://cointopay.com/CloneMasterTransaction';
		$response  = wp_safe_remote_post($url, $params);
		if (( false === is_wp_error($response) ) && ( 200 === $response['response']['code'] ) && ( 'OK' === $response['response']['message'] )) {
			$php_arr = json_decode($response['body']);
			$new_php_arr = array();

			if(!empty($php_arr))
			{
				for($i=0;$i<count($php_arr)-1;$i++)
				{
					if(($i%2)==0)
					{
						$new_php_arr[$php_arr[$i+1]] = $php_arr[$i];
					}
				}
			}
			
			return $new_php_arr;
		}
	}
	
}