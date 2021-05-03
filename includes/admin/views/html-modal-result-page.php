<?php
defined('ABSPATH') or die();

include('html-start.php');

/**
 * @var $request
 */
if ($request === WCMPBE_Export::ADD_RETURN) {
    printf('<h3>%s</h3>', __('Return email successfully sent to customer', 'woocommerce-myparcelbe'));
}

include('html-end.php');
