<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\AddressBook;

use PHPUnit\Framework\Assert;
use Swagger\Client\Api\AddressApi;
use Swagger\Client\ApiException;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Repository\AddressRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class RemoveAddressApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

    /**
     * @test
     */
    public function it_deletes_address_from_address_book(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->get('sylius.repository.address');
        /** @var AddressInterface $address */
        $address = $addressRepository->findOneBy(['street' => 'Kupreska']);

        $this->removeAddress((string) $address->getId(), $addressClient);

        $address = $addressRepository->findOneBy(['street' => 'Kupreska']);
        Assert::assertNull($address);
    }

    /**
     * @test
     */
    public function it_returns_a_not_found_exception_if_address_has_not_been_found(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        try {
            $this->removeAddress('-1', $addressClient);

            $thrown = false;
        } catch (ApiException $exception) {
            Assert::assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    /**
     * @test
     */
    public function it_validates_if_current_user_is_owner_of_address(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = $this->get('sylius.repository.address');
        /** @var AddressInterface $address */
        $address = $addressRepository->findOneBy(['street' => 'Vukovarska']);

        try {
            $this->removeAddress((string) $address->getId(), $addressClient);

            $thrown = false;
        } catch (ApiException $exception) {
            Assert::assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    private function removeAddress(string $id, AddressApi $client): void
    {
        $client->deleteAddress($id);
    }
}
