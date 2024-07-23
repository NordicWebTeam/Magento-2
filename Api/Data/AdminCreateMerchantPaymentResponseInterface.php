<?php declare(strict_types=1);

namespace Qliro\QliroOne\Api\Data;

use Qliro\QliroOne\Api\Data\ContainerInterface;

/**
 * Interface for Create Merchant Payment response data model
 */
interface AdminCreateMerchantPaymentResponseInterface extends ContainerInterface
{
    /**
     * @param int $orderId
     * @return void
     */
    public function setOrderId(int $orderId): void;

    /**
     * @return ?int
     */
    public function getOrderId(): ?int;

    /**
     * @param \Qliro\QliroOne\Api\Data\AdminOrderPaymentTransactionInterface[] $transactions
     * @return void
     */
    public function setPaymentTransactions(array $transactions): void;

    /**
     * @return \Qliro\QliroOne\Api\Data\AdminOrderPaymentTransactionInterface[]
     */
    public function getPaymentTransactions(): array;
}
