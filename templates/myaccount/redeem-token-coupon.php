<h2><?php echo __('Your coupon code:', 'la2c') ?></h2>

<p>
	<?php
		if (!empty($coupon_code)) {
			print $coupon_code;
		} else {
			print __('Something went wrong, no coupon code generated. Make sure you sent the Liquid Assets to the invoice.', 'la2c');
		}
	?>
</p>


