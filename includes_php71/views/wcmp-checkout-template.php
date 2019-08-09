<?php
/*
 *  Template for the MyParcel checkout.
 *
 */
?>
<style>
	<?php
	if (WooCommerce_MyParcelBE()->setting_collection->getByName('custom_css')) {
		echo WooCommerce_MyParcelBE()->setting_collection->getByName('custom_css');
	}
	?>
</style>

<div id="mypabe-spinner"></div>
<div id="mypabe-message"></div>
<div id="mypabe-location-details"></div>


<div id="mypabe-delivery-option-form">
    <div id="mypabe-send-per-carrier">
        <div id="mypabe-txt-send-per-carrier">Bezorgopties</div>
    </div>
    <ul id="mypabe-pickup-selector">
        <li>
            <label for="mypabe-deliver-date">Bezorgen op</label>
            <div class="full-width">
                <input name="mypabe-deliver-or-pickup" id="mypabe-deliver-pickup-deliver" value="mypabe-deliver" type="radio">
                <div name="mypabe-delivery-date" id="mypabe-delivery-date" type="text" readonly></div>
                <input name="mypabe-delivery-date-only" id="mypabe-delivery-date-only-bpost" type="hidden">
            </div>
        </li>
        <li id="mypabe-bpost-saturday-delivery">
            <label for="mypabe-deliver-date-bpost-saturday">Bezorgen op</label>
            <div class="full-width">
                <input name="mypabe-deliver-or-pickup" id="mypabe-deliver-pickup-deliver-bpost-saturday" value="mypabe-deliver-bpost-saturday" type="radio" readonly>
                <div name="mypabe-delivery-date-bpost-saturday" id="mypabe-delivery-date-bpost-saturday" type="text"></div>
                <input name="mypabe-delivery-date-only-saturday" id="mypabe-delivery-date-only-bpost-saturday" type="hidden">
                <span id="mypabe-delivery-bpost-saturday-price"></span>
            </div>
        </li>
    </ul>
    <ul id="mypabe-delivery-selectors-be">
        <li>
            <div class="full-width">
                <div><input name="mypabe-method-signature-selector-be" id="mypabe-method-signature-selector-be" type="checkbox"</div>
                <label for ="mypabe-method-signature-selector-be">Handtekening voor ontvangst&nbsp;<span id="mypabe-price-bpost-signature"></span></label>
            </div>
        </li>
    </ul>
    <ul id="mypabe-pickup-location-selector">
        <li>
            <label for="mypabe-pickup-location">Afhalen op locatie<span id="mypabe-price-pickup"></span></label>
            <div class="full-width">
                <input name="mypabe-deliver-or-pickup" id="mypabe-deliver-pickup-pickup" value="mypabe-pickup" type="radio">
                <select name="mypabe-pickup-location" id="mypabe-pickup-location">
                    <option value="">Geen Locatie</option>
                </select>
                <span id="mypabe-show-location-details">
                    <svg class="svg-inline--fa fa-clock fa-w-16" aria-hidden="true" data-prefix="fas" data-icon="clock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg=""><path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm57.1 350.1L224.9 294c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h48c6.6 0 12 5.4 12 12v137.7l63.5 46.2c5.4 3.9 6.5 11.4 2.6 16.8l-28.2 38.8c-3.9 5.3-11.4 6.5-16.8 2.6z"></path></svg>
                </span>
            </div>
        </li>
    </ul>
</div>
<input type="hidden" name="mypabe-post-be-data" id="mypabe-post-be-data">

<script src="<?= $urlJsConfig ?>"></script>
<script src="<?= $urlJs ?>"></script>
<script>
    MyParcelBE.init();
    MyParcelBE.bind();
</script>