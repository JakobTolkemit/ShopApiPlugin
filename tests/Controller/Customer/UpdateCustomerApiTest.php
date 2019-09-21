<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\Customer;

use PHPUnit\Framework\Assert;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class UpdateCustomerApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

    /**
     * @test
     */
    public function it_updates_customer(): void
    {
        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml']);
        $this->logInUser('oliver@queen.com', '123password');

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->get('sylius.repository.customer');

        $data =
<<<JSON
        {
            "firstName": "New name",
            "lastName": "New lastName",
            "email": "oliver@queen.com",
            "birthday": "2017-11-01",
            "gender": "m",
            "phoneNumber": "0918972132",
            "subscribedToNewsletter": true
        }
JSON;
        $this->client->request('PUT', '/shop-api/me', [], [], self::CONTENT_TYPE_HEADER, $data);
        $response = $this->client->getResponse();
        $this->assertResponse($response, 'customer/update_customer', Response::HTTP_OK);

        /** @var CustomerInterface $customer */
        $customer = $customerRepository->findOneByEmail('oliver@queen.com');

        Assert::assertEquals($customer->getFirstName(), 'New name');
        Assert::assertEquals($customer->getLastName(), 'New lastName');
        Assert::assertEquals($customer->getEmail(), 'oliver@queen.com');
        Assert::assertEquals($customer->getBirthday(), new \DateTimeImmutable('2017-11-01'));
        Assert::assertEquals($customer->getGender(), 'm');
        Assert::assertEquals($customer->getPhoneNumber(), '0918972132');
        Assert::assertEquals($customer->isSubscribedToNewsletter(), true);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_update_customer_without_being_logged_in(): void
    {
        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml']);

        $data =
<<<JSON
        {
            "firstName": "New name",
            "lastName": "New lastName",
            "email": "shop@example.com",
            "birthday": "2017-11-01",
            "gender": "m",
            "phoneNumber": "0918972132",
            "subscribedToNewsletter": true
        }
JSON;
        $this->client->request('PUT', '/shop-api/me', [], [], self::CONTENT_TYPE_HEADER, $data);
        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_update_customer_without_passing_required_data(): void
    {
        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml']);
        $this->logInUser('oliver@queen.com', '123password');

        $data =
<<<JSON
        {
            "firstName": "",
            "lastName": "",
            "email": "",
            "gender": "",
            "phoneNumber": ""
        }
JSON;
        $this->client->request('PUT', '/shop-api/me', [], [], self::CONTENT_TYPE_HEADER, $data);
        $response = $this->client->getResponse();
        $this->assertResponse($response, 'customer/validation_empty_data', Response::HTTP_BAD_REQUEST);
    }
}
