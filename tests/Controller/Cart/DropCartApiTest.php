<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\ShopApiPlugin\Command\Cart\AddressOrder;
use Sylius\ShopApiPlugin\Command\Cart\AssignCustomerToCart;
use Sylius\ShopApiPlugin\Command\Cart\ChoosePaymentMethod;
use Sylius\ShopApiPlugin\Command\Cart\ChooseShippingMethod;
use Sylius\ShopApiPlugin\Command\Cart\CompleteOrder;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Sylius\ShopApiPlugin\Model\Address;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class DropCartApiTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_returns_not_found_exception_if_cart_has_not_been_found(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        try {
            $cartClient->cartDrop($token);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);

        try {
            $cartClient->cartDrop($token, 'de_DE');

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_in_german_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_deletes_a_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $cartClient->cartDrop($token);
    }

    /**
     * @test
     */
    public function it_returns_not_found_exception_if_order_is_in_different_state_then_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml', 'country.yml', 'shipping.yml', 'payment.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new AssignCustomerToCart($token, 'sylius@example.com'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));
        $bus->dispatch(new AddressOrder(
            $token,
            Address::createFromArray([
                'firstName' => 'Sherlock',
                'lastName' => 'Holmes',
                'city' => 'London',
                'street' => 'Baker Street 221b',
                'countryCode' => 'GB',
                'postcode' => 'NWB',
                'provinceName' => 'Greater London',
            ]), Address::createFromArray([
                'firstName' => 'Sherlock',
                'lastName' => 'Holmes',
                'city' => 'London',
                'street' => 'Baker Street 221b',
                'countryCode' => 'GB',
                'postcode' => 'NWB',
                'provinceName' => 'Greater London',
            ])
        ));
        $bus->dispatch(new ChooseShippingMethod($token, 0, 'DHL'));
        $bus->dispatch(new ChoosePaymentMethod($token, 0, 'PBC'));

        /** @var OrderInterface $order */
        $order = $this->get('sylius.repository.order')->findOneBy(['tokenValue' => $token]);

        $bus->dispatch(new CompleteOrder($token));

        try {
            $cartClient->cartDrop($order->getTokenValue());

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}
