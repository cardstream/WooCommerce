const debuggingOn = false;
const ApplePayVersion = 4;
var ApplePayRequest;

// On window load check if applepay is enabled and can make payments.
// If it is and can then enable the payment option
window.onload = function () {

	getApplePayButton();

	function getApplePayButton() {
		if (isApplePayEnabled() && canMakeApplePayPayments()) {
			log('Apple Pay enabled and can make payments');
			const urlParams = new URLSearchParams(window.location.search);

			processAPIRequest("get_applepay_request", { orderID: urlParams.get('key') ?? false }).then(function (response) {
				ApplePayRequest = response.data;
				log("get_applepay_request", ApplePayRequest);
				log("get_applepay_request - line items", ApplePayRequest.lineItems);
				window.document.getElementById("applepay-button-container").style.display = "block";
			}).catch(function (err) {
				log("session.onvalidatemerchant - failure: ", err);
			});

			// Add event listeners if Apple Pay is enabled.

			jQuery(document.body).on("updated_checkout", function () {
				getApplePayButton();
			});
		
			jQuery(document.body).on("updated_shipping_method", function () {
				getApplePayButton();
			});
		
			jQuery(document.body).on("updated_wc_div", function () {
				window.location.reload();
			});

		} else if (isApplePayEnabled() && !canMakeApplePayPayments()) {
			log('Apple  Pay enabled but cant make payment');
			window.document.getElementById("applepay-not-setup").style.display = "block";
		} else {
			window.document.getElementById("applepay-not-available-message").style.display = "block";
		}
	}
};

/**
 * Apple Pay button
 *
 * Runs when the ApplePay button is clicked.
 */
function applePayButtonClicked() {
	if (!isApplePayEnabled()) {
		log("applePayButtonClicked() - Apple Pay button was clicked but apple pay is not enabled");
		return;
	}
	log("applePayButtonClicked() - Create a new ApplePaySession");
	// Create a new ApplePaySession
	var session = new ApplePaySession(ApplePayVersion, ApplePayRequest);
	// Being the ApplePaySession
	session.begin();

	/**
	 * Apple Pay Session - On Validate Merchant
	 *
	 * Ensures that the merchant identifier being used to conduct the
	 * transaction is known and confirmed by Apple Pay Session as a
	 * legitimate merchant.
	 *
	 * After the session begins onvalidatemerchant is called by the
	 * ApplePay session
	 *
	 * @param {object} event
	 * @returns void
	 */
	session.onvalidatemerchant = function (event) {
		log("session.onvalidatemerchant() ", event);

		processAPIRequest("validate_applepay_merchant", { validationURL: event.validationURL }).then(function (merchantSession) {
			console.log("Complete session");
			session.completeMerchantValidation(JSON.parse(merchantSession.data));
		}).catch(function (error) {
			log("session.onvalidatemerchant() - failure: ", error);
		});
	}
	/**
	* Apple Pay Session - On Shipping Method Selected
	*
	* @param {object} event
	* @returns {void}
	*/
	session.onshippingmethodselected = function (event) {
		log("onShippingMethodSelected() ", event);
		log("Pay request line items: ", ApplePayRequest.lineItem);

		processAPIRequest("update_shipping_method", { shippingMethodSelected: JSON.stringify(event.shippingMethod) }).then(function (response) {


			log("session.onshippingmethodselected() - ApplePayRequest.total: ", ApplePayRequest.total);
			log("response", response.data);
			session.completeShippingMethodSelection(
				{
					newTotal: { label: "Total", amount: response.data.total },
					newLineItems: response.data.lineItems
				}
			);
		})
			.catch(function (err) {
				log("updateOrderShippingMethod - failure: ", err);
			});
	};
	/**
	* Apple Pay Session - On Shipping Contact Selected
	*
	* @param {object} event
	* @returns {void}
	*/
	session.onshippingcontactselected = function (event) {

		log("Pay request line items: ", ApplePayRequest.lineItem);

		log("session.onshippingcontactselected() - event.shippingContact : ", event.shippingContact);

		processAPIRequest("get_shipping_methods", { "shippingContactSelected": JSON.stringify(event.shippingContact) }).then(function (response) {

			let completeShippingContactSelectionParams = {
				newShippingMethods: response.data.shippingMethods,
				newTotal: { label: (response.data.status ? "Total" : "No shipping options available"), amount: response.data.total },
				newLineItems: response.data.lineItems
			};

			log("completeShippingContactSelectionParams", completeShippingContactSelectionParams);

			if (response.data.status == false) {
				log("session.onshippingcontactselected() - No shipping methods available ");
				completeShippingContactSelectionParams.errors = [
					new ApplePayError("shippingContactInvalid", "postalAddress", "No shipping methods available for your address")
				];
			}

			log("session.onshippingcontactselected() - ApplePayRequest.shippingMethods ", ApplePayRequest.shippingMethods)
			log("session.onshippingcontactselected() - completeShippingContactSelectionParams: ", completeShippingContactSelectionParams);

			// Complete shipping contact selection.
			session.completeShippingContactSelection(completeShippingContactSelectionParams);
		}).catch(function (err) {
			log("session.onvalidatemerchant - failure: ", err);
		});
	};
	/**
	 * Apple Pay Session - On Payment Authorized
	 *
	 * After the payment is authorized, create the order and
	 * process the token.
	 * 
	 * @param {*} event
	 * @returns void
	 */
	session.onpaymentauthorized = function (event) {
		log("session.onpaymentauthorized() -", event);
		// Send the token to the backend to be processed.
		const urlParams = new URLSearchParams(window.location.search);

		processAPIRequest("process_applepay_payment", { payment: JSON.stringify(event.payment), orderID: urlParams.get('key') ?? false }).then(function (response) {
			log("session.onpaymentauthorized() - Gateway response - ", response);
			try {
				let result = {
					status: (response.data.paymentComplete
						? ApplePaySession.STATUS_SUCCESS
						: ApplePaySession.STATUS_FAILURE),
				};
				if (!response.success) {
					result.errors = [new ApplePayError("unknown", "name", response.message)];
				} else {
					if (ApplePayVersion >= 3) {
						session.completePayment(result);
					} else {
						log("Completing payment");
						session.completePayment(result.status);
					}
					log("response", response.data["redirect"]);
					window.location.replace(response.data["redirect"]);
				}
			} catch (error) {
				log("Error completing payment with Apple Pay: ", error);
				// Continue as normal as the payment has been taken even if
				// couldn't tell Apple Pay it had been.
			}
		})
			.catch(function (error) {
				log("session.onpaymentauthorised() - failed: ", error);
			});
	};
	/**
	 * Apple Pay Session - On Cancel
	 *
	 * @param {object} event
	 */
	session.oncancel = function (event) {
		window.location.reload();
	};

	session.addEventListener("couponcodechanged", function (event) {

		var newCode = event.couponCode;

		processAPIRequest("apply_coupon_code", { "couponCode": newCode }).then(function (response) {

			let completeCouponCodeChange = {
				//	newShippingMethods: response.data.shippingMethods,
				newTotal: { label: "Total", amount: response.data.total },
				newLineItems: response.data.lineItems
			};

			session.completeCouponCodeChange(completeCouponCodeChange);

		}).catch(function (err) {
			log("session.onvalidatemerchant - failure: ", err);
		});

	});
}

