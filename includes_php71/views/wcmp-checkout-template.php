<?php
/*
 *  Template for the MyParcel checkout.
 *
 */
?>
<style>
	<?php
	if (!empty(WooCommerce_MyParcelBE()->general_settings['custom_css'])) {
		echo WooCommerce_MyParcelBE()->general_settings['custom_css'];
	}
	?>
</style>

<div id="myparcel-checkout"></div>
