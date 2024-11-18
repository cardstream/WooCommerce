<?php

use P3\SDK\Gateway;

/**
 * Gateway class
 */

class WC_Payment_Network extends WC_Payment_Gateway
{
	/**
	 * @var string
	 */
	public $lang;

	/**
	 * @var string
	 */
	public $merchant_id;

	/**
	 * @var string
	 */
	public $merchant_country_code;

	/**
	 * @var Gateway
	 */
	protected $gateway;

	/**
	 * Merchant signature key
	 * @var string
	 */
	protected $merchant_signature_key;

	/**
	 * Logging ( verbose options )
	 * @var Array
	 */
	protected static $logging_options;

	/**
	 * Module version
	 * @var String
	 */
	protected $module_version;

	/**
	 * Key used to generate the nonce for AJAX calls.
	 * @var string
	 */
	protected $nonce_key;

	public function __construct()
	{
		$configs = include(dirname(__FILE__) . '/../config.php');

		$this->has_fields			= false;
		$this->id					= str_replace(' ', '', strtolower($configs['default']['gateway_title']));
		$this->lang					= strtolower('woocommerce_' . $this->id);
		$this->icon					= plugins_url('/', dirname(__FILE__)) . 'assets/img/logo.png';
		$this->method_title			= __($configs['default']['gateway_title'], $this->lang);
		$this->method_description	= __($configs['default']['method_description'], $this->lang);
		$this->module_version 		= (file_exists(dirname(__FILE__) . '/../VERSION') ? file_get_contents(dirname(__FILE__) . '/../VERSION') : "UV");

		$this->nonce_key = '312b9f8852142b9c8fbc';

		$this->supports = array(
			'subscriptions',
			'products',
			'refunds',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_admin'
		);

		$this->init_form_fields();
		$this->init_settings();

		// Get setting values
		$this->title					= $this->settings['title'];
		$this->description				= $this->settings['description'];
		$this->merchant_signature_key	= $this->settings['signature'];
		$this->merchant_id				= $this->settings['merchantID'];
		$this->merchant_country_code	= $this->settings['merchant_country_code'];
		static::$logging_options		= (empty($this->settings['logging_options']) ? null : array_flip(array_map('strtoupper', $this->settings['logging_options'])));

		$this->gateway = new Gateway(
			$this->settings['merchantID'],
			$this->settings['signature'],
			$this->settings['gatewayURL']
		);

		// Hooks
		add_action('wp_enqueue_scripts', array($this, 'payment_scripts'),0);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

		add_action('woocommerce_api_wc_' . $this->id, array($this, 'process_response_callback'));
		add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'process_scheduled_subscription_payment_callback'));
	}

	/**
	 * Initialise Gateway Settings
	 */

	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __('Enable/Disable', $this->lang),
				'label'       => __('Enable ' . strtoupper($this->method_title), $this->lang),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __('Title', $this->lang),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', $this->lang),
				'default'     => __($this->method_title, $this->lang)
			),
			'type' => array(
				'title'       => __('Type of integration', $this->lang),
				'type'        => 'select',
				'options' => array(
					'hosted'     => 'Hosted',
					'hosted_v2'  => 'Hosted (Embedded)',
					'hosted_v3'  => 'Hosted (Modal)',
					'direct'     => 'Direct 3DS',
				),
				'description' => __('This controls method of integration.', $this->lang),
				'default'     => 'hosted'
			),
			'description' => array(
				'title'       => __('Description', $this->lang),
				'type'        => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', $this->lang),
				'default'     => $this->method_description,
			),
			'merchantID' => array(
				'title'       => __('Merchant ID', $this->lang),
				'type'        => 'text',
				'description' => __('Please enter your ' . $this->method_title . ' merchant ID', $this->lang),
				'default'     => $this->merchant_id,
				'custom_attributes' => [
					'required'        => true,
				],
			),
			'merchant_country_code' => array(
				'title'       => __('Merchant country code', $this->lang),
				'type'        => 'text',
				'description' => __('Please enter your ' . $this->method_title . ' merchant country code', $this->lang),
				'default'     => $this->merchant_country_code,
				'custom_attributes' => [
					'required'        => true,
				],
			),
			'signature' => array(
				'title'       => __('Signature Key', $this->lang),
				'type'        => 'text',
				'description' => __('Please enter the signature key for the merchant account.', $this->lang),
				'default'     => $this->merchant_signature_key,
				'custom_attributes' => [
					'required'        => true,
				],
			),
			'gatewayURL' => array(
				'title'       => __('Gateway URL', $this->lang),
				'type'        => 'text',
				'description' => __('Please enter your gateway URL.', $this->lang),
				'custom_attributes' => [
					'required'        => true,
				],
			),
			'formResponsive' => array(
				'title'       => __('Responsive form', $this->lang),
				'type'        => 'select',
				'options' => array(
					'Y'       => 'Yes',
					'N'       => 'No'
				),
				'description' => __('This controls whether the payment form is responsive.', $this->lang),
				'default'     => 'No'
			),
			'customerWalletsEnabled' => array(
				'title'       => __('Customer wallets', $this->lang),
				'type'        => 'select',
				'options' => array(
					'Y'       => 'Yes',
					'N'       => 'No'
				),
				'description' => __('This controls whether wallets is enabled for customers on the hosted form.', $this->lang),
				'default'     => 'No'
			),
			'logging_options' => array(
				'title'       => __('Logging', $this->lang),
				'type'        => 'multiselect',
				'options' => array(
					'critical'			=> 'Critical',
					'error'				=> 'Error',
					'warning'			=> 'Warning',
					'notice'			=> 'Notice',
					'info'				=> 'Info',
					'debug'				=> 'Debug',
				),
				'description' => __('This controls if logging is turned on and how verbose it is. Warning! Logging will take up additional space, especially if Debug is selected.', $this->lang),
			),
		);
	}

	/**
	 * You will need it if you want your custom credit card form, Step 4 is about it
	 */
	public function payment_fields()
	{
		if ($this->description) {
			echo wpautop(wp_kses_post($this->description));
		}

		if ($this->settings['type'] === 'direct') {

			// These default values for the device information will be replaced by
			// the actual device information (if obtainable) when Hosted Fields is being used.
			$deviceData = [
				'deviceChannel'				=> 'browser',
				'deviceIdentity'			=> (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
				'deviceTimeZone'			=> '0',
				'deviceCapabilities'		=> '',
				'deviceScreenResolution'	=> '1x1x1',
				'deviceAcceptContent'		=> (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : '*/*'),
				'deviceAcceptEncoding'		=> (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? htmlentities($_SERVER['HTTP_ACCEPT_ENCODING']) : '*'),
				'deviceAcceptLanguage'		=> (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'en-gb;q=0.001'),
			];

			$browserInfo = '';

			foreach ($deviceData as $key => $value) {
				echo '<input type="hidden" id="' . $key . '" name="browserInfo[' . $key . ']" value="' . htmlentities($value) . '" />';
			}

			$merchantID = $this->settings['merchantID'];

			echo <<<HTML

				<style class="hf-input-style">
					.hostedfield {
						font-size: 20px;
						font-weight: 500;
						padding: 4px;
					}
					.hostedfield:invalid {
						border: 1px solid #ff2b2b;
							
					}
					.hostedfield:valid {
						border: 1px solid #1fb52c;		
					}
				</style>	

				<!-- Card payment container (hosted fields) -->
				<div id="payment-options-container" class="hf-box-container">

					<input type="hidden" id="merchantID" name="merchantID" value="{$merchantID}">
					<input type="hidden" id="paymentToken" name="paymentToken" value="">
					<input type="hidden" id="hosted-fields-security-code" name="hosted-fields-security-code" value="">
					<input type="hidden" id="hosted-fields-error-input" name="hosted-fields-error-input" value="">

					<div class="hf-container-col">
						<label for="form-card-number">Card Number</label>
						<input
						id="form-card-number"
						type="hostedfield:cardNumber"
						name="card-number"
						autocomplete="cc-number"
						required
						data-hostedfield='{"stylesheet":"style.hf-input-style", "placeholder":"Card Number", "submitOnEnter":false}'>
					</div>

					<div class="hf-container-row">

						<div class="hf-container-col">
							<label for="form-card-cvv">Expiry Date</label>
							<input
							id="form-card-expiry-date"
							type="hostedfield:cardExpiryDate"
							name="card-expiry-date"
							autocomplete="cc-exp"
							required 
							data-hostedfield='{"stylesheet":"style.hf-input-style", "placeholder":"MM/YY", "submitOnEnter":false}'>
						</div>

						<div class="hf-container-col">
							<label for="form-card-cvv">CVV</label>
							<input
							id="form-card-cvv"
							type="hostedfield:cardCVV"
							name="card-cvv"
							autocomplete="cc-csc"
							required 
							data-hostedfield='{"stylesheet":"style.hf-input-style", "placeholder":"CVV", "submitOnEnter":false}'>
						</div>

					</div>

					<div id="hosted-fields-error" class="hf-container-row hosted-fields-error"></div>

				</div>

				<script>
					// Trigger payment fields ready event.
					document.body.dispatchEvent(new Event("payment-fields-ready"))
				</script>
	HTML;
			}
		// Output Module version as HTML comment on checkout page.
		echo "<!-- WC Module Version: {$this->module_version} -->";
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param $order_id
	 *
	 * @return array
	 */

	public function process_payment($order_id)
	{

		$order = new WC_Order($order_id);
		$this->debug_log('INFO', "Processing payment for order {$order_id}");

		if (in_array($this->settings['type'], ['hosted', 'hosted_v2', 'hosted_v3'], true)) {
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			);
		}

		// If this is not a Hosted Form request then verify a secuirty 
		// code was submitted with the payment token.
		if (!wp_verify_nonce($_POST['hosted-fields-security-code'], $this->nonce_key)) {
			wp_die();
		}

		$args = array_merge(
			$this->capture_order($order_id),
			$_POST['browserInfo'],
			[
				'type'                 => 1,
				'paymentToken'         => $_POST['paymentToken'],
				'remoteAddress'        => $_SERVER['REMOTE_ADDR'],
				'threeDSRedirectURL'   => add_query_arg(
					[
						'wc-api' => 'wc_' . $this->id,
						'3dsResponse' => 'Y',
					],
					home_url('/')
				),
			]
		);

		$response = $this->gateway->directRequest($args);

		setcookie('xref', $response['xref'], [
			'expires' => time() + 500,
			'path' => '/',
			'domain' => $_SERVER['HTTP_HOST'],
			'secure' => true,
			'httponly' => false,
			'samesite' => 'None'
		]);

		return $this->process_response_callback($response);
	}

	/**
	 * Process Refund
	 *
	 * Refunds a settled transactions or cancels
	 * one not yet settled.
	 *
	 * @param Interger        $amount
	 * @param Float         $amount
	 */
	public function process_refund($orderID, $amount = null, $reason = '')
	{

		// Get the transaction XREF from the order ID and the amount.
		$order = wc_get_order($orderID);
		$transactionXref = $order->get_transaction_id();
		$amountToRefund = \P3\SDK\AmountHelper::calculateAmountByCurrency($amount, $order->get_currency());

		// Check the order can be refunded.
		if (!$this->can_refund_order($order)) {
			return new WP_Error('error', __('Refund failed.', 'woocommerce'));
		}

		// Query the transaction state.
		$queryPayload = [
			'merchantID' => $this->merchant_id,
			'xref' => $transactionXref,
			'action' => 'QUERY',
		];

		// Sign the request and send to gateway.
		$transaction = $this->gateway->directRequest($queryPayload);

		if (empty($transaction['state'])) {
			return new WP_Error('error', "Could not get the transaction state for {$transactionXref}");
		}

		if ($transaction['responseCode'] == 65558) {
			return new WP_Error('error', "IP blocked primary");
		}

		// Build the refund request
		$refundRequest = [
			'merchantID' => $this->merchant_id,
			'xref' => $transactionXref,
		];

		switch ($transaction['state']) {
			case 'approved':
			case 'captured':
				// If amount to refund is equal to the total amount captured/approved then action is cancel.				
				if ($transaction['amountReceived'] === $amountToRefund || ($transaction['amountReceived'] - $amountToRefund <= 0)) {
					$refundRequest['action'] = 'CANCEL';
				} else {
					$refundRequest['action'] = 'CAPTURE';
					$refundRequest['amount'] = ($transaction['amountReceived'] - $amountToRefund);
				}
				break;

			case 'accepted':
				$refundRequest = array_merge($refundRequest, [
					'action' => 'REFUND_SALE',
					'amount' => $amountToRefund,
				]);
				break;

			default:
				return new WP_Error('error', "Transaction {$transactionXref} it not in a refundable state.");
		}

		// Sign the refund request and sign it.
		$refundResponse = $this->gateway->directRequest($refundRequest);

		// Handle the refund response
		if (empty($refundResponse) && empty($refundResponse['responseCode'])) {

			return new WP_Error('error', "Could not refund {$transactionXref}.");
		} else {

			$orderMessage = ($refundResponse['responseCode'] == "0" ? "Refund Successful" : "Refund Unsuccessful") . "<br/><br/>";

			$state = $refundResponse['state'] ?? null;

			if ($state != 'canceled') {
				$orderMessage .= "Amount Refunded: " . number_format($amountToRefund / pow(10, $refundResponse['currencyExponent']), $refundResponse['currencyExponent']) . "<br/><br/>";
			}

			$order->add_order_note($orderMessage);
			return true;
		}

		return new WP_Error('error', "Could not refund {$transactionXref}.");
	}

	/**
	 * receipt_page
	 */
	public function receipt_page($order)
	{
		if (in_array($this->settings['type'], ['hosted', 'hosted_v2', 'hosted_v3'])) {
			$redirect = add_query_arg('wc-api', 'wc_' . $this->id, home_url('/'));
			$callback = add_query_arg('wc-api', 'wc_' . $this->id, home_url('/'));

			$req = array_merge($this->capture_order($order), array(
				'redirectURL' => $redirect,
				'callbackURL' => $callback . '&callback',
				'formResponsive' => $this->settings['formResponsive'],
			));

			echo $this->gateway->hostedRequest($req, 'hosted_v2' == $this->settings['type'], 'hosted_v3' == $this->settings['type']);
		}

		return null;
	}

	/**
	 * On order success
	 *
	 * @param Array	$response
	 */

	public function on_order_success($response)
	{
		$order = new WC_Order((int)$response['orderRef']);

		$order_notes = '';

		// If callback or gateway response add note.
		if (isset($_GET['callback'])) {
			$order_notes  .= "\r\nType : Callback Response\r\n";
		} else {
			$order_notes  .= "\r\nType : Gateway Response\r\n";
		}

		$order_notes .= "\r\nResponse Code : {$response['responseCode']}\r\n";
		$order_notes .= "Message : {$response['responseMessage']}\r\n";
		$order_notes .= 'Amount Received : ' . number_format($response['amountReceived'] / 100, 2) . "\r\n";
		$order_notes .= "Unique Transaction Code : {$response['transactionUnique']}";

		$order->set_transaction_id($response['xref']);
		$order->add_order_note(__(ucwords($this->method_title) . ' payment completed.' . $order_notes, $this->lang));
		$order->payment_complete();

		$successUrl = $this->get_return_url($order);

		if (is_ajax()) {
			return [
				'result' => 'success',
				'redirect' => $successUrl,
			];
		}

		$this->redirect($successUrl);
		die();
	}

	/**
	 * On 3DS required
	 */
	public function on_threeds_required($res)
	{

		setcookie('threeDSRef',  $res['threeDSRef'], [
			'expires' => time() + 600,
			'path' => '/',
			'domain' => $_SERVER['HTTP_HOST'],
			'secure' => true,
			'httponly' => false,
			'samesite' => 'None'
		]);

		if (isset($_GET['3dsResponse'])) {

			// Echo out the ACS form that will auto submit and then stop executing immediately after.
			echo Gateway::silentPost($res['threeDSURL'], $res['threeDSRequest']);
			wp_die();
		} else {

			return [
				'result' => 'success',
				'redirect' => add_query_arg(
					[
						'ACSURL' => rawurlencode($res['threeDSURL']),
						'threeDSRef' => rawurlencode($res['threeDSRef']),
						'threeDSRequest' => $res['threeDSRequest'],
					],
					plugins_url('public/3d-secure-form-v2.php', dirname(__FILE__))
				)
			];
		}
	}

	##########################
	## Hook Callbacks
	##########################

	/**
	 * Hook to process a subscription payment
	 *
	 * @param $amount_to_charge
	 * @param $renewal_order
	 */

	public function process_scheduled_subscription_payment_callback($amount_to_charge, $renewal_order)
	{
		// Gets all subscriptions ( hopefully just one ) linked to this order
		$subs = wcs_get_subscriptions_for_renewal_order($renewal_order);

		// Get all orders on this subscription and remove any that haven't been paid
		$orders = array_filter(current($subs)->get_related_orders('all'), function ($ord) {
			return $ord->is_paid();
		});

		// Replace every order with orderId=>xref kvps
		$xrefs = array_map(function ($ord) {
			return $ord->get_transaction_id();
		}, $orders);

		// Return the xref corresponding to the most recent order (assuming order number increase with time)
		$xref = $xrefs[max(array_keys($xrefs))];

		$req = array(
			'merchantID' => $this->merchant_id,
			'xref' => $xref,
			'amount' => \P3\SDK\AmountHelper::calculateAmountByCurrency($amount_to_charge, $renewal_order->get_currency()),
			'action' => "SALE",
			'type' => 9,
			'rtAgreementType' => 'recurring',
			'avscv2CheckRequired' => 'N',
		);

		$this->debug_log('DEBUG', 'Request for gateway', $req);

		// Send subscription payment request to gateway.
		$this->debug_log('INFO', 'Sending Subscription payment request to gateway');
		$response = $this->gateway->directRequest($req);
		$this->debug_log('DEBUG', 'Response from gateway', $response);

		// Handle the response.
		try {

			// Verify the response.
			$this->debug_log('INFO', 'Verifying response signature');
			$this->gateway->verifyResponse($response, $this->merchant_signature_key);
			$this->debug_log('INFO', 'Response has been verified');

			// Create order notes to be added to the final notes.
			$order_notes  = "\r\nResponse Code : {$response['responseCode']}\r\n";
			$order_notes .= "Message : {$response['responseMessage']}\r\n";
			$order_notes .= "Unique Transaction Code : {$response['transactionUnique']}";

			$renewal_order->set_transaction_id($response['xref']);


			if ($response['responseCode'] == 0) {

				$this->debug_log('INFO', "A subscription payment was accepted by the gateway");
				$order_notes .= "Amount Received : " . number_format($response['amountReceived'] / 100, 2) . "\r\n";
				$renewal_order->add_order_note(__(ucwords($this->method_title) . ' payment completed.' . $order_notes, $this->lang));
				$renewal_order->payment_complete();
				$renewal_order->save();

				WC_Subscriptions_Manager::process_subscription_payments_on_order($renewal_order);

				return true;
			} else {

				$this->debug_log('INFO', "A subscription payment was declined by the gateway");
				$renewal_order->add_order_note(__(ucwords($this->method_title) . ' payment failed' . $order_notes, $this->lang));
				$renewal_order->save();
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($renewal_order);

				return false;
			}
		} catch (Exception $exception) {

			$this->debug_log('ERROR', "Something went wrong when trying to process a subscription", [$req, $response, $exception]);

			$renewal_order->add_order_note(
				__(ucwords($this->method_title) . "\r\nError processing automatic payment\r\n" . $response['responseMessage'], $this->lang)
			);
			$renewal_order->save();

			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order($renewal_order);
		}
	}

	/**
	 * Process Response or Callback
	 * 
	 * Hook to process the response from payment gateway
	 * or from an ACS
	 * 
	 * @param Array $response
	 */
	public function process_response_callback($response = null)
	{
		$this->debug_log('INFO', 'Processing response or callback');
		$this->debug_log('DEBUG', 'Response/Callback/$POST data', $response ?? $_POST);

		// 3DS v2 handling.
		if (isset($_POST['threeDSMethodData']) || isset($_POST['cres'])) {


			$this->debug_log('INFO', 'An ACS has posted data');
			$this->debug_log('DEBUG', 'ACS Postback data', $_POST);

			$req = array(
				'merchantID' => $this->merchant_id,
				// The following field must be passed to continue the 3DS request
				'threeDSRef' => $_COOKIE['threeDSRef'],
				'threeDSResponse' => $_POST,
			);

			$response = $this->gateway->directRequest($req);
			$this->debug_log('INFO', 'ACS data processed by gateway');
			$this->debug_log('DEBUG', 'ACS Postback data', $_POST);
		}

		$response = (empty($response) ? stripslashes_deep($_POST) : $response);

		// Verify the response signature. If the response was not verified it will throw a runtime excpetion.
		try {
			$this->debug_log('INFO', 'Verifying response signature');
			$this->gateway->verifyResponse($response, $this->merchant_signature_key);
			$this->debug_log('INFO', 'Response has been verified');
		} catch (RuntimeException $exception) {
			$this->debug_log('WARNING', "Response could not be verified", $response);
			return $this->process_error($exception->getMessage(), $response);
		} catch (Exception $exception) {
			$this->debug_log('WARNING', "Something went wrong when trying to verify the response", [$response, $exception]);
			return $this->process_error($exception->getMessage(), $response);
		}

		// Get the WC Order that matched the orderRef in the response.
		$order = new WC_Order((int)$response['orderRef']);

		// If order has been paid and this a callback log and ignore.
		if (isset($_GET['callback']) && $order->is_paid()) {
			$this->debug_log('INFO', 'Callback received payment for response already processed');
			return;
		}

		if ($order->is_paid() && isset($_COOKIE['duplicate_payment_response_count']) && $_COOKIE['duplicate_payment_response_count'] > 0) {

			$this->debug_log("NOTICE", "A duplicate response has been received for an order thats already processed a payment");
			// Add an order note
			$order_notes   = "\r\nA duplicate payment response was received.\r\n";
			$order_notes  .= "\r\nOrder #{$response['orderRef']}\r\n";
			$order_notes  .= "\r\nOutcome {$response['responseMessage']}\r\n";
			$order_notes  .= "\r\nXREF {$response['xref']}\r\n";
			$order_notes  .= "\r\nA Duplicate count number {$_COOKIE['duplicate_payment_response_count']}.\r\n";
			$order->add_order_note(__(ucwords($this->method_title) . '- Duplicate Response!' . $order_notes, $this->lang));
			// Redirect customer to order page.
			$this->redirect($this->get_return_url($order));
		} else if ($order->is_paid()) {

			// Increase duplicate_payment_response_count by one if the inter
			if ($this->settings['type'] !== 'direct') {
				setcookie('duplicate_payment_response_count', ($_COOKIE['duplicate_payment_response_count'] + 1), [
					'expires' => time() + 500,
					'path' => '/',
					'domain' => $_SERVER['HTTP_HOST'],
					'secure' => true,
					'httponly' => false,
					'samesite' => 'None'
				]);
			}

			$this->redirect($this->get_return_url($order));
		}

		// Create a wallet if a walletID in the response.
		if (!empty($response['walletID'])) {
			$this->create_wallet($response);
		}

		// Handle the outcome based on the response code.
		if ((int)$response['responseCode'] === 0) {

			$this->debug_log('INFO', "Payment for order {$response['orderRef']} was successful");
			return $this->on_order_success($response);
		} else if ((int)$response['responseCode'] === 65802) {

			return $this->on_threeds_required($response);
		} else {
			$this->debug_log('INFO', "Payment for order {$response['orderRef']} failed");
			$this->process_error('Payment failed', $response);
		}
	}

	##########################
	## Helper functions
	##########################

	/**
	 * @param $order_id
	 * @return array
	 * @throws Exception
	 */
	protected function capture_order($order_id)
	{
		$order     = new WC_Order($order_id);
		$amount    = \P3\SDK\AmountHelper::calculateAmountByCurrency($order->get_total(), $order->get_currency());

		$billing_address  = $order->get_billing_address_1();
		$billing2 = $order->get_billing_address_2();

		if (!empty($billing2)) {
			$billing_address .= "\n" . $billing2;
		}
		$billing_address .= "\n" . $order->get_billing_city();
		$state = $order->get_billing_state();
		if (!empty($state)) {
			$billing_address .= "\n" . $state;
			unset($state);
		}
		$country = $order->get_billing_country();
		if (!empty($country)) {
			$billing_address .= "\n" . $country;
			unset($country);
		}

		// Fields for hash
		$req = array(
			'action'				=> ($amount == 0 ? 'VERIFY' : 'SALE'),
			'merchantID'			=> $this->merchant_id,
			'amount'				=> $amount,
			'countryCode'			=> $this->merchant_country_code,
			'currencyCode'			=> $order->get_currency(),
			'transactionUnique'		=> uniqid($order->get_order_key() . '-'),
			'orderRef'				=> $order_id,
			'customerName'			=> $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customerCountryCode'	=> $order->get_billing_country(),
			'customerAddress'		=> $billing_address,
			'customerCounty'		=> $order->get_billing_state(),
			'customerTown'			=> $order->get_billing_city(),
			'customerPostCode'		=> $order->get_billing_postcode(),
			'customerEmail'			=> $order->get_billing_email(),
			'merchantData'      => json_encode(array(
				'platform' => 'WooCommerce',
				'version' => $this->module_version
			)),
		);

		$phone = $order->get_billing_phone();
		if (!empty($phone)) {
			$req['customerPhone'] = $phone;
			unset($phone);
		}

		/**
		 * Add extra fields for hosted intergrations.
		 */
		if (!empty($req['customerCountryCode']) && $this->settings['type'] !== 'direct') {
			$req = array_merge($req, ['customerCountryCodeMandatory' => 'Y']);
		}

		/**
		 * Subscriptions
		 */
		if (class_exists('WC_Subscriptions_Product')) {
			foreach ($order->get_items() as $item) {
				// If the product in the order is a subscription.
				if (WC_Subscriptions_Product::is_subscription($item->get_product_id())) {
					// Add rtagreementType flag
					$req['rtAgreementType'] = 'recurring';
					// Break out of the loop. Only one product needs to be a sub.
					break;
				}
			}
		}

		/**
		 * Wallets
		 */
		if ($this->settings['customerWalletsEnabled'] === 'Y' && is_user_logged_in()) {
			//Try and find the users walletID in the wallets table.
			global $wpdb;
			$wallet_table_name = $wpdb->prefix . 'woocommerce_payment_network_wallets';

			//Query table. Select customer wallet where belongs to user id and current configured merchant.
			$customersWalletID = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT wallets_id FROM $wallet_table_name WHERE users_id = %d AND merchants_id = %d LIMIT 1",
					get_current_user_id(),
					$this->merchant_id
				)
			);

			//If the customer wallet record exists.
			if ($customersWalletID > 0) {
				//Add walletID to request.
				$req['walletID'] = $customersWalletID;
			}

			$req['walletEnabled'] = 'Y';
			$req['walletRequired'] = 'Y';
		}

		// Set the duplicate payment checker to 0.
		setcookie('duplicate_payment_response_count', 0, [
			'expires' => time() + 500,
			'path' => '/',
			'domain' => $_SERVER['HTTP_HOST'],
			'secure' => true,
			'httponly' => false,
			'samesite' => 'None'
		]);

		return $req;
	}

	/**
	 * Redirect to the URL provided depending on integration type
	 */
	protected function redirect($url)
	{
		if ($this->settings['type'] === 'hosted_v2') {
			echo <<<SCRIPT
			<script>window.top.location.href = "$url";
			</script>;
			SCRIPT;
		} else {
			wp_redirect($url);
		}
		exit;
	}

	/**
	 * Wallet creation.
	 *
	 * A wallet will always be created if a walletID is returned. Even if payment fails.
	 */
	protected function create_wallet($response)
	{
		global $wpdb;

		if (!isset($response['orderRef'])) {
			return;
		}

		$order = new WC_Order((int)$response['orderRef']);

		//when the wallets is enabled, the user is logged in and there is a wallet ID in the response.
		if ($this->settings['customerWalletsEnabled'] === 'Y' && isset($response['walletID']) && $order->get_user_id() != 0) {
			$wallet_table_name = $wpdb->prefix . 'woocommerce_payment_network_wallets';

			$customersWalletID = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT wallets_id FROM $wallet_table_name WHERE users_id = %d AND merchants_id = %d AND wallets_id = %d LIMIT 1",
					$order->get_user_id(),
					$this->merchant_id,
					$response['walletID']
				)
			);

			//If the customer wallet record does not exists.
			if ($customersWalletID === null) {
				//Add walletID to request.
				$wpdb->insert($wallet_table_name, [
					'users_id' => $order->get_user_id(),
					'merchants_id' => $this->merchant_id,
					'wallets_id' => $response['walletID']
				]);
			}
		}
	}

	/**
	 * Process Error
	 */
	protected function process_error($message, $response)
	{
		$this->debug_log('INFO', 'Payment failed', $message);
		$this->debug_log('DEBUG', 'Process error data', [$message, $response]);

		if (isset($response['responseCode']) && in_array($response['responseCode'], [66315, 66316, 66316, 66320])) {
			$message = 'Double check to make sure that you entered your Credit Card number, CVV2 code, and Expiration Date correctly.';
		}

		$_SESSION['payment_gateway_error'] = $message;

		wc_add_notice($message, 'error');

		$redirectUrl = get_site_url();
		if (isset($response['orderRef'], $response['responseCode'], $response['responseMessage'])) {
			$order = new WC_Order((int)$response['orderRef']);

			$order_notes = '';

			// If callback or gateway response add note.
			if (isset($_GET['callback'])) {
				$order_notes  .= "\r\nType : Callback Response\r\n";
			} else {
				$order_notes  .= "\r\nType : Gateway Response\r\n";
			}

			$order_notes .= "\r\nResponse Code : {$response['responseCode']}\r\n";
			$order_notes .= "Message : {$response['responseMessage']}\r\n";
			$order_notes .= "Unique Transaction Code : {$response['transactionUnique']}";

			$order->update_status('failed');
			$order->add_order_note(__(ucwords($this->method_title) . ' payment failed.' . $order_notes, $this->lang));

			$redirectUrl = $this->get_return_url($order);
		}

		if (is_ajax()) {
			return [];
		} else {
			$this->redirect($redirectUrl);
			die();
		}
	}

	public function payment_scripts()
	{
		// we need JavaScript to process a token only on cart/checkout pages, right?
		if (!is_cart() && !is_checkout()) {
			return;
		}

		// if our payment gateway is disabled, we do not have to enqueue JS too
		if ($this->enabled === 'no') {
			return;
		}

		// Register and enqueue PaymentFields CSS
		wp_enqueue_style('hosted_payment_fields_css', plugins_url('/', dirname(__FILE__)) . 'assets/css/hostedfields.css',null,	rand(99,9999));
	
		// Register PaymentFields JavaScript
		$gatewayURL = $this->settings['gatewayURL'];
		$hostedFieldsURL = "{$gatewayURL}/sdk/web/v1/js/hostedfields.min.js";
		
		wp_enqueue_script(
			'hosted_payment_fields_jquery_min', 
			'https://code.jquery.com/jquery-3.4.1.min.js'
		);

		wp_enqueue_script(
			'hosted_payment_fields_jquery_validate', 
			'https://cdn.jsdelivr.net/npm/jquery-validation@1.19.1/dist/jquery.validate.min.js',
			['hosted_payment_fields_jquery_min']
		);

		wp_enqueue_script(
			'hosted_payment_fields_gateway_javascript', 
			$hostedFieldsURL,
			null,
			'1.0',
		);

		wp_enqueue_script(
			'hosted_payment_fields_script', 
			plugins_url('/', dirname(__FILE__)) . 'assets/js/hostedfields.js',
			['hosted_payment_fields_gateway_javascript'],
			'1.0',
		);

		wp_localize_script('hosted_payment_fields_script', 'hfLocalizeVars', array(
			'securitycode' => wp_create_nonce($this->nonce_key),
		));
		
	}

	/**
	 * Validates Hosted Fields data.
	 */
	public function validate_fields()
	{
		// If this is a direct integration and there are Hosted Fields error
		// add them to WC error notices.
		if ($this->settings['type'] === 'direct') {
			if (!empty($_POST['hosted-fields-error-input'])) {
				wc_add_notice($_POST['hosted-fields-error-input'], 'error');
				return false;
			}
		}
		// If no errors return true.
		return true;
	}

	/**
	 * Debug
	 */
	public function debug_log($type, $logMessage, $objects = null)
	{
		// If logging is not null and $type isin logging verbose selection.
		if (isset(static::$logging_options[$type])) {
			wc_get_logger()->{$type}(print_r($logMessage, true) . print_r($objects, true), array('source' => $this->title));
		}
		// If logging_options empty.
		return;
	}
}
