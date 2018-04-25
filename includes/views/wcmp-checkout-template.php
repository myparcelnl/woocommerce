<?php
/*
 *  Template for the MyParcel checkout.
 *
 */
?>
<style>
	<?php
	if (!empty(WooCommerce_MyParcelBE()->checkout_settings['custom_css'])) {
		echo WooCommerce_MyParcelBE()->checkout_settings['custom_css'];
	}
	?>
</style>

<div id="mypa-spinner"></div>
<div id="mypa-message"></div>
<div id="mypa-location-details"></div>


<div id="mypa-delivery-option-form">
    <div id="mypa-send-per-carrier">
        <div id="mypa-txt-send-per-carrier">Bezorgopties</div>
    </div>
    <ul id="mypa-pickup-selector">
        <li>
            <label for="mypa-deliver-date">Bezorgen op</label>
            <div class="full-width">
                <input name="mypa-deliver-or-pickup" id="mypa-deliver-pickup-deliver" value="mypa-deliver" type="radio">
                <input name="mypa-delivery-date" id="mypa-delivery-date" type="text" readonly>
                <input name="mypa-delivery-date-only" id="mypa-delivery-date-only-bpost" type="hidden">
            </div>
        </li>
        <li id="mypa-bpost-saturday-delivery">
            <label for="mypa-deliver-date-bpost-saturday">Bezorgen op</label>
            <div class="full-width">
                <input name="mypa-deliver-or-pickup" id="mypa-deliver-pickup-deliver-bpost-saturday" value="mypa-deliver-bpost-saturday" type="radio" readonly>
                <input name="mypa-delivery-date-bpost-saturday" id="mypa-delivery-date-bpost-saturday" type="text">
                <input name="mypa-delivery-date-only-saturday" id="mypa-delivery-date-only-bpost-saturday" type="hidden">
                <span id="mypa-delivery-bpost-saturday-price"></span>
            </div>
        </li>
    </ul>
    <ul id="mypa-delivery-selectors-be">
        <li>
            <div class="full-width">
                <div><input name="mypa-method-signature-selector-be" id="mypa-method-signature-selector-be" type="checkbox"</div>
                <label for ="mypa-method-signature-selector-be">Handtekening voor ontvangst&nbsp;<span id="mypa-price-bpost-signature"></span></label>
            </div>
        </li>
    </ul>
    <ul id="mypa-pickup-location-selector">
        <li>
            <label for="mypa-pickup-location">Afhalen op locatie<span id="mypa-price-pickup"></span></label>
            <div class="full-width">
                <input name="mypa-deliver-or-pickup" id="mypa-deliver-pickup-pickup" value="mypa-pickup" type="radio">
                <select name="mypa-pickup-location" id="mypa-pickup-location">
                    <option value="">Geen Locatie</option>
                </select>
                <span id="mypa-show-location-details">
                    <svg class="svg-inline--fa fa-clock fa-w-16" aria-hidden="true" data-prefix="fas" data-icon="clock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm57.1 350.1L224.9 294c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h48c6.6 0 12 5.4 12 12v137.7l63.5 46.2c5.4 3.9 6.5 11.4 2.6 16.8l-28.2 38.8c-3.9 5.3-11.4 6.5-16.8 2.6z"></path></svg>
                </span>
            </div>
        </li>
    </ul>
</div>
<input type="hidden" name="mypa-post-be-data" id="mypa-post-be-data">

<script src="<?= $urlJsConfig ?>"></script>
<script src="<?= $urlJs ?>"></script>
<script>
    MyParcel.init();
    MyParcel.bind();
</script>