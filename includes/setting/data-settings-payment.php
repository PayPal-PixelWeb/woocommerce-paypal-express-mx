<?php 

return array(
    /* 'manual_field' => array(
        'title' => __( 'Configuration Manual', 'woocommerce-paypal-express-mx' ),
        'type' => 'html',
        'description' => ''
    ), */
    'enabled' => array(
        'title' => __( 'Enable/Disable', 'woocommerce-paypal-express-mx' ),
        'type' => 'checkbox',
        'label' => __( 'Enable Paypal Express Checkout', 'woocommerce-paypal-express-mx' ),
        'default' => 'yes'
    ),
    'debug' => array(
        'title' => __( 'Debug', 'woocommerce-paypal-express-mx' ),
        'type' => 'checkbox',
        'label' => __( 'Enable log', 'woocommerce-paypal-express-mx' ),
        'default' => 'no',
        'description' => sprintf( __( 'To review the log of Paypal, see the directory: %s', 'woocommerce-paypal-express-mx' ), '<code>/wp-content/plugins/woocommerce-paypal-express-mx/logs/</code>' ),
    ),
    'api_credentials' => array(
        'title'       => __( 'API Credentials', 'woocommerce-paypal-express-mx' ),
        'type'        => 'title',
        'description' => $api_creds_text,
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
    'api_username' => array(
        'title'       => __( 'API Username', 'woocommerce-paypal-express-mx' ),
        'type'        => 'text',
        'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ),
        'default'     => '',
        'desc_tip'    => true,
    ),
    'api_password' => array(
        'title'       => __( 'API Password', 'woocommerce-paypal-express-mx' ),
        'type'        => 'password',
        'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ),
        'default'     => '',
        'desc_tip'    => true,
    ),
    'api_signature' => array(
        'title'       => __( 'API Signature', 'woocommerce-paypal-express-mx' ),
        'type'        => 'text',
        'description' => __( 'Get your API credentials from PayPal.', 'woocommerce-paypal-express-mx' ),
        'default'     => '',
        'desc_tip'    => true,
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
    )
);