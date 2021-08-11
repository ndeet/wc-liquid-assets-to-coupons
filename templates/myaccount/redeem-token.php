<div class="la2c-redeem-form">
    <h3><?php echo __('Convert your Liquid tokens to coupon codes.', 'la2c'); ?></h3>
    <form id="redeem-token-form" name="redeem-token-form" method="post">
        <input type="hidden" name="asset_symbol" value="<?php echo LA2C_ASSET_SYMBOL; ?>" />
        <input type="text" name="quantity" value="1" />
        <input type="submit" name="submit" value="<?php printf(__('Convert %s to coupon', 'la2c'), LA2C_ASSET_SYMBOL); ?>" />
    </form>
</div>

<?php if (!empty($redemptions)) : ?>
    <div class="la2c-redemptions-list">
        <h3><?php print __('Past token redemptions:', 'la2c'); ?></h3>
        <table class="la2c-table">
            <thead>
                <tr>
                    <th><?php print __('Date', 'la2c'); ?></th>
                    <th><?php print __('Invoice ID', 'la2c'); ?></th>
                    <th><?php print __('Status', 'la2c'); ?></th>
                    <th><?php print __('Coupon Code', 'la2c'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redemptions as $item) : ?>
                    <tr>
                        <td><?php print $item->created; ?></td>
                        <td><?php print $item->invoice_id; ?></td>
                        <td><?php print $item->status; ?></td>
                        <td><?php print $item->coupon_code; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
