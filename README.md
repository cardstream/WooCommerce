Disclaimer: Please note that we no longer support older versions of SDKs and Modules. We recommend that the latest versions are used.

Cardstream Woocommerce Plugin v3.2.1
==============

This module enables the use of https://gateway.cardstream.com payment gateway using the Woocommerce project http://www.woothemes.com.

COMPATIBILITY
------------

Compatible with version 10.x of Woocommerce and upto 6.x of Wordpress.

REQUIREMENTS
------------

PHP-BCMaths

INTRODUCTION
------------

This module enables the woocommerce customers to pay for their items using the Cardstream hosted form or direct payment gateway.

What does it do?
----------------
Presents the option to pay with credit card or debit card via the Cardstream payment gateway.


INSTALLATION
------------

1. Go to the plugins section of the admin panel

2. Click Add New

3. Click Upload plugin (Near the top left of the page nexto the menu)

4. Click the "Choose File" button and select the module (which will be the whole zip file this readme is in)

4. Click the "Install Now" button and then click the "Activate" button.


Manual installation 
--------------------

1. Unzip and upload the plugin folder to your /wp-content/plugins/ directory

2. Activate the plugin through the Plugins menu in WordPress

3. Go to WooCommerce -> Settings and click on the Checkout tab.

4. Find Cardstream in the Payment Gateways section 

5. Click the settings button to configure and enable the gateway.

6. Click 'Save Changes'.


Rebrand Instructions
--------------------

The module does not require any editing of file to be used. The options can be changed via the plugin settings.
However you can pre set some of the branding options by by editing the config.php file.
This will allow you to set the defaults which are :

gateway_title is the title of the module that will appear to the user when selecting the payment method on the checkout.
method_description is the description that appears in the payment selected on checkout.
default_merchant_id is the default merchant ID the module will use. It's recommended to use a test account.
default_secrect is the signature/secret for the default merchant.


Setup Instructions
--------------------

Setting up the module requires at a minimum a merchantID, a signature/secret key and
a gateway URL i.e. https://gateway.cardstream.com to be entered in the plugin's settings.

You will then need to select an integration type to use.

The module will also need to be enabled so it appears as a payment option on the checkout.

**NOTE:**
 The gateway_validation_available flag should only be set to true if your Gateway Merchant ID has been enabled for Apple Pay. Enabling it without gateway configuration could result in issues with transactions.

**Disclaimer**

Sample code, SDKs and modules have been created as reference material only. Modules are developed and tested against vanilla base platform installs only. Any further module compatibility would need to be tested by the user/merchant/developer. Version support is as shown within the associated VERSION section. All sample code, SDKs and modules offer foundation transaction functionality for merchant and developers to use as a guide and/or to adapt, enhance or otherwise build upon. For the avoidance of doubt, this means that some desired functionality may not be useable or exist. All sample code, SDKs or modules that are used will require complete full end to end testing by the user/merchant/developer. Further to this, use of any sample code, SDKs or modules, Cardstream bears no responsibility for; nor extends any warranty in regard to; nor accepts any liability arising due to any changes or errors in functionality that may result. Developers, merchants or other users of any sample code, SDKs or modules accept these conditions de facto upon usage.
