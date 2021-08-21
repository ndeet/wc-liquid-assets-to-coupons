<?php

/**
 * Helper class to create coupons and coupon codes.
 */
class Liquid2CouponCoupon {

	public static function generatePerProductPerUserCoupon($user_id, $coupon_code, $product_id, $quantity) {
		$coupon = new WC_Coupon();
		$coupon->set_code($coupon_code);
		//the coupon discount type can be 'fixed_cart', 'percent' or 'fixed_product', defaults to 'fixed_cart'
		$coupon->set_discount_type('fixed_product');
		// Coupon amount is set to product price multiplied by the amount.
		/** @var WC_Product */
		if (!$product = wc_get_product($product_id)) {
			throw new \Exception("Product not found.");
		}
		// Get product and the price.
		$amount = $product->get_price() * $quantity;
		$coupon->set_amount($amount);

		// Coupon is only valid for the product the token was bought for.
		$coupon->set_product_ids([$product_id]);
		// Only usable once.
		$coupon->set_individual_use(true);
		$coupon->set_usage_limit(1);
		$coupon->set_usage_limit_per_user(1);
		$coupon->set_limit_usage_to_x_items($quantity);
		$coupon->set_free_shipping(false);
		$coupon->set_exclude_sale_items(false);


		// Limit the discount code to one specific user.
		/** @var WP_User $user */
		if (!$user = get_user_by('id', $user_id)) {
			throw new \Exception("Could not load user, aborting.");
		}
		$coupon->set_email_restrictions([$user->get('user_email')]);

		$coupon->save();

		return $coupon->get_id();
	}

	public static function generateCouponCodeFromInvoiceId($invoice_id, $user_id) {
		return hash('ripemd128', '$user_id' . '$invoice_id' . time());
	}
}
