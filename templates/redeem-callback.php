<?php

require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponCoupon.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponDbAbstract.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponDb.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponProductsMap.php';
require_once plugin_dir_path(__FILE__) . '../includes/Liquid2CouponBTCPayGF.php';

$rawPostData = file_get_contents('php://input');

if ( false === $rawPostData) {
	throw new \Exception('La2c: Could not read from the php://input stream or invalid BTCPay IPN received.');
}

// Validate webhook request.
// Note: getallheaders() CamelCases all headers for PHP-FPM/Nginx but for others maybe not, so "BTCPay-Sig" may becomes "Btcpay-Sig".
$headers = getallheaders();
$signature = '';
foreach ($headers as $key => $value) {
	if (strtolower($key) === 'btcpay-sig') {
		$signature = $value;
	}
}

$btcpay = new Liquid2CouponBTCPayGF();
if (empty($signature) || !$btcpay->validWebhookRequest($signature, $rawPostData)) {
	throw new \Exception('La2c: Failed to validate webhook, aborting.');
}

$ipn = json_decode($rawPostData);

if (empty($ipn)) {
	throw new \Exception('Could not decode the JSON payload from BTCPay.');
}

if (empty($ipn->invoiceId)) {
	throw new \Exception('Invalid BTCPay payment notification message received - did not receive invoice ID.');
}

$invoiceId = filter_var($ipn->invoiceId, FILTER_SANITIZE_STRING);

try {
	$invoice = $btcpay->getInvoice($invoiceId);
} catch (\Throwable $e) {
	throw new \Exception("La2c: Error fetching invoice from BTCPay.");
}

// Update the redemption data.
$db = new Liquid2CouponDb();
$updated = false;

if ($data = $db->get_by('invoice_id', $invoice->getData()['id'])) {
	$invoiceStatus = $invoice->getStatus();
	$invoiceStatusAdditional = $invoice->getData()['additionalStatus'];
	if (in_array($invoiceStatus, ['Processing', 'Settled']) ||
		in_array($invoiceStatusAdditional, ['PaidLate', 'PaidOver'])
	) {
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
