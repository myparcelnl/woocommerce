<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Action;

use MyParcelNL\Pdk\Plugin\Action\PdkEndpointActions;

class WcEndpointActions extends PdkEndpointActions
{
    public function getBaseUrl(): string
    {
        return get_rest_url( null, 'myparcel/v1' );
    }
}
