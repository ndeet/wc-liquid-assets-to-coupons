<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Liquid Assets to Coupon settings page.
 */
function la2c_settings_init() {
	// Register a new setting for "la2c".
	register_setting( 'la2c', 'la2c_options', 'la2c_validate_form' );

	// Register sections in the "la2c" page.
	add_settings_section(
		'la2c_section_maps',
		__( 'Product to asset symbol and asset ID mapping:', 'la2c' ), 'la2c_section_maps_callback',
		'la2c'
	);

	// Add mapping fields.
	for ($i = 1; $i <= 30; $i++) {
		add_settings_field(
			'la2c_map_' . $i,
			'Mapping '. $i . ':',
			'la2c_map_render',
			'la2c',
			'la2c_section_maps',
			array(
				'label_for' => 'la2c_map_' . $i,
				'class' => 'la2c_row',
				'la2c_number' => $i,
			)
		);
	}

    add_settings_section(
		'la2c_section_btcpay',
		__( 'BTCPay Greenfield API config:', 'la2c' ), 'la2c_section_btcpay_callback',
		'la2c'
	);

	add_settings_field(
		'la2c_btcpay_url',
		__( 'Server URL', 'la2c' ),
		'la2c_btcpay_url_render',
		'la2c',
		'la2c_section_btcpay',
        [
	        'label_for' => 'la2c_btcpay_url',
	        'class' => 'la2c_row',
        ]
	);

	add_settings_field(
		'la2c_btcpay_key',
		__( 'API Key', 'la2c' ),
		'la2c_btcpay_key_render',
		'la2c',
		'la2c_section_btcpay',
		[
			'label_for' => 'la2c_btcpay_key',
			'class' => 'la2c_row',
		]
	);

	add_settings_field(
		'la2c_btcpay_storeid',
		__( 'Store ID', 'la2c' ),
		'la2c_btcpay_storeid_render',
		'la2c',
		'la2c_section_btcpay',
		[
			'label_for' => 'la2c_btcpay_storeid',
			'class' => 'la2c_row',
		]
	);

	add_settings_field(
		'la2c_btcpay_webhook',
		__( 'Webhook data', 'la2c' ),
		'la2c_btcpay_webhook_render',
		'la2c',
		'la2c_section_btcpay',
		[
			'label_for' => 'la2c_btcpay_webhook',
			'class' => 'la2c_row',
		]
	);

	add_settings_field(
		'la2c_btcpay_delete_webhook',
		__( 'DELETE webhook data', 'la2c' ),
		'la2c_btcpay_delete_webhook_render',
		'la2c',
		'la2c_section_btcpay',
		[
			'label_for' => 'la2c_btcpay_delete_webhook',
			'class' => 'la2c_row',
		]
	);

}

add_action( 'admin_init', 'la2c_settings_init' );

/**
 * Maps section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function la2c_section_maps_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Please enter the product to asset id mappings in the following format, separated by semicolon ";":', 'la2c' ); ?></p>
    <p><pre>SYMBOL;ASSET_ID;PRODUCT_ID</pre></p>
    <p>e.g. for B-JDE and Product ID 151:</p>
    <p><pre>B-JDE;78557eb89ea8439dc1a519f4eb0267c86b261068648a0f84a5c6b55ca39b66f1;151</pre></p>
    <p><strong>Info:</strong> please make also sure that each product and asset is only used once.</p>
	<?php
}

/**
 * BTCPay Greenfield API section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function la2c_section_btcpay_callback( $args ) {
	?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Please configure your BTCPay Greenfield API credentials here.', 'la2c' ); ?></p>
	<?php
}


/**
 * Maps fields callback function.
 *
 * @param array $args  The field settings array.
 */
function la2c_map_render( $args ) {
	$options = get_option( 'la2c_options' );
	?>
    <input type='text' id='<?php echo esc_attr( $args['label_for'] ); ?>' name='la2c_options[<?php echo esc_attr( $args['label_for'] ); ?>]' value='<?php echo $options[ $args['label_for'] ]; ?>' size="80">
	<?php
}


// Render BTCPay url field.
function la2c_btcpay_url_render( $args ) {
	$options = get_option( 'la2c_options' );
	?>
    <input type='text' id='<?php echo esc_attr( $args['label_for'] ); ?>' name='la2c_options[<?php echo esc_attr( $args['label_for'] ); ?>]' value='<?php echo $options[ $args['label_for'] ]; ?>' size="50">
    <p>Enter the URL of your public BTCPay instance, e.g <code>https://mainnet.demo.btcpayserver.org</code></p>
    <?php
}

