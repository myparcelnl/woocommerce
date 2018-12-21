<!-- Add the Custom styles to the checkout -->
<?php if ( ! empty(WooCommerce_MyParcelBE()->checkout_settings['custom_css'])) {
    echo "<style>";
    echo WooCommerce_MyParcelBE()->checkout_settings['custom_css'];
    echo "</style>";
} ?>

<div id="mypabe-load" class="myparcelbe-delivery-options">
    <input style="display:none;" name='mypabe-post-nl-data' id="mypabe-input" />

    <div id="mypabe-spinner-model">
        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 99" enable-background="new 0 0 100 99" xml:space="preserve">
            <image id="bpost-logo" width="100" height="99" href="<?php echo WooCommerce_MyParcelBE()->plugin_url() . '/assets/img/wcmp-bpost-logo.png' ?>" />
        </svg>
        <div id="mypabe-spinner"></div>
    </div>

    <div class="mypabe-message-model">
        <div id="mypabe-message"></div>
    </div>
    <div id="mypabe-location-details"></div>
    <div id="mypabe-delivery-option-form">
        <table class="mypabe-delivery-option-table">
            <tbody>
            <tr id="header-delivery-options-title">
                <td colspan="3">
                    <label for="mypabe-delivery-options-title">
                        <h3><span id="mypabe-delivery-options-title"></span></h3>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <input name="mypabe-deliver-or-pickup" id="mypabe-select-delivery" value="mypabe-deliver" type="radio">
                </td>
                <td colspan="2">
                    <label id="mypabe-select-delivery-title" for="mypabe-select-delivery">
                        <span id="mypabe-delivery-title"></span></label>
                </td>
            </tr>
            <tr id="mypabe-delivery-date-select">
                <td></td>
                <td colspan="2">
                    <select name="mypabe-delivery-date-select" id="mypabe-select-date" title="Delivery date"></select>
                </td>
            </tr>
            <tr id="mypabe-delivery-date-text">
                <td></td>
                <td colspan="2">
                    <div name="mypabe-delivery-date-text" id="mypabe-date" title="Delivery date"></div>
                </td>
            </tr>
            <tr id="mypabe-delivery-option method-myparcelbe-normal-div">
                <td></td>
                <td>
                    <div id="mypabe-delivery" class="mypabe-delivery-option">
                        <input name="shipping-method" id="method-myparcelbe-normal" type="radio" value="myparcelbe-normal">
                        <label for="method-myparcelbe-normal"><span id="mypabe-standard-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="mypabe-delivery-option">
                        <span id="mypabe-normal-delivery"></span>
                    </div>
                </td>
            </tr>
            <tr class="mypabe-extra-delivery-option-signature">
                <td></td>
                <td id="mypabe-signature" class=" mypabe-extra-delivery-options-padding-top">
                    <div class="mypabe-delivery-option">
                        <input name="myparcelbe-signature-selector" id="mypabe-signature-selector" type="checkbox" value="myparcelbe-signature-selector">
                        <label for="mypabe-signature-selector"><span id="mypabe-signature-title"></span></label>
                    </div>
                </td>
                <td class="mypabe-extra-delivery-options-padding-top">
                    <span id="mypabe-signature-price"></span>
                </td>
            </tr>
            <tr id="mypabe-pickup-location-selector" class="mypabe-is-pickup-element">
                <td>
                    <input name="mypabe-deliver-or-pickup" id="mypabe-pickup-delivery" value="mypabe-pickup" type="radio">
                </td>
                <td colspan="2">
                    <label for="mypabe-pickup-delivery"><span id="mypabe-pickup-title"></span></label>
                </td>
            </tr>
            <tr id="mypabe-pickup-options" class="mypabe-is-pickup-element">
                <td></td>
                <td colspan="2">
                    <select name="mypabe-pickup-location" id="mypabe-pickup-location">
                        <option value="">Geen Locatie</option>
                    </select> <span id="mypabe-show-location-details">
                        <svg class="svg-inline--fa mypabe-fa-clock fa-w-16" aria-hidden="true" data-prefix="fas" data-icon="clock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                            <path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm57.1 350.1L224.9 294c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h48c6.6 0 12 5.4 12 12v137.7l63.5 46.2c5.4 3.9 6.5 11.4 2.6 16.8l-28.2 38.8c-3.9 5.3-11.4 6.5-16.8 2.6z"></path>
                        </svg>
                    </span>
                </td>
            </tr>
            <tr id="mypabe-pickup" class="mypabe-is-pickup-element">
                <td></td>
                <td>
                    <input name="method-myparcelbe-pickup-selector" id="mypabe-pickup-selector" type="radio" value="myparcelbe-pickup-selector">
                    <label for="mypabe-pickup-selector">Ophalen vanaf 15:00</label>
                </td>
                <td>
                    <span id="mypabe-pickup-price"></span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
