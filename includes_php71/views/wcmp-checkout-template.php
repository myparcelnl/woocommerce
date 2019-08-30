<div class="woocommerce-myparcel__checkout">
    <?php
    // Add custom css to the checkout, if any
    if (!empty(WooCommerce_MyParcelBE()->setting_collection->getByName("custom_css"))) {
        echo "<style>";
        echo WooCommerce_MyParcelBE()->setting_collection->getByName("custom_css");
        echo "</style>";
    }
    ?>
  <div id="myparcel-checkout"></div>
</div>
