<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Validators\Rules;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Rule\Rule;
use MyParcelNL\Sdk\src\Support\Str;

class UseHttpsRule extends Rule
{
    /**
     * @param  mixed $validationSubject
     */
    public function validate($validationSubject): void
    {
        if (! Str::startsWith($validationSubject, 'https://')) {
            $this->addError('String must start with https://');
        }
    }
}
