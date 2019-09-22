<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller;

use Swagger\Client\Api\AddressApi;
use Swagger\Client\Api\CartApi;
use Swagger\Client\Api\CheckoutApi;
use Swagger\Client\Api\OrderApi;
use Swagger\Client\Api\ProductsApi;
use Swagger\Client\Api\TaxonsApi;
use Swagger\Client\Api\UsersApi;
use Swagger\Client\Configuration;

abstract class JsonApiTestCase extends \ApiTestCase\JsonApiTestCase
{
    public const CONTENT_TYPE_HEADER = ['CONTENT_TYPE' => 'application/json', 'ACCEPT' => 'application/json'];

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->dataFixturesPath = __DIR__ . '/../DataFixtures/ORM';
        $this->expectedResponsesPath = __DIR__ . '/../Responses/Expected';
    }

    protected function get($id)
    {
        if (property_exists(static::class, 'container')) {
            return static::$container->get($id);
        }

        return parent::get($id);
    }

    protected function getAddressClient(): AddressApi
    {
        return new AddressApi(null, $this->getClientConfig());
    }

    protected function getCartClient(): CartApi
    {
        return new CartApi(null, $this->getClientConfig());
    }

    protected function getCheckoutClient(): CheckoutApi
    {
        return new CheckoutApi(null, $this->getClientConfig());
    }

    protected function getUsersClient(): UsersApi
    {
        return new UsersApi(null, $this->getClientConfig());
    }

    protected function getOrderClient(): OrderApi
    {
        return new OrderApi(null, $this->getClientConfig());
    }

    protected function getProductsClient(): ProductsApi
    {
        return new ProductsApi(null, $this->getClientConfig());
    }

    protected function getTaxonsClient(): TaxonsApi
    {
        return new TaxonsApi(null, $this->getClientConfig());
    }

    private function getClientConfig(): Configuration
    {
        $config = new Configuration();
        $config->setHost('localhost:8080');

        return $config;
    }
}
