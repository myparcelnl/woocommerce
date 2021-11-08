<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\includes\Validators;

defined('ABSPATH') or die();

use MyParcelNL\Sdk\src\Validator\AbstractValidator;
use MyParcelNL\WooCommerce\includes\Validators\Rules\StartWithRestApiUrlRule;
use MyParcelNL\WooCommerce\includes\Validators\Rules\UseHttpsRule;

class WebhookCallbackUrlValidator extends AbstractValidator
{
    protected function getRules(): array
    {
        return [
            new StringRule(),
            new UseHttpsRule(),
            new StartWithRestApiUrlRule(),
        ];
    }
}
