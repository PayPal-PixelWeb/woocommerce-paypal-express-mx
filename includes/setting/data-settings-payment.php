<?php
/**
 * Fields of Plugins settings.
 *
 * @package   WooCommerce -> Paypal Express Checkout MX
 * @author    Kijam Lopez <info@kijam.com>
 * @license   Apache-2.0
 * @since 1.0.0
 */

return array(

	'enabled' => array(
		'title' => __( 'Enable/Disable', 'woocommerce-paypal-express-mx' ),
		'type' => 'checkbox',
		'label' => __( 'Enable Paypal Express Checkout', 'woocommerce-paypal-express-mx' ),
		'default' => 'yes',
	),
	'title' => array(
		'title'       => __( 'Title', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-paypal-express-mx' ),
		'default'     => __( 'PayPal', 'woocommerce-paypal-express-mx' ),
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-paypal-express-mx' ),
		'default'     => __( 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.', 'woocommerce-paypal-express-mx' ),
	),
	'environment' => array(
		'title'       => __( 'Environment', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'This setting specifies whether you will process live transactions, or whether you will process simulated transactions using the PayPal Sandbox.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'live',
		'desc_tip'    => true,
		'options'     => array(
			'live'    => __( 'Live', 'woocommerce-paypal-express-mx' ),
			'sandbox' => __( 'Sandbox', 'woocommerce-paypal-express-mx' ),
		),
	),
	'api_credentials' => array(
		'title'       => __( 'API Credentials', 'woocommerce-paypal-express-mx' ),
		'type'        => 'title',
		'description' => $api_creds_text,
	),
	'api_username' => array(
		'title'       => __( 'API Username', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ) . ' ' . sprintf(
			/* translators: %1$s: is URL of manual-get Sandbox API Credential.  */
					__( 'To get Sandbox NVP/SOAP manualy click <a href="%1$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
			'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
		),
		'default'     => '',
		'desc_tip'    => false,
	),
	'api_password' => array(
		'title'       => __( 'API Password', 'woocommerce-paypal-express-mx' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ) . ' ' . sprintf(
			/* translators: %1$s: is URL of manual-get Sandbox API Credential.  */
					__( 'To get Sandbox NVP/SOAP manualy click <a href="%1$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
			'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
		),
		'default'     => '',
		'desc_tip'    => false,
	),
	'api_signature' => array(
		'title'       => __( 'API Signature', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ) . ' ' . sprintf(
			/* translators: %1$s: is URL of manual-get Sandbox API Credential.  */
					__( 'To get Sandbox NVP/SOAP manualy click <a href="%1$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
			'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
		),
		'default'     => '',
		'desc_tip'    => false,
		'placeholder' => __( 'Optional if you provide a certificate below', 'woocommerce-paypal-express-mx' ),
	),
	'api_certificate' => array(
		'title'       => __( 'API Certificate', 'woocommerce-paypal-express-mx' ),
		'type'        => 'file',
		'description' => $api_certificate_msg,
		'default'     => '',
	),
	'api_subject' => array(
		'title'       => __( 'API Subject', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'If you\'re processing transactions on behalf of someone else\'s PayPal account, enter their email address or Secure Merchant Account ID (also known as a Payer ID) here. Generally, you must have API permissions in place with the other account in order to process anything other than "sale" transactions for them.', 'woocommerce-paypal-express-mx' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),
	'sandbox_api_credentials' => array(
		'title'       => __( 'Sandbox API Credentials', 'woocommerce-paypal-express-mx' ),
		'type'        => 'title',
		'description' => $sandbox_api_creds_text,
	),
	'sandbox_api_username' => array(
		'title'       => __( 'Sandbox API Username', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ) . ' ' . sprintf(
			/* translators: %1$s: is URL of manual-get Sandbox API Credential.  */
					__( 'To get Sandbox NVP/SOAP manualy click <a href="%1$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
			'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
		),
		'default'     => '',
		'desc_tip'    => false,
	),
	'sandbox_api_password' => array(
		'title'       => __( 'Sandbox API Password', 'woocommerce-paypal-express-mx' ),
		'type'        => 'password',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ) . ' ' . sprintf(
			/* translators: %1$s: is URL of manual-get Sandbox API Credential.  */
					__( 'To get Sandbox NVP/SOAP manualy click <a href="%1$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
			'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
		),
		'default'     => '',
		'desc_tip'    => false,
	),
	'sandbox_api_signature' => array(
		'title'       => __( 'Sandbox API Signature', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ) . ' ' . sprintf(
			/* translators: %1$s: is URL of manual-get Sandbox API Credential.  */
					__( 'To get Sandbox NVP/SOAP manualy click <a href="%1$s" target="_blank">here</a>', 'woocommerce-paypal-express-mx' ),
			'https://www.sandbox.paypal.com/businessprofile/mytools/apiaccess/firstparty'
		),
		'default'     => '',
		'desc_tip'    => false,
		'placeholder' => __( 'Optional if you provide a certificate below', 'woocommerce-paypal-express-mx' ),
	),
	'sandbox_api_certificate' => array(
		'title'       => __( 'Sandbox API Certificate', 'woocommerce-paypal-express-mx' ),
		'type'        => 'file',
		'description' => $sandbox_api_certificate_msg,
		'default'     => '',
	),
	'sandbox_api_subject' => array(
		'title'       => __( 'Sandbox API Subject', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'If you\'re processing transactions on behalf of someone else\'s PayPal account, enter their email address or Secure Merchant Account ID (also known as a Payer ID) here. Generally, you must have API permissions in place with the other account in order to process anything other than "sale" transactions for them.', 'woocommerce-paypal-express-mx' ),
		'default'     => '',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),
	'features' => array(
		'title'       => __( 'Features enabled', 'woocommerce-paypal-express-mx' ),
		'type'        => 'title',
	),
	'cart_checkout_enabled' => array(
		'title'       => __( 'Checkout on cart page', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable PayPal checkout on the cart page', 'woocommerce-paypal-express-mx' ),
		'description' => __( 'This shows or hides the PayPal checkout button on the cart page.', 'woocommerce-paypal-express-mx' ),
		'desc_tip'    => true,
		'default'     => 'yes',
	),
	'product_checkout_enabled' => array(
		'title'       => __( 'Checkout on Product', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Checkout on Product', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'Enable Express checkout on Product view.', 'woocommerce-paypal-express-mx' ),
	),
	'payment_checkout_enabled' => array(
		'title'       => __( 'Checkout on List Payments Gateways', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Checkout on List Payments Gateways', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'Enable Paypal on List Payments Gateways', 'woocommerce-paypal-express-mx' ),
	),
	'invoice_prefix' => array(
		'title'       => __( 'Invoice Prefix', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'WC-',
		'desc_tip'    => true,
	),
	'require_confirmed_address' => array(
		'title'       => __( 'Require confirmed address by Paypal for Selling Protection Program', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Require confirmed address by Paypal for Selling Protection Program', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'description' => __( 'Require buyer to enter their confirmed address during checkout for Selling Protection Program', 'woocommerce-paypal-express-mx' ),
	),

	/*
	'credit_enabled' => array(
		'title'       => __( 'Enable PayPal Credit', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Currently PayPal Credit only available for U.S. merchants.', 'woocommerce-paypal-express-mx' ),
		'disabled'    => ! $this->is_credit_supported(),
		'default'     => 'no',
		'desc_tip'    => true,
		'description' => __( 'This enables PayPal Credit, which displays a PayPal Credit button next to the Express Checkout button. PayPal Express Checkout lets you give customers access to financing through PayPal Credit® - at no additional cost to you. You get paid up front, even though customers have more time to pay. A pre-integrated payment button shows up next to the PayPal Button, and lets customers pay quickly with PayPal Credit®.', 'woocommerce-paypal-express-mx' ),
	),
	 'require_billing_address' => array(
		'title'       => __( 'Billing Addresses', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Require Billing Address', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'description' => sprintf( __( 'PayPal only returns a shipping address back to the website. To make sure billing address is returned as well, please enable this functionality on your PayPal account by calling %1$sPayPal Technical Support%2$s.', 'woocommerce-paypal-express-mx' ), '<a href="https://www.paypal.com/us/selfhelp/contact/call">', '</a>' ),
	),
	*/
	'paymentaction' => array(
		'title'       => __( 'Payment Type', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether you wish to capture funds immediately or authorize payment only.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'sale',
		'desc_tip'    => true,
		'options'     => array(
			'Sale'          => __( 'Direct sale', 'woocommerce-paypal-express-mx' ),
			'Authorization' => __( 'Authorize and Capture', 'woocommerce-paypal-express-mx' ),
		),
	),
	'allow_note_enabled' => array(
		'title'       => __( 'Allow Note on checkout', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Allow Note on checkout', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'description' => __( 'Customer may enter a note to the merchant on the PayPal page during checkout', 'woocommerce-paypal-express-mx' ),
	),
	'show_installment_gateway' => array(
		'title'       => __( 'Show installment gateway on checkout', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Show installment gateway on checkout', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'description' => __( 'This option add a new gateway payment on checkout for "Paypal Checkout with Installment".', 'woocommerce-paypal-express-mx' ),
	),
	'title_installment' => array(
		'title'       => __( 'Title of Installment Gateway', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout for Installment.', 'woocommerce-paypal-express-mx' ),
		'default'     => __( 'PayPal with Installment', 'woocommerce-paypal-express-mx' ),
		'desc_tip'    => true,
	),
	'description_installment' => array(
		'title'       => __( 'Description of Installment Gateway', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'desc_tip'    => true,
		'description' => __( 'This controls the description which the user sees during checkout for Installment.', 'woocommerce-paypal-express-mx' ),
		'default'     => __( 'Pay via PayPal with Installment.', 'woocommerce-paypal-express-mx' ),
	),
	'require_phone_number' => array(
		'title'       => __( 'Require Phone Number', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Require Phone Number', 'woocommerce-paypal-express-mx' ),
		'default'     => 'no',
		'description' => __( 'Require buyer to enter their telephone number during checkout if none is provided by PayPal', 'woocommerce-paypal-express-mx' ),
	),
	'checkout_mode' => array(
		'title'       => __( 'Checkout Mode', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Indicate if customers will pay in a modal window or the page will be redirected to paypal.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'live',
		'desc_tip'    => true,
		'options'     => array(
			'modal'              => __( 'Modal Windows with confirmation page on Checkout', 'woocommerce-paypal-express-mx' ),
			'modal_on_checkout'  => __( 'Modal Windows without confirmation page on Checkout', 'woocommerce-paypal-express-mx' ),
			'redirect'           => __( 'Redirect', 'woocommerce-paypal-express-mx' ),
		),
	),
	'debug' => array(
		'title' => __( 'Debug', 'woocommerce-paypal-express-mx' ),
		'type' => 'checkbox',
		'label' => __( 'Enable log', 'woocommerce-paypal-express-mx' ),
		'default' => 'no',
		/* translators: %1$s: is the PATH of Log  */
		'description' => sprintf( __( 'To review the log of Paypal, see the directory: %1$s', 'woocommerce-paypal-express-mx' ), '<code>/wp-content/plugins/woocommerce-paypal-express-mx/logs/</code>' ),
	),
	'style' => array(
		'title'       => __( 'Style on Checkout', 'woocommerce-paypal-express-mx' ),
		'type'        => 'title',
	),
	'brand_name' => array(
		'title'       => __( 'Brand Name', 'woocommerce-paypal-express-mx' ),
		'type'        => 'text',
		'description' => __( 'A label that overrides the business name in the PayPal account on the PayPal hosted checkout pages.', 'woocommerce-paypal-express-mx' ),
		'default'     => get_bloginfo( 'name', 'display' ),
		'desc_tip'    => true,
	),
	'paypal_logo_footer' => array(
		'title'       => __( 'Show Paypal Logo on Footer Page', 'woocommerce-paypal-express-mx' ),
		'type'        => 'checkbox',
		'label'       => __( 'Show Paypal Logo on Footer Page', 'woocommerce-paypal-express-mx' ),
		'default'     => 'yes',
		'description' => __( 'Show Paypal Logo on Footer Page', 'woocommerce-paypal-express-mx' ),
		'desc_tip'    => true,
	),
	'button_size_cart' => array(
		'title'       => __( 'Button Size on Cart', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'PayPal offers different sizes of the "PayPal Checkout" buttons, allowing you to select a size that best fits your site\'s theme. This setting will allow you to choose which size button(s) appear on your cart page.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'medium',
		'desc_tip'    => true,
		'options'     => array(
			'small'  => __( 'Small', 'woocommerce-paypal-express-mx' ),
			'medium' => __( 'Medium', 'woocommerce-paypal-express-mx' ),
			'responsive'  => __( 'Responsive', 'woocommerce-paypal-express-mx' ),
		),
	),
	'button_size_product' => array(
		'title'       => __( 'Button Size on Product', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'PayPal offers different sizes of the "PayPal Checkout" buttons, allowing you to select a size that best fits your site\'s theme. This setting will allow you to choose which size button(s) appear on your product page.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'medium',
		'desc_tip'    => true,
		'options'     => array(
			'small'       => __( 'Small', 'woocommerce-paypal-express-mx' ),
			'medium'      => __( 'Medium', 'woocommerce-paypal-express-mx' ),
			'responsive'  => __( 'Responsive', 'woocommerce-paypal-express-mx' ),
		),
	),
	'button_type' => array(
		'title'       => __( 'Button Type', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether is square or oval.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'pill',
		'desc_tip'    => true,
		'options'     => array(
			'rect'    => __( 'Squere', 'woocommerce-paypal-express-mx' ),
			'pill'    => __( 'Oval', 'woocommerce-paypal-express-mx' ),
		),
	),
	'button_color' => array(
		'title'       => __( 'Button Color', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether is blue, gold or gray.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'blue',
		'desc_tip'    => true,
		'options'     => array(
			'blue'    => __( 'Blue', 'woocommerce-paypal-express-mx' ),
			'gold'    => __( 'Gold', 'woocommerce-paypal-express-mx' ),
			'silver'  => __( 'Silver', 'woocommerce-paypal-express-mx' ),
		),
	),
	'button_locale' => array(
		'title'       => __( 'Button Lang', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Choose whether Spanish and English.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'blue',
		'desc_tip'    => true,
		'options'     => array(
			'es_ES'    => __( 'Spanish', 'woocommerce-paypal-express-mx' ),
			'en_US'    => __( 'English', 'woocommerce-paypal-express-mx' ),
		),
	),
	'landing_page' => array(
		'title'       => __( 'Landing Page', 'woocommerce-paypal-express-mx' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Type of PayPal page to display.', 'woocommerce-paypal-express-mx' ),
		'default'     => 'Login',
		'desc_tip'    => true,
		'options'     => array(
			'Billing' => _x( 'Billing (Non-PayPal account)', 'Type of PayPal page', 'woocommerce-paypal-express-mx' ),
			'Login'   => _x( 'Login (PayPal account login)', 'Type of PayPal page', 'woocommerce-paypal-express-mx' ),
		),
	),
	'logo_image_url' => array(
		'title'       => __( 'Logo Image (190×60)', 'woocommerce-paypal-express-mx' ),
		'type'        => 'media',
		'description' => __( 'If you want PayPal to co-brand the checkout page with your logo, enter the URL of your logo image here.<br/>The image must be no larger than 190x60, GIF, PNG, or JPG format, and should be served over HTTPS.', 'woocommerce-paypal-express-mx' ),
		'preview'     => '' !== $logo_image_url ? wp_get_attachment_image( $logo_image_url, array( 190, 60 ), false, array(
			'id' => 'logo_image_url-image',
		) ) . '<br /><a style="color: #bc0b0b;" href="javascript:void(ppexpress_remove_img(\'input#media-woocommerce_ppexpress_mx_logo_image_url\',\'img#logo_image_url-image\'));">' . __( 'Remove Image', 'woocommerce-paypal-express-mx' ) . '</a>' : '',
		'max-width'   => '190',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),

	/*
	'header_image_url' => array(
		'title'       => __( 'Header Image (750×90)', 'woocommerce-paypal-express-mx' ),
		'type'        => 'media',
		'description' => __( 'If you want PayPal to co-brand the checkout page with your header, enter the URL of your header image here.<br/>The image must be no larger than 750x90, GIF, PNG, or JPG format, and should be served over HTTPS.', 'woocommerce-paypal-express-mx' ),
		'preview'     => '' !== $header_image_url ? wp_get_attachment_image( $header_image_url, array( 750, 90 ), false, array(
			'id' => 'header_image_url-image',
		) ) . '<br /><a style="color: #bc0b0b;" href="javascript:void(ppexpress_remove_img(\'input#media-woocommerce_ppexpress_mx_header_image_url\',\'img#header_image_url-image\'));">' . __( 'Remove Image', 'woocommerce-paypal-express-mx' ) . '</a>' : '',
		'max-width'   => '750',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),
	'background_header_color' => array(
		'title'       => __( 'Background color for the header of the payment', 'woocommerce-paypal-express-mx' ),
		'type'        => 'color',
		'description' => __( 'Background color for the header of the payment', 'woocommerce-paypal-express-mx' ),
		'default'     => '#ffffff',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),
	'border_header_color' => array(
		'title'       => __( 'Border color for the header of the payment', 'woocommerce-paypal-express-mx' ),
		'type'        => 'color',
		'description' => __( 'Border color for the header of the payment', 'woocommerce-paypal-express-mx' ),
		'default'     => '#000000',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),
	'background_page_color' => array(
		'title'       => __( 'Background color for the payment page', 'woocommerce-paypal-express-mx' ),
		'type'        => 'color',
		'description' => __( 'Background color for the header of the payment', 'woocommerce-paypal-express-mx' ),
		'default'     => '#ffffff',
		'desc_tip'    => true,
		'placeholder' => __( 'Optional', 'woocommerce-paypal-express-mx' ),
	),
	*/
    
	 'manual_field' => array(
		'title' => __( 'Firewall', 'woocommerce-paypal-express-mx' ),
		'type' => 'html',
		'description' => __( 'If you have a firewall enabled, please add the following Paypal IPs to your whitelist to prevent problems of communication with the IPN:', 'woocommerce-paypal-express-mx' ).
                            '<br /><br />173.0.80.0 - 173.0.93.255<br />64.4.248.0 - 64.4.249.255<br />66.211.169.0 - 66.211.170.255'
	),
);
