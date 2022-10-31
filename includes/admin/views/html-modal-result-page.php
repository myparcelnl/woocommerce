<?php
defined('ABSPATH') or die();
include('html-start.php');

/**
 * @var string $request
 */
if (ExportActions::EXPORT_RETURN === $request) {
    printf('<h3>%s</h3>', __('Return email successfully sent to customer', 'woocommerce-myparcel'));
}

include('html-end.php');
