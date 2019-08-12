<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Customer;

use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;

final class LoggedInCustomerDetailsActionTest extends JsonApiTestCase
{
    /**
     * @test
     */
    public function it_shows_currently_logged_in_customer_details(): void
    {
        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml']);

        $data =
            <<<JSON
        {
            "email": "oliver@queen.com",
            "password": "123password"
        }
JSON;

        $this->client->request('POST', '/shop-api/login', [], [], self::CONTENT_TYPE_HEADER, $data);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $response['token']));

        $this->client->request('GET', '/shop-api/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'ACCEPT' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertResponse($response, 'customer/logged_in_customer_details_response', Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_show_customer_details_without_being_logged_in(): void
    {
        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml']);

        $data =
            <<<JSON
        {
            "email": "oliver@queen.com",
            "password": "123password"
        }
JSON;

        $this->client->request('GET', '/shop-api/me', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'ACCEPT' => 'application/json',
        ]);

        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_UNAUTHORIZED);
    }
}
