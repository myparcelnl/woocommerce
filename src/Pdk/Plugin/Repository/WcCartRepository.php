<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\App\Cart\Model\PdkCart;
use MyParcelNL\Pdk\App\Cart\Repository\AbstractPdkCartRepository;
use MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface;
use MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface;
use MyParcelNL\Pdk\Base\Support\Arr;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\WooCommerce\Adapter\WcAddressAdapter;
use WC_Cart;

class WcCartRepository extends AbstractPdkCartRepository
{
    /**
     * @var \MyParcelNL\WooCommerce\Adapter\WcAddressAdapter
     */
    private $addressAdapter;

    /**
     * @var \MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface
     */
    private $currencyService;

    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface                $storage
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkProductRepositoryInterface $pdkProductRepository
     * @param  \MyParcelNL\Pdk\Base\Contract\CurrencyServiceInterface           $currencyService
     * @param  \MyParcelNL\WooCommerce\Adapter\WcAddressAdapter                 $addressAdapter
     */
    public function __construct(
        StorageInterface              $storage,
        PdkProductRepositoryInterface $pdkProductRepository,
        CurrencyServiceInterface      $currencyService,
        WcAddressAdapter              $addressAdapter
    ) {
        parent::__construct($storage);
        $this->productRepository = $pdkProductRepository;
        $this->currencyService   = $currencyService;
        $this->addressAdapter    = $addressAdapter;
    }

    /**
     * @param  \WC_Cart|string|int|null $input
     *
     * @return \MyParcelNL\Pdk\App\Cart\Model\PdkCart
     */
    public function get($input): PdkCart
    {
        $input = $input ?? WC()->cart;

        if (! $input instanceof WC_Cart) {
            throw new InvalidArgumentException('Invalid input for cart repository');
        }

        return $this->fromWcCart($input);
    }

    /**
     * @param  \WC_Cart $cart
     *
     * @return \MyParcelNL\Pdk\App\Cart\Model\PdkCart
     */
    protected function fromWcCart(WC_Cart $cart): PdkCart
    {
        return $this->retrieve($cart->get_cart_hash(), function () use ($cart): PdkCart {
            $shipmentPriceAfterVat = $cart->get_shipping_total() + $cart->get_shipping_tax();
            $orderPriceAfterVat    = $cart->get_cart_contents_total() + $cart->get_cart_contents_tax();

            /** @var null|\WC_Shipping_Method|\WC_Shipping_Rate $shippingMethod */
            $shippingMethod = Arr::first($cart->calculate_shipping());

            return new PdkCart([
                'externalIdentifier'    => $cart->get_cart_hash(),
                'shipmentPrice'         => $this->currencyService->convertToCents($cart->get_shipping_total()),
                'shipmentPriceAfterVat' => $this->currencyService->convertToCents($shipmentPriceAfterVat),
                'shipmentVat'           => $this->currencyService->convertToCents($cart->get_shipping_tax()),
                'orderPrice'            => $this->currencyService->convertToCents($cart->get_cart_contents_total()),
                'orderPriceAfterVat'    => $this->currencyService->convertToCents($orderPriceAfterVat),
                'orderVat'              => $this->currencyService->convertToCents($cart->get_cart_contents_tax()),
                'shippingMethod'        => [
                    'id'              => $shippingMethod ? $shippingMethod->get_id() : null,
                    'name'            => $shippingMethod ? $shippingMethod->get_label() : null,
                    'shippingAddress' => $this->addressAdapter->fromWcCart($cart),
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
                }, array_values($cart->cart_contents)),
            ]);
        });
    }
}
