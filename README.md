# Liquid Assets to Coupons Plugin for WooCommerce

This plugin allows users to exchange their Liquid assets against coupon codes. E.g. B-JDE to coupon codes. You can configure them in the La2C settings.

## How it works:
The user has a new menu entry under woocommerce “/my-account” alongside the order history and other sections, “/my-account/redeem-token”

The user can enter the quantity of tokens they want to convert to coupon codes. Default is 1.

When the user hits the [Convert to…] button he will get redirected to BTCPay Server invoice page which is only payable with the configured token, e.g. B-JDE.

The invoice amount will depend on the entered quantity. So if you enter 4 above you need to pay 4 B-JDE and will get a coupon code about the amount for 4 Jade products.

After successful payment the user gets redirected to the store my-account/redeem-token/ page and a coupon code will be shown to the user which he can use in the checkout. The coupon value will be set to the configured product and quantity (see installation). Depending on the configured product ID the price of that product will looked up and multiplied by the quantity, this means in checkout the user can apply the coupon and only the shipping costs will be left to pay.


## Coupon code properties:
For each payment/redemption a separate coupon is created with the following config:
unique coupon code per redemption
only redeemable once
only redeemable by the user that was logged in in the store and triggered the conversion process
the coupon value depends on the product configured in wp-config.php (see installation below)


## Installation and configuration:

### Requirements:
- Make sure you have a newer PHP version running > 7.4
- This plugin works only in combination with BTCPay for Woocommerce V2 (https://wordpress.org/plugins/btcpay-greenfield-for-woocommerce/)

### Installation:
Upload and install the liquid-assets-to-coupon.zip

### Configuration:
Navigate to the La2c settings menu on the left menu bar and configure your asset to product mappings and configure the BTCPay Server API connection.

Note: After you entered the BTCPay Server details URL, API key and Store ID upon saving the plugin will create a new webhook entry on your BTCPay Server store.

## Troubleshoot
If your logged in user sees a page not found / 404 on the /my-account/redeem-token/ url please flush the rewrite cache by going to your WP dashboard Settings -> Permalinks and hit “SAVE” to flush everything. The endpoints should now be available.
