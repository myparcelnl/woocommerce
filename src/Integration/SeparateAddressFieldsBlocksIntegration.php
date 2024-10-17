<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Integration;

use MyParcelNL\Pdk\Facade\Pdk;

class SeparateAddressFieldsBlocksIntegration extends AbstractBlocksIntegration
{
    /**
     * @return array
     */
    protected function getScriptData(): array
    {
        return [
            'fields' => [
                Pdk::get('fieldAddress1'),
                Pdk::get('fieldAddress2'),
                Pdk::get('fieldStreet'),
                Pdk::get('fieldNumber'),
                Pdk::get('fieldNumberSuffix'),
            ],
        ];
    }
}
