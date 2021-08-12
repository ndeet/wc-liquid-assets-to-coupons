<?php
/*
Plugin Name: Liquid Assets to Coupons
Description: Redeem coupons from liquid promotion assets to coupons.
Version:     0.4.0
Author:      Andreas Tasch
Author URI:  https://attec.at
License:     MIT
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function la2c_redeem_endpoints_init() {
	add_rewrite_endpoint( 'redeem-callback', EP_ROOT );
	add_rewrite_endpoint( 'redeem-token', EP_ROOT | EP_PAGES );
	wp_register_style('la2c', plugins_url('assets/style.css',__FILE__ ));
	wp_enqueue_style('la2c');
}
add_action( 'init', 'la2c_redeem_endpoints_init', 10 );

require_once plugin_dir_path(__FILE__) . '/includes/la2c-admin-page.php';

// Override wc_get_template to search in plugin directory.
function la2c_get_template($located, $template_name)
{
	switch ($template_name) {
		case 'myaccount/redemptions.php':
		case 'myaccount/redeem-token.php':
		case 'myaccount/redeem-token-coupon.php':
		case 'redeem-callback.php':
		return plugin_dir_path(__FILE__) . 'templates/' . $template_name;
	}

	return $located;
}
add_filter('wc_get_template', 'la2c_get_template', 10, 2);

function la2c_menu_myaccount_items($items) {
	$logout = $items['customer-logout'];
	unset($items['customer-logout']);
	$items['redeem-token'] = __('Redeem tokens', 'la2c');
	$items['customer-logout'] = $logout;
	return $items;
}
add_filter('woocommerce_account_menu_items', 'la2c_menu_myaccount_items');

function la2c_query_vars($vars) {
	$vars[] = 'redeem-token';
	return $vars;
}
add_filter('query_vars', 'la2c_query_vars');

require_once plugin_dir_path(__FILE__) . '/includes/Liquid2CouponBTCPayClientLegacy.php';
require_once plugin_dir_path(__FILE__) . '/includes/Liquid2CouponCoupon.php';
require_once plugin_dir_path(__FILE__) . '/includes/Liquid2CouponDbAbstract.php';
require_once plugin_dir_path(__FILE__) . '/includes/Liquid2CouponDb.php';

function la2c_redeem_token_template() {
	$redeem_id = get_query_var('redeem-token');

	// Make sure the configured product exists in store or abort here.
	if (!$product = wc_get_product(LA2C_PRODUCT_ID)) {
		echo __('The configured product does not exist, please make sure to use an existing product.');
		return;
	}

	if (empty($redeem_id) && empty($_POST)) {
		$db = new Liquid2CouponDb();
		// todo: if needed add pagination like for admin here.
		$redemptions = $db->get_redemptions(
			[
				'customer_id' => get_current_user_id(),
				'number' => '50'
			]
		);
		wc_get_template(
			'myaccount/redeem-token.php',
			['redemptions' => $redemptions ]
		);
	}

	if (!empty($redeem_id)) {
		$db = new Liquid2CouponDb();
		if ($data = $db->get($redeem_id)) {
			// Only continue here if the user viewing this page is also the user logged in.
			if ($data->customer_id != get_current_user_id()) {
				echo __('Access denied.', 'la2c');
				http_response_code(403);
				return;
			}

			$product_id = LA2C_PRODUCT_ID;

			// Check the invoice status.
			$client = new Liquid2CouponBTCPayClientLegacy();
			if ($invoice = $client->getInvoice($data->invoice_id)) {
				$valid_invoice_states = ['paid', 'confirmed', 'complete'];
				if (!in_array($invoice->getStatus(), $valid_invoice_states)) {
					echo __('The invoice has not been paid yet, therefore no coupon code was generated. Return here after payment completed.', 'la2c');
					return; // abort in case the invoice is not fully paid.
				}
				$db->update($redeem_id, ['status' => $invoice->getStatus()]);
			}

			// Only continue if there has not yet been a coupon code already generated.
			if (empty($data->coupon_code)) {
				try {
					// Create a coupon.
					if ($coupon_code = Liquid2CouponCoupon::generateCouponCodeFromInvoiceId($data->invoice_id, $data->customer_id)) {
						$coupon_id = Liquid2CouponCoupon::generatePerProductPerUserCoupon($data->customer_id, $coupon_code, $product_id, $data->quantity);
						// Store coupon code in DB.
						$db->update($redeem_id, ['status' => 'complete', 'coupon_code' => $coupon_code, 'coupon_id' => $coupon_id]);
					} else {
						// todo log
						echo __('There was a problem generating the coupon code, please contact support by providing your transaction id and/or this redemption id: ' . $redeem_id, 'la2c');
					}
				} catch (\Throwable $e) {
					// todo log.
					// $e->getMessage();
					echo __('There was a problem generating the coupon code, please contact support by providing your transaction id and/or this redemption id: ' . $redeem_id, 'la2c');
				}
			} else { // Coupon code already exists, show it instead.
				$coupon_code = $data->coupon_code;
			}
		}

		wc_get_template(
			'myaccount/redeem-token-coupon.php',
			[
				'coupon_code' => $coupon_code
			]
		);
	}
}
add_action('woocommerce_account_redeem-token_endpoint', 'la2c_redeem_token_template');

function la2c_install() {
	require_once dirname( __FILE__ ) . '/includes/Liquid2CouponDbAbstract.php';
	require_once dirname( __FILE__ ) . '/includes/Liquid2CouponDb.php';
	$db = new Liquid2CouponDb();
	$db->create_table();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'la2c_install' );

function la2c_template_redirect_redeem_token() {
	global $wp_query;

	// if this is not a request for json or a singular object then bail
	if ( ! isset( $wp_query->query_vars['redeem-token'] ) || ! is_singular() )
		return;

	$redeem_id = get_query_var('redeem-token');

	// if has POST data try create an invoice.
	if (empty($redeem_id) && !empty($_POST)) {
		$asset_symbol = sanitize_text_field($_POST['asset_symbol']);
		$asset_id = LA2C_ASSET_MAP[$asset_symbol];
		$quantity = sanitize_text_field($_POST['quantity']);
		if (!is_numeric($quantity) || $quantity <= 1) {
			$message = __('Quantity needs to be a number and >=1, aborting', 'la2c');
			echo $message;
			// todo log
			return;
		}

		// Create redemption entry in db.
		$db = new Liquid2CouponDb();
		$data = [
			'customer_id' => get_current_user_id(),
			'asset_id' => $asset_id,
			'quantity' => $quantity,
			'status' => 'new'
		];
		try {
			if (!$db_redeem_id = $db->insert($data)) {
				throw new \Exception("Error creating db entry for redemption.");
				return;
			}
		} catch (\Throwable $e) {
			echo $e->getMessage();
		}

		try {
			$legacyClient = new Liquid2CouponBTCPayClientLegacy();

			$redirect_url = get_home_url() . '/my-account/redeem-token/' . $db_redeem_id;
			$callback_url = get_home_url() . '/redeem-callback';

			/** @var Bitpay\Invoice $invoice */
			$invoice = $legacyClient->createInvoice(
				$asset_id,
				$asset_symbol,
				$quantity,
				get_current_user_id(),
				$db_redeem_id,
				$callback_url,
				$redirect_url
			);
		} catch ( \Throwable $e ) {
			//todo: log
			echo $e->getMessage();
			return;
		}

		if ($invoice) {
			// Update entry db with invoice id
			$db->update($db_redeem_id, [
				'invoice_id' => $invoice->getId(),
				'status' => 'unpaid',
			]);

			// Redirect to BTCPay Server invoice.
			if (wp_redirect($invoice->getUrl())) {
				exit;
			}

		} else {
			throw new \Exception("Error creating invoice on BTCPay Server");
		}
	}
}
add_action( 'template_redirect', 'la2c_template_redirect_redeem_token' );

function la2c_template_redirect_callback() {
	global $wp_query;

	// Only continue if redeem request.
	if (! isset( $wp_query->query_vars['redeem-callback'] ) ) {
		return;
	}
	wc_get_template('redeem-callback.php');
	exit;
}
add_action( 'template_redirect', 'la2c_template_redirect_callback' );
