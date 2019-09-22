<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Sylius\ShopApiPlugin\Command\Cart\AddCoupon;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class RemoveCouponShopApiTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_allows_to_remove_a_promotion_coupon_from_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'coupon_based_promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));
        $bus->dispatch(new AddCoupon($token, 'BANANAS'));

        $cart = $cartClient->cartRemoveCoupon($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/filled_cart_with_simple_product_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_allows_to_remove_a_promotion_coupon_from_the_cart_even_if_it_does_not_exist(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'coupon_based_promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $cart = $cartClient->cartRemoveCoupon($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/filled_cart_with_simple_product_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_promotion_code_if_cart_does_not_exists(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        try {
            $cartClient->cartRemoveCoupon('WRONGTOKEN');

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}
