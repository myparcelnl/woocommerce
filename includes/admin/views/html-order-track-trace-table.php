<?php

/**
 * This template is for the Track & Trace information in the MyParcelBE meta box in a single order/
 */

/**
 * @var array $consignments
 * @var int   $order_id
 * @var bool  $downloadDisplay
 */

$shipments = [];

try {
    $shipments = WCMYPABE()->export->getShipmentData(array_keys($consignments), $order);
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

<table class="wcmpbe__table--track-trace">
  <thead>
  <tr>
    <th><?php _e("Track & Trace", "woocommerce-myparcelbe"); ?></th>
    <th><?php _e("Status", "woocommerce-myparcelbe"); ?></th>
    <th>&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?php

  foreach ($shipments as $shipment_id => $shipment):
      $consignment = $shipments[$shipment_id];

      ?>
    <tr>
      <td class="wcmpbe__order__track-trace">
          <?php WCMYPABE_Admin::renderTrackTraceLink($shipment, $order_id); ?>
      </td>
      <td class="wcmpbe__order__status">
          <?php WCMYPABE_Admin::renderStatus($shipment, $order_id) ?>
      </td>
      <td class="wcmpbe__td--create-label">
          <?php
          $action    = WCMPBE_Export::EXPORT;
          $getLabels = WCMPBE_Export::GET_LABELS;

          $order            = wc_get_order($order_id);
          $returnShipmentId = $order->get_meta(WCMYPABE_Admin::META_RETURN_SHIPMENT_IDS);

          WCMYPABE_Admin::renderAction(
              admin_url("admin-ajax.php?action=$action&request=$getLabels&shipment_ids=$shipment_id&return_shipment_id=$returnShipmentId"),
              __("Print MyParcel BE label", "woocommerce-myparcelbe"),
              WCMYPABE()->plugin_url() . "/assets/img/sendmyparcel-print.svg"
          );
          ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