/**
 * Is Apple Pay Enabled
 *
 * Checks if apple pay is enabled.
 *
 * @returns {bool}
 */
function isApplePayEnabled() {
	// If ApplePaySession is undefined or null then Applepay is not available on this device
	if (typeof ApplePaySession === "undefined" || ApplePaySession === null) {
		log("ApplePay Session not available. Either this is not an Apple Device or payment not available");
		return false;
	} else if (ApplePaySession) {
		console.log('Applepay enabled');
		return true;
	}
}

/**
 * Can Apple Pay make payments
 *
 * Checks if apple pay is enabled and can make payments.
 *
 * @returns {bool}
 */
function canMakeApplePayPayments() {
	if (typeof ApplePaySession !== "undefined") {
		// Check if ApplePay session can make payments
		if (ApplePaySession.canMakePayments()) {
			return true;
		}
	}
	return false;
}

/**
 * Log
 * 
 * Outputs a log message to the console if
 * debuggingOn is true.
 *
 * @param {string} string
 * @param {object} object
 * @return {void}
 */
function log(string, object = null) {
	if (debuggingOn) {
		if (object) {
			console.log("[ApplePay][" + Date.now() + "] - " + string, object);
		} else {
			console.log("[ApplePay][" + Date.now() + "] - " + string);
		}
	}
}

/**
 * Process API Request
 *
 * @param {string}  action
 * @param {JSON}    data
 * @returns {Promise}
 */
function processAPIRequest(action, data = {}) {
	// Create a new FormData.
	let formData = new FormData();
	// Add the action and security code to the form data.
	formData.append("action", action);
	formData.append("securitycode", localizeVars.securitycode);
	// For each key/value in data object add to form data.
	for (let [key, value] of Object.entries(data)) {
		formData.append(key, value);
	}
	// Return a new promise that processes the API request.
	return new Promise(function (resolve, reject) {
		// Fetch data from the localized ajaxURL
		fetch(localizeVars.ajaxurl, {
			method: "POST",
			body: formData,
		}).then(function (response) {
			log(`Processing API action ${action} - success: result is `, response);
			resolve(response.json());
		}).catch(function (error) {
			log(`Processing API action ${action} - failure: `, error);
			reject(error);
		});
	});
}