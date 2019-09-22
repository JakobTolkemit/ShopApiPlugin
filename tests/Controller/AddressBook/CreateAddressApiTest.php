<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\AddressBook;

use PHPUnit\Framework\Assert;
use Swagger\Client\Api\AddressApi;
use Swagger\Client\ApiException;
use Swagger\Client\Model\LoggedInCustomerAddressBookAddress;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Repository\AddressRepositoryInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class CreateAddressApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

    /**
     * @test
     */
    public function it_allows_user_to_add_new_address_to_address_book(): void
    {
        $addressClient = new AddressApi();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml']);
        $this->logInUser('oliver@queen.com', '123password');
        $addressClient->getConfig()->setApiKey('Authorization', $this->client->getServerParameter('HTTP_Authorization'));

        $data = new LoggedInCustomerAddressBookAddress([
            "first_name" => "New name",
            "last_name" => "New lastName",
            "phone_number" => "0918972132",
            "country_code" => "GB",
            "province_code" => "GB-WLS",
            "street" => "New street",
            "city" => "New city",
            "postcode" => "2000",
        ]);

        $addressClient->createAddress($data);

        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->get('sylius.repository.customer');
        /** @var Customer $customer */
        $customer = $customerRepository->findOneBy(['email' => 'oliver@queen.com']);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->get('sylius.repository.address');
        /** @var AddressInterface $address */
        $address = $addressRepository->findOneBy(['street' => 'New street']);

        Assert::assertSame($address->getCustomer()->getId(), $customer->getId());
        Assert::assertNotNull($address);
        Assert::assertTrue($customer->hasAddress($address));
    }

    /**
     * @test
     */
    public function it_does_not_allow_user_to_add_new_address_to_address_book_without_passing_required_data(): void
    {
        $addressClient = new AddressApi();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml']);
        $this->logInUser('oliver@queen.com', '123password');
        $addressClient->getConfig()->setApiKey('Authorization', $this->client->getServerParameter('HTTP_Authorization'));

        $data = new LoggedInCustomerAddressBookAddress([

            "first_name" => "",
            "last_name" => "",
            "country_code" => "",
            "street" => "",
            "city" => "",
            "postcode" => "",
        ]);

        try {
            $addressClient->createAddress($data);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent(
                $exception->getResponseBody(),
                'address_book/validation_create_address_book_response',
                'json'
            );

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_user_to_add_new_address_to_address_book_without_passing_correct_country_code(): void
    {
        $addressClient = new AddressApi();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml']);
        $this->logInUser('oliver@queen.com', '123password');
        $addressClient->getConfig()->setApiKey('Authorization', $this->client->getServerParameter('HTTP_Authorization'));

        $data = new LoggedInCustomerAddressBookAddress([

            "first_name" => "Davor",
            "last_name" => "Duhovic",
            "country_code" => "WRONG_COUNTRY_NAME",
            "street" => "Marmontova 21",
            "city" => "Split",
            "postcode" => "2100",
        ]);

        try {
            $addressClient->createAddress($data);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent(
                $exception->getResponseBody(),
                'address_book/validation_create_address_book_with_wrong_country_response',
                'json'
            );

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_user_to_add_new_address_to_address_book_without_passing_correct_province_code(): void
    {
        $addressClient = new AddressApi();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml']);
        $this->logInUser('oliver@queen.com', '123password');
        $addressClient->getConfig()->setApiKey('Authorization', $this->client->getServerParameter('HTTP_Authorization'));

        $data = new LoggedInCustomerAddressBookAddress([

            "first_name" => "Davor",
            "last_name" => "Duhovic",
            "country_code" => "GB",
            "street" => "Marmontova 21",
            "city" => "Split",
            "postcode" => "2100",
            "province_code" => "WRONG_PROVINCE_CODE",
        ]);

        try {
            $addressClient->createAddress($data);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());
            $this->assertResponseContent(
                $exception->getResponseBody(),
                'address_book/validation_create_address_book_with_wrong_province_response',
                'json'
            );

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }
}
