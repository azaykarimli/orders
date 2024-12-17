<?php

namespace App\Dto;

class OrderResponse
{
    public string $id;
    public array $product;
    public int $qty;
    public float $amount;

    public function __construct(string $id, array $product, int $qty, float $amount)
    {
        $this->id = $id;
        $this->product = $product;
        $this->qty = $qty;
        $this->amount = $amount;
    }

    // Static method to create the DTO from Order entity and product details
    public static function fromEntityWithProduct($order, array $product): self
    {
        return new self(
            $order->getId(),
            $product,
            $order->getQty(),
            $order->getAmount()
        );
    }
}
