<?php
defined('ABSPATH') or die();

include('html-start.php');

/**
 * @var $request
 */
if ($request === WCMP_Export::ADD_RETURN) {
    printf('<h3>%s</h3>', __('Return email successfully sent to customer'));
}

include('html-end.php');
