<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class PickupApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

    /**
     * @test
     */
    public function it_creates_a_new_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $cart = $cartClient->cartPickUp();
        $this->assertTrue($cart->valid());

        $this->assertResponseContent($cart, 'cart/empty_response', self::RESPONSE_FORMAT);

        $orderRepository = $this->get('sylius.repository.order');
        $count = $orderRepository->count([]);

        $this->assertSame(1, $count, 'Only one cart should be created');
    }

    /**
     * @test
     */
    public function it_only_creates_one_cart_if_user_is_logged_in(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'customer.yml']);

        $this->logInUser('oliver@queen.com', '123password', $cartClient);

        $cart = $cartClient->cartPickUp();
        $this->assertTrue($cart->valid());

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $orders = $orderRepository->findAll();

        $this->assertCount(1, $orders, 'Only one cart should be created');

        /** @var OrderInterface $order */
        $order = $orders[0];
        $customer = $order->getCustomer();
        $this->assertNotNull($customer, 'Cart should have customer assigned, but it has not.');
        $this->assertSame('oliver@queen.com', $customer->getEmail());
    }

    /**
     * @test
     */
    public function it_does_not_create_a_new_cart_if_cart_was_picked_up_before_logging_in(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'customer.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $this->logInUserWithCart('oliver@queen.com', '123password', $token, $cartClient);

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $orders = $orderRepository->findAll();

        $this->assertCount(1, $orders, 'Only one cart should be created');

        /** @var OrderInterface $order */
        $order = $orders[0];
        $customer = $order->getCustomer();
        $this->assertNotNull($customer, 'Cart should have customer assigned, but it has not.');
        $this->assertSame('oliver@queen.com', $customer->getEmail());
    }
}
