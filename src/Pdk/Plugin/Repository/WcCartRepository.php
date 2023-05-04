<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface;
use MyParcelNL\Pdk\Plugin\Model\PdkCart;
use MyParcelNL\Pdk\Plugin\Repository\AbstractPdkCartRepository;
use MyParcelNL\Pdk\Product\Contract\ProductRepositoryInterface;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use WC_Cart;

class WcCartRepository extends AbstractPdkCartRepository
{
    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface
     */
    private $currencyService;

    /**
     * @var \MyParcelNL\Pdk\Product\Contract\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface           $storage
     * @param  \MyParcelNL\Pdk\Product\Contract\ProductRepositoryInterface $productRepository
     * @param  \MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface      $currencyService
     */
    public function __construct(
        StorageInterface           $storage,
        ProductRepositoryInterface $productRepository,
        CurrencyServiceInterface   $currencyService
    ) {
        parent::__construct($storage);
        $this->productRepository = $productRepository;
        $this->currencyService   = $currencyService;
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
            $shipmentPriceAfterVat = $input->get_shipping_total() + $input->get_shipping_tax();
            $orderPriceAfterVat    = $input->get_cart_contents_total() + $input->get_cart_contents_tax();

            $data = [
                'externalIdentifier'    => $input->get_cart_hash(),
                'shipmentPrice'         => $this->currencyService->convertToCents($input->get_shipping_total()),
                'shipmentPriceAfterVat' => $this->currencyService->convertToCents($shipmentPriceAfterVat),
                'shipmentVat'           => $this->currencyService->convertToCents($input->get_shipping_tax()),
                'orderPrice'            => $this->currencyService->convertToCents($input->get_cart_contents_total()),
                'orderPriceAfterVat'    => $this->currencyService->convertToCents($orderPriceAfterVat),
                'orderVat'              => $this->currencyService->convertToCents($input->get_cart_contents_tax()),
                'shippingMethod'        => [
                    'shippingAddress' => [
                        'cc'         => WC()->customer->get_shipping_country(),
                        'postalCode' => WC()->customer->get_shipping_postcode(),
                        'fullStreet' => WC()->customer->get_shipping_address(),
                    ],
                ],
                'lines'                 => array_map(function (array $item) {
                    $product       = $this->productRepository->getProduct($item['data']);
                    $priceAfterVat = $item['line_subtotal'] + $item['line_subtotal_tax'];

                    return [
                        'quantity'      => (int) $item['quantity'],
                        'price'         => $this->currencyService->convertToCents($item['line_subtotal']),
                        'vat'           => $this->currencyService->convertToCents($item['line_subtotal_tax']),
                        'priceAfterVat' => $this->currencyService->convertToCents($priceAfterVat),
                        'product'       => $product,
                    ];
                }, array_values($input->cart_contents)),
            ];

            return new PdkCart($data);
        });
    }
}
