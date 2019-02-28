<!-- Add the Custom styles to the checkout -->
<?php if ( ! empty(WooCommerce_PostNL()->checkout_settings['custom_css'])) {
    echo "<style>";
    echo WooCommerce_PostNL()->checkout_settings['custom_css'];
    echo "</style>";
} ?>

<div id="post-load" class="postnl-delivery-options">
    <input style="display:none;" name='post-post-nl-data' id="post-input" />

    <div id="post-spinner-model">
        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 99" enable-background="new 0 0 100 99" xml:space="preserve">
            <image id="postnl-logo" width="100" height="99" href="<?php echo WooCommerce_PostNL()->plugin_url() . '/assets/img/wcmp-postnl-logo.png' ?>" />
        </svg>
        <div id="post-spinner"></div>
    </div>

    <div class="post-message-model">
        <div id="post-message"></div>
    </div>
    <div id="post-location-details"></div>
    <div id="post-delivery-option-form">
        <table class="post-delivery-option-table">
            <tbody>
            <tr id="header-delivery-options-title">
                <td colspan="3">
                    <label for="post-delivery-options-title">
                        <h3><span id="post-delivery-options-title"></span></h3>
                    </label>
                </td>
            </tr>
            <tr>
                <td>
                    <input name="post-deliver-or-pickup" id="post-select-delivery" value="post-deliver" type="radio">
                </td>
                <td colspan="2">
                    <label id="post-select-delivery-title" for="post-select-delivery">
                    <span id="post-delivery-title"></span></label>
                </td>
            </tr>
            <tr id="post-delivery-date-select">
                <td></td>
                <td colspan="2">
                    <select name="post-delivery-date-select" id="post-select-date" title="Delivery date"></select>
                </td>
            </tr>
            <tr id="post-delivery-date-text">
                <td></td>
                <td colspan="2">
                    <div name="post-delivery-date-text" id="post-date" title="Delivery date"></div>
                </td>
            </tr>
            <tr id="method-postnl-delivery-morning-div">
                <td></td>
                <td>
                    <div class="post-delivery-option">
                        <input name="shipping-method" id="method-postnl-delivery-morning" type="radio" value="postnl-morning">
                        <label for="method-postnl-delivery-morning"><span id="post-morning-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="post-delivery-option">
                        <span id="post-morning-delivery"></span>
                    </div>
                </td>
            </tr>
            <tr id="post-delivery-option method-postnl-normal-div">
                <td></td>
                <td>
                    <div id="post-delivery" class="post-delivery-option">
                        <input name="shipping-method" id="method-postnl-normal" type="radio" value="postnl-normal">
                        <label for="method-postnl-normal"><span id="post-standard-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="post-delivery-option">
                        <span id="post-normal-delivery"></span>
                    </div>
                </td>
            </tr>
            <tr id="method-postnl-delivery-evening-div">
                <td></td>
                <td>
                    <div class="post-delivery-option">
                        <input name="shipping-method" id="method-postnl-delivery-evening" type="radio" value="postnl-delivery-evening">
                        <label for="method-postnl-delivery-evening"><span id="post-evening-title"></span></label>
                    </div>
                </td>
                <td>
                    <div class="post-delivery-option">
                        <span id="post-evening-delivery"> </span>
                    </div>
                </td>
            </tr>
            <tr class="post-extra-delivery-option-signature">
                <td></td>
                <td id="post-signature" class=" post-extra-delivery-options-padding-top">
                    <div class="post-delivery-option">
                        <input name="postnl-signature-selector" id="post-signature-selector" type="checkbox" value="postnl-signature-selector">
                        <label for="post-signature-selector"><span id="post-signature-title"></span></label>
                    </div>
                </td>
                <td class="post-extra-delivery-options-padding-top">
                    <span id="post-signature-price"></span>
                </td>
            </tr>
            <tr class="post-extra-delivery-options">
                <td></td>
                <td id="post-only-recipient">
                    <div class="post-delivery-option">
                        <input name="method-postnl-only-recipient-selector" id="post-only-recipient-selector" type="checkbox" value="postnl-only-recipient-selector">
                        <label for="post-only-recipient-selector"><span id="post-only-recipient-title"></span></label>
                    </div>
                </td>
                <td>
                    <span id="post-only-recipient-price"></span>
                </td>
            </tr>
            <tr id="post-pickup-location-selector" class="post-is-pickup-element">
                <td>
                    <input name="post-deliver-or-pickup" id="post-pickup-delivery" value="post-pickup" type="radio">
                </td>
                <td colspan="2">
                    <label for="post-pickup-delivery"><span id="post-pickup-title"></span></label>
                </td>
            </tr>
            <tr id="post-pickup-options" class="post-is-pickup-element">
                <td></td>
                <td colspan="2">
                    <select name="post-pickup-location" id="post-pickup-location">
                        <option value="">Geen Locatie</option>
                    </select> <span id="post-show-location-details">
                        <svg class="svg-inline--fa post-fa-clock fa-w-16" aria-hidden="true" data-prefix="fas" data-icon="clock" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
                            <path fill="currentColor" d="M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm57.1 350.1L224.9 294c-3.1-2.3-4.9-5.9-4.9-9.7V116c0-6.6 5.4-12 12-12h48c6.6 0 12 5.4 12 12v137.7l63.5 46.2c5.4 3.9 6.5 11.4 2.6 16.8l-28.2 38.8c-3.9 5.3-11.4 6.5-16.8 2.6z"></path>
                        </svg>
                    </span>
                </td>
            </tr>
            <tr id="post-pickup" class="post-is-pickup-element">
                <td></td>
                <td>
                    <input name="method-postnl-pickup-selector" id="post-pickup-selector" type="radio" value="postnl-pickup-selector">
                    <label for="post-pickup-selector"><span class="post-pickup-delivery-titel"></span> 15:00</label>
                </td>
                <td>
                    <span id="post-pickup-price"></span>
                </td>
            </tr>
            <tr id="post-pickup-express" class="post-is-pickup-element">
                <td></td>
                <td>
                    <input name="method-postnl-pickup-selector" id="post-pickup-express-selector" type="radio" value="postnl-pickup-express-selector">
                    <label for="post-pickup-express-selector"><span class="post-pickup-delivery-titel"></span> 09:00</label>
                </td>
                <td>
                    <span id="post-pickup-express-price"></span>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
