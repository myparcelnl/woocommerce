<div class="woocommerce-myparcel__delivery-options">
    <?php
    // Add custom css to the delivery options, if any
    if (!empty(WCMP()->setting_collection->getByName("delivery_options_custom_css"))) {
        echo "<style>";
        echo WCMP()->setting_collection->getByName("delivery_options_custom_css");
        echo "</style>";
    }
    ?>
  <div id="myparcel-delivery-options"></div>
</div>
