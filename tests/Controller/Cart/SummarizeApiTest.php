<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use Swagger\Client\ApiException;
use Swagger\Client\Model\PutItemToCartRequest;
use Sylius\ShopApiPlugin\Command\Cart\AddCoupon;
use Sylius\ShopApiPlugin\Command\Cart\PickupCart;
use Sylius\ShopApiPlugin\Command\Cart\PutSimpleItemToCart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\OrderPlacerTrait;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class SummarizeApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;
    use OrderPlacerTrait;

    /**
     * @test
     */
    public function it_shows_summary_of_an_empty_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/empty_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_returns_not_found_exception_if_cart_has_not_been_found(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        try {
            $cartClient->cartSummarize('SDAOSLEFNWU35H3QLI5325');

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/cart_has_not_been_found_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_returns_not_found_exception_if_order_is_not_in_state_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['customer.yml', 'country.yml', 'address.yml', 'shop.yml', 'payment.yml', 'shipping.yml']);
        $token = 'SDAOSLEFNWU35H3QLI5325';
        $email = 'oliver@queen.com';

        $this->logInUser($email, '123password', $cartClient);

        $this->placeOrderForCustomerWithEmail($email, $token);

        try {
            $cartClient->cartSummarize($token);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/cart_has_not_been_found_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_shows_summary_of_a_cart_filled_with_a_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/filled_cart_with_simple_product_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_shows_summary_of_a_cart_filled_with_a_simple_product_in_different_language(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_DE'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/german_filled_cart_with_simple_product_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_shows_summary_of_a_cart_filled_with_a_product_with_variant(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $variantWithOptions = [
            "productCode" => "LOGAN_HAT_CODE",
            "options" => [
                "HAT_SIZE" => "HAT_SIZE_S",
                "HAT_COLOR" => "HAT_COLOR_RED",
            ],
            "quantity" => 3,
        ];

        $regularVariant = [
            "productCode" => "LOGAN_T_SHIRT_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 3,
        ];

        $cartClient->cartAddItem($token, new PutItemToCartRequest($regularVariant));
        $cartClient->cartAddItem($token, new PutItemToCartRequest($variantWithOptions));

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/filled_cart_with_product_variant_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_shows_summary_of_a_cart_filled_with_a_product_with_variant_in_different_language(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_DE'));

        $variantWithOptions = [
            "productCode" => "LOGAN_HAT_CODE",
            "options" => [
                "HAT_SIZE" => "HAT_SIZE_S",
                "HAT_COLOR" => "HAT_COLOR_RED",
            ],
            "quantity" => 3,
        ];

        $cartClient->cartAddItem($token, new PutItemToCartRequest($variantWithOptions));

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/german_filled_cart_with_product_variant_summary_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_shows_summary_of_a_cart_with_coupon_applied(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'coupon_based_promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 5));
        $bus->dispatch(new AddCoupon($token, 'BANANAS'));

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/cart_with_coupon_based_promotion_applied_response', self::RESPONSE_FORMAT);
    }
}
