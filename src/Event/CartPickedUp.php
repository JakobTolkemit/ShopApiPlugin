<?php

declare(strict_types=1);

namespace Sylius\ShopApiPlugin\Event;

class CartPickedUp
{
    /** @var string */
    protected $orderToken;

    public function __construct(string $orderToken)
    {
        $this->orderToken = $orderToken;
    }

    public function orderToken(): string
    {
        return $this->orderToken;
    }
}
