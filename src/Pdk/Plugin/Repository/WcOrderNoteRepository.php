<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\App\Order\Collection\PdkOrderNoteCollection;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrder;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderNote;
use MyParcelNL\Pdk\App\Order\Repository\AbstractPdkOrderNoteRepository;
use MyParcelNL\Pdk\Base\Support\Collection;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Fulfilment\Model\OrderNote;
use MyParcelNL\Pdk\Storage\Contract\StorageInterface;
use MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface;
use RuntimeException;
use stdClass;
use WC_DateTime;
use WC_Order;

class WcOrderNoteRepository extends AbstractPdkOrderNoteRepository
{
    /**
     * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
     */
    private $pdkOrderRepository;

    /**
     * @var \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface
     */
    private $wcOrderRepository;

    /**
     * @param  \MyParcelNL\Pdk\Storage\Contract\StorageInterface                       $storage
     * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface          $pdkOrderRepository
     * @param  \MyParcelNL\WooCommerce\WooCommerce\Contract\WcOrderRepositoryInterface $wcOrderRepository
     */
    public function __construct(
        StorageInterface            $storage,
        PdkOrderRepositoryInterface $pdkOrderRepository,
        WcOrderRepositoryInterface  $wcOrderRepository
    ) {
        parent::__construct($storage);
        $this->pdkOrderRepository = $pdkOrderRepository;
        $this->wcOrderRepository  = $wcOrderRepository;
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrderNote $note
     *
     * @return void
     * @throws \MyParcelNL\Pdk\Base\Exception\InvalidCastException
     */
    public function add(PdkOrderNote $note): void
    {
        $wcOrder = $this->wcOrderRepository->get($note->orderIdentifier);

        $wcOrder->add_order_note($note->note);

        $this->update($note);
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrder $order
     *
     * @return \MyParcelNL\Pdk\App\Order\Collection\PdkOrderNoteCollection
     */
    public function getFromOrder(PdkOrder $order): PdkOrderNoteCollection
    {
        $wcOrder = $this->wcOrderRepository->get($order->externalIdentifier);

        return $this->retrieve($order->externalIdentifier, function () use ($wcOrder) {
            $existingNotes = new PdkOrderNoteCollection($wcOrder->get_meta(Pdk::get('metaKeyOrderNotes')) ?: []);
            $notes         = wc_get_order_notes(['order_id' => $wcOrder->get_id()]);
            $customerNote  = $wcOrder->get_customer_note();

            if ($customerNote) {
                $notes[] = (object) [
                    'id'       => 'customer_note',
                    'content'  => $customerNote,
                    'added_by' => OrderNote::AUTHOR_CUSTOMER,
                ];
            }

            $newNotes = (new Collection($notes))
                ->filter(static function (stdClass $note) {
                    return 'system' !== $note->added_by;
                })
                ->map(function (stdClass $note) use ($wcOrder) {
                    return $this->toPdkOrderNote($note, $wcOrder);
                });

            return (new PdkOrderNoteCollection($newNotes))->mergeByKey($existingNotes, 'externalIdentifier');
        });
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Model\PdkOrderNote $note
     *
     * @return void
     */
    public function update(PdkOrderNote $note): void
    {
        if (! $note->orderIdentifier) {
            throw new RuntimeException('Order identifier is missing');
        }

        $this->updateMany(new PdkOrderNoteCollection([$note]));
    }

    /**
     * @param  \MyParcelNL\Pdk\App\Order\Collection\PdkOrderNoteCollection $notes
     *
     * @return void
     */
    public function updateMany(PdkOrderNoteCollection $notes): void
    {
        $pdkOrder      = $this->pdkOrderRepository->get($notes->first()->orderIdentifier);
        $existingNotes = $this->getFromOrder($pdkOrder);

        /** @var PdkOrderNoteCollection $mergedNotes */
        $mergedNotes = $existingNotes->mergeByKey($notes, 'externalIdentifier');

        $this->saveNotes($pdkOrder->externalIdentifier, $mergedNotes->toStorableArray());
    }

    /**
     * @return string
     */
    protected function getKeyPrefix(): string
    {
        return 'pdkOrderNote';
    }

    /**
     * @param  string $orderId
     * @param  array  $notes
     *
     * @return void
     */
    private function saveNotes(string $orderId, array $notes): void
    {
        $wcOrder = $this->wcOrderRepository->get($orderId);

        $wcOrder->update_meta_data(Pdk::get('metaKeyOrderNotes'), $notes);
        $wcOrder->save();

        // Invalidate cache
        $this->storage->delete($this->getKeyPrefix() . $orderId);
    }

    /**
     * @param  \stdClass $note
     * @param  \WC_Order $wcOrder
     *
     * @return array
     */
    private function toPdkOrderNote(stdClass $note, WC_Order $wcOrder): array
    {
        $noteCreatedDate = ($note->date_created ?? $wcOrder->get_date_created() ?? new WC_DateTime())
            ->date(Pdk::get('defaultDateFormat'));

        return [
            'apiIdentifier'      => null,
            'orderIdentifier'    => $wcOrder->get_id(),
            'externalIdentifier' => $note->id,
            'author'             => OrderNote::AUTHOR_CUSTOMER === $note->added_by
                ? OrderNote::AUTHOR_CUSTOMER
                : OrderNote::AUTHOR_WEBSHOP,
            'note'               => $note->content ?? null,
            'createdAt'          => $noteCreatedDate,
            'updatedAt'          => $noteCreatedDate,
        ];
    }
}
