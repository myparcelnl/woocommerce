<?php

declare(strict_types=1);

/**
 * This template is for the Track & Trace information in the MyParcel meta box in a single order/
 */

/**
 * @var array $consignments
 * @var int   $orderId
 * @var bool  $downloadDisplay
 */

use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Plugin\Collection\PdkOrderCollection;
use MyParcelNL\WooCommerce\PdkOrderRepository;

$shipments = [];

try {
    $orderRepository    = (Pdk::get(PdkOrderRepository::class));
    $pdkOrder           = $orderRepository->get($orderId);
    $pdkOrderCollection = new PdkOrderCollection();

    $pdkOrderCollection->push($pdkOrder->getPdkOrder());
    $shipments = WCMYPA()->export->getShipmentData((array) $orderId, $pdkOrder);
    $test      = 0;
    //$shipments = WCMYPA()->export->getShipmentData(array_keys($consignments), $order);
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

<table class="wcmp__table wcmp__table--track-trace">
  <thead>
  <tr>
    <th><?php _e('Track & Trace', 'woocommerce-myparcel'); ?></th>
    <th><?php _e('Status', 'woocommerce-myparcel'); ?></th>
    <th>&nbsp;</th>
  </tr>
  </thead>
  <tbody>
  <?php

  foreach ($shipments as $shipment_id => $shipment):
      ?>

    <tr>
      <td class="wcmp__order__track-trace">
          <?php WCMYPA_Admin::renderTrackTraceLink($shipment, $orderId); ?>
      </td>
      <td class="wcmp__order__status">
          <?php WCMYPA_Admin::renderStatus($shipment, $orderId) ?>
      </td>
      <td class="wcmp__td--create-label">
          <?php
          $action    = ExportActions::ACTION_NAME;
          $getLabels = ExportActions::GET_LABELS;

          $order            = wc_get_order($orderId);
          $returnShipmentId = $order->get_meta(WCMYPA_Admin::META_RETURN_SHIPMENT_IDS);

          WCMYPA_Admin::renderAction(
              admin_url("admin-ajax.php?action=$action&request=$getLabels&order_ids=$orderId&shipment_ids=$shipment_id&return_shipment_id=$returnShipmentId"),
              __('action_print_myparcel_label', 'woocommerce-myparcel'),
              WCMYPA()->plugin_url() . '/assets/img/print.svg'
          );
          ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
