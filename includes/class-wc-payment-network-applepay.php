<?php

define('ALLOW_UNFILTERED_UPLOADS', true);

use P3\SDK\Gateway;

/**
 * Gateway class
 */
class WC_Payment_Network_ApplePay extends WC_Payment_Gateway
{
	/**
	 * @var string
	 */
	protected $lang;

	/**
	 * @var Gateway
	 */
	protected $gateway;

	/**
	 * The default module name.
	 *
	 * @var string
	 */
	protected $defaultModuleName;

	/**
	 * The default merchant ID that will be used
	 * when processing a request on the gateway.
	 *
	 * @var string
	 */
	protected $defaultMerchantID;

	/**
	 * The default mercant signature.
	 *
	 * @var string
	 */
	protected $defaultMerchantSignature;

	/**
	 * The gateway URL
	 *
	 * @var string
	 */
	protected $defaultGatewayURL;

	/**
	 * Key used to generate the nonce for AJAX calls.
	 * @var string
	 */
	protected $nonce_key;

	/**
	 * Url of plugin
	 * @var string
	 */
	protected $pluginURL;

	/**
	 * Use gateway for merchant validation.
	 * @var string
	 */
	protected $gatewayMerchantValidation;

	/**
	 * Apple Pay gateway enabled
	 * @var bool
	 */
	protected $gatewayValidationAvailable;

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

	public function __construct()
	{
		global $wp_query;

		// Include the module config file.
		$configs = include dirname(__FILE__) . '/../config.php';
		$this->pluginURL = plugins_url('/', dirname(__FILE__));

		$this->defaultModuleName = str_replace(' ', '', strtolower($configs['default']['gateway_title']));

		$this->has_fields = true;
		$this->id = preg_replace("/[^A-Za-z0-9_.\/]/", "", $this->defaultModuleName) . '_applepay';
		$this->lang = 'woocommerce_' . $this->id;
		// $this->icon                       = plugins_url('/', dirname(__FILE__)) . 'assets/img/logo.png';
		$this->method_title = __($configs['default']['gateway_title'], $this->lang);
		$this->method_description = __($configs['applepay']['method_description'], $this->lang);
		$this->module_version 		= (file_exists(dirname(__FILE__) . '/../VERSION') ? file_get_contents(dirname(__FILE__) . '/../VERSION') : "UV");
		$this->gatewayValidationAvailable = $configs['applepay']['gateway_validation_available'];
		// Get main modules settings to use in this sub module.
		$mainModuleID = str_replace("_applepay", "", $this->id);
		$mainModuleSettings = get_option('woocommerce_' . $mainModuleID . '_settings');

		$this->defaultGatewayURL = ($mainModuleSettings['gatewayURL'] ?? null);
		$this->defaultMerchantID = ($mainModuleSettings['merchantID'] ?? null);
		$this->defaultMerchantSignature = ($mainModuleSettings['signature'] ?? null);

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
			'subscription_payment_method_change_admin',
		);

		$this->nonce_key = '12d4c8031f852b9c';

		$this->init_settings();

		static::$logging_options = (empty($this->settings['logging_options']) ? null : array_flip(array_map('strtoupper', $this->settings['logging_options'])));
		$this->title = ($this->settings['title'] ?? null);
		$this->gatewayMerchantValidation = ($this->settings['gateway_merchant_validation'] ?? null);

