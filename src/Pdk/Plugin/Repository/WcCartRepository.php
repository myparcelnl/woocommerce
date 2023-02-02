<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\Plugin\Model\PdkCart;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkCartRepository;
use MyParcelNL\Pdk\Product\Repository\ProductRepositoryInterface;
use MyParcelNL\Pdk\Storage\StorageInterface;
use WC_Cart;

class WcCartRepository extends AbstractPdkCartRepository
{
    /**
     * @var \MyParcelNL\Pdk\Product\Repository\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\StorageInterface                      $storage
     * @param  \MyParcelNL\Pdk\Product\Repository\ProductRepositoryInterface $productRepository
     */
    public function __construct(StorageInterface $storage, ProductRepositoryInterface $productRepository)
    {
        parent::__construct($storage);
        $this->productRepository = $productRepository;
    }

    /**
     * @param  \WC_Cart|string|int $input
     *
     * @return \MyParcelNL\Pdk\Plugin\Model\PdkCart
     */
    public function get($input): PdkCart
    {
        if (! $input instanceof WC_Cart) {
            throw new InvalidArgumentException('Invalid input for cart repository');
        }

        return $this->retrieve($input->get_cart_hash(), function () use ($input): PdkCart {
            $data = [
                'externalIdentifier'    => $input->get_cart_hash(),
                'shipmentPrice'         => $input->get_shipping_total(),
                'shipmentPriceAfterVat' => $input->get_shipping_total() + $input->get_shipping_tax(),
                'shipmentVat'           => $input->get_shipping_tax(),
                'orderPrice'            => $input->get_cart_contents_total(),
                'orderPriceAfterVat'    => $input->get_cart_contents_total() + $input->get_cart_contents_tax(),
                'orderVat'              => $input->get_cart_contents_tax(),
                'shippingMethod'        => [
                    'packageType'     => 'package',
                    'shippingAddress' => [
                        'cc'         => WC()->customer->get_shipping_country(),
                        'postalCode' => WC()->customer->get_shipping_postcode(),
                        'fullStreet' => WC()->customer->get_shipping_address(),
                    ],
                ],
                'lines'                 => array_map(function ($item) {
                    $product = $this->productRepository->getProduct($item['data']);

                    return [
                        'quantity'      => $item['quantity'],
                        'price'         => $item['line_subtotal'],
                        'vat'           => $item['line_subtotal_tax'],
                        'priceAfterVat' => $item['line_subtotal'] + $item['line_subtotal_tax'],
                        'product'       => $product,
                    ];
                }, array_values($input->cart_contents)),
            ];

            return new PdkCart($data);
        });
    }
}
