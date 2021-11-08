<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Validators\Rules;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Rule\Rule;
use MyParcelNL\Sdk\src\Support\Str;

class StartWithRestApiUrlRule extends Rule
{
    /**
     * @param  mixed $validationSubject
     */
    public function validate($validationSubject): void
    {
        $baseUrl = get_rest_url();

        if (! Str::startsWith($validationSubject, $baseUrl)) {
            $this->addError('(String) url must start with ' . $baseUrl);
        }
    }
}
