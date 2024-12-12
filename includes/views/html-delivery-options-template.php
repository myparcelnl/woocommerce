<div class="woocommerce-myparcel__delivery-options">
    <?php
    $css = WCMYPA()->setting_collection->getByName(WCMYPA_Settings::SETTING_DELIVERY_OPTIONS_CUSTOM_CSS) ?? '';
    // Add custom css to the delivery options, if any
    if ($css) {
        echo '<style>';
        echo esc_js(str_replace(["\n","\r","\t"], '', $css));
        echo '</style>';
    }
    ?>
  <div id="myparcel-delivery-options"></div>
</div>
