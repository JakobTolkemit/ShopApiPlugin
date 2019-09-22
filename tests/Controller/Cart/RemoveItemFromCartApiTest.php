<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Sylius\ShopApiPlugin\Command\Cart\PutVariantBasedConfigurableItemToCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class RemoveItemFromCartApiTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_deletes_item(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 1));

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');

        $order = $orderRepository->findOneBy(['tokenValue' => $token]);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $order->getItems()->first();

        $cart = $cartClient->cartDeleteItem($token, $orderItem->getId());

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/cart_after_deleting_an_item', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_returns_not_found_exception_if_cart_item_has_not_been_found(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        try {
            $cartClient->cartDeleteItem($token, 420);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/cart_item_has_not_been_found_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_reprocesses_the_order_after_deleting_an_item(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 1));
        $bus->dispatch(new PutVariantBasedConfigurableItemToCart($token, 'LOGAN_HAT_CODE', 'SMALL_RED_LOGAN_HAT_CODE', 10));

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');

        $order = $orderRepository->findOneBy(['tokenValue' => $token]);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $order->getItems()->last();

        $cart = $cartClient->cartDeleteItem($token, $orderItem->getId());

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/reprocessed_cart_after_deleting_an_item', self::RESPONSE_FORMAT);
    }
}
