<?php
/** @noinspection PhpUnhandledExceptionInspection,StaticClosureCanBeUsedInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Product\Repository;

use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\WooCommerce\Tests\Mock\MockWcData;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use WC_Product;
use function MyParcelNL\Pdk\Tests\usesShared;

usesShared(new UsesMockWcPdkInstance());

it('does not clone the woocommerce product', function () {
    // Cloning a WC_Product clears its meta ids. When a filter saves that clone, all meta is inserted again.
    MockWcData::create(
        new class(['id' => 7100]) extends WC_Product {
            public function __clone()
            {
                throw new RuntimeException('The product must not be cloned.');
            }
        }
    );

    /** @var PdkProductRepositoryInterface $productRepository */
    $productRepository = Pdk::get(PdkProductRepositoryInterface::class);

    expect($productRepository->getProduct(7100)->externalIdentifier)->toBe('7100');
});
