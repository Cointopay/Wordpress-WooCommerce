<?php
/**
 * Define Cointopay Class
 *
 * @package  WooCommerce
 * @author   Cointopay <info@cointopay.com>
 * @link     cointopay.com
 */
 
if (!defined('ABSPATH')) exit;

if ( class_exists('WC_Cointopay_Gateway') ) {
    return;
}

class WC_Cointopay_Gateway extends WC_Payment_Gateway {
	/**
	 * Define Cointopay Class constructor
	 **/
	public function __construct() {
		$this->id   = 'cointopay';
		$this->icon = WC_Cointopay_Payments::plugin_url().'/assets/images/crypto.png';

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option('title');
		$this->enabled          = $this->get_option('enabled');
		$this->description = $this->get_option('description') ?? 'You will be redirected to cointopay.com to complete your purchase.';
		$this->altcoinid   = 666;
		$this->merchantid  = $this->get_option('merchantid');

		$this->apikey         = '1';
		$this->secret         = $this->get_option('secret');
		$this->msg['message'] = '';
		$this->msg['class']   = '';

		add_action('init', array(&$this, 'check_cointopay_response'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
		add_action('woocommerce_api_' . strtolower(get_class($this)), array( &$this, 'check_cointopay_response' ));
		//add_action('wp_enqueue_scripts', array(&$this, 'Cointopay_Crypto_Gateway_admin_js' ));
	
		
		add_action('woocommerce_checkout_process', array($this, 'cointopay_crypto_process_custom_payment' ));
		add_action('woocommerce_checkout_update_order_meta', array($this, 'cointopay_crypto_select_checkout_update_order_meta' ));

		// Valid for use.
		if (empty($this->merchantid)) {
			$this->enabled = 'no';
		} elseif(empty($this->secret)) {
			$this->enabled = 'no';
		}

		// Checking if apikey is not empty.
		if (empty($this->merchantid)) {
			add_action('admin_notices', array( &$this, 'apikey_missingmessage' ));
		}

		// Checking if app_secret is not empty.
		if (empty($this->secret)) {
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

			'enabled' => array(
				'title'   => __('Enable/Disable', 'wc-cointopay-com'),
				'type'    => 'checkbox',
				'label'   => __('Enable Cointopay', 'wc-cointopay-com'),
				'default' => 'yes',
			),

			'title' => array(
				'title'       => __('Title', 'wc-cointopay-com'),
				'type'        => 'text',
				'description' => __('This controls the title the user can see during checkout.', 'wc-cointopay-com'),
				'default'     => __('Cointopay', 'wc-cointopay-com'),
			),

			'description' => array(
				'title'       => __('Description', 'wc-cointopay-com'),
				'type'        => 'textarea',
				'description' => __('This controls the title the user can see during checkout.', 'wc-cointopay-com'),
				'default'     => __('You will be redirected to cointopay.com to complete your purchase.', 'wc-cointopay-com'),
			),

			'merchantid' => array(
				'title'    => __('Your MerchantID', 'wc-cointopay-com'),
				'type'     => 'text',
				'required' => true,

				'description' => sprintf(
				    /* translators: 1: Cointopay account URL */
					__('Please enter your Cointopay Merchant ID. You can get this information in: <a href="%s" target="_blank" rel="noopener noreferrer">Cointopay Account</a>.', 'wc-cointopay-com'),
					esc_url('https://cointopay.com')
				),
				'default' => '',
			),

			'secret' => array(
				'title' => __('SecurityCode', 'wc-cointopay-com'),
				'type'  => 'password',

				'description' => sprintf(
					/* translators: 1: Cointopay account URL */
					__('Please enter your Cointopay SecurityCode. You can get this information in: <a href="%s" target="_blank" rel="noopener noreferrer">Cointopay Account</a>.', 'wc-cointopay-com'),
						esc_url('https://cointopay.com')
					),

				'default' => '',
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
			<h3><?php esc_html_e('Cointopay Checkout', 'wc-cointopay-com'); ?></h3>

			<div id="wc_get_started">
			<span class="main"><?php esc_html_e('Provides a secure way to accept crypto currencies.', 'wc-cointopay-com'); ?></span>
			<p><a href="https://app.cointopay.com/index.jsp?#Register" target="_blank" class="button button-primary"><?php esc_html_e('Join free', 'wc-cointopay-com'); ?></a> <a href="https://cointopay.com" target="_blank" class="button"><?php esc_html_e('Learn more about WooCommerce and Cointopay', 'wc-cointopay-com'); ?></a></p>
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

		if (!empty($this->description)) {
			echo wp_kses_post($this->description);
		}

		if ($this->enabled === 'yes' && !empty($this->merchantid)) {

			$merchant_id = $this->merchantid;

			$coins = $this->cointopay_get_merchantCoins($merchant_id);
			$noncealt = wp_create_nonce('cointopay_coints_nonce');

			echo '<br><br>';
			echo '<label>' . esc_html__('Crypto Selection for Cointopay WooCommerce', 'wc-cointopay-com') . '</label>';

			echo '<select name="cointopay_crypto_alt_coin" class="cointopay_crypto_alt_coin" style="width:100%;">';

			echo '<option value="">' . esc_html__('Select Alt Coin', 'wc-cointopay-com') . '</option>';

			if (!empty($coins) && is_array($coins)) {
				foreach ($coins as $key => $value) {
					echo '<option value="' . esc_attr($key) . '">' . esc_html($value) . '</option>';
				}
			}

			echo '</select>';
			echo '<input type="hidden" class="input-hidden" name="cointopay_calt_nonce" id="cointopay_calt_nonce" value="' . esc_html($noncealt) . '" />';
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
		$alt_nonce = !empty(sanitize_text_field(wp_unslash($_POST['alt_nonce']))) ? sanitize_text_field(wp_unslash($_POST['alt_nonce'])) : null;
		if($alt_nonce) {
			if ( ! wp_verify_nonce( $alt_nonce, 'cointopay_coints_nonce' ) ) {
				throw new Exception('Invalid nonce');
			}
		}
		$altcoin = !empty(sanitize_text_field(wp_unslash($_POST['alt_coin']))) ? sanitize_text_field(wp_unslash($_POST['alt_coin'])) : null;
		
		if (get_post_meta( $orderid, 'cointopay_crypto_alt_coin', true)) {
			$this->altcoinid = get_post_meta( $orderid, 'cointopay_crypto_alt_coin', true);
		} elseif($altcoin) {
			$this->altcoinid = $altcoin;
		} elseif($order->get_meta('alt_coin')) {
			$this->altcoinid = $order->get_meta('alt_coin');
		}

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
		$customer_email = $order->get_billing_email();
		$params = array(
			'body' => array(
				'email'                 => sanitize_email($customer_email),
				'SecurityCode'          => sanitize_text_field($this->secret),
				'MerchantID'            => sanitize_text_field($this->merchantid),
				'Amount'                => number_format((float) $order->get_total(), 8, '.', ''),
				'AltCoinID'             => sanitize_text_field($this->altcoinid),
				'output'                => 'json',
				'inputCurrency'         => get_woocommerce_currency(),
				'CustomerReferenceNr'   => sanitize_text_field($orderid . '-' . $order->get_order_number()),
				'returnurl'             => esc_url_raw($this->get_return_url($order)),
				'transactionconfirmurl' => esc_url_raw(site_url('/?wc-api=Cointopay')),
				'transactionfailurl'    => esc_url_raw($order->get_cancel_order_url()),
			),
		);
		$url       = 'https://app.cointopay.com/MerchantAPI?Checkout=true';
		$response  = wp_safe_remote_post($url, $params);
		if (( false === is_wp_error($response) ) && ( 200 === $response['response']['code'] ) && ( 'OK' === $response['response']['message'] )) {
			$results = json_decode($response['body']);
			return array(
				'result'   => 'success',
				'redirect' => $results->shortURL . "?crypto=1",
			);
		} elseif (isset($response['body'])) {
			$results = json_decode($response['body']);
			if (is_string($results)) {
				wc_add_notice( $response['body'], 'error' );
				return;
			}
		}

	}//end process_payment()
	
	private function extractOrderId(string $customer_reference_nr)
	{
		return intval(explode('-', $customer_reference_nr)[0]);
	}


	/**
	 * Check for valid Cointopay server callback
	 *
	 * @return string
	 **/
	public function check_cointopay_response() {
		if (is_admin()) {
			return;
		}
		// Nonce verification is not used here because this is a server-to-server callback.
		// Security is handled via transaction validation and confirm code verification.
		if(isset($_GET['wc-api']) && isset($_GET['CustomerReferenceNr']) && isset($_GET['TransactionID']))
		{
        $ctp_crypto = (isset($_GET['wc-api'])) ? sanitize_text_field(wp_unslash($_GET['wc-api'])) : '';
		if ($ctp_crypto == 'Cointopay') {
		global $woocommerce;
		$woocommerce->cart->empty_cart();
		$orderid                = (isset($_GET['CustomerReferenceNr'])) ? $this->extractOrderId(sanitize_text_field(wp_unslash($_GET['CustomerReferenceNr']))) : 0;
		$ordstatus        = ( !empty(sanitize_text_field(wp_unslash($_GET['status']))) ) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
		$ordtransactionid = ( !empty(sanitize_text_field(wp_unslash($_GET['TransactionID']))) ) ? sanitize_text_field(wp_unslash($_GET['TransactionID'])) : '';
		$ordconfirmcode   = ( !empty(sanitize_text_field(wp_unslash($_GET['ConfirmCode']))) ) ? sanitize_text_field(wp_unslash($_GET['ConfirmCode'])) : '';
		$notenough        = ( isset($_GET['notenough']) ) ? intval($_GET['notenough']) : '';

		$order    = new WC_Order($orderid);
		$data     = array(
			'mid'           => $this->merchantid,
			'TransactionID' => $ordtransactionid,
			'ConfirmCode'   => $ordconfirmcode,
		);
		$transactionData = $this->validate_order($data);
		
		if(200 !== $transactionData['status_code']){
			get_header();
			echo '<div class="container" style="text-align: center;">
			<div><div><br><br>
			<h2 style="color:#ff0000">Failure!</h2>
			<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
			<p style="font-size:20px;color:#5C5C5C;">' . esc_html($transactionData['message'] ?? '') . '</p>
			<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-align:center;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
			<br><br>
			</div></div></div>';
			get_footer();
				exit;
		}
		else {

			$transaction_order_id = $this->extractOrderId(
				$transactionData['data']['CustomerReferenceNr'] ?? ''
			);

			$message = $transactionData['message'] ?? '';

			if (($transactionData['data']['Security'] ?? '') != $ordconfirmcode) {

				get_header();

				echo '<div class="container" style="text-align: center;">
						<div><div><br><br>
						<h2 style="color:#ff0000">Failure!</h2>
						<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
						<p style="font-size:20px;color:#5C5C5C;">' . esc_html__('Data mismatch! ConfirmCode doesn\'t match', 'wc-cointopay-com') . '</p>
						<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
						<br><br>
						</div></div></div>';

				get_footer();
				exit;
			}

			elseif ($transaction_order_id != $orderid) {

				get_header();

				echo '<div class="container" style="text-align: center;">
						<div><div><br><br>
						<h2 style="color:#ff0000">Failure!</h2>
						<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
						<p style="font-size:20px;color:#5C5C5C;">' . esc_html__('Data mismatch! CustomerReferenceNr doesn\'t match', 'wc-cointopay-com') . '</p>
						<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
						<br><br>
						</div></div></div>';

				get_footer();
				exit;
			}

			elseif (($transactionData['data']['TransactionID'] ?? '') != $ordtransactionid) {

				get_header();

				echo '<div class="container" style="text-align: center;">
						<div><div><br><br>
						<h2 style="color:#ff0000">Failure!</h2>
						<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
						<p style="font-size:20px;color:#5C5C5C;">' . esc_html__('Data mismatch! TransactionID doesn\'t match', 'wc-cointopay-com') . '</p>
						<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
						<br><br>
						</div></div></div>';

				get_footer();
				exit;
			}

			elseif (($transactionData['data']['Status'] ?? '') != $ordstatus) {

				get_header();

				echo '<div class="container" style="text-align: center;">
						<div><div><br><br>
						<h2 style="color:#ff0000">Failure!</h2>
						<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
						<p style="font-size:20px;color:#5C5C5C;">' .
							esc_html__('Data mismatch! status doesn\'t match. Your order status is ', 'wc-cointopay-com') .
							esc_html($transactionData['data']['Status'] ?? '') .
						'</p>
						<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
						<br><br>
						</div></div></div>';

				get_footer();
				exit;
			}
		}//end if
		
		if (( 'paid' === $ordstatus ) && ( 0 === $notenough )) {
			$status = $order->get_status();

			if ( 'completed' === $status || 'processing' === $status ) {
				// Do nothing if order is already completed or processing
				//$new_status = $status;
			} else {
				$order->payment_complete(); // This automatically sets status to processing
				$new_status = $order->get_status();
				/* translators: 1: previous order status, 2: new order status, 3: order ID */
				$message = sprintf(__( 'IPN: Update event for Cointopay from status %1$s to %2$s: %3$s', 'wc-cointopay-com' ),
					$status,
					$new_status,
					$orderid
				);

				$order->add_order_note( $message );
			}
			
			get_header();

			echo '<div class="container" style="text-align: center;">
					<div><div><br><br>
					<h2 style="color:#0fad00">Success!</h2>
					<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/check.png') . '">
					<p style="font-size:20px;color:#5C5C5C;">' .
						esc_html__('The payment has been received and confirmed successfully.', 'wc-cointopay-com') .
					'</p>
					<a href="' . esc_url(site_url()) . '" style="background-color:#0fad00;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
					<br><br>
					</div></div></div>';

			get_footer();
			exit;
		} elseif ('failed' === $ordstatus && 1 === $notenough) {
			$order->update_status('on-hold', sprintf(__('IPN: Payment failed notification from Cointopay because notenough', 'wc-cointopay-com')));
			get_header();

			echo '<div class="container" style="text-align: center;">
					<div><div><br><br>
					<h2 style="color:#ff0000">Failure!</h2>
					<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
					<p style="font-size:20px;color:#5C5C5C;">' .
						esc_html__('The payment has been failed.', 'wc-cointopay-com') .
					'</p>
					<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
					<br><br>
					</div></div></div>';

			get_footer();
			exit;
		} else {
			$order->update_status('failed', sprintf(__('IPN: Payment failed notification from Cointopay', 'wc-cointopay-com')));
			get_header();

			echo '<div class="container" style="text-align: center;">
					<div><div><br><br>
					<h2 style="color:#ff0000">Failure!</h2>
					<img style="margin:auto;" src="' . esc_url(WC_Cointopay_Payments::plugin_url() . '/assets/images/fail.png') . '">
					<p style="font-size:20px;color:#5C5C5C;">' .
						esc_html__('The payment has been failed.', 'wc-cointopay-com') .
					'</p>
					<a href="' . esc_url(site_url()) . '" style="background-color:#ff0000;border:none;color:white;padding:15px 32px;text-decoration:none;display:inline-block;font-size:16px;">Back</a>
					<br><br>
					</div></div></div>';

			get_footer();
			exit;
		}//end if
		
		}
		}
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

		echo wp_kses_post($message);

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

		echo wp_kses_post($message);

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
	
	
	//* Do NOT include the opening php tag shown above. Copy the code shown below.

	public function cointopay_crypto_process_custom_payment(){
		$alt_nonce = !empty(sanitize_text_field(wp_unslash($_POST['cointopay_calt_nonce']))) ? sanitize_text_field(wp_unslash($_POST['cointopay_calt_nonce'])) : null;
		if($alt_nonce) {
			if ( ! wp_verify_nonce( $alt_nonce, 'cointopay_coints_nonce' ) ) {
				wc_add_notice( __( 'Invalid nonce', 'wc-cointopay-com' ), 'error' );
			}
		}
		if(isset($_POST['payment_method']) && sanitize_text_field(wp_unslash($_POST['payment_method'])) != 'cointopay')
			return;

		if( !isset($_POST['cointopay_crypto_alt_coin']) || empty(sanitize_text_field(wp_unslash($_POST['cointopay_crypto_alt_coin']))) )
			wc_add_notice( __( 'Please select valid Alt Coin', 'wc-cointopay-com' ), 'error' );

	}
	//* Do NOT include the opening php tag shown above. Copy the code shown below.
	//* Update the order meta with field value
	 
	 public function cointopay_crypto_select_checkout_update_order_meta( $order_id ) {
		$alt_nonce = !empty(sanitize_text_field(wp_unslash($_POST['cointopay_calt_nonce']))) ? sanitize_text_field(wp_unslash($_POST['cointopay_calt_nonce'])) : null;
		if($alt_nonce) {
			wp_verify_nonce( $alt_nonce, 'cointopay_coints_nonce' );
		}
		if (isset($_POST['cointopay_crypto_alt_coin']) && (isset($_POST['payment_method']) && sanitize_text_field(wp_unslash($_POST['payment_method'])) == 'cointopay')) {
			update_post_meta( $order_id, 'cointopay_crypto_alt_coin', sanitize_text_field(wp_unslash($_POST['cointopay_crypto_alt_coin'])));
		}
	 }
	
	public function validate_merchantid_field( $key, $value ) {
		if ( empty( $value ) ) {
			WC_Admin_Settings::add_error( __( 'Merchant ID is required.', 'wc-cointopay-com' ) );
		}
		return $value;
	}

	public function validate_secret_field( $key, $value ) {
		if ( empty( $value ) ) {
			WC_Admin_Settings::add_error( __( 'Security Code is required.', 'wc-cointopay-com' ) );
		}
		return $value;
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


}//end class
