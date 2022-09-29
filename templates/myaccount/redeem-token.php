<div class="la2c-redeem-form">
    <h3><?php echo __('Convert your Liquid tokens to coupon codes.', 'la2c'); ?></h3>
    <?php if (!empty($asset_symbols)) : ?>
    <form id="redeem-token-form" name="redeem-token-form" method="post">
        <select name="asset_symbol" id="asset-symbol">
            <option value="none"><?php print __('-- select token --', 'la2c'); ?></option>
            <?php foreach($asset_symbols as $symbol) : ?>
                <option value="<?php print $symbol; ?>"><?php print $symbol; ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="quantity" value="1" />
        <input type="submit" id="token-submit" name="submit" value="<?php printf(__('Convert %s to coupon', 'la2c'), 'token'); ?>" disabled="disabled" />
    </form>
    <?php endif; ?>
    <script>
        document.getElementById('asset-symbol').onchange = function () {
            if (this.value === 'none') {
                document.getElementById('token-submit').setAttribute('disabled', 'disabled');
            } else {
                document.getElementById('token-submit').removeAttribute('disabled');
            }
        };
    </script>
</div>

<?php if (!empty($redemptions)) : ?>
    <?php
        $productMap = new Liquid2CouponProductsMap();
    ?>
    <div class="la2c-redemptions-list">
        <h3><?php print __('Past token redemptions:', 'la2c'); ?></h3>
        <table class="la2c-table">
            <thead>
                <tr>
                    <th><?php print __('Date', 'la2c'); ?></th>
                    <th><?php print __('Invoice ID', 'la2c'); ?></th>
                    <th><?php print __('Status', 'la2c'); ?></th>
                    <th><?php print __('Token', 'la2c'); ?></th>
                    <th><?php print __('Coupon Code', 'la2c'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redemptions as $item) : ?>
                    <?php
                        $token = $productMap->getSymbolByAssetId($item->asset_id);
                    ?>
                    <tr>
                        <td><?php print $item->created; ?></td>
                        <td><?php print $item->invoice_id; ?></td>
                        <td><?php print $item->status; ?></td>
                        <td><?php print $token; ?></td>
                        <td><?php print $item->coupon_code; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
