<div class="woocommerce-myparcel__delivery-options">
    <?php
    // Add custom css to the delivery options, if any
    if (!empty(WooCommerce_MyParcelBE()->setting_collection->getByName("delivery_options_custom_css"))) {
        echo "<style>";
        echo WooCommerce_MyParcelBE()->setting_collection->getByName("delivery_options_custom_css");
        echo "</style>";
    }
    ?>
  <div id="myparcel-delivery-options"></div>
</div>
