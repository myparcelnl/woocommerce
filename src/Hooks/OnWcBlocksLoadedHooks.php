<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use Exception;
use MyParcelNL\Pdk\Facade\Logger;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Sdk\src\Support\Arr;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use MyParcelNL\WooCommerce\Service\WooCommerceService;
use MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface;

class OnWcBlocksLoadedHooks implements WordPressHooksInterface
{
    /**
     * @var \MyParcelNL\WooCommerce\Service\WooCommerceService
     */
    private $wooCommerceService;

    /**
     * @param  \MyParcelNL\WooCommerce\Service\WooCommerceService $wooCommerceService
     */
    public function __construct(WooCommerceService $wooCommerceService)
    {
        $this->wooCommerceService = $wooCommerceService;
    }

    public function apply(): void
    {
        if (! $this->wooCommerceService->isUsingBlocksCheckout()) {
            return;
        }

        $this->registerWcBlocksCheckoutFields();
    }

    /**
     * @return array<class-string<AddressFieldInterface>>
     */
    private function getCustomFields(): array
    {
        return Arr::flatten(Pdk::get('customFields'), 1);
    }

    /**
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Address\Contract\AddressFieldInterface $field
     *
     * @return void
     * @throws \Exception
     */
    private function registerWcBlocksCheckoutField(AddressFieldInterface $field): void
    {
        woocommerce_register_additional_checkout_field([
            'id'         => $field->getId(),
            'label'      => $field->getTranslatedLabel(),
            'type'       => $field->getType(),
            'required'   => $field->isRequired(),
            'location'   => $field->getLocation(),
            'attributes' => $field->getBlocksCheckoutAttributes(),
            'index'      => $field->getIndex(),
        ]);
    }

    /**
     * @return void
     */
    private function registerWcBlocksCheckoutFields(): void
    {
        foreach ($this->getCustomFields() as $class) {
            try {
                $instance = new $class();

                $this->registerWcBlocksCheckoutField($instance);
            } catch (Exception $e) {
                Logger::error(
                    'Failed to register field',
                    [
                        'error' => $e->getMessage(),
                        'class' => $class,
                    ]
                );
            }
        }
    }
}
