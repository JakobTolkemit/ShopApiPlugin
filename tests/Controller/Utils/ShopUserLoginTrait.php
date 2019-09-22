<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Utils;

use PHPUnit\Framework\Assert;
use Swagger\Client\Api\AddressApi;
use Swagger\Client\Api\CartApi;
use Swagger\Client\Api\CheckoutApi;
use Swagger\Client\Api\OrderApi;
use Swagger\Client\Api\ProductsApi;
use Swagger\Client\Api\TaxonsApi;
use Swagger\Client\Api\UsersApi;
use Swagger\Client\Model\LoginRequest;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\Response;

trait ShopUserLoginTrait
{
    /**
     * @param string $username
     * @param string $password
     * @param AddressApi|CartApi|CheckoutApi|OrderApi|ProductsApi|TaxonsApi|UsersApi $client
     *
     * @throws \Swagger\Client\ApiException
     */
    protected function logInUser(string $username, string $password, $client): void
    {
        $this->sendLogInRequest([
            'email' => $username,
            'password' => $password,
        ], $client);
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $token
     * @param AddressApi|CartApi|CheckoutApi|OrderApi|ProductsApi|TaxonsApi|UsersApi $client
     *
     * @throws \Swagger\Client\ApiException
     */
    protected function logInUserWithCart(string $username, string $password, string $token, $client): void
    {
        $this->sendLogInRequest([
            'email' => $username,
            'password' => $password,
            'token' => $token,
        ], $client);
    }

    /**
     * @param array $data
     * @param AddressApi|CartApi|CheckoutApi|OrderApi|ProductsApi|TaxonsApi|UsersApi $client
     *
     * @throws \Swagger\Client\ApiException
     */
    private function sendLogInRequest(array $data, $client): void
    {
        /** @var UsersApi $userClient */
        $userClient = $this->createUsersClient();

        $loginRequest = new LoginRequest($data);
        $response = $userClient->loginUser($loginRequest);

        $client->getConfig()->setApiKey('Authorization', sprintf('Bearer %s', $response->getToken()));
    }
}
