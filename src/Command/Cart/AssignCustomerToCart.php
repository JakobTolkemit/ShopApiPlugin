<?php

declare(strict_types=1);

namespace Sylius\ShopApiPlugin\Command\Cart;

use Sylius\ShopApiPlugin\Command\CommandInterface;

class AssignCustomerToCart implements CommandInterface
{
    /** @var string */
    protected $orderToken;

    /** @var string */
    protected $email;

    public function __construct(string $orderToken, string $email)
    {
        $this->orderToken = $orderToken;
        $this->email = $email;
    }

    public function orderToken(): string
    {
        return $this->orderToken;
    }

    public function email(): string
    {
        return $this->email;
    }
}
