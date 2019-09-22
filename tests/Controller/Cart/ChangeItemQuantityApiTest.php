<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Swagger\Client\Model\Cart;
use Swagger\Client\Model\ChangeItemQuantityRequest;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class ChangeItemQuantityApiTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_does_not_allow_to_change_quantity_if_cart_does_not_exists(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';
        $id = 1;

        $data = [
            "quantity" => 5,
        ];

        try {
            $cartClient->cartUpdateItem($token, $id, new ChangeItemQuantityRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_and_cart_item_not_exist_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_changes_item_quantity(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 3));

        $data = [
            "quantity" => 5,
        ];

        /** @var Cart $cartResponse */
        $cartResponse = $cartClient->cartUpdateItem($token, $this->getFirstOrderItemId($token), new ChangeItemQuantityRequest($data));

        $this->assertResponseContent($cartResponse, 'cart/filled_cart_with_simple_product_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_set_quantity_lower_than_one(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 3));

        $data = [
            "quantity" => 0,
        ];

        try {
            $cartClient->cartUpdateItem($token, $this->getFirstOrderItemId($token), new ChangeItemQuantityRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_quantity_lower_than_one_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_change_quantity_without_quantity_defined(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 3));

        try {
            $cartClient->cartUpdateItem($token, $this->getFirstOrderItemId($token), new ChangeItemQuantityRequest());

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_quantity_lower_than_one_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_change_quantity_if_cart_item_does_not_exists(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "quantity" => 5,
        ];

        try {
            $cartClient->cartUpdateItem($token, 420, new ChangeItemQuantityRequest());

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_item_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    private function getFirstOrderItemId(string $orderToken): string
    {
        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');

        $order = $orderRepository->findOneBy(['tokenValue' => $orderToken]);

        /** @var OrderItemInterface $orderItem */
        $orderItem = $order->getItems()->first();

        return (string) $orderItem->getId();
    }
}
