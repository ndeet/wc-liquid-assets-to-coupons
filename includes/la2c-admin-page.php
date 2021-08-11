<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add Liquid Assets to Coupon Page
 */
function la2c_add_settings_page() {
	add_submenu_page( 'woocommerce', 'WooCommerce Liquid Assets to Coupons', 'Liquid Assets Coupons', 'manage_options', 'la2c-coupons', 'la2c_render_coupons_page' );
}
add_action( 'admin_menu', 'la2c_add_settings_page', 99 );

/**
 * Render Liquid Assets coupons page.
 */
function la2c_render_coupons_page() {
	require_once plugin_dir_path(__FILE__) . '/Liquid2CouponDbAbstract.php';
	require_once plugin_dir_path(__FILE__) . '/Liquid2CouponDb.php';

	$db = new Liquid2CouponDb();
	$page_query_var = filter_var($_GET['page'], FILTER_SANITIZE_STRING);
	$results_per_page = 100;
	$offset = 0;
	$pager = 1;

	if (isset($_GET['pager']) && $pager = filter_var($_GET['pager'],FILTER_SANITIZE_NUMBER_INT)) {
	    $offset = $results_per_page * ($pager - 1);
    }

	// Fetch entries.
	$redemptions = $db->get_redemptions([
	    'number' => $results_per_page,
        'offset' => $offset
    ]);
	$total_results = $db->count();
	$total_pages = ceil($total_results/$results_per_page);
	?>
	<h2>Woocommerce Liquid Assets Coupons</h2>
    <p>Total: <?php print $total_results; ?></p>
	<?php if (!empty($redemptions)) : ?>
        <div class="la2c-redemptions-admin-list">
            <table class="la2c-table">
                <thead>
                    <tr>
                        <th><?php print __('ID', 'la2c'); ?></th>
                        <th><?php print __('Date', 'la2c'); ?></th>
                        <th><?php print __('User ID', 'la2c'); ?></th>
                        <th><?php print __('Invoice ID', 'la2c'); ?></th>
                        <th><?php print __('Status', 'la2c'); ?></th>
                        <th><?php print __('Coupon Code', 'la2c'); ?></th>
                    </tr>
                </thead>
                <tbody>
				<?php foreach ($redemptions as $item) : ?>
                    <tr>
                        <td><?php print $item->id; ?></td>
                        <td><?php print $item->created; ?></td>
                        <td><?php print $item->customer_id; ?></td>
                        <td><?php print $item->invoice_id; ?></td>
                        <td><?php print $item->status; ?></td>
                        <td><?php print $item->coupon_code; ?></td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <div class="la2c-pager-container">
        <?php if ($total_pages > 1) : ?>
        <ul class="la2c-pager">
	        <?php for($i = 1; $i <= $total_pages; $i++) : ?>
                <?php if ($pager == $i) : ?>
                    <li class="la2c-active"><?php print $i; ?></li>
                <?php else : ?>
                    <li><a href="?page=<?php print $page_query_var; ?>&pager=<?php print $i; ?>"><?php print $i; ?></a></li>
                <?php endif; ?>
	        <?php endfor; ?>
        </ul>
        <?php endif; ?>
    </div>
	<?php endif; ?>
	<?php
}
