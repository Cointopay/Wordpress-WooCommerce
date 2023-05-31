<?php
/**
 * Plugin Name: WooCommerce Cointopay.com
 * Description: Extends WooCommerce with crypto payments gateway.
 * Version: 1.3.9
 * Author: Cointopay
 *
 * @author   Cointopay <info@cointopay.com>
 * @link     cointopay.com
 */

defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
if (is_plugin_active('woocommerce/woocommerce.php') === true) {
	require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
	// Add the Gateway to WooCommerce.
	add_filter('woocommerce_payment_gateways', 'wc_ctp_gateway');
	add_action('plugins_loaded', 'woocommerce_cointopay_init', 0);


	/**
	 * Add the gateways to WooCommerce
	 *
	 * @param array $methods $args {
	 *                       Optional. An array of arguments.
	 *
	 * @type   type $key Description. Default 'value'. Accepts 'value', 'value'.
	 *    (aligned with Description, if wraps to a new line)
	 * @type   type $key Description.
	 * }
	 * @return array $methods
	 * @since  1.0.0
	 */
	function wc_ctp_gateway( $methods) {
		$methods[] = 'cointopay';
		return $methods;

	}//end wc_ctp_gateway()


	/**
	 * Add the Gateway to WooCommerce init
	 *
	 * @return bool
	 */
	function woocommerce_cointopay_init() {
		if (class_exists('WC_Payment_Gateway') === false) {
			return;
		}

	}//end woocommerce_cointopay_init()


	/**
	 * Define Cointopay Class
	 *
	 * @package  WooCommerce
	 * @author   Cointopay <info@cointopay.com>
	 * @link     cointopay.com
	 */
	class Cointopay extends WC_Payment_Gateway {
		/**
		 * Define Cointopay Class constructor
		 **/
		public function __construct() {
			$this->id   = 'cointopay';
			$this->icon = plugins_url('images/crypto.png', __FILE__);

			$this->init_form_fields();
			$this->init_settings();

			$this->title       = $this->get_option('title');
			$this->description = $this->get_option('description');
			$this->altcoinid   = 666;
			$this->merchantid  = $this->get_option('merchantid');

			$this->apikey         = '1';
			$this->secret         = $this->get_option('secret');
			$this->msg['message'] = '';
			$this->msg['class']   = '';

			add_action('init', array(&$this, 'check_cointopay_response'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
			add_action('woocommerce_api_' . strtolower(get_class($this)), array( &$this, 'check_cointopay_response' ));
			add_action('wp_enqueue_scripts', array(&$this, 'Cointopay_Crypto_Gateway_admin_js' ));
		
			
			add_action('woocommerce_after_order_notes', array(&$this, 'cointopay_crypto_select_checkout_field' ));
			add_action('woocommerce_checkout_process', array(&$this, 'cointopay_crypto_process_custom_payment' ));
			add_action( 'woocommerce_after_order_notes', array(&$this, 'cointopay_crypto_checkout_hidden_field' ), 10, 1 );
			add_action('woocommerce_checkout_update_order_meta', array(&$this, 'cointopay_crypto_select_checkout_update_order_meta' ));

			// Valid for use.
			if (empty($this->settings['enabled']) === false && empty($this->apikey) === false && empty($this->secret) === false) {
				$this->enabled = 'yes';
			} else {
				$this->enabled = 'no';
			}

			// Checking if apikey is not empty.
			if (empty($this->apikey) === true) {
				add_action('admin_notices', array( &$this, 'apikey_missingmessage' ));
			}

			// Checking if app_secret is not empty.
			if (empty($this->secret) === true) {
				add_action('admin_notices', array( &$this, 'secret_missingmessage' ));
			}

		}//end __construct()


		/**
		 * Define initFormfields function
		 *
		 * @return mixed
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'     => array(
					'title'   => __('Enable/Disable', 'Cointopay'),
					'type'    => 'checkbox',
					'label'   => __('Enable Cointopay', 'Cointopay'),
					'default' => 'yes',
				),
				'title'       => array(
					'title'       => __('Title', 'Cointopay'),
					'type'        => 'text',
					'description' => __('This controls the title the user can see during checkout.', 'Cointopay'),
					'default'     => __('Cointopay', 'Cointopay'),
				),
				'description' => array(
					'title'       => __('Description', 'Cointopay'),
					'type'        => 'textarea',
					'description' => __('This controls the title the user can see during checkout.', 'Cointopay'),
					'default'     => __('You will be redirected to cointopay.com to complete your purchase.', 'Cointopay'),
				),
				'merchantid'  => array(
					'title'       => __('Your MerchantID', 'Cointopay'),
					'type'        => 'text',
					'description' => __('Please enter your Cointopay Merchant ID, You can get this information in: <a href="' . esc_url( 'https://cointopay.com' ) . '" target="_blank">Cointopay Account</a>.', 'Cointopay'),
					'default'     => '',
				),
				'secret'      => array(
					'title'       => __('SecurityCode', 'Cointopay'),
					'type'        => 'password',
					'description' => __('Please enter your Cointopay SecurityCode, You can get this information in: <a href="' . esc_url( 'https://cointopay.com' ) . '" target="_blank">Cointopay Account</a>.', 'Cointopay'),
					'default'     => '',
				),
			);

		}//end init_form_fields()


		/**
		 * Define adminOptions function
		 *
		 * @return mixed
		 */
		public function admin_options() {
			?>
				<h3><?php esc_html_e('Cointopay Checkout', 'Cointopay'); ?></h3>

				<div id="wc_get_started">
				<span class="main"><?php esc_html_e('Provides a secure way to accept crypto currencies.', 'Cointopay'); ?></span>
				<p><a href="https://app.cointopay.com/index.jsp?#Register" target="_blank" class="button button-primary"><?php esc_html_e('Join free', 'Cointopay'); ?></a> <a href="https://cointopay.com" target="_blank" class="button"><?php esc_html_e('Learn more about WooCommerce and Cointopay', 'Cointopay'); ?></a></p>
				</div>

				<table class="form-table">
			  <?php $this->generate_settings_html(); ?>
				</table>
				<?php

		}//end admin_options()


		/**
		 *  There are no payment fields for Cointopay, but we want to show the description if set.
		 *
		 * @return string
		 **/
		public function payment_fields() {
			if (true === $this->description) {
				echo esc_html($this->description);
			}

		}//end payment_fields()


		/**
		 * Process the payment and return the result
		 *
		 * @param int $orderid comment
		 *
		 * @return $array
		 **/
		public function process_payment( $orderid) {
			global $woocommerce;
			$order = wc_get_order($orderid);
			$this->altcoinid = get_post_meta( $orderid, 'cointopay_crypto_alt_coin', true);

			$itemnames = array();

			if (count($order->get_items()) > 0) :
				foreach ($order->get_items() as $item) :
					if (true === $item['qty']) {
						$itemnames[] = $item['name'] . ' x ' . $item['qty'];
					}
				endforeach;
			endif;
			$params    = array(
				"authentication:$this->apikey",
				'cache-control: no-cache',
			);
			$itemnames = 'Order ' . $order->get_order_number() . ' - ' . implode(', ', $itemnames);
			$params    = array(
				'body' => 'SecurityCode=' . $this->secret . '&MerchantID=' . $this->merchantid . '&Amount=' . number_format($order->get_total(), 8, '.', '') . '&AltCoinID=' . $this->altcoinid . '&output=json&inputCurrency=' . get_woocommerce_currency() . '&CustomerReferenceNr=' . $orderid . '&returnurl=' . rawurlencode(esc_url($this->get_return_url($order))) . '&transactionconfirmurl=' . site_url('/?wc-api=Cointopay') . '&transactionfailurl=' . rawurlencode(esc_url($order->get_cancel_order_url())),
			);
			$url       = 'https://app.cointopay.com/MerchantAPI?Checkout=true';
			$response  = wp_safe_remote_post($url, $params);
			if (( false === is_wp_error($response) ) && ( 200 === $response['response']['code'] ) && ( 'OK' === $response['response']['message'] )) {
				$results = json_decode($response['body']);
				return array(
					'result'   => 'success',
					'redirect' => $results->shortURL . "?crypto=1",
				);
			}

		}//end process_payment()


		/**
		 * Check for valid Cointopay server callback
		 *
		 * @return string
		 **/
		public function check_cointopay_response() {
			global $woocommerce;
			$woocommerce->cart->empty_cart();
			$orderid          = ( !empty(intval($_REQUEST['CustomerReferenceNr'])) ) ? intval($_REQUEST['CustomerReferenceNr']) : 0;
			$ordstatus        = ( !empty(sanitize_text_field($_REQUEST['status'])) ) ? sanitize_text_field($_REQUEST['status']) : '';
			$ordtransactionid = ( !empty(sanitize_text_field($_REQUEST['TransactionID'])) ) ? sanitize_text_field($_REQUEST['TransactionID']) : '';
			$ordconfirmcode   = ( !empty(sanitize_text_field($_REQUEST['ConfirmCode'])) ) ? sanitize_text_field($_REQUEST['ConfirmCode']) : '';
			$notenough        = ( isset($_REQUEST['notenough']) ) ? intval($_REQUEST['notenough']) : '';

			$order    = new WC_Order($orderid);
			$data     = array(
				'mid'           => $this->merchantid,
				'TransactionID' => $ordtransactionid,
				'ConfirmCode'   => $ordconfirmcode,
			);
			$transactionData = $this->validate_order($data);
			if(200 !== $transactionData['status_code']){
				get_header();
				echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">'.$transactionData['message'] .'</p><a href="' . esc_url(site_url()) . '" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br></div></div></div>';
				 get_footer();
					exit;
			}
			else{
				if($transactionData['data']['Security'] != $ordconfirmcode){
					get_header();
				echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">Data mismatch! ConfirmCode doesn\'t match</p><a href="' . esc_url(site_url()) . '" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br></div></div></div>';
				get_footer();
					exit;
				}
				elseif($transactionData['data']['CustomerReferenceNr'] != $orderid){
					get_header();
				echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">Data mismatch! CustomerReferenceNr doesn\'t match</p><a href="' . esc_url(site_url()) . '" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br></div></div></div>';
				get_footer();
					exit;
				}
				elseif($transactionData['data']['TransactionID'] != $ordtransactionid){
					get_header();
				echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">Data mismatch! TransactionID doesn\'t match</p><a href="' . esc_url(site_url()) . '" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br></div></div></div>';
				get_footer();
					exit;
				}
				elseif($transactionData['data']['Status'] != $ordstatus){
					get_header();
				echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">Data mismatch! status doesn\'t match. Your order status is '. $transactionData['data']['Status'].'</p><a href="' . esc_url(site_url()) . '" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br></div></div></div>';
				get_footer();
					exit;
				}

				}
				if (( 'paid' === $ordstatus ) && ( 0 === $notenough )) {
					// Do your magic here, and return 200 OK to Cointopay.
					if ('processing' === $order->status) {
						$order->update_status('processing', sprintf(__('IPN: Payment completed notification from Cointopay', 'woocommerce')));
					} else {
						$order->payment_complete();
						$order->update_status('processing', sprintf(__('IPN: Payment completed notification from Cointopay', 'woocommerce')));
					}
					
					$order->save();
					
					$order->add_order_note( __( 'IPN: Update status event for Cointopay to status COMPLETED:', 'woocommerce' ) . ' ' . $orderid);

					get_header();
					echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#0fad00">Success!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/check.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">The payment has been received and confirmed successfully.</p><a href="' . esc_url(site_url()) . '" style="background-color: #0fad00;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br><br><br></div></div></div>';
					get_footer();
					exit;
				} elseif ('failed' === $ordstatus && 1 === $notenough) {
					$order->update_status('on-hold', sprintf(__('IPN: Payment failed notification from Cointopay because notenough', 'woocommerce')));
					get_header();
					echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">The payment has been failed.</p><a href="' . esc_url(site_url()) . '" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >Back</a><br><br><br><br></div></div></div>';
					get_footer();
					exit;
				} else {
					$order->update_status('failed', sprintf(__('IPN: Payment failed notification from Cointopay', 'woocommerce')));
					get_header();
					echo '<div class="container" style="text-align: center;"><div><div><br><br><h2 style="color:#ff0000">Failure!</h2><img style="margin:auto;"  src="' . esc_url(plugins_url('images/fail.png', __FILE__)) . '"><p style="font-size:20px;color:#5C5C5C;">The payment has been failed.</p><a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color: white;padding:15px 32px;text-align: center;text-decoration:none;display:inline-block;font-size:16px;">Back</a><br><br><br><br></div></div></div>';
					get_footer();
					exit;
				}//end if
			

		}//end check_cointopay_response()


		/**
		 * Adds error message when not configured the api key.
		 *
		 * @return string Error Mensage.
		 */
		public function apikey_missingmessage() {
			$message  = '<div class="notice notice-info is-dismissible">';
			$message .= '<p><strong>Gateway Disabled</strong> You should enter your API key in Cointopay configuration. <a href="' . get_admin_url() . 'admin.php?page=wc-settings&amp;tab=checkout&amp;section=cointopay">Click here to configure</a></p>';
			$message .= '</div>';

			echo $message;

		}//end apikey_missingmessage()


		/**
		 * Adds error message when not configured the secret.
		 *
		 * @return String Error Mensage.
		 */
		public function secret_missingmessage() {
			$message  = '<div class="notice notice-info is-dismissible">';
			$message .= '<p><strong>Gateway Disabled</strong> You should check your MerchantID and SecurityCode in Cointopay configuration. <a href="' . get_admin_url() . 'admin.php?page=wc-settings&amp;tab=checkout&amp;section=cointopay">Click here to configure!</a></p>';
			$message .= '</div>';

			echo $message;

		}//end secret_missingmessage()


		/**
		 * Check for valid Cointopay server callback
		 *
		 * @param array $data $args {
		 *                    Optional. An array of arguments.
		 *
		 * @type   type $key Description. Default 'value'. Accepts 'value', 'value'.
		 *    (aligned with Description, if wraps to a new line)
		 * @type   type $key Description.
		 * }
		 * @return string
		 **/
		public function validate_order( $data) {
			$params = array(
				'body'           => 'MerchantID=' . $data['mid'] . '&Call=Transactiondetail&APIKey=a&output=json&ConfirmCode=' . $data['ConfirmCode'],
				'authentication' => 1,
				'cache-control'  => 'no-cache',
			);

			$url = 'https://app.cointopay.com/v2REAPI?';

			$response = wp_safe_remote_post($url, $params);
			$results  = json_decode($response['body'], true);

			return $results;

		}//end validate_order()
		
		public function Cointopay_Crypto_Gateway_admin_js() {
			wp_enqueue_script( 'ctp-crypto-custom-js', plugins_url('js/ctp_crypto_custom_js.js', __FILE__), array(), '1.0.0', true );
			wp_localize_script( 'ctp-crypto-custom-js', 'ajaxurlctp', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); 
		}
		
		
		//* Do NOT include the opening php tag shown above. Copy the code shown below.

		//* Add select field to the checkout page
		
		public function cointopay_crypto_select_checkout_field( $checkout ) {
				
				if($this->enabled === 'yes' && $this->merchantid !== ''){
					// The user link
					$cointopay_crypto_merchant_id = $this->merchantid;

					woocommerce_form_field( 'cointopay_crypto_alt_coin', array(
						'type'          => 'select',
						'class'         => array( 'cointopay_crypto_alt_coin' ),
						'label'         => __( 'Crypto Selection for Cointopay Woocommerce' ),
						'options'       => array(
						'blank'		=> __( 'Select Alt Coin', 'woocommerce' ),
						)
				 ),

					$checkout->get_value( 'cointopay_crypto_alt_coin' ));
				}
		}
		
		public function cointopay_crypto_process_custom_payment(){
			if($_POST['payment_method'] != 'cointopay')
				return;

			if( !isset($_POST['cointopay_crypto_alt_coin']) || empty($_POST['cointopay_crypto_alt_coin']) )
				wc_add_notice( __( 'Please select valid Alt Coin', $this->domain ), 'error' );

		}
		//* Do NOT include the opening php tag shown above. Copy the code shown below.
		//* Update the order meta with field value
		 
		 public function cointopay_crypto_select_checkout_update_order_meta( $order_id ) {
			if (isset($_POST['cointopay_crypto_alt_coin']) && $_POST['payment_method'] == 'cointopay') update_post_meta( $order_id, 'cointopay_crypto_alt_coin', sanitize_text_field($_POST['cointopay_crypto_alt_coin']));
		 }
		
		public function cointopay_crypto_checkout_hidden_field( $checkout ) {
			
				if($this->enabled === 'yes' && $this->merchantid !== ''){
					// The user link
					$cointopay_crypto_merchant_id = $this->merchantid;

					// Output the hidden link
				   echo '<input type="hidden" class="input-hidden" name="cointopay_crypto_merchant_id" id="cointopay_crypto_merchant_id" value="' . $cointopay_crypto_merchant_id . '" />';
				}
		}


	}//end class
		add_action( 'wp_ajax_nopriv_getCTPMerchantCoinsByAjax', 'getCTPMerchantCoinsByAjax' );
		add_action( 'wp_ajax_getCTPMerchantCoinsByAjax', 'getCTPMerchantCoinsByAjax' );
		function getCTPMerchantCoinsByAjax()
		{
			$merchantId = 0;
			$merchantId = intval($_REQUEST['merchant']);
			if(isset($merchantId) && $merchantId !== 0)
			{
				$option = '';
				$arr = getCTPMerchantCoins($merchantId);
				foreach($arr as $key => $value)
				{
					$option .= '<option value="'.$key.'">'.$value.'</option>';
				}
				
				echo $option;exit();
			}
		}

		function getCTPMerchantCoins($merchantId)
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
}//end if