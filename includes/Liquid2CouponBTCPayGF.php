<?php

class Liquid2CouponBTCPayGF {
	protected $host = '';
	protected $apiKey = '';
	protected $storeId = '';
	protected $webhook = [];

	public function __construct() {
		// Get BTCPay setings from la2c options.
		$settings = get_option('la2c_options');

		foreach ($settings as $key => $value) {
			if (strpos($key, 'la2c_btcpay') !== false && !empty($value)) {
				switch ($key) {
					case "la2c_btcpay_url":
						$this->host = $value;
						break;
					case "la2c_btcpay_key":
						$this->apiKey = $value;
						break;
					case "la2c_btcpay_storeid":
						$this->storeId = $value;
						break;
				}
			}
		}

		if ($webhookData = get_option('la2c_webhook')) {
			$this->webhook = $webhookData;
		}
	}

	public function getHost() {
		return $this->host;
	}

	public function getApiKey() {
		return $this->apiKey;
	}

	public function getStoreId() {
		return $this->storeId;
	}

	public function getWebhook() {
		return $this->webhook;
	}

	/**
	 * Create invoice on BTCPay Server.
	 */
	public function createInvoice($assetId, $assetSymbol, $quantity, $userId, $redeemId) {

		// Remove dashes from asset symbol if any.
		$assetSymbol = str_replace('-', '', $assetSymbol);
		$redirectUrl = get_home_url() . '/my-account/redeem-token/' . $redeemId;

		$amount = \BTCPayServer\Util\PreciseNumber::parseString($quantity);

		// Setup custom checkout options, defaults get picked from store config.
		$checkoutOptions = new \BTCPayServer\Client\InvoiceCheckoutOptions();
		$checkoutOptions
			->setSpeedPolicy($checkoutOptions::SPEED_HIGH)
			->setPaymentMethods([$assetSymbol])
			->setRedirectURL($redirectUrl);

		$metadata = [
			'itemDesc' => 'Redeem a voucher for asset id: ' . substr($assetId, 0, 28) . '... user id: ' . $userId,
			'itemCode' => 'redeemtoken',
			'itemPrice' => $quantity,
		];

		try {
			$client = new \BTCPayServer\Client\Invoice($this->getHost(), $this->getApiKey());
			$invoice = $client->createInvoice(
				$this->getStoreId(),
				$assetSymbol,
				$amount,
				'RT' . $redeemId,
				null,
				$metadata,
				$checkoutOptions
			);
		} catch (\Throwable $e) {
			throw new \Exception($e->getMessage());
		}

		return $invoice;
	}

	/**
	 * Load invoice from BTCPay Server.
	 */
	public function getInvoice($invoiceId) {
		try {
			$client = new \BTCPayServer\Client\Invoice( $this->getHost(), $this->getApiKey() );
			return $client->getInvoice($this->getStoreId(), $invoiceId);
		} catch (\Throwable $e) {
			throw new \Exception($e->getMessage());
		}
	}

	public function webhookExists() {
		return !empty($this->webhook);
	}

	public function registerWebhook($host, $apiKey, $storeId) {

		$webhookEvents  = [
			'InvoiceReceivedPayment',
			'InvoicePaymentSettled',
			'InvoiceProcessing',
			'InvoiceExpired',
			'InvoiceSettled',
			'InvoiceInvalid'
		];

		$callbackUrl = get_home_url() . '/redeem-callback';

		try {
			$whClient = new \BTCPayServer\Client\Webhook( $host, $apiKey );
			$webhook = $whClient->createWebhook(
				$storeId,
				$callbackUrl,
				$webhookEvents,
				null
			);

			// Store in option table.
			$webhookData = [
				'id' => $webhook->getData()['id'],
				'secret' => $webhook->getData()['secret'],
				'url' => $webhook->getData()['url']
			];

			update_option('la2c_webhook', $webhookData);

			return $webhookData;
		} catch (\Throwable $e) {
			throw new \Exception($e->getMessage());
		}

		return null;
	}

	/**
	 * Check webhook signature to be a valid request.
	 */
	public function validWebhookRequest($signature, $requestData) {
		return \BTCPayServer\Client\Webhook::isIncomingWebhookRequestValid($requestData, $signature, $this->getWebhook()['secret']);
	}
}