		// Register hooks.
		add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'process_scheduled_subscription_payment_callback'), 10, 3);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		
		// Enqueue Apple Pay script when main site.
		if (isset($wp_query) && (is_checkout() || is_cart())) {
			add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
		}
		// Enqueue Admin scripts when in plugin settings.
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		add_action('woocommerce_proceed_to_checkout', array($this, 'cart_page_ap'));

		if (isset($mainModuleSettings['enabled']) && $mainModuleSettings['enabled'] == "no") {
			$this->enabled = "no";
		}

		$this->init_form_fields();
	}

	/**
	 * Generate CSR and private key
	 * ----------------------------
	 *
	 * This will generate a CSR and privat key
	 * then return it as a JSON response. It
	 * is used by the certificate setup help
	 * window to aid generating required files.
	 *
	 * @return JSON
	 */
	public function generate_csr_and_key()
	{
		// Check nonce sent in request that called the function is correct.
		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}

		$keyPassword = $_POST['keypassword'];

		$csrSettings = array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'encrypt_key' => true);
		// Generate a new private (and public) key pair
		$privkey = openssl_pkey_new($csrSettings);

		// Generate a certificate signing request
		$csr = openssl_csr_new(array(), $privkey, $csrSettings);

		// Export key
		openssl_csr_export($csr, $csrout);
		openssl_pkey_export($privkey, $pkeyout, $keyPassword);

		$JSONResponse = array(
			'status' => true,
			'csr_file' => $csrout,
			'key_file' => $pkeyout,
		);

		wp_send_json_success($JSONResponse);

		wp_die(); // Ensure nothing further is processed.
	}

	/**
	 * Enqueue Admin Scripts
	 *
	 * Enqueues the Javascript needed for the
	 * plugin settings page
	 */
	public function enqueue_admin_scripts()
	{
		$optionPrefix = "woocommerce_{$this->id}_";
		$certificateData = get_option($optionPrefix . 'merchantCert');
		$certificatekeyData = get_option($optionPrefix . 'merchantCertKey');

		wp_register_style('custom_wp_admin_css', $this->pluginURL . '/assets/css/pluginadmin.css');
		wp_enqueue_style('custom_wp_admin_css');
		wp_register_script('applepay_admin_script', $this->pluginURL . '/assets/js/applepayadmin.js');
		wp_enqueue_script('applepay_admin_script');
		wp_localize_script('applepay_admin_script', 'localizeVars', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'securitycode' => wp_create_nonce($this->nonce_key),
			'certificateAndKeyExist' => (!empty($certificateData) || !empty($certificatekeyData)),
		));
	}

	/**
	 * Initialise Form fields
	 */
	public function init_form_fields()
	{

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', $this->lang),
				'label' => __('Enable Apple Pay', $this->lang),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no',
			),
			'title' => array(
				'title' => __('Title', $this->lang),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', $this->lang),
				'default' => __('Apple pay', $this->lang),
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
			'merchant_display_name' => array(
				'title' => __('Merchant display name.', $this->lang),
				'type' => 'text',
				'description' => __('The Apple Pay merchant display name.', $this->lang),
			),
		);

		if ($this->gatewayValidationAvailable) {
			$this->form_fields = array_merge($this->form_fields, [
				'gateway_merchant_validation' => array(
					'title' => __('Gateway merchant validation', $this->lang),
					'type' => 'checkbox',
					'default' => 'yes',
					'description' => __('Disable to use your own Apple Pay developer account and merchant certificates. After saving new options will appear.', $this->lang),
				),
			]);
		}

		if (($this->gatewayMerchantValidation === 'no' && $this->gatewayValidationAvailable) || $this->gatewayValidationAvailable === false) {

			$this->form_fields = array_merge($this->form_fields, [
				'merchant_identifier' => array(
					'title' => __('Merchant identifier', $this->lang),
					'type' => 'text',
					'description' => __('The Apple Pay merchant identifier.', $this->lang),
					'custom_attributes' => array(
						'required' => true,
					),
				),
				'merchant_cert_key_password' => array(
					'title' => __('Merchant certificate key password', $this->lang),
					'type' => 'password',
					'description' => __('The Apple Pay merchant identifier.', $this->lang),
					'custom_attributes' => array(
						'required' => true,
					),
				),
			]);
		}
	}

	/**
	 * Admin Options
	 * -------------
	 *
	 * Initialise admin options for plugin settings which
	 * includes checking setups is valid.
	 * Outputs the admin UI
	 */
	public function admin_options()
	{
		$optionPrefix = "woocommerce_{$this->id}_";

		// For each setting with an empty value output it's required.
		// This is incase a theme stops a required field.
		foreach ($_POST as $key => $value) {
			if (empty($value) && strpos($key, $optionPrefix) === 0) {
				echo "<label id=\"save-field-error\">Field {$key} required is empty</label>";
			}
		}

		// The key password is stored in settings.
		$currentSavedKeyPassword = ($this->settings['merchant_cert_key_password'] ?? null);

		$certificateSaveResultHTML = '';
		$certificateSetupStatus = '';

		// Check for files to store. If no files to store then check current saved files.
		if (!empty($_FILES['merchantCertFile']['tmp_name']) || !empty($_FILES['merchantCertKey']['tmp_name'])) {

			$certificateSaveResult = $this->store_merchant_certificates($_FILES, $currentSavedKeyPassword);
			$certificateSaveResultHTML = ($certificateSaveResult['saved'] ?
				"<div id=\"certs-saved-container\" class=\"cert-saved\"><label id=\"certificate-saved-label\">Certificates saved</label></div>" :
				"<div id=\"certs-saved-container\" class=\"cert-saved-error\"><label id=\"certificate-saved-error-label\">Certificates save error: {$certificateSaveResult['error']}</label></div>");
		}

		// Check if Apple pay certificates have been saved and valid.
		$currentSavedCertData = get_option($optionPrefix . 'merchantCert');
		$currentSavedCertKey = get_option($optionPrefix . 'merchantCertKey');
		$certificateSetupStatus = (openssl_x509_check_private_key($currentSavedCertData, array($currentSavedCertKey, $currentSavedKeyPassword)) ?
			'<label class="cert-message cert-message-valid">Certificate, key and password saved are all valid</label>' :
			'<label class="cert-message cert-validation-error">Certificate, key and password are not valid or saved</label>');

		// Plugin settings field HTML.
		$pluginSettingFieldsHTML = '<table class="form-table">' . $this->generate_settings_html(null, false) . '</table>';

		$adminPageHTML = <<<HTML
		{$certificateSaveResultHTML}
		<h1>{$this->method_title} - Apple Pay settings</h1>
		{$pluginSettingFieldsHTML}

HTML;

		$certificateSetupHTML = <<<HTML
		<hr>
		<h1 id="apple-pay-merchant-cert-setup-header">Apple Pay merchant certificate setup</h1>
		<p><label>Current certificate setup status: </label>{$certificateSetupStatus}</p>
		<div>
		<div id="upload-cert-message">Upload new certificate and key  <img id="upload-cert-help-icon" src="{$this->pluginURL}/assets/img/help-icon.png" alt="CSR file download"></div>
		<div id="apple-pay-cert-key-upload-container">
		<div id ="merchant-cert-upload-label">Merchant certificate file upload</div>
		<input type="file" id="merchantCertUpload" name="merchantCertFile"/>
		<div id ="merchant-cert-upload-label">Merchant certificate key</div>
		<input type="file" id="merchantCertKeyUpload" name="merchantCertKey"/>
		</div>
		<div id="certificate-help-window">
		<img id="close-help-window-icon" class="close-help-window-icon" src="{$this->pluginURL}/assets/img/close-window-icon.png" alt="Close help window">
		<h2 style="text-decoration: underline;">Apple Pay merchant identity certificate</h2>
		<p>To obtain an Apple Pay <em>merchant identity</em> you must have enrolled in the
		<a href="https://developer.apple.com/programs/" target="_blank" rel=" noopener noreferrer nofollow" data-disabled="">Apple Developer Program</a>
		 and <a href="https://help.apple.com/developer-account/#/devb2e62b839?sub=dev103e030bb" target="_blank" rel=" noopener noreferrer nofollow">
		created a unique Apple Pay merchant identifier</a>.</p>
		<p>The merchant identity is associated with your merchant identifier and used to identify the merchant in SSL communications.
		The certificate expires every 25 months. If the certificate is revoked, you can recreate it. You will also need to setup a payment processing certificates
		with the payment gateway before the Apple Pay button is fully functional.</p>
		<p><b>You must generate your own CSR when creating a <em>merchant identity certificate</em> for the payment module.
		<a href="https://help.apple.com/developer-account/#/devbfa00fef7" target="_blank" rel=" noopener noreferrer nofollow"></a>.</b></p>
		<ol>
			<li><p>Open the <a href="https://developer.apple.com/account/resources" target="_blank" rel=" noopener noreferrer nofollow" data-disabled="">Apple Developer Certificates, Identifiers &amp; Profiles</a> webpage and select 'Identifiers' from the sidebar.</p></li>
			<li><p>Under 'Identifiers', select 'Merchant IDs' using the filter in the top-right.</p></li>
			<li><p>On the right, select your merchant identifier.</p></li>
			<li><p>Under 'Apple Pay Merchant Identity Certificate', click 'Create Certificate'.</p></li>
			<li><p>Use a CSR you have generated to upload. If you do not have a CSR then click the button below to generate one.</p></li>
			<li><p>Click 'Choose File' and select the CSR you just downloaded.</p></li>
			<li><p>Click 'Continue'.</p></li>
			<li><p>Click 'Download' to download the <em>merchant identity certificate</em> and save to a file.</p></li>
			<li><p>Along with the key file generated with the CSR, upload the CER file download from Apple Pay</p></li>
			<li><p>Update the password in the settings</p></li>
			<li><p>Click the save button.</p></li>
		</ol>
		<button class="merchant-cert-gen-button" type="button" id="merchant-cert-gen-button">Generate CSR and key</button>
		<br>
		<div id="generated-certs-container">
		<label>Files ready to download.</label>
			<div id="downloadable-cert-and-key-container">
				<div id="csrdownloadicon">
						<a id="csrdownloadhref" href="link">
						<img src="{$this->pluginURL}/assets/img/certification-icon.png" alt="CSR file download">
						<br>
						<label>CSR Certificate file</label>
						</a>
					</div>
					<div id="keydownloadicon">
						<a id="keydownloadhref" href="link">
						<img src="{$this->pluginURL}/assets/img/certification-icon.png" alt="Key file download">
						<br>
						<label>Certificate key file</label>
						</a>
					</div>
				</div>
			</div>
		</div>
HTML;

		echo $adminPageHTML;

		if (($this->gatewayMerchantValidation === 'no' && $this->gatewayValidationAvailable) || $this->gatewayValidationAvailable === false) {
			echo $certificateSetupHTML;
		}
	}

	/**
	 * Stores the merchant certificates ass options in the settings database.
	 */
	public function store_merchant_certificates($files, $keyPassword)
	{
		// Check if admin
		if (!is_admin()) {
			wp_die();
		}

		$optionPrefix = "woocommerce_{$this->id}_";

		// Check files are present. Return file missing
		if ($files['merchantCertKey']['size'] == 0 || $files['merchantCertFile']['size'] == 0) {
			$fileMissing = (($files['merchantCertFile']['size'] > 0) ? 'private key' : 'certificate');
			$response['saved'] = false;
			$response['error'] = "Missing {$fileMissing}";
			return $response;
		}

		// Get the file contents.
		$merchantCert = file_get_contents($files['merchantCertFile']['tmp_name']);
		$merchantCertKey = file_get_contents($files['merchantCertKey']['tmp_name']);

		// If merchantCertFile is .cer convert it to pem.
		if ($files['merchantCertFile']['type'] === 'application/pkix-cert' || $files['merchantCertFile']['type'] === 'application/x-x509-ca-cert') {
			$merchantCert = '-----BEGIN CERTIFICATE-----' . PHP_EOL
				. chunk_split(base64_encode($merchantCert), 64, PHP_EOL)
				. '-----END CERTIFICATE-----' . PHP_EOL;
		}

		// Check the files are valid.
		$certRexEx = '/-{3,}BEGIN CERTIFICATE-{3,}.*?^-{3,}END CERTIFICATE-{3,}/ms';
		$keyRegEx = '/-{3,}BEGIN ENCRYPTED PRIVATE KEY-{3,}.*?^-{3,}END ENCRYPTED PRIVATE KEY-{3,}/ms';

		if (!preg_match($certRexEx, $merchantCert) || !preg_match($keyRegEx, $merchantCertKey)) {
			$response['saved'] = false;
			$response['error'] = "Certificate and/or key are invalid. No files saved.";
			return $response;
		}

		// Check private key matches certificate.
		if (!openssl_x509_check_private_key($merchantCert, array($merchantCertKey, $keyPassword))) {
			$response['saved'] = false;
			$response['error'] = 'Certificate, key and password do not match. Try retyping the password along with saving the files';
			return $response;
		} else {

			// If the certificates are already stored then update them. Else add them.
			if (get_option($optionPrefix . 'merchantCert') || get_option($optionPrefix . 'merchantCertKey')) {
				update_option($optionPrefix . 'merchantCert', $merchantCert);
				update_option($optionPrefix . 'merchantCertKey', $merchantCertKey);
				$response['saved'] = true;
				$response['message'] = 'Previous certificate and key overwritten';
			} else {
				add_option($optionPrefix . 'merchantCert', $merchantCert);
				add_option($optionPrefix . 'merchantCertKey', $merchantCertKey);
				$response['saved'] = true;
				$response['message'] = 'Certificate and key have been saved';
			}

			return $response;
		}
	}

	/**
	 * Validate ApplePay merchant
	 * --------------------------
	 *
	 * This function is called by the actions wp_ajax_nopriv_process_applepay
	 * and wp_ajax_validate_process_applepay. It will validate the merchant
	 */
	public function validate_applepay_merchant()
	{
		$this->debug_log('INFO', "Validating ApplePay merchant");

		// Check nonce sent in request that called the function is correct.
		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}

		$validationURL = $_POST['validationURL'];

		// if no validation URL
		if (!$validationURL) {
			wp_send_json_error();
			wp_die();
		}

		$apwDomainName = $_SERVER['HTTP_HOST'];

		if ($this->gatewayMerchantValidation === 'yes') {

			$this->debug_log('INFO', "Gateway validation method enabled");
			$this->debug_log('INFO', "Gateway validation method enabled", $validationURL);

			// Request validation from gateway.
			$gatewayRequest = array(
				'merchantID' => $this->defaultMerchantID,
				'process' => 'applepay.validateMerchant',
				'validationURL' => $validationURL,
				'displayName' => $this->settings['merchant_display_name'],
				'domainName' => $apwDomainName,
				'action' => 'VERIFY',
				'amount' => 0,
				'redirectURL' => 'https://example.com',
			);

			$gatewayRequest['signature'] = $this->createSignature($gatewayRequest, $this->defaultMerchantSignature);

			$this->debug_log('DEBUG', "Gateway verification request", $gatewayRequest);

			$gatewayResponse = $this->send_to($this->defaultGatewayURL . '/hosted/', $gatewayRequest);

			$this->debug_log('DEBUG', "Gateway verification request response", $gatewayResponse);

			wp_send_json_success($gatewayResponse);
		} else {

			$this->debug_log('INFO', "Merchant validation method enabled");

			$optionPrefix = "woocommerce_{$this->id}_";
			$certificateData = get_option($optionPrefix . 'merchantCert');
			$certificatekeyData = get_option($optionPrefix . 'merchantCertKey');
			$apwDisplayName = $this->settings['merchant_display_name'];
			$apwMerchantIdentifier = $this->settings['merchant_identifier'];
			$certficiateKeyPassword = $this->settings['merchant_cert_key_password'];

			// First check all settings required are present as well as the certificate and key.
			if (
				!isset($apwMerchantIdentifier) &&
				!isset($apwDomainName) &&
				!isset($apwDisplayName) &&
				!isset($certificateData) &&
				!isset($certificatekeyData) &&
				!isset($certficiateKeyPassword)
			) {
				wp_send_json_error();
				wp_die();
			}

			// Prepare merchant certificate and key file for CURL

			// Merchant certificate.
			$tempCertFile = tmpfile();
			fwrite($tempCertFile, $certificateData);
			$tempCertFilePath = stream_get_meta_data($tempCertFile);
			$tempCertFilePath = $tempCertFilePath['uri'];

			// Merchant certificate key.
			$tempCertKeyFile = tmpfile();
			fwrite($tempCertKeyFile, $certificatekeyData);
			$tempCertFileKeyPath = stream_get_meta_data($tempCertKeyFile);
			$tempCertFileKeyPath = $tempCertFileKeyPath['uri'];

			if (($tmp = parse_url($validationURL)) && $tmp['scheme'] === 'https' && substr($tmp['host'], -10) === '.apple.com') {

				$data = '{"merchantIdentifier":"' . $apwMerchantIdentifier . '", "domainName":"' . $apwDomainName . '", "displayName":"' . $apwDisplayName . '"}';

				$curlOptions = array(
					CURLOPT_SSLCERT => $tempCertFilePath,
					CURLOPT_SSLKEY => $tempCertFileKeyPath,
					CURLOPT_SSLKEYPASSWD => $certficiateKeyPassword,
				);

				// Send request to Apple.
				$response = $this->send_to($validationURL, $data, $curlOptions);

				// Close the temp cert file and key.
				fclose($tempCertFile);
				fclose($tempCertKeyFile);

				$this->debug_log('DEBUG', "Apple verifcation response", $response);

				if ($response === false) {
					wp_send_json_error();
				} else {
					wp_send_json_success($response);
				}
			} else {
				error_log('URL should be SSL or contain apple.com. URL was: ' . $validationURL);
				wp_send_json_error();
			}
		}
	}

	/**
	 * Process Apple Pay Payment
	 * -------------------------
	 *
	 * This function will process the token retrieved from the checkout
	 * when using Apple Pay. It is called by the ApplePay javascript.
	 *
	 * It will use the contact/address information from the token to create
	 * an order then process the token with the gateway and finally process
	 * the order.
	 *
	 */
	public function process_applepay_payment()
	{
		// Check nonce sent in request that called the function is correct.
		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}
		// Strip slashes added by WP from the JSON data.
		$paymentData = stripslashes_deep($_POST['payment']);
		// Decode the JSON payment token data.
		$paymentData = json_decode($paymentData);

		if (isset($_POST['orderID']) && $_POST['orderID'] != "false") {

			$order = wc_get_order(wc_get_order_id_by_order_key($_POST['orderID']));
			// Apple users get the chance to change billing address. Update the order.
			$order->set_billing_first_name($paymentData->billingContact->givenName);
			$order->set_billing_last_name($paymentData->billingContact->familyName);
			$order->set_billing_address_1($paymentData->billingContact->addressLines[0]);
			$order->set_billing_address_1($paymentData->billingContact->addressLines[1]);
			$order->set_billing_city($paymentData->billingContact->locality);
			$order->set_billing_state($paymentData->billingContact->administrativeArea);
			$order->set_billing_postcode($paymentData->billingContact->postalCode);
		} else {

			$woocommerceOrderRequest = array(
				'paymentMethod' => $this->id,
				'billingEmail' => $paymentData->shippingContact->emailAddress,
				// Billing address details
				'billingAddress' => array(
					'first_name' => $paymentData->billingContact->givenName,
					'last_name' => $paymentData->billingContact->familyName,
					'email' => $paymentData->billingContact->emailAddress,
					'phone' => $paymentData->billingContact->phoneNumber,
					'address_1' => $paymentData->billingContact->addressLines[0],
					'address_2' => $paymentData->billingContact->addressLines[1],
					'city' => $paymentData->billingContact->locality,
					'state' => $paymentData->billingContact->administrativeArea,
					'postcode' => $paymentData->billingContact->postalCode,
					'country' => $paymentData->billingContact->country,
				),
				// Shipping address details
				'shippingAddress' => array(
					'first_name' => $paymentData->shippingContact->givenName,
					'last_name' => $paymentData->shippingContact->familyName,
					'email' => $paymentData->shippingContact->emailAddress,
					'phone' => $paymentData->shippingContact->phoneNumber,
					'address_1' => $paymentData->shippingContact->addressLines[0],
					'address_2' => $paymentData->shippingContact->addressLines[1],
					'city' => $paymentData->shippingContact->locality,
					'state' => $paymentData->shippingContact->administrativeArea,
					'postcode' => $paymentData->shippingContact->postalCode,
					'country' => $paymentData->shippingContact->country,
					'country_code' => $paymentData->shippingContact->countryCode,
				),
			);

			// Try and create the order
			try {
				$order = $this->create_order($woocommerceOrderRequest);
			} catch (\Exception $exception) {
				return false;
			}
		}

		$gatewayTransactionRequest = array(
			'merchantID' => $this->defaultMerchantID,
			'action' => 'SALE',
			'amount' => $order->calculate_totals(),
			'countryCode' => wc_get_base_location()['country'],
			'currencyCode' => $order->get_currency(),
			'transactionUnique' => uniqid($order->get_order_key() . "-"),
			'type' => '1',
			'paymentMethod' => 'applepay',
			'merchantData' => json_encode(array(
				'platform' => 'WooCommerce',
				'version' => $this->module_version
			)),
			'paymentToken' => $paymentData->token->paymentData,
			'customerName' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customerAddress' => $order->get_billing_address_1() . '\n' . $order->get_billing_address_2(),
			'customerCounty' => $order->get_billing_state(),
			'customerTown' => $order->get_billing_city(),
			'customerPostCode' => $order->get_billing_postcode(),
			'customerEmail' => $order->get_billing_email(),
		);

		$gatewayRequestResult = $this->sendGatewayRequest($gatewayTransactionRequest);

		// Add gateway respone to order meta data
		$order->update_meta_data('gatewayResponse', $gatewayRequestResult);
		$order->save();

		$JSONResponse['paymentComplete'] = false;

		// Clear shipping selection for backward compatibility.
		WC()->session->__unset('chosen_shipping_methods');

		if (!isset($gatewayRequestResult['responseCode']) || (int)$gatewayRequestResult['responseCode'] !== 0) {

			$this->on_order_fail($order);
		} else if ((int)$gatewayRequestResult['responseCode'] === 0) {

			$this->on_order_success($order);
			$JSONResponse['paymentComplete'] = true;
		} else {
			$this->on_order_fail($order);
		}

		$JSONResponse['redirect'] = $this->get_return_url($order);
		$JSONResponse['message'] = ($JSONResponse['paymentComplete'] ? 'Approved' : 'Declined');

		wp_send_json_success($JSONResponse);
	}

	/**
	 * Create Order
	 * ------------
	 *
	 * Creates a woocommerce order from the $data passed.
	 *
	 * Example WC Order
	 * ----------------
	 *
	 * [
	 *  'paymentMethod' => '',
	 *  'billingEmail' => '',
	 *  'billingAddress => array(
	 *           'first_name' => 'John',
	 *           'last_name'  => 'Doe',
	 *           'company'    => 'JDLTD',
	 *           'email'      => 'example@domainnamehere.com',
	 *           'phone'      => '01899 999888',
	 *           'address_1'  => '16 Test street',
	 *           'address_2'  => '',
	 *           'city'       => 'TCity',
	 *           'state'      => 'London',
	 *           'postcode'   => 'E12 LTD',
	 *           'country'    => 'UK'
	 *  ),
	 *  'shippingAddress => array(
	 *           'first_name' => 'John',
	 *           'last_name'  => 'Doe',
	 *           'company'    => 'JDLTD',
	 *           'email'      => 'example@domainnamehere.com',
	 *           'phone'      => '01899 999888',
	 *           'address_1'  => '16 Test street',
	 *           'address_2'  => '',
	 *           'city'       => 'TCity',
	 *           'state'      => 'London',
	 *           'postcode'   => 'E12 LTD',
	 *           'country'    => 'UK'
	 *  )
	 * )
	 *
	 * @param  Array            $data
	 * @return Array|bool	    $order
	 */
	private function create_order($data)
	{
		$gateways = WC()->payment_gateways->get_available_payment_gateways();

		$checkout = WC()->checkout();

		$orderID = $checkout->create_order(array(
			'payment_method' => $data['paymentMethod'],
			'billing_email' => $data['billingEmail'],
		));

		// Get the chosen shipping method from the session data.
		$shippingMethodSelected = WC()->session->get('chosen_shipping_methods')[0];
		$order = wc_get_order($orderID);
		update_post_meta($orderID, '_customer_user', get_current_user_id());

		// Retrieve the customer shipping zone
		$shippingZones = WC_Shipping_Zones::get_zones();
		$shippingMethodID = explode(':', $shippingMethodSelected);
		$shippingMethodIndentifier = $shippingMethodID[0];
		$shippingMethodInstanceID = $shippingMethodID[1];

		// For each shipping method in zone locations in shipping zones, find the one
		// selected on the Apple pay window by the user.
		foreach ($shippingZones as $zone) {
			foreach ($zone['zone_locations'] as $zonelocation) {
				if ($zonelocation->code === $data['shippingAddress']['country_code']) {
					foreach ($zone['shipping_methods'] as $shippingMethod) {
						if (
							$shippingMethod->id == $shippingMethodIndentifier &&
							$shippingMethod->instance_id == $shippingMethodInstanceID
						) {
							$item = new WC_Order_Item_Shipping();
							$item->set_method_title($shippingMethod->title);
							$item->set_method_id($shippingMethod->id);
							$item->set_instance_id($shippingMethod->instance_id);
							$item->set_total($shippingMethod->cost ? $shippingMethod->cost : 0);
							$order->add_item($item);
							// Shipping method found and set. Break out of all loops.
							break 3;
						}
					}
				}
			}
		}

		// Check if cart has subscriptions.
		$cart = WC()->cart;

		foreach ($cart->cart_contents as $item) {
			// If subscrition, setup.
			if (class_exists('WC_Subscriptions_Product') && WC_Subscriptions_Product::is_subscription($item['product_id'])) {

				$sub = wcs_create_subscription(array(
					'order_id' => $orderID,
					'customer_id' => get_current_user_id(),
					'status' => 'pending',
					'billing_period' => WC_Subscriptions_Product::get_period($item['product_id']),
					'billing_interval' => WC_Subscriptions_Product::get_interval($item['product_id'])
				));

				if (is_wp_error($sub)) {
					return false;
				}

				$start_date = gmdate('Y-m-d H:i:s');
				// Add product to subscription
				$sub->add_product(wc_get_product($item['product_id']), $item["quantity"]);

				$dates = array(
					'trial_end'    => WC_Subscriptions_Product::get_trial_expiration_date($orderID, $start_date),
					'next_payment' => WC_Subscriptions_Product::get_first_renewal_payment_date($orderID, $start_date),
					'end'          => WC_Subscriptions_Product::get_expiration_date($orderID, $start_date),
				);

				// Add billing & shipping address.
				$sub->set_address($data["billingAddress"], 'billing');
				$sub->set_address($data["shippingAddress"], 'shipping');

				// Get payment gateway instances and set the payment method to the main module.
				$payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
				$sub->set_payment_method($payment_gateways[$this->defaultModuleName]);

				$sub->update_dates($dates);
				$sub->calculate_totals();
			}
		}

		$order->calculate_totals();
		$order->set_address($data['billingAddress'], 'billing');
		$order->set_address((isset($data['shippingAddress']) ? $data['shippingAddress'] : $data['billingAddress']), 'shipping');

		// Return the created order or false
		if ($order) {
			return $order;
		}
		return false;
	}

	/**
	 * Send Gateway Request
	 * --------------------
	 *
	 * This method will send a gateway request to the gateway
	 * and return the fields needed to process the order.
	 *
	 *  Example
	 *  -------
	 *  $gatewayTransactionRequest = [
	 *       'action' => 'SALE',
	 *       'amount' => '299',
	 *       'countryCode' => '826',
	 *       'currencyCode' => '826',
	 *       'transactionUnique' => 'APPLEPAYTESTING' . uniqid(),
	 *       'type' => '1',
	 *       'paymentMethod' => 'applepay',
	 *       'paymentToken' =>  json_encode($paymentData->token->paymentData)
	 *   ];
	 *
	 *  @param Array        $gatewayTransactionRequest
	 *  @return Array       $gatewayResponse | false
	 */
	private function sendGatewayRequest($gatewayTransactionRequest)
	{
		$gateway = new Gateway(
			$this->defaultMerchantID,
			$this->defaultMerchantSignature,
			$this->defaultGatewayURL
		);

		$response = $gateway->directRequest($gatewayTransactionRequest);

		$gatewayResponse = array(
			'responseCode' => $response['responseCode'],
			'responseMessage' => $response['responseMessage'],
			'xref' => $response['xref'],
			'amount' => $response['amount'],
			'transactionUnique' => $response['transactionUnique'],
		);

		return ($gatewayResponse ? $gatewayResponse : false);
	}

	/**
	 * Get ApplePay request
	 * --------------------
	 *
	 * This function builds the ApplePay request and returns it as a JSON
	 * array to the ApplePay.JS
	 *
	 * paymentRequest = {
	 *      currencyCode: 'GBP',
	 *      countryCode: 'GB',
	 *      requiredBillingContactFields: ['email', 'name', 'phone', 'postalAddress'],
	 *      requiredShippingContactFields: ['email', 'name', 'phone', 'postalAddress'],
	 *      lineItems: [{
	 *          label: 'test item',
	 *          amount: '2.99'
	 *      }],
	 *      total: {
	 *          label: 'Total label',
	 *          amount: '2.99'
	 *      },
	 *      supportedNetworks: [
	 *          "amex",
	 *          "visa",
	 *          "discover",
	 *          "masterCard"
	 *      ],
	 *      merchantCapabilities: ['supports3DS']
	 *  }
	 */
	public function get_applepay_request()
	{

		// Check nonce sent in request that called the function is correct.
		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}

		$cartContents = array();
		$shippingAmountTotal = 0;
		$cartTotal = 0;

		$failedOrderPaymnet = (isset($_POST['orderID']) && $_POST['orderID'] != "false");

		// If failed order cookie get cart items from the order as the cart will be empty.
		// Other wise get the items from the cart as it's not become an order yet.
		if ($failedOrderPaymnet) {

			$order = wc_get_order(wc_get_order_id_by_order_key($_POST['orderID']));

			foreach ($order->get_items() as $item_id => $item) {
				array_push(
					$cartContents,
					array(
						'title' => $item->get_name(),
						'quantity' => $item->get_quantity(),
						'price' => $item->get_total() /  $item->get_quantity(),
						'product_id' => $item->get_product_id(),
					)
				);
			}

			$shippingAmountTotal = $order->get_shipping_total();
			$cartTotal = $order->get_total();
		} else {

			$cart = WC()->cart;

			foreach ($cart->cart_contents as $item) {
				array_push(
					$cartContents,
					array(
						'title' => $item['data']->get_title(),
						'quantity' => $item['quantity'],
						'price' => $item['data']->get_price(),
						'product_id' => $item['product_id'],
						'virtual_product' => $item['data']->is_virtual(),
					)
				);
			}

			$shippingAmountTotal = $cart->get_shipping_total();
			$cartTotal = $cart->total;
		}

		// Apple Pay request line items.
		$lineItems =  $this->get_cart_data()['cartItems'];

		$applePayRequest = array(
			'currencyCode' => get_woocommerce_currency(),
			'countryCode' => wc_get_base_location()['country'],
			'requiredBillingContactFields' => array('email', 'name', 'phone', 'postalAddress'),
			'lineItems' => $lineItems,
			'total' => array(
				'label' => 'Total',
				'amount' => $cartTotal,
			),
			'supportedNetworks' => array(
				'amex',
				'visa',
				'discover',
				'masterCard',
			),
			'merchantCapabilities' => array('supports3DS'),
		);

		// Check if any coupons are available (therfore enabled)
		// If so add support for them to Apple Pay request.
		if (empty(WC()->cart->get_applied_coupons())) {
			$applePayRequest['supportsCouponCode'] = true;
		}

		// If shipping methods are available and one product in the
		// cart is not a virtual product, then add shipping requirmenets
		// to the Apple Pay request.
		if (!empty(WC()->session->get('chosen_shipping_methods')[0])) {
			foreach ($cartContents as $item) {
				if ($item['virtual_product'] === false) {
					$applePayRequest['requiredShippingContactFields'] = array('email', 'name', 'phone', 'postalAddress');
				}
			}
		}

		// If this is a failed order payment remove the shipping requirment.
		if ($failedOrderPaymnet) {
			unset($applePayRequest['requiredShippingContactFields']);
		}

		wp_send_json_success($applePayRequest);
		wp_die();
	}

	/**
	 * Update Shipping method.
	 * 
	 * This function will update the shipping
	 * method selected on the Apple Pay screen.
	 */
	public function update_shipping_method()
	{
		// Check nonce sent in request that called the function is correct.
		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}

		// Check there is a shipping method selected being posted.
		if (!empty($_POST['shippingMethodSelected'])) {

			// If the selected method is not a string then it's the Apple Pay UI updating. 
			// New cart data will be needed in a response.
			$shippingMethodSelected = json_decode(stripslashes_deep($_POST['shippingMethodSelected']));
			WC()->session->set('chosen_shipping_methods', array($shippingMethodSelected->identifier));

			WC()->cart->calculate_shipping();
			WC()->cart->calculate_totals();

			$cartData = $this->get_cart_data();

			// Return the response
			$JSONResponse = array(
				'status' => true,
				'lineItems' => $cartData['cartItems'],
				'total' => $cartData['cartTotal'],
			);
		} else {
			$JSONResponse = array(
				'status' => false,
			);
		}

		wp_send_json_success($JSONResponse);
	}

	/**
	 * Get Shipping Methods
	 *
	 * This function will get shipping methods 
	 * available for selection on the Apple Pay screen.
	 */
	public function get_shipping_methods()
	{
		// Check nonce sent in request that called the function is correct.
		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}

		$shippingContactSelectDetails = json_decode(stripslashes_deep($_POST['shippingContactSelected']));
		$zones = WC_Shipping_Zones::get_zones();
		$countryCode = $shippingContactSelectDetails->countryCode;
		$newShippingMethods = array();

		// Get the chosen shipping method from the session data.
		$shippingMethodSelected =  WC()->session->get('chosen_shipping_methods')[0];

		// Get coupons.
		$appliedCoupons = WC()->cart->get_applied_coupons();

		// For each shipping zone try to match with customer country code.
		foreach ($zones as $zone) {
			$shippingZoneID = new WC_Shipping_Zone($zone['id']);
			foreach ($zone['zone_locations'] as $zonelocation) {
				if ($zonelocation->code === $countryCode) {
					$newShippingMethods = $this->get_shipping_methods_from_zone($shippingZoneID, $appliedCoupons);
				}
			}
		}

		// If no shipping methods have been returned then try and return the default zone 0.
		if (($catchAllZone = new WC_Shipping_Zone(0)) && (count($newShippingMethods) === 0)) {
			$newShippingMethods =  $this->get_shipping_methods_from_zone($catchAllZone, $appliedCoupons);
		}

		WC()->customer->set_shipping_country($countryCode);
		// Set selected shipping method or top one as default
		WC()->session->set('chosen_shipping_methods', array($shippingMethodSelected));

		$cartData = $this->get_cart_data();

		// Return the response
		$JSONResponse = array(
			'status' => (count($newShippingMethods) === 0 ? false : true),
			'shippingMethods' => $newShippingMethods,
			'lineItems' => $cartData['cartItems'],
			'total' => $cartData['cartTotal']
		);

		wp_send_json_success($JSONResponse);
	}

	/**
	 * Get shipping methods from shipping zones
	 * 
	 * @param WC_Shipping_Zone $zone
	 */
	private function get_shipping_methods_from_zone($zone, $appliedCoupons) {

		$freeShippingCoupon = false;

		// For each coupon, if free shipping then set free shipping method allowed.
		foreach ($appliedCoupons as $couponCode) {
			$coupon = new WC_Coupon($couponCode);
			if ($coupon->get_free_shipping()) {
				$freeShippingCoupon = true;
			}
		}

		$newShippingMethods = array();

		$shippingMethods = $zone->get_shipping_methods();

		foreach ($shippingMethods as $shippingMethod) {
			if ($shippingMethod->requires == "coupon" && !$freeShippingCoupon) {
				continue;
			} else {
				array_push($newShippingMethods, array(
					'label' => strip_tags($shippingMethod->method_title),
					'detail' => strip_tags($shippingMethod->method_description),
					'amount' => (strlen($shippingMethod->cost) ? (float)$shippingMethod->cost : 0),
					'identifier' => $shippingMethod->id . ':' . $shippingMethod->instance_id,
					'selected' => ($shippingMethodSelected === ($shippingMethod->id . ':' . $shippingMethod->instance_id))
				));
			}
		}

		return ($newShippingMethods ?? false); 

	}

	/**
	 * Apple a coupon code from ApplePay 
	 */
	public function apply_coupon_code()
	{

		if (!wp_verify_nonce($_POST['securitycode'], $this->nonce_key)) {
			wp_die();
		}

		if (!empty($couponCode = $_POST['couponCode'])) {

			if (WC()->cart->has_discount($couponCode)) {
				return;
			}

			WC()->cart->apply_coupon($couponCode);

			$cartData = $this->get_cart_data();

			// Return the response
			$JSONResponse = array(
				'lineItems' => $cartData['cartItems'],
				'total' => $cartData['cartTotal']
			);

			wp_send_json_success($JSONResponse);
		}

		wp_send_json_success(['error' => 'Missing shipping contact']);
	}

	/**
	 * Get shopping cart items and totals
	 *
	 * This function will recalculate the shopping 
	 * carts totals including shipping cost and return
	 * the data as an array. It is assumed at this 
	 * point a shipping method has been selected, 
	 * otherwise no shipping costs will returned 
	 * which can result in mismatched amounts 
	 * between the order and ApplePay token
	 * 
	 * returns Array
	 */
	protected function get_cart_data()
	{

		// Recalculate cart totals.
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();

		$cartContents = array();
		$shippingAmountTotal = 0;
		$cartTotal = 0;

		$cart = WC()->cart;

		foreach ($cart->cart_contents as $item) {
			array_push(
				$cartContents,
				array(
					'title' => $item['data']->get_title(),
					'quantity' => $item['quantity'],
					'price' => $item['data']->get_price(),
					'product_id' => $item['product_id'],
				)
			);
		}

		$shippingAmountTotal = $cart->get_shipping_total();
		$cartTotal = $cart->total;


		// Apple Pay request line items.
		$lineItems = array();

		// Add the shipping amount to the request.
		array_push($lineItems, array('label' => 'Shipping', 'amount' => $shippingAmountTotal));

		// For each item in the cart add to line items.
		foreach ($cartContents as $item) {

			$itemTitle = $item['title'];
			$itemPrice = $item['price'];
			$itemQuantity = $item['quantity'];

			$productID = wc_get_product($item['product_id']);


			if (class_exists('WC_Subscriptions_Product') && WC_Subscriptions_Product::is_subscription($productID)) {

				$firstPaymentDate = (WC_Subscriptions_Product::get_trial_expiration_date($productID)
					? WC_Subscriptions_Product::get_trial_expiration_date($productID) : date('Y-m-d'));

				$recurringPaymentIntervalUnit = WC_Subscriptions_Product::get_period($productID);
				$recurringPaymentIntervalCount = WC_Subscriptions_Product::get_interval($productID);

				$subscriptionItem = array(
					'label' => "{$itemTitle}",
					'amount' => $itemPrice,
					'recurringPaymentStartDate' => $firstPaymentDate,
					'recurringPaymentIntervalUnit' => $recurringPaymentIntervalUnit,
					'paymentTiming' => 'recurring',
					'recurringPaymentIntervalCount' => $recurringPaymentIntervalCount,
				);

				// Detect if subscription is a week and convert to 7 days.
				// ApplePayRecurringPaymentDateUnit only accepts minute, hour, day, month or year.
				if (($recurringPaymentIntervalUnit = WC_Subscriptions_Product::get_period($productID)) == 'week') {
					$subscriptionItem['recurringPaymentIntervalUnit'] = 'day';
					$subscriptionItem['recurringPaymentIntervalCount'] = $recurringPaymentIntervalCount * 7;
				}

				if ($signUpFee = WC_Subscriptions_Product::get_sign_up_fee($productID)) {
					array_push($lineItems, array('label' => "{$itemTitle} Sign up fee ", 'amount' => $signUpFee));
				}

				// Add sub
				array_push($lineItems, $subscriptionItem);
			} else {
				array_push($lineItems, array('label' => "{$itemQuantity} x {$itemTitle}", 'amount' => ($itemPrice * $itemQuantity)));
			}
		}


		return array('cartItems' => $lineItems, 'cartTotal' => $cartTotal, 'shippingAmountTotal' => $shippingAmountTotal);
	}

	/**
	 * On Order Success
	 *
	 * Called when the payment is successful.
	 * This will complete the order.
	 *
	 * @param Array     $data
	 */
	private function on_order_success($data)
	{
		// Get an instance of the WC_Order object
		$order = wc_get_order($data);

		$gatewayResponse = $order->get_meta('gatewayResponse');

		$orderNotes = "\r\nResponse Code : {$gatewayResponse['responseCode']}\r\n";
		$orderNotes .= "Message : {$gatewayResponse['responseMessage']}\r\n";
		$orderNotes .= "Amount Received : " . number_format($gatewayResponse['amount'] / 100, 2) . "\r\n";
		$orderNotes .= "Unique Transaction Code : {$gatewayResponse['transactionUnique']}";

		$order->set_transaction_id($gatewayResponse['xref']);
		$order->add_order_note(__(ucwords($this->method_title) . ' payment completed.' . $orderNotes, $this->lang));
		$order->payment_complete();

		$redirectURL = $this->get_return_url($order);
		// Return the redirect URL
		return $redirectURL;
	}

	/**
	 * On Order Fail
	 *
	 * Called when the payment is successful.
	 * This will complete the order.
	 *
	 * @param Array     $data
	 */
	private function on_order_fail($data)
	{
		// Get an instance of the WC_Order object
		$order = wc_get_order($data);

		$gatewayResponse = $order->get_meta('gatewayResponse');

		$orderNotes = "\r\nResponse Code : {$gatewayResponse['responseCode']}\r\n";
		$orderNotes .= "Message : {$gatewayResponse['responseMessage']}\r\n";
		$orderNotes .= "Amount Received : " . number_format($gatewayResponse['amount'] / 100, 2) . "\r\n";
		$orderNotes .= "Unique Transaction Code : {$gatewayResponse['transactionUnique']}";

		$order->update_status('failed');
		$order->add_order_note(__(ucwords($this->method_title) . ' payment failed.' . $orderNotes, $this->lang));

		return $this->get_return_url($order);
	}

	/**
	 * Payment fields
	 *
	 * Uses the payment field method to display the Apple Pay
	 * button on the checkout page.
	 */
	public function payment_fields()
	{
		echo <<<EOS
		<style>
		#applepay-button {
			width: auto;
			height: 60px;
			border-radius: 5px;
			background-repeat: no-repeat;
			background-size: 80%;
			background-image: -webkit-named-image(apple-pay-logo-white);
			background-position: 50% 50%;
			background-color: black;
			margin: auto;
			cursor: pointer;
		}
		</style>
		<div id="applepay-button-container" style="display: none;" >
			<div id="applepay-button" onclick="applePayButtonClicked()"> </div>
		</div>
		<div id="applepay-not-available-message" style="display: none;">
			<label>Apple Pay is not available on this device.</label>
		</div>
		<div id="applepay-not-setup" style="display: none;">
			<label>Apple Pay is not setup on this device.</label>
		</div>
		EOS;
	}

	/**
	 * Cart page Apple Pay
	 *
	 * Allows the Apple Pay button to appear on the cart page
	 * If a subscription is in the cart and the user is not logged in
	 * the button will not appear as they have to be logged in to 
	 * sign up.
	 */
	public function cart_page_ap()
	{

		if (class_exists('WC_Subscriptions_Product')) {
			$cart = WC()->cart;

			foreach ($cart->cart_contents as $item) {
				// If subscription setup.
				if (WC_Subscriptions_Product::is_subscription($item['product_id']) && !is_user_logged_in()) {
					return false;
				}
			}
		}
		$this->payment_fields();
	}

	/**
	 * Payments scripts
	 *
	 * Enqueues the Apple Pay javascript
	 */
	public function payment_scripts()
	{
		// if our payment gateway is disabled, we do not have to enqueue JS too
		if ($this->enabled === 'no') {
			return;
		}

		wp_register_script('applepay_script', $this->pluginURL . '/assets/js/applepay.js');
		wp_enqueue_script('applepay_script');
		wp_localize_script('applepay_script', 'localizeVars', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'securitycode' => wp_create_nonce($this->nonce_key),
		));

		add_action('wp_ajax_nopriv_get_data', array($this, 'get_data'), 10, 2);
		add_action('wp_ajax_get_data', array($this, 'get_data'), 10, 2);
	}

	/**
	 * Create signature
	 *
	 * @param Array		$data
	 * @param String	$key
	 * @return String
	 */
	public function createSignature(array $data, $key)
	{
		// Sort by field name
		ksort($data);
		// Create the URL encoded signature string
		$ret = http_build_query($data, '', '&');
		// Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
		$ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);
		// Hash the signature string and the key together
		return hash('SHA512', $ret . $key);
	}

	/**
	 * Process Refund
	 *
	 * Refunds a settled transactions or cancels
	 * one not yet settled.
	 *
	 * @param Integer        $amount
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

		$gateway = new Gateway(
			$this->defaultMerchantID,
			$this->defaultMerchantSignature,
			$this->defaultGatewayURL
		);

		// Query the transaction state.
		$queryPayload = [
			'merchantID' => $this->defaultMerchantID,
			'xref' => $transactionXref,
			'action' => 'QUERY',
		];

		// Sign the request and send to gateway.
		$transaction = $gateway->directRequest($queryPayload);

		if (empty($transaction['state'])) {
			return new WP_Error('error', "Could not get the transaction state for {$transactionXref}");
		}

		if ($transaction['responseCode'] == 65558) {
			return new WP_Error('error', "IP blocked primary");
		}

		// Build the refund request
		$refundRequest = [
			'merchantID' => $this->defaultMerchantID,
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
		$refundResponse = $gateway->directRequest($refundRequest);

		// Handle the refund response
		if (empty($refundResponse) && empty($refundResponse['responseCode'])) {

			return new WP_Error('error', "Could not refund {$transactionXref}.");
		} else {

			$orderMessage = ($refundResponse['responseCode'] == "0" ? "Refund Successful" : "Refund Unsuccessful") . "<br/><br/>";

			$state = ($refundResponse['state'] ?? null);

			if ($state != 'canceled') {
				$orderMessage .= "Amount Refunded: " . number_format($amountToRefund / pow(10, $refundResponse['currencyExponent']), $refundResponse['currencyExponent']) . "<br/><br/>";
			}

			$order->add_order_note($orderMessage);
			return true;
		}

		return new WP_Error('error', "Could not refund {$transactionXref}.");
	}

	/**
	 * Send To
	 * 
	 * Posts data to a URL using curl.
	 */
	public function send_to($url, array $data, array $options = null, bool $raw = false)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, ($raw ? http_build_query($data) : $data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if (isset($options)) {
			curl_setopt_array($ch, $options);
		}

		try {
			$ret = curl_exec($ch);
		} catch (\exception $e) {
			curl_close($ch);
			$this->debug_log('DEBUG', "Curl exception", $e);
		}

		$curl_info = curl_getinfo($ch);
		if ($ret === false) {
			$curl_info['errno'] = $errno = (int)curl_errno($ch);
			$curl_info['error'] = curl_error($ch);
			$this->debug_log('DEBUG', "Request error", $curl_info);
		} else if (isset($curl_info['http_code'])) {
			$status = (int)$curl_info['http_code'];
		}

		curl_close($ch);
		if ($ret === false) {
			$this->debug_log('DEBUG', "Request error", $curl_info['errno']);
		}

		return $ret;
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