<?php

class Liquid2CouponBTCPayClientLegacy
{
	var $client;
	var $btcPayGateway;
	var $callbackUrl;
	var $redirectUrl;

	public function __construct() {
		// Load BTCPay payment plugin.
		$this->btcPayGateway = new WC_Gateway_BtcPay();
		// Get a BitPay Client to prepare for invoice creation
		$this->client = new \Bitpay\Client\Client();
		$this->client->setUri($this->btcPayGateway->api_url);
		$this->client->setToken($this->btcPayGateway->api_token);
		$this->client->setPublicKey($this->btcPayGateway->api_pub);
		$this->client->setPrivateKey($this->btcPayGateway->api_key);

		$curlAdapter = new \Bitpay\Client\Adapter\CurlAdapter();
		$this->client->setAdapter($curlAdapter);
	}

	public function createInvoice($asset_id, $asset_symbol, $quantity, $user_id, $redeem_id, $callback_url, $redirect_url) {
		// For some reason Bitpay/Item is not autoloaded.
		if ( ! class_exists( "Bitpay\Item" ) ) {
			require_once WP_PLUGIN_DIR . '/btcpay-for-woocommerce/lib/Bitpay/Item.php';
		}
		// Remove dashes from asset symbol if any.
		$asset_symbol = str_replace('-', '', $asset_symbol);

		$item = new \BitPay\Item();
		// todo: set price to token quanity.
		$item->setCode('redeemtoken')
		     ->setDescription('Redeem a voucher for asset id: ' . substr($asset_id, 0, 28) . '... user id: ' . $user_id)
		     ->setPrice($quantity);

		$invoice = new \Bitpay\Invoice();
		// 'high' -> 0-conf for this purpose.
		$invoice->setTransactionSpeed('high');
		$invoice->setItem($item);
		$invoice->setOrderId('RT' . $redeem_id);
		$invoice->setCurrency(new \Bitpay\CurrencyUnrestricted($asset_symbol));
		$invoice->setPaymentCurrencies(array($asset_symbol));
		$invoice->setRedirectUrl($redirect_url);
		$invoice->setNotificationUrl($callback_url);

		return $this->client->createInvoice($invoice);
	}

	public function getInvoice($invoice_id) {
		return $this->client->getInvoice($invoice_id);
	}
}
