<div class="woocommerce-myparcel__delivery-options">
    <?php
    // Add custom css to the delivery options, if any
    if (!empty(WCMYPA()->settingCollection->getByName('delivery_options_custom_css'))) {
        echo '<style>';
        echo WCMYPA()->settingCollection->getByName('delivery_options_custom_css');
        echo '</style>';
    }
    ?>
  <div id="myparcel-delivery-options"></div>
</div>