// Render BTCPay API key field.
function la2c_btcpay_key_render( $args ) {
	$options = get_option( 'la2c_options' );
	?>
    <input type='text' id='<?php echo esc_attr( $args['label_for'] ); ?>' name='la2c_options[<?php echo esc_attr( $args['label_for'] ); ?>]' value='<?php echo $options[ $args['label_for'] ]; ?>' size="50">
    <p>Please make sure you grant the following permissions:</p>
    <p>
        <code>View invoices</code>, <code>Create invoice</code>, <code>Modify invoices</code>, <code>Modify stores webhooks</code>, <code>View your stores</code>
    </p>
    <?php
}

// Render BTCPay store ID field.
function la2c_btcpay_storeid_render( $args ) {
	$options = get_option( 'la2c_options' );
	?>
    <input type='text' id='<?php echo esc_attr( $args['label_for'] ); ?>' name='la2c_options[<?php echo esc_attr( $args['label_for'] ); ?>]' value='<?php echo $options[ $args['label_for'] ]; ?>' size="50">
    <p>Enter Store ID, can be found on BTCPay Server under store settings.</p>
	<?php
}

// Render BTCPay webhook data field.
function la2c_btcpay_webhook_render( $args ) {
	if ($webhook = get_option( 'la2c_webhook' )) {
        foreach ($webhook as $key => $value) {
            echo '<p>' . $key . ': ' . $value . '</p>';
	    } // endforeach
    } else {
    ?>
        <p>Webhook gets created automatically on saving.</p>
	<?php
    }
}

// Render BTCPay store ID field.
function la2c_btcpay_delete_webhook_render( $args ) {
	$options = get_option( 'la2c_options' );
	?>
    <input type='checkbox' id='<?php echo esc_attr( $args['label_for'] ); ?>' name='la2c_options[<?php echo esc_attr( $args['label_for'] ); ?>]' value='delete'>
    <p>When this is checked it will delete the current webhook config so you can create one on a new store.</p>
    <p><strong>Important:</strong> Make sure to delete the webhook on your current store on BTCPay Server. In Store Settings -> Webhooks</p>
	<?php
}

function la2c_validate_form( $input ) {
    $output = [];
    $countBtcpay = 0;
    $btcpayFields = ['la2c_btcpay_url','la2c_btcpay_key','la2c_btcpay_storeid'];

    foreach( $input as $key => $value) {
        if (!empty($value) && strpos($key, 'la2c_map') !== false) {
            // Strip whitespaces.
            $output[$key] = str_replace(' ', '', $value);

            // Make sure the value has all the needed segments.
            $tmpList = explode(';', $output[$key]);
            if (count($tmpList) !== 3) {
	            add_settings_error(
		            'la2c_messages',
		            esc_attr( $key ),
		            __('Please make sure you separate your inputs with semicolons and contain all elements as seen in the example.'),
                    'error'
	            );
            }
        } else {
            if (in_array($key, $btcpayFields)) {
                $countBtcpay++;
            }
            $output[$key] = str_replace(' ', '', $value);
        }
    }

    // If we have BTCPay credentials, try to create a webhook entry if it does not already exist.
    if ($countBtcpay === 3) {
        $btcpay = new Liquid2CouponBTCPayGF();
        if ($btcpay->webhookExists() === false) {
            try {
	            $webhook = $btcpay->registerWebhook( $output['la2c_btcpay_url'], $output['la2c_btcpay_key'], $output['la2c_btcpay_storeid'] );
	            if ( empty( $webhook ) ) {
		            add_settings_error(
			            'la2c_messages',
			            'la2c_btcpay_webhook',
			            __( 'Could not register webhook with BTCPay, make sure your API key has the right permissions.' ),
			            'error'
		            );
	            }
            } catch (\Throwable $e) {
	            add_settings_error(
		            'la2c_messages',
		            'la2c_btcpay_webhook',
		            __( 'Error creating webhook: ' . $e->getMessage() ),
		            'error'
	            );
            }
        }
    }

    // Delete webhook data if selected.
    if ($output['la2c_btcpay_delete_webhook'] === 'delete') {
	    delete_option('la2c_webhook');
    }

    return $output;
}

/**
 * Add the top level menu page.
 */
function la2c_options_page() {
	add_menu_page(
		'Liquid assets to coupon settings',
		'La2c settings',
		'manage_options',
		'la2c',
		'la2c_options_page_html'
	);
}

add_action( 'admin_menu', 'la2c_options_page' );

/**
 * Top level menu callback function
 */
function la2c_options_page_html() {
	// Only allow admin to access the page.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if settings submitted successfully.
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error( 'la2c_messages', 'la2c_message', __( 'Settings Saved', 'la2c' ), 'updated' );
	}

	// Show error / success messages.
	settings_errors( 'la2c_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// Output security fields for the registered setting "la2c".
			settings_fields( 'la2c' );
			// Output setting sections and their fields.
			do_settings_sections( 'la2c' );

			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}
