<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Cart;

use FOS\RestBundle\Controller\Annotations\Put;
use Swagger\Client\ApiException;
use Swagger\Client\Model\PutItemToCartRequest;
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
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class PutItemToCartApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

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
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => 3,
        ];

        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_simple_product_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_recalculates_cart_when_customer_log_in(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'customer.yml', 'promotion.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));
        $bus->dispatch(new PutSimpleItemToCart($token, 'LOGAN_MUG_CODE', 1));

        $this->logInUserWithCart('oliver@queen.com', '123password', $token, $cartClient);

        $cart = $cartClient->cartSummarize($token);

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/recalculated_cart_after_log_in', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_increases_quantity_of_existing_item_if_the_same_product_is_added_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => 1,
        ];

        $cartClient->cartAddItem($token, new PutItemToCartRequest($data));
        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_simple_product_multiple_times_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_validates_if_product_is_simple_during_add_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_HAT_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_not_simple_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_validates_if_quantity_is_larger_than_0_during_add_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => 0,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

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
    public function it_converts_quantity_as_an_integer_and_adds_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => "3",
        ];

        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_simple_product_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_validates_if_product_code_is_defined_during_add_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_not_defined_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_validates_if_product_exists_during_add_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "BARBECUE_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_product_if_cart_does_not_exists_during_add_simple_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_add_product_if_order_has_been_placed(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml', 'country.yml', 'shipping.yml', 'payment.yml']);

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

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_cart_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_adds_a_product_variant_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_T_SHIRT_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 3,
        ];

        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_product_variant_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_increases_quantity_of_existing_item_if_the_same_variant_is_added_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_T_SHIRT_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 3,
        ];

        $cartClient->cartAddItem($token, new PutItemToCartRequest($data));
        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_product_variant_multiple_times_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_validates_if_quantity_is_larger_than_0_during_add_variant_based_configurable_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_T_SHIRT_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 0,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

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
    public function it_converts_quantity_as_an_integer_and_adds_variant_based_configurable_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_T_SHIRT_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => "3",
        ];

        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_product_variant_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_validates_if_product_code_is_defined_during_add_variant_based_configurable_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_not_defined_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_validates_if_product_exists_during_add_variant_based_configurable_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "BARBECUE_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_validates_if_product_is_configurable_during_add_variant_based_configurable_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "variantCode" => "SMALL_LOGAN_T_SHIRT_CODE",
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_not_configurable_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_validates_if_product_variant_exist_during_add_variant_based_configurable_product(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_T_SHIRT_CODE",
            "variantCode" => "BARBECUE_CODE",
            "quantity" => 3
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/validation_product_variant_not_exists_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function it_throws_an_exception_if_product_variant_has_not_been_found(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_HAT_CODE",
            "options" => [
                "HAT_SIZE" => "HAT_SIZE_S",
            ],
            "quantity" => 3,
        ];

        try {
            $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());
            $this->assertResponseContent($exception->getResponseBody(), 'cart/product_variant_has_not_been_found_response', self::RESPONSE_FORMAT);

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_adds_a_product_variant_based_on_options_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_HAT_CODE",
            "options" => [
                "HAT_SIZE" => "HAT_SIZE_S",
                "HAT_COLOR" => "HAT_COLOR_RED",
            ],
            "quantity" => 3,
        ];

        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_product_variant_based_on_options_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_increases_quantity_of_existing_item_while_adding_the_same_product_variant_based_on_option_to_the_cart(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $token = 'SDAOSLEFNWU35H3QLI5325';

        /** @var MessageBusInterface $bus */
        $bus = $this->get('sylius_shop_api_plugin.command_bus');
        $bus->dispatch(new PickupCart($token, 'WEB_GB'));

        $data = [
            "productCode" => "LOGAN_HAT_CODE",
            "options" => [
                "HAT_SIZE" => "HAT_SIZE_S",
                "HAT_COLOR" => "HAT_COLOR_RED",
            ],
            "quantity" => 3,
        ];

        $cartClient->cartAddItem($token, new PutItemToCartRequest($data));
        $cart = $cartClient->cartAddItem($token, new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_product_variant_based_on_options_multiple_times_to_cart_response', self::RESPONSE_FORMAT);
    }

    /**
     * @test
     */
    public function it_creates_new_cart_when_token_is_not_passed(): void
    {
        $cartClient = $this->createCartClient();

        $this->loadFixturesFromFiles(['shop.yml']);

        $data = [
            "productCode" => "LOGAN_MUG_CODE",
            "quantity" => 3,
        ];

        $cart = $cartClient->cartAddItem('', new PutItemToCartRequest($data));

        $this->assertTrue($cart->valid());
        $this->assertResponseContent($cart, 'cart/add_simple_product_to_new_cart_response', self::RESPONSE_FORMAT);
    }
}
