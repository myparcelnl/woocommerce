<?php 
$ajax_url = admin_url( 'admin-ajax.php' );
$request_prefix = strpos($ajax_url, '?') !== false ? '&' : '?';
$frontend_api_url = wp_nonce_url( $ajax_url . $request_prefix . 'action=wc_myparcel_frontend', 'wc_myparcel_frontend' );
?>
<script type="text/javascript">
/* <![CDATA[ */
window.mypa.settings = {
  base_url:              '<?php echo $frontend_api_url; ?>',
  exclude_delivery_type: 'pickup; pickup_express; morning',
  price: {
    morning:               '10,00',
    default:               '',
    night:                 '',
    pickup:                '1,00',
    pickup_express:        '2,00',
  }
}
/* ]]> */
</script>
<div id='mypa-delivery-options-container'>
  <div id="mypa-slider">
    <!-- First frame -->
    <div id="mypa-delivery-type-selection" class="mypa-tab-container mypa-slider-pos-0">
      <div id="mypa-date-slider-left" class="mypa-arrow-left mypa-back-arrow mypa-date-slider-button mypa-slider-disabled"></div>
      <div id="mypa-date-slider-right" class="mypa-arrow-right myapa-next-arrow mypa-date-slider-button mypa-slider-disabled"></div>
      <div id="mypa-tabs-container">
        <div id='mypa-tabs'>
        </div>
      </div>
      <div class='mypa-delivery-content mypa-container-lg'>
        <div class='mypa-header-lg mypa-delivery-header'>
          <span><b>BEZORGOPTIES</b></span> <span class="mypa-location"></span>
        </div>
        <div id='mypa-delivery-body'>
          <div id='mypa-delivery-row' class='mypa-row-lg'>
            <input id='mypa-delivery-option-check' type="radio" name="mypa-delivery-type" checked>
            <label id='mypa-delivery-options-title' class='mypa-row-title' for="mypa-delivery-option-check">
              <div class="mypa-checkmark mypa-main">
                <div class="mypa-circle mypa-circle-checked"></div>
                <div class="mypa-checkmark-stem"></div>
                <div class="mypa-checkmark-kick"></div>
              </div>
              <span class="mypa-highlight">Thuis of op het werk bezorgd</span>
            </label>
            <div id='mypa-delivery-options' class='mypa-content-lg'>
            </div>
          </div>
          <div id='mypa-pickup-row' class='mypa-row-lg'>
            <input type="radio" name="mypa-delivery-type" id="mypa-pickup-location">
            <label id='mypa-pickup-options-title' class='mypa-row-title' for="mypa-pickup-location">
              <div class="mypa-checkmark mypa-main">
                <div class="mypa-circle"></div>
                <div class="mypa-checkmark-stem"></div>
                <div class="mypa-checkmark-kick"></div>
              </div>
              <span class="mypa-highlight">Ophalen bij een PostNL locatie</span>
            </label>
            <div id='mypa-pickup-options-content' class='mypa-content-lg'>
              <div>
                <label for='mypa-pickup' class='mypa-row-subitem mypa-pickup-selector'>
                  <input id='mypa-pickup' type="radio" name="mypa-delivery-time">
                  <label for="mypa-pickup" class="mypa-checkmark">
                    <div class="mypa-circle"></div>
                    <div class="mypa-checkmark-stem"></div>
                    <div class="mypa-checkmark-kick"></div>
                  </label>
                  <span class="mypa-highlight">Vanaf 16.00 uur</span>
                  <span class='mypa-price mypa-pickup-price'></span>
                </label>
                <label for='mypa-pickup-express' class='mypa-row-subitem mypa-pickup-selector'>
                  <input id='mypa-pickup-express' type="radio" name="mypa-delivery-time">
                  <label for='mypa-pickup-express' class="mypa-checkmark">
                    <div class="mypa-circle mypa-circle-checked"></div>
                    <div class="mypa-checkmark-stem"></div>
                    <div class="mypa-checkmark-kick"></div>
                  </label>
                  <span class="mypa-highlight">Vanaf 8.30 uur</span>
                  <span class='mypa-price mypa-pickup-express-price'></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div id="mypa-location-selector" class="mypa-tab-container mypa-slider-pos-0">
      <!-- Second frame -->
      <div id='mypa-tabs-2'>
      </div>
      <div class='mypa-container-lg mypa-delivery-content'>
        <div class='mypa-header-lg mypa-delivery-header'>
          <span id='mypa-back-arrow' class="mypa-arrow-left mypa-arrow-clickable"><b>AFHALEN </b><span class="mypa-location-time"></span></span>
        </div>
        <div id="mypa-location-container">

        </div>
      </div>
    </div>
  </div>
</div>