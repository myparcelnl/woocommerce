<?php
/*
 *  Template for the MyParcel checkout.
 *
 */
?>

<script defer src="https://use.fontawesome.com/releases/v5.0.6/js/all.js"></script>

<div id="mypa-spinner"></div>
<div id="mypa-message"></div>
<div id="mypa-location-details"></div>

<div id="mypa-delivery-option-form">
    <fieldset>
        <legend id="mypa-send-per-carrier">
            <div id="mypa-txt-send-per-carrier">Bezorgopties</div>
        </legend>
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
        <ul id="mypa-pre-selectors-nl">
            <li>
                <label for="method-myparcel-signature-selector">Handtekening</label>
                <div><input name="myparcel-signature-selector" id="mypa-signature-selector" type="checkbox" value="myparcel-signature-selector" ></div>
            </li>
            <li>
                <label for="method-myparcel-recipient-only-selector">Alleen geadresseerde</label>
                <div><input name="method-yparcel-recipient-only-selector" id="mypa-recipient-only-selector" type="checkbox" value="myparcel-recipient-only-selector" ></div>
                <a id="mypa-method-myparcel-recipient-only-selector-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>
        </ul>
        <ul id="mypa-delivery-selectors-nl">
            <li class="mypa-delivery-option method-myparcel-flatrate-div">
                <label for="method-myparcel-flatrate">Thuislevering.</label>
                <div><input name="shipping-method" id="method-myparcel-flatrate" type="radio" value="myparcel-flatrate"></div>
                <a id="mypa-method-myparcel-flatrate-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-delivery-signature-div">
                <label for="method-myparcel-delivery-signature">Thuis of op werk bezorgd met handtekening voor ontvangst.</label>
                <div><input name="shipping-method" id="method-myparcel-delivery-signature" type="radio" value="myparcel-delivery-signature" ></div>
                <a id="mypa-method-myparcel-delivery-signature-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-delivery-only-recipient-div">
                <label for="method-myparcel-delivery-only-recipient">Thuis of op werk bezorgd, niet bij de buren.</label>
                <div><input name="shipping-method" id="method-myparcel-delivery-only-recipient" type="radio" value="myparcel-delivery-only-recipient" ></div>
                <a id="mypa-method-myparcel-delivery-only-recipient-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-delivery-signature-and-only-recipient-fee-div">
                <label for="method-myparcel-delivery-signature-and-only-recipient-fee">Thuis of op werk bezorgd, niet bij de buren, met handtekening.</label>
                <div><input name="shipping-method" id="method-myparcel-delivery-signature-and-only-recipient-fee" type="radio" value="myparcel-delivery-signature-and-only-recipient-fee" ></div>
                <a id="mypa-method-myparcel-delivery-signature-and-only-recipient-fee-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-delivery-evening-signature-div">
                <label for="method-myparcel-delivery-evening-signature">Avondlevering met handtekening.</label>
                <div><input name="shipping-method" id="method-myparcel-delivery-evening-signature" type="radio" value="myparcel-delivery-evening-signature" ></div>
                <a id="mypa-method-myparcel-delivery-evening-signature-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-pickup-express-div">
                <label for="method-myparcel-pickup-express">Extra vroeg ophalen bij PostNL.</label>
                <div><input name="shipping-method" id="method-myparcel-pickup-express" type="radio" value="myparcel-pickup-express" ></div>
                <a id="mypa-method-myparcel-deliverypickup-expreshelp" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-morning-div">
                <label for="method-myparcel-morning">Ochtendlevering.</label>
                <div><input name="shipping-method" id="method-myparcel-morning" type="radio" value="myparcel-morning" ></div>
                <a id="mypa-method-myparcel-morning-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
            </li>

            <li class="mypa-delivery-option method-myparcel-morning-signature-div">
                <label for="method-myparcel-morning-signature">Ochtendlevering met handtekening.</label>
                <div><input name="shipping-method" id="method-myparcel-morning-signature" type="radio" value="myparcel-morning-signature" ></div>
                <a id="mypa-method-myparcel-morning-signature-help" class="mypa-help" href=""><i class="fas fa-question"></i></a>
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
                    <span id="mypa-show-location-details"><i class="fas fa-clock"></i></span>
                </div>
            </li>
        </ul>
    </fieldset>
    <div id="mypa-bottom-spacer"></div>
</div>

<script src="<?= $urlJsConfig ?>"></script>
<script src="<?= $urlJs ?>"></script>
<script>
    MyParcel.init();
    MyParcel.bind();
</script>