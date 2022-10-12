<?php

/**
 * Product to liquid asset id mappings.
 */
class Liquid2CouponProductsMap {

	private $productMapSource = [];

	private $productMap = [];

	private $enforceCouponProducts = [];

	public function __construct() {
		$options = get_option( 'la2c_options' );
		foreach ($options as $key => $value) {
			if (strpos($key, 'la2c_map') !== false && !empty($value)) {
				$this->productMapSource[] = $value;
			}
			if ($key === 'la2c_enforce_coupons' && !empty($value)) {
				$tmpList = explode(',', $value);
				foreach ($tmpList as $item) {
					if (!empty($item) && is_numeric($item)) {
						$this->enforceCouponProducts[] = (int) trim($item);
					}
				}
			}
		}

		$this->productMap = $this->generateProductMap();
	}

	/**
	 * Converts the raw options input in to a nicer assoc array.
	 *
	 * @return array
	 */
	public function generateProductMap() {
		$list = [];
		foreach ($this->productMapSource as $entry) {
			$tempList = explode(';', $entry);
			if (count($tempList) === 3) {
				$list[] = [
					'symbol' => $tempList[0],
					'assetId' => $tempList[1],
					'productId' => $tempList[2],
				];
			}
		}

		return $list;
	}

	public function getProductIds() {
		$pids = [];
		foreach ($this->productMap as $entry) {
			$pids[] = $entry['productId'];
		}
		return $pids;
	}

	public function getSymbols() {
		$symbols = [];
		foreach ($this->productMap as $entry) {
			$symbols[] = $entry['symbol'];
		}
		return $symbols;
	}

	public function getAssetIdBySymbol($symbol) {
		foreach ($this->productMap as $entry) {
			if ($entry['symbol'] === $symbol) {
				return $entry['assetId'];
			}
		}
		return NULL;
	}

	public function getProductIdBySymbol($symbol) {
		foreach ($this->productMap as $entry) {
			if ($entry['symbol'] === $symbol) {
				return $entry['productId'];
			}
		}
		return NULL;
	}

	public function getProductIdByAssetId($assetId) {
		foreach ($this->productMap as $entry) {
			if ($entry['assetId'] === $assetId) {
				return $entry['productId'];
			}
		}
		return NULL;
	}

	public function getSymbolByAssetId($assetId) {
		foreach ($this->productMap as $entry) {
			if ($entry['assetId'] === $assetId) {
				return $entry['symbol'];
			}
		}
		return NULL;
	}

	public function getEnforcedCouponProductIds() {
		return $this->enforceCouponProducts;
	}
}
