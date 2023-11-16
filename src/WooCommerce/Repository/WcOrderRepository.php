<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\WooCommerce\Repository;

use InvalidArgumentException;
use MyParcelNL\Pdk\Base\Repository\Repository;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use WC_Order;
use WC_Order_Item_Product;

final class WcOrderRepository extends Repository implements WcOrderRepositoryInterface
{
    /**
     * @param  int|string|WC_Order|\WP_Post $input
     *
     * @return \WC_Order
     * @throws \Throwable
     */
    public function get($input): WC_Order
    {
        if (is_object($input) && method_exists($input, 'get_id')) {
            $id = $input->get_id();
        } elseif (is_object($input) && isset($input->ID)) {
            $id = $input->ID;
        } else {
            $id = $input;
        }

        if (! is_scalar($id)) {
            throw new InvalidArgumentException('Invalid input');
        }

        return $this->retrieve((string) $id, function () use ($input, $id) {
            if (is_a($input, WC_Order::class)) {
                return $input;
            }

            return new WC_Order($id);
        });
    }

    /**
     * @param  int|string|WC_Order|\WP_Post $input
     *
     * @return \MyParcelNL\Pdk\Base\Support\Collection
     * @throws \Throwable
     */
    public function getItems($input): Collection
    {
        $order = $this->get($input);

        return $this->retrieve("{$order->get_id()}_items", function () use ($order) {
            return new Collection(
                array_map(static function ($item) {
                    $product = $item instanceof WC_Order_Item_Product ? $item->get_product() : null;

                    return [
                        'item'    => $item,
                        'product' => $product,
                    ];
                }, array_values($order->get_items() ?: []))
            );
        });
    }

    protected function getKeyPrefix(): string
    {
        return WC_Order::class;
    }
}
