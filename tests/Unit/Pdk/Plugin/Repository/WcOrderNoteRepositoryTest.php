<?php
/** @noinspection StaticClosureCanBeUsedInspection,PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Tests\Pdk\Plugin\Repository;

use MyParcelNL\Pdk\App\Order\Collection\PdkOrderNoteCollection;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\App\Order\Model\PdkOrderNote;
use MyParcelNL\Pdk\Facade\Pdk;
use MyParcelNL\Pdk\Fulfilment\Model\OrderNote;
use MyParcelNL\Sdk\Support\Arr;
use MyParcelNL\WooCommerce\Tests\Uses\UsesMockWcPdkInstance;
use RuntimeException;
use WC_DateTime;
use function MyParcelNL\Pdk\Tests\factory;
use function MyParcelNL\Pdk\Tests\usesShared;
use function MyParcelNL\WooCommerce\Tests\createWcOrder;

usesShared(new UsesMockWcPdkInstance());

it('adds notes', function () {
    $wcOrder = createWcOrder(['id' => 1]);

    $input = [
        'orderIdentifier' => '1',
        'author'          => OrderNote::AUTHOR_WEBSHOP,
        'note'            => 'hello',
        'createdAt'       => '2023-01-01 12:00:00',
        'updatedAt'       => '2023-01-01 12:00:00',
    ];

    $note = factory(PdkOrderNote::class)
        ->with($input)
        ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface $repository */
    $repository = Pdk::get(PdkOrderNoteRepositoryInterface::class);
    $repository->add($note);

    expect($wcOrder->get_meta())->toBe([
        Pdk::get('metaKeyOrderNotes') => [
            [
                'externalIdentifier' => 'customer_note',
                'orderIdentifier'    => '1',
                'author'             => 'customer',
                'note'               => 'This is a test order',
                'createdAt'          => '2021-01-01 18:03:41',
                'updatedAt'          => '2021-01-01 18:03:41',
            ],
            $input,
        ],
    ]);
});

it('gets notes from order', function () {
    createWcOrder([
        'id'            => 68000,
        'customer_note' => 'whoops',
        'order_notes'   => [
            (object) [
                'id'           => 13990,
                'content'      => 'hi',
                'date_created' => new WC_DateTime('2023-01-01 00:00:00'),
            ],
        ],
    ]);

    $pdkOrder = Pdk::get(PdkOrderRepositoryInterface::class)
        ->get('68000');

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface $repository */
    $repository = Pdk::get(PdkOrderNoteRepositoryInterface::class);
    $notes      = $repository->getFromOrder($pdkOrder);

    expect($notes->count())
        ->toBe(2)
        ->and($notes->toArrayWithoutNull())
        ->toBe([
            [
                'externalIdentifier' => '13990',
                'orderIdentifier'    => '68000',
                'author'             => OrderNote::AUTHOR_WEBSHOP,
                'note'               => 'hi',
                'createdAt'          => '2023-01-01 00:00:00',
                'updatedAt'          => '2023-01-01 00:00:00',
            ],
            [
                'externalIdentifier' => 'customer_note',
                'orderIdentifier'    => '68000',
                'author'             => OrderNote::AUTHOR_CUSTOMER,
                'note'               => 'whoops',
                'createdAt'          => '2021-01-01 18:03:41',
                'updatedAt'          => '2021-01-01 18:03:41',
            ],
        ]);
});

it('updates notes', function () {
    createWcOrder([
        'id'          => 9400,
        'order_notes' => [
            (object) [
                'id'      => 12000,
                'content' => 'hi',
            ],
        ],
    ]);

    $pdkOrder = Pdk::get(PdkOrderRepositoryInterface::class)
        ->get('9400');

    $pdkOrderNote =
        factory(PdkOrderNote::class)
            ->withExternalIdentifier('12000')
            ->withOrderIdentifier('9400')
            ->withNote('boo')
            ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface $repository */
    $repository = Pdk::get(PdkOrderNoteRepositoryInterface::class);
    $repository->update($pdkOrderNote);

    $notes = $repository->getFromOrder($pdkOrder);

    /** @var PdkOrderNote $webshopNote */
    $webshopNote = $notes->first();

    expect($webshopNote->toArray())->toHaveKeysAndValues([
        'externalIdentifier' => '12000',
        'orderIdentifier'    => '9400',
        'note'               => 'boo',
    ]);
});

it('throws error when updating with note without order identifier', function () {
    $pdkOrderNote = factory(PdkOrderNote::class)
        ->withOrderIdentifier(null)
        ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface $repository */
    $repository = Pdk::get(PdkOrderNoteRepositoryInterface::class);
    $repository->update($pdkOrderNote);
})->throws(RuntimeException::class, 'Order identifier is missing');

it('updates many order notes', function () {
    createWcOrder([
        'id'          => 29300,
        'order_notes' => [
            (object) ['id' => 14001, 'content' => 'yes'],
            (object) ['id' => 14002, 'content' => 'no'],
            (object) ['id' => 14003, 'content' => 'maybe'],
        ],
    ]);

    $pdkOrder = Pdk::get(PdkOrderRepositoryInterface::class)
        ->get('29300');

    $pdkOrderNotes =
        factory(PdkOrderNoteCollection::class)
            ->push(
                ['externalIdentifier' => '14001'],
                ['externalIdentifier' => '14002']
            )
            ->eachWith(['orderIdentifier' => '29300', 'note' => 'maybe'])
            ->make();

    /** @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderNoteRepositoryInterface $repository */
    $repository = Pdk::get(PdkOrderNoteRepositoryInterface::class);
    $repository->updateMany($pdkOrderNotes);

    $notes = $repository->getFromOrder($pdkOrder);

    $notesArray = $notes->toArrayWithoutNull();

    // Remove the customer note
    array_pop($notesArray);

    expect(Arr::pluck($notesArray, 'externalIdentifier'))
        ->toBe(['14001', '14002', '14003'])
        ->and($notesArray)
        ->each->toHaveKeysAndValues([
            'orderIdentifier' => '29300',
            'note'            => 'maybe',
        ]);
});
