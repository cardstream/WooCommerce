window.onload = function () {

  // Add listener to document body to check when payment fields are ready.
	jQuery(document.body).on("apple-pay-admin-loaded", function () {
		setupApplePayAdminJS();
	});

  function setupApplePayAdminJS() {

    var merchantCertGenButton = document.getElementById("merchant-cert-gen-button");
    var saveButton = document.getElementsByName("save")[0];
    var uploadCertHelpIcon = document.getElementById("upload-cert-help-icon");
    var certificateHelpWindow = document.getElementById("certificate-help-window");
    var certificateHelpWindowCloseButton = document.getElementById("close-help-window-icon");
    var certificatesContainer = document.getElementById("generated-certs-container");

    uploadCertHelpIcon.addEventListener("click", function (event) {
      certificateHelpWindow.style.display = 'block';
    });

    certificateHelpWindowCloseButton.addEventListener("click", function (event) {
      certificatesContainer.style.display = "none";
      certificateHelpWindow.style.display = "none";
    });

    saveButton.addEventListener("click", function (event) {
      if (
        localizeVars.certificateAndKeyExist &&
        (document.getElementById("merchantCertUpload").files.length > 0 ||
          document.getElementById("merchantCertKeyUpload").files.length > 0)
      ) {
        let confirmAction = confirm("Are you sure you want to overwrite your certificate and key?");

        if (confirmAction) {
          alert("Remember to change the password saved if the new certificate key's password changed");
          return;
        } else {
          event.preventDefault();
        }
      }
    });

    // Merchant Cert Gen button - Click
    merchantCertGenButton.addEventListener("click", function () {

      // Prompt the user to enter a key password
      let certificateKeyPassword = window.prompt(
        "Enter a password for the certificate. Note this down and remember to enter this into the 'Merchant certificate key password' field."
      );

      // If they do not abort
      if (certificateKeyPassword === null) {
        alert("Certificate generation aborted");
        return;
      }

      // Remove the certificate container.
      // Change the state of the button to loading.
      certificatesContainer.style.display = "none";
      merchantCertGenButton.style.width = "130px";
      merchantCertGenButton.innerHTML = "Generating files";
      merchantCertGenButton.disabled = true;
      merchantCertGenButton.classList.add("button--loading");

      document.getElementById("generated-certs-container").style.display = "hidden";

      generate_csr_and_key(certificateKeyPassword).then(function (response) {
        if (response.success) {
          // CSR download
          csrdownloadhref = document.getElementById("csrdownloadhref");
          csrdownloadhref.setAttribute(
            "href",
            "data:text/plain;charset=utf-8," + encodeURIComponent(response.data.csr_file)
          );
          csrdownloadhref.setAttribute("download", "apple_pay_merchant_id.csr");
          // Key download
          keydownloadhref = document.getElementById("keydownloadhref");
          keydownloadhref.setAttribute(
            "href",
            "data:text/plain;charset=utf-8," + encodeURIComponent(response.data.key_file)
          );
          keydownloadhref.setAttribute(
            "download",
            "apple_pay_merchant_id_key_file.key"
          );
          // Display the certificate and key for downloading.
          document.getElementById("generated-certs-container").style.display = "grid";
        } else {
          alert("Something went wrong");
        }
      }).catch(function (err) {
        console.log("gen cert fail - failure: ", err);
      }).finally(() => {
        merchantCertGenButton.classList.remove("button--loading");
        merchantCertGenButton.style.width = "fit-content";
        merchantCertGenButton.innerHTML = "Generate CSR and key"
        merchantCertGenButton.disabled = false;
      });
    });
  };

  /**
   * Generate CSR and Private Key
   * 
   * @param {string} keyPassword 
   * @returns {Promise}
   */
  function generate_csr_and_key(keyPassword) {
    return new Promise(function (resolve, reject) {
      let formData = new FormData();
      formData.append("action", "generate_csr_and_key");
      formData.append("securitycode", localizeVars.securitycode);
      formData.append("keypassword", keyPassword);

      fetch(localizeVars.ajaxurl, {
        method: "POST",
        body: formData,
      }).then(function (res) {
        console.log("performValidation() - success: result is ", res);
        resolve(res.json());
      }).catch(function (err) {
        console.log("performValidation() - failure: ", err);
        reject(err);
      });
    });
  }

}
