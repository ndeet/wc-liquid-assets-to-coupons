<?php

require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponBTCPayClientLegacy.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponCoupon.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponDbAbstract.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponDb.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponProductsMap.php';

$raw_post_data = file_get_contents('php://input');

if (false === $raw_post_data) {
	throw new \Exception('Could not read from the php://input stream or invalid BTCPay IPN received.');
}

$ipn = json_decode($raw_post_data);

if (true === empty($ipn)) {
	throw new \Exception('Could not decode the JSON payload from BTCPay.');
}

if (empty($ipn->id)) {
	throw new \Exception('Invalid BTCPay payment notification message received - did not receive invoice ID.');
}

$ipn_invoice_id = filter_var($ipn->id, FILTER_SANITIZE_STRING);

try {
	$client = new Liquid2CouponBTCPayClientLegacy();
	$invoice = $client->getInvoice($ipn_invoice_id);
} catch (\Throwable $e) {
	throw new \Exception("Error fetching invoice from BTCPay.");
}

// Update the redemption data.
$db = new Liquid2CouponDb();
$updated = false;

if ($data = $db->get_by('invoice_id', $invoice->getId())) {
	if (in_array($invoice->getStatus(), ['paid', 'confirmed', 'complete'])) {
		$productsMap = new Liquid2CouponProductsMap();
		$productId = $productsMap->getProductIdByAssetId($data->asset_id);
		// Create a new coupon if none was generated already.
		if (empty($data->coupon_code)) {
			try {
				$coupon_code = Liquid2CouponCoupon::generateCouponCodeFromInvoiceId($data->invoice_id, $data->customer_id);
				$coupon_id = Liquid2CouponCoupon::generatePerProductPerUserCoupon($data->customer_id, $coupon_code, $productId, $data->quantity);
				// Store coupon code in DB.
				$updated = $db->update($data->id, ['status' => 'complete', 'coupon_code' => $coupon_code, 'coupon_id' => $coupon_id]);
			} catch (\Throwable $e) {
				// todo log.
				echo $e->getMessage();
				http_response_code(500);
			}
		}
	} else {
		$updated = $db->update($data->id, ['status' => $invoice->getStatus()]);
	}

	if ($updated) {
		http_response_code(200);
	} else {
		http_response_code(500);
	}
}
