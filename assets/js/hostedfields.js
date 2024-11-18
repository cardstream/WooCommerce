$(document).ready(function() {

	// Collect 3DS Device information variables.
	var screen_width = (window && window.screen ? window.screen.width : '0');
	var screen_height = (window && window.screen ? window.screen.height : '0');
	var screen_depth = (window && window.screen ? window.screen.colorDepth : '0');
	var identity = (window && window.navigator ? window.navigator.userAgent : '');
	var language = (window && window.navigator ? (window.navigator.language ? window.navigator.language : window.navigator.browserLanguage) : '');
	var timezone = (new Date()).getTimezoneOffset();
	var java = (window && window.navigator ? navigator.javaEnabled() : false);

	// Hosted Fields instance.
	var hostedFields = false;

	// Add listener to document body to check when payment fields are ready.
	jQuery(document.body).on("payment-fields-ready", function () {
		setupHostedFields();
	});

	/**
	 * Setup Hosted fields
	 */
	function setupHostedFields() {

		// If there is already a hosted fields instance destory it.
		if (hostedFields) {
			hostedFields.destroy();
			hostedFields = false;
		}

		// Add secuirty toen
		$("#hosted-fields-security-code").val(hfLocalizeVars.securitycode);

		// Add device information needed to complete 3DS.
		document.getElementById('deviceIdentity').value = identity;
		document.getElementById('deviceTimeZone').value = timezone;
		document.getElementById('deviceCapabilities').value = 'javascript' + (java ? ',java' : '');
		document.getElementById('deviceAcceptLanguage').value = language;
		document.getElementById('deviceScreenResolution').value = screen_width + 'x' + screen_height + 'x' + screen_depth;

		hostedFields = new window.hostedFields.classes.Form(window.document.forms.checkout, {
			// Auto setup the payment fields.
			autoSetup: true,
			// Disable auto submit.
			autoSubmit: false,
		});

		// Setup place order listener.
		$("#place_order").on("click", async function (event) {

			// Temporaliy prevent the default event actions.
			event.preventDefault();
			// Reset any hosted previous fields errors.
			$("#hosted-fields-error").text('');
			$("#hosted-fields-error-input").val('');

			// Tokenise the card details.
			await hostedFields.getPaymentDetails({}, true)
				.then(function (result) {
					
					if (result.success) {
						// Add payment token to the submitted form.
						hostedFields.addPaymentToken(result.paymentToken);
						// Continue to submit checkout form.
						$("#place_order").submit();

					} else if (result.invalid) {

						let hostedFieldsError = Object.values(result.invalid)[0];

						hostedFields.reset();
						$("#hosted-fields-error").text(hostedFieldsError);
						$("#hosted-fields-error-input").val(hostedFieldsError);
						$("#place_order").submit();

					} else {
						alert("There was a problem with the payment. Please contact support.");
						return false;
					}
				},
			);
		});
	}
});
