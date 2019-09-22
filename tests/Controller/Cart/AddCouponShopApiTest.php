<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Swagger\Client\Model\AddCouponRequest;
use Swagger\Client\Model\Cart;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class AddCouponShopApiTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_allows_to_add_promotion_coupon_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml', 'coupon_based_promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $data = [
            "coupon" => "BANANAS",
        ];

        /** @var Cart $cart */
        $cart = $cartClient->cartAddCoupon($token, new AddCouponRequest($data));

        $this->assertResponseContent($cart, 'cart/cart_with_coupon_based_promotion_applied_response', self::RESPONSE_EXTENSION);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_promotion_if_coupon_is_not_specified(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        try {
            $cartClient->cartAddCoupon($token, new AddCouponRequest());

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_coupon_not_found_response', self::RESPONSE_EXTENSION);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_promotion_code_if_cart_does_not_exists(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $data = [
            "coupon" => "BANANAS",
        ];

        try {
            $cartClient->cartAddCoupon('WRONGTOKEN', new AddCouponRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_response', self::RESPONSE_EXTENSION);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_promotion_code_if_promotion_code_does_not_exist(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $data = [
            "coupon" => "BANANAS",
        ];

        try {
            $cartClient->cartAddCoupon($token, new AddCouponRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_coupon_not_valid_response', self::RESPONSE_EXTENSION);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_promotion_code_if_code_is_invalid(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml', 'coupon_based_promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $data = [
            "coupon" => "USED_BANANA",
        ];

        try {
            $cartClient->cartAddCoupon($token, new AddCouponRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_coupon_not_valid_response', self::RESPONSE_EXTENSION);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_promotion_code_if_related_promotion_is_not_valid(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['channel.yml', 'shop.yml', 'coupon_based_promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $data = [
            "coupon" => "PINEAPPLE",
        ];

        try {
            $cartClient->cartAddCoupon($token, new AddCouponRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_coupon_not_valid_response', self::RESPONSE_EXTENSION);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}
