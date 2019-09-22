<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Swagger\Client\Model\PutItemsToCartRequest;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class PutItemsToCartApiTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_adds_a_product_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "items" => [
                [
                    "productCode" => "LOGAN_MUG_CODE",
                    "quantity" => 3,
                ],
                [
                    "productCode" => "LOGAN_T_SHIRT_CODE",
                    "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
                    "quantity" => 3,
                ],
                [
                    "productCode" => "LOGAN_HAT_CODE",
                    "options" => [
                        "HAT_SIZE" => "HAT_SIZE_S",
                        "HAT_COLOR" => "HAT_COLOR_RED",
                    ],
                    "quantity" => 3,
                ],
            ],
        ];

        $cart = $cartClient->cartPutItems($token, new PutItemsToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_multiple_products_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_does_nothing_if_any_of_requested_products_is_not_valid(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "items" => [
                [
                    "productCode" => "LOGAN_MUG_CODE",
                    "quantity" => 3,
                ],
                [
                    "productCode" => "LOGAN_T_SHIRT_CODE",
                    "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
                ],
                [
                    "productCode" => "LOGAN_HAT_CODE",
                    "options" => [
                        "HAT_SIZE" => "HAT_SIZE_S",
                        "HAT_COLOR" => "HAT_COLOR_RED",
                    ],
                    "quantity" => 3,
                ],
            ],
        ];

        try {
            $cartClient->cartPutItems($token, new PutItemsToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $thrown = true;
        }
        $this->assertTrue($thrown);

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/empty_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_shows_validation_error_for_proper_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "items" => [
                [
                    "productCode" => "LOGAN_MUG_CODE",
                    "quantity" => 3,
                ],
                [
                    "productCode" => "LOGAN_T_SHIRT_CODE",
                    "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
                ],
                [
                    "productCode" => "LOGAN_HAT_CODE",
                    "options" => [
                        "HAT_SIZE" => "HAT_SIZE_S",
                        "HAT_COLOR" => "HAT_COLOR_RED",
                    ],
                ],
            ],
        ];

        try {
            $cartClient->cartPutItems($token, new PutItemsToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/add_multiple_products_to_cart_validation_error_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_creates_new_cart_when_token_is_not_passed(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $data = [
            "items" => [
                [
                    "productCode" => "LOGAN_MUG_CODE",
                    "quantity" => 3,
                ],
                [
                    "productCode" => "LOGAN_T_SHIRT_CODE",
                    "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
                    "quantity" => 3,
                ],
                [
                    "productCode" => "LOGAN_HAT_CODE",
                    "options" => [
                        "HAT_SIZE" => "HAT_SIZE_S",
                        "HAT_COLOR" => "HAT_COLOR_RED",
                    ],
                    "quantity" => 3,
                ],
            ],
        ];

        $cart = $cartClient->cartPutItems('', new PutItemsToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_multiple_products_to_new_cart_response', self::RESPONSE_FORMAT);
    }
}
