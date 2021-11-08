<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Validators;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Rule\Rule;

class StringRule extends Rule
{
    /**
     * @param  mixed $validationSubject
     */
    public function validate($validationSubject): void
    {
        if (! is_string($validationSubject)) {
            $this->addError('Must be a string');
        }
    }
}
