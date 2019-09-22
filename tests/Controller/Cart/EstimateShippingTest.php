<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class EstimateShippingTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_returns_not_found_exception_if_cart_has_not_been_found(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'country.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        try {
            $cartClient->estimateShippingCost($token, '', '');

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/cart_and_country_does_not_exist_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_calculates_estimated_shipping_cost_based_on_country(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'country.yml', 'shipping.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $shippingCost = $cartClient->estimateShippingCost($token, 'GB', '');

        $this->assertResponseContent($shippingCost, 'cart/estimated_shipping_cost_bases_on_country_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_calculates_estimated_shipping_cost_based_on_country_and_province(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'country.yml', 'shipping.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $shippingCost = $cartClient->estimateShippingCost($token, 'GB', 'GB-SC');

        $this->assertResponseContent($shippingCost, 'cart/estimated_shipping_cost_bases_on_country_and_province_response', self::RESPONSE_FORMAT);
    }
}
