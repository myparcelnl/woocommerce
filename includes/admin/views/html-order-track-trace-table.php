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
    $shipments = WCMP()->export->get_shipment_data(array_keys($consignments), $order);
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
      <td class="wcmp__order__track-trace">
          <?php $this->renderTrackTraceLink($shipment, $order_id); ?>
      </td>
      <td class="wcmp__order__status">
          <?php $this->renderStatus($shipment) ?>
      </td>
      <td class="wcmp__td--create-label">
          <?php
          $action    = WCMP_Export::EXPORT;
          $getLabels = WCMP_Export::GET_LABELS;

          $this->renderAction(
              admin_url("admin-ajax.php?action=$action&request=$getLabels&shipment_ids=$shipment_id"),
              __("Print MyParcel BE label", "woocommerce-myparcelbe"),
              WCMP()->plugin_url() . "/assets/img/myparcelbe-pdf.png"
          );
          ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
