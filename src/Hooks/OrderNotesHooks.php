<?php

declare(strict_types=1);

namespace MyParcelNL\WooCommerce\Hooks;

use MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface;
use MyParcelNL\Pdk\Fulfilment\Collection\OrderNoteCollection;
use MyParcelNL\Pdk\Fulfilment\Repository\OrderNotesRepository;
use MyParcelNL\WooCommerce\Hooks\Contract\WordPressHooksInterface;
use WC_Order;

final class OrderNotesHooks implements WordPressHooksInterface
{
	/**
	 * @var \MyParcelNL\Pdk\Fulfilment\Repository\OrderNotesRepository
	 */
	private $orderNotesRepository;
	/**
	 * @var \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface
	 */
	private $orderRepository;

	/**
	 * @param  \MyParcelNL\Pdk\App\Order\Contract\PdkOrderRepositoryInterface $pdkOrderRepository
	 * @param  \MyParcelNL\Pdk\Fulfilment\Repository\OrderNotesRepository     $orderNotesRepository
	 */
	public function __construct(PdkOrderRepositoryInterface $pdkOrderRepository, OrderNotesRepository $orderNotesRepository)
	{
		$this->orderRepository      = $pdkOrderRepository;
		$this->orderNotesRepository = $orderNotesRepository;
	}

	/**
	 * @return void
	 */
	public function apply(): void
	{
		add_action('woocommerce_order_note_added', [$this, 'addOrderNotes'], 2, 2);
	}

	/**
	 * @param  int       $commentId
	 * @param  \WC_Order $order
	 *
	 * @return void
	 */
	public function addOrderNotes(int $commentId, WC_Order $order): void
	{
		$note     = get_comment($commentId);
		$pdkOrder = $this->orderRepository->get($order->get_id());

		$this->orderNotesRepository->postOrderNotes(
			new OrderNoteCollection([
				[
					'note' => $note->comment_content,
					'author' => 'webshop',
				]
			]),
			$pdkOrder->fulfilmentIdentifier
		);
	}
}
