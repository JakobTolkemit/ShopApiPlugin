<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\AddressBook;

use PHPUnit\Framework\Assert;
use Swagger\Client\Api\AddressApi;
use Swagger\Client\ApiException;
use Swagger\Client\Model\LoggedInCustomerAddressBookAddress;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Repository\AddressRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class UpdateAddressApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

    /**
     * @test
     */
    public function it_updates_address_in_address_book(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->get('sylius.repository.address');
        /** @var AddressInterface $address */
        $address = $addressRepository->findOneBy(['street' => 'Kupreska']);

        $data = [
            "firstName" => "New name",
            "lastName" => "New lastName",
            "company" => "Locastic",
            "street" => "New street",
            "countryCode" => "GB",
            "provinceCode" => "GB-WLS",
            "city" => "New city",
            "postcode" => "2000",
            "phoneNumber" => "0918972132",
        ];

        $responseAddress = $this->updateAddress((string) $address->getId(), $data, $addressClient);
        $this->assertTrue($responseAddress->valid());

        /** @var AddressInterface $updatedAddress */
        $updatedAddress = $addressRepository->findOneBy(['id' => $address->getId()]);
        Assert::assertEquals($updatedAddress->getFirstName(), 'New name');
        Assert::assertEquals($updatedAddress->getLastName(), 'New lastName');
        Assert::assertEquals($updatedAddress->getCompany(), 'Locastic');
        Assert::assertEquals($updatedAddress->getCity(), 'New city');
        Assert::assertEquals($updatedAddress->getPostcode(), '2000');
        Assert::assertEquals($updatedAddress->getProvinceCode(), 'GB-WLS');
        Assert::assertEquals($updatedAddress->getPhoneNumber(), '0918972132');
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_update_address_if_country_or_province_code_are_not_valid(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->get('sylius.repository.address');
        /** @var AddressInterface $address */
        $address = $addressRepository->findOneBy(['street' => 'Kupreska']);

        $data = [
            "firstName" => "New name",
            "lastName" => "New lastName",
            "company" => "Locastic",
            "street" => "New street",
            "countryCode" => "WRONG_CODE",
            "provinceCode" => "WRONG_CODE",
            "city" => "New city",
            "postcode" => "2000",
            "phoneNumber" => "0918972132",
        ];

        try {
            $this->updateAddress((string)$address->getId(), $data, $addressClient);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_does_not_allow_to_update_address_without_passing_required_data(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->get('sylius.repository.address');
        /** @var AddressInterface $address */
        $address = $addressRepository->findOneBy(['street' => 'Kupreska']);

        $data = [
            "firstName" => "",
            "lastName" => "",
            "street" => "",
            "countryCode" => "",
            "city" => "",
            "postcode" => "",
        ];

        try {
            $this->updateAddress((string)$address->getId(), $data, $addressClient);

            $thrown = false;
        } catch (ApiException $exception) {
            $this->assertSame(Response::HTTP_BAD_REQUEST, $exception->getCode());

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    private function updateAddress(string $id, array $data, AddressApi $addressClient): LoggedInCustomerAddressBookAddress
    {
        return $addressClient->updateAddressBook($id, new LoggedInCustomerAddressBookAddress($data));
    }
}
