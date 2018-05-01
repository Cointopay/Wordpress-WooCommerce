<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: WooCommerce Cointopay.com
Description: Extends WooCommerce with crypto payments gateway.
Version: 0.2
Author: Cointopay
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(is_plugin_active( 'woocommerce/woocommerce.php') )
{
	/**
	* Add the Gateway to WooCommerce
	**/
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_Cointopay_gateway');
	add_action('plugins_loaded', 'woocommerce_Cointopay_init', 0);
	function woocommerce_add_Cointopay_gateway($methods)
	{
		$methods[] = 'Cointopay';
		return $methods;
	}

	function woocommerce_Cointopay_init()
	{
		if (!class_exists('WC_Payment_Gateway'))
		{
			return;
		}

		class Cointopay extends WC_Payment_Gateway
		{
			public function __construct()
			{

				$this->id = 'cointopay';
				$this->icon = plugins_url( 'images/crypto.png', __FILE__ );
				$this->has_fields = false;

				$this->init_form_fields();
				$this->init_settings();

				$this->title = $this->get_option('title');
				$this->description = $this->get_option('description');
				$this->altcoinid = $this->get_option('altcoinid');
				$this->merchantid = $this->get_option('merchantid');

				$this->apikey = '1';
				$this->secret = $this->get_option('secret');
				//$this->debug = $this->get_option('debug');

				$this->msg['message'] = "";
				$this->msg['class'] = "";

				add_action('init', array(&$this, 'check_Cointopay_response'));

				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));

				add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( &$this, 'check_Cointopay_response' ) );

				// Valid for use.
				$this->enabled = (($this->settings['enabled'] && !empty($this->apikey) && !empty($this->secret)) ? 'yes' : 'no');

				// Checking if apikey is not empty.
				$this->apikey == '' ? add_action( 'admin_notices', array( &$this, 'apikey_missing_message' ) ) : '';

				// Checking if app_secret is not empty.
				$this->secret == '' ? add_action( 'admin_notices', array( &$this, 'secret_missing_message' ) ) : '';
			}

			function init_form_fields()
			{
				$this->form_fields = array(
				'enabled' => array(
				'title' => __( 'Enable/Disable', 'Cointopay' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Cointopay', 'Cointopay' ),
				'default' => 'yes'
				),
				'title' => array(
				'title' => __( 'Title', 'Cointopay' ),
				'type' => 'text',
				'description' => __( 'This controls the title the user can see during checkout.', 'Cointopay' ),
				'default' => __( 'Cointopay', 'Cointopay' )
				),
				'description' => array(
				'title' => __( 'Description', 'Cointopay' ),
				'type' => 'textarea',
				'description' => __( 'This controls the title the user can see during checkout.', 'Cointopay' ),
				'default' => __( 'You will be redirected to cointopay.com to complete your purchase.', 'Cointopay' )
				),
				'merchantid' => array(
				'title' => __( 'Your MerchantID', 'Cointopay' ),
				'type' => 'text',
				'description' => __( 'Please enter your Cointopay Merchant ID', 'Cointopay' ) . ' ' . sprintf( __( 'You can get this information in: %sCointopay Account%s.', 'Cointopay' ), '<a href="https://cointopay.com" target="_blank">', '</a>' ),
				'default' => ''
				),
				'altcoinid' => array(
				'title' => __( 'Default Checkout AltCoinID', 'Cointopay' ),
				'type' => 'text',
				'description' => __( 'Please enter your Preferred AltCoinID (1 for bitcoin)', 'Cointopay' ) . ' ' . sprintf( __( 'You can get this information in: %sCointopay Account%s.', 'Cointopay' ), '<a href="https://cointopay.com" target="_blank">', '</a>' ),
				'default' => '1'
				),
				//'apikey' => array(
				//	'title' => __( 'Invoice API Key', 'Cointopay' ),
				//	'type' => 'password',
				//	'description' => __( 'Please enter your Cointopay Merchant API key', 'Cointopay' ) . ' ' . sprintf( __( 'You can get this information in: %sCointopay Account%s.', 'Cointopay' ), '<a href="https://cointopay.com" target="_blank">', '</a>' ),
				//	'default' => ''
				//),
				'secret' => array(
				'title' => __( 'SecurityCode', 'Cointopay' ),
				'type' => 'password',
				'description' => __( 'Please enter your Cointopay SecurityCode', 'Cointopay' ) . ' ' . sprintf( __( 'You can get this information in: %sCointopay Account%s.', 'Cointopay' ), '<a href="https://cointopay.com" target="_blank">', '</a>' ),
				'default' => ''
				),
				/*'debug' => array(
				'title' => __( 'Debug Log', 'Cointopay' ),
				'type' => 'checkbox',
				'label' => __( 'Enable logging', 'Cointopay' ),
				'default' => 'no',
				'description' => __( 'Log Cointopay events, such as API requests, inside <code>woocommerce/logs/Cointopay.txt</code>', 'Cointopay'  ),
				)*/
				);
			}

			public function admin_options()
			{
				?>
				<h3><?php _e('Cointopay Checkout', 'Cointopay');?></h3>

				<div id="wc_get_started">
					<span class="main"><?php _e('Provides a secure way to accept crypto currencies.', 'Cointopay'); ?></span>
					<p><a href="https://cointopay.com/index.jsp?#Register" target="_blank" class="button button-primary"><?php _e('Join free', 'Cointopay'); ?></a> <a href="https://cointopay.com" target="_blank" class="button"><?php _e('Learn more about WooCommerce and Cointopay', 'Cointopay'); ?></a></p>
				</div>

				<table class="form-table">
					<?php $this->generate_settings_html(); ?>
				</table>
				<?php
			}

			/**
			*  There are no payment fields for Cointopay, but we want to show the description if set.
			**/
			function payment_fields()
			{
				if ($this->description)
				echo wpautop(wptexturize($this->description));
			}

			/**
			* Process the payment and return the result
			**/
			function process_payment($order_id)
			{
				global $woocommerce;
				$order = wc_get_order($order_id);

				$item_names = array();

				if (sizeof($order->get_items()) > 0) : foreach ($order->get_items() as $item) :
				if ($item['qty']) $item_names[] = $item['name'] . ' x ' . $item['qty'];
				endforeach; endif;
				$params = array(
				"authentication:$this->apikey",
				'cache-control: no-cache',
				);
				$item_name = sprintf( __('Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode(', ', $item_names);

				$ch = curl_init();

				curl_setopt_array($ch, array(
				CURLOPT_URL => 'https://cointopay.com/MerchantAPI?Checkout=true',
				//CURLOPT_USERPWD => $this->apikey,
				CURLOPT_POSTFIELDS => 'SecurityCode=' . $this->secret . '&MerchantID=' . $this->merchantid . '&Amount=' . number_format($order->get_total(), 8, '.', '') . '&AltCoinID=' . $this->altcoinid . '&output=json&inputCurrency=' . get_woocommerce_currency() . '&CustomerReferenceNr=' . $order_id . '&returnurl='.rawurlencode(esc_url($this->get_return_url($order))).'&transactionconfirmurl='.site_url('/?wc-api=Cointopay') .'&transactionfailurl='.rawurlencode(esc_url($order->get_cancel_order_url())),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HTTPHEADER => $params,
				CURLOPT_USERAGENT => $this->apikey,
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC
				)
				);

				$redirect = curl_exec($ch);
				curl_close($ch);
				if($redirect){
					$results = json_decode($redirect);
					return array(
					'result' => 'success',
					'redirect' => $results->RedirectURL
					);
				}
			}

			/**
			* Check for valid Cointopay server callback
			**/
			function check_Cointopay_response()
			{
				$Cointopay = $_REQUEST;
				$order_id = intval($Cointopay['CustomerReferenceNr']);

				$order = new WC_Order($order_id);
				if ($Cointopay['status'] == 'paid') {
					// Do your magic here, and return 200 OK to Cointopay.

					if ($order->status == 'completed')
					{
						$order->update_status( 'completed', sprintf( __( 'IPN: Payment completed notification from Cointopay', 'woocommerce' ) ) );
					}
					else
					{
						$order->payment_complete();
						$order->update_status( 'completed', sprintf( __( 'IPN: Payment completed notification from Cointopay', 'woocommerce' ) ) );

					}

					echo '<div class="container" style="text-align: center;"><div><div>
					<br><br>
					<h2 style="color:#0fad00">Success!</h2>
					<img src="'.plugins_url( 'images/check.png', __FILE__ ).'">
					<p style="font-size:20px;color:#5C5C5C;">The payment has been received and confirmed successfully.</p>
					<a href="'.site_url().'" style="background-color: #0fad00;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
					<br><br>
					<p>Redirecting in 5 Seconds.</p>
					<br><br>
					</div>
					</div>
					</div>';
					echo "<script>
					setTimeout(function () {
					window.location.href= '".site_url()."';
					}, 5000);
					</script>";
					//header('HTTP/1.1 200 OK');
					exit;
				}
				else if ($Cointopay['status'] == 'failed' && $Cointopay['notenough'] == 1) {

					$order->update_status( 'on-hold', sprintf( __( 'IPN: Payment failed notification from Cointopay because notenough', 'woocommerce' ) ) );
					echo '<div class="container" style="text-align: center;"><div><div>
					<br><br>
					<h2 style="color:#ff0000">Failure!</h2>
					<img src="'.plugins_url( 'images/fail.png', __FILE__ ).'">
					<p style="font-size:20px;color:#5C5C5C;">The payment has been failed.</p>
					<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
					<br><br>
					<p>Redirecting in 5 Seconds.</p>
					<br><br>
					</div>
					</div>
					</div>';
					echo "<script>
					setTimeout(function () {
					window.location.href= '".site_url()."';
					}, 5000);
					</script>";
					exit;
				}
				else{

					$order->update_status( 'failed', sprintf( __( 'IPN: Payment failed notification from Cointopay', 'woocommerce' ) ) );
					echo '<div class="container" style="text-align: center;"><div><div>
					<br><br>
					<h2 style="color:#ff0000">Failure!</h2>
					<img src="'.plugins_url( 'images/fail.png', __FILE__ ).'">
					<p style="font-size:20px;color:#5C5C5C;">The payment has been failed.</p>
					<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
					<br><br>
					<p>Redirecting in 5 Seconds.</p>
					<br><br>
					</div>
					</div>
					</div>';
					echo "<script>
					setTimeout(function () {
					window.location.href= '".site_url()."';
					}, 5000);
					</script>";
					exit;
				}
			}

			/**
			* Adds error message when not configured the api key.
			*
			* @return string Error Mensage.
			*/
			public function apikey_missing_message() {
				$message = '<div class="error">';
				$message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should enter your API key in Cointopay configuration. %sClick here to configure!%s' , 'wcCointopay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&amp;tab=checkout&amp;section=wc_Cointopay">', '</a>' ) . '</p>';
				$message .= '</div>';

				echo $message;
			}

			/**
			* Adds error message when not configured the secret.
			*
			* @return String Error Mensage.
			*/
			public function secret_missing_message() {
				$message = '<div class="error">';
				$message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should check your SecurityCode in Cointopay configuration. %sClick here to configure!%s' , 'wcCointopay' ), '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&amp;tab=checkout&amp;section=wc_Cointopay">', '</a>' ) . '</p>';
				$message .= '</div>';

				echo $message;
			}
		}
	}
}