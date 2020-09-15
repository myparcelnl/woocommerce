<?php

/**
 * This template is for the Track & Trace information in the MyParcel meta box in a single order/
 */

/**
 * @var array $consignments
 * @var int   $order_id
 * @var bool  $downloadDisplay
 */

$shipments = [];

try {
    $shipments = WCMP()->export->getShipmentData(array_keys($consignments), $order);
} catch (Exception $e) {
    $message = $e->getMessage();
}

if (isset($message)) {
    echo "<p>$message</p>";
}

/**
 * Don't render the table if no shipments have been exported.
 */
if (! count($shipments)) {
    return;
}

?>

<table class="wcmp__table--track-trace">
  <thead>
  <tr>
    <th><?php _e("Track & Trace", "woocommerce-myparcel"); ?></th>
    <th><?php _e("Status", "woocommerce-myparcel"); ?></th>
    <th>&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?php

  foreach ($shipments as $shipment_id => $shipment):
      $consignment = $shipments[$shipment_id];

      ?>
    <tr>
      <td class="wcmp__order__track-trace">
          <?php WCMP_Admin::renderTrackTraceLink($shipment, $order_id); ?>
      </td>
      <td class="wcmp__order__status">
          <?php WCMP_Admin::renderStatus($shipment, $order_id) ?>
      </td>
      <td class="wcmp__td--create-label">
          <?php
          $action    = WCMP_Export::EXPORT;
          $getLabels = WCMP_Export::GET_LABELS;

          $order = wc_get_order($order_id);
          $returnShipmentId = $order->get_meta(WCMP_Admin::META_RETURN_SHIPMENT_IDS);

          WCMP_Admin::renderAction(
              admin_url("admin-ajax.php?action=$action&request=$getLabels&shipment_ids=$shipment_id&return_shipment_id=$returnShipmentId"),
              __("Print MyParcel label", "woocommerce-myparcel"),
              WCMP()->plugin_url() . "/assets/img/myparcel-pdf.png"
          );
          ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
