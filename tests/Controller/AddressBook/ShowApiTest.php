<?php

declare(strict_types=1);

namespace Tests\Sylius\ShopApiPlugin\Controller\AddressBook;

use PHPUnit\Framework\Assert;
use Swagger\Client\Api\AddressApi;
use Swagger\Client\ApiException;
use Swagger\Client\Model\LoggedInCustomerAddressBook;
use Symfony\Component\HttpFoundation\Response;
use Tests\Sylius\ShopApiPlugin\Controller\JsonApiTestCase;
use Tests\Sylius\ShopApiPlugin\Controller\Utils\ShopUserLoginTrait;

final class ShowApiTest extends JsonApiTestCase
{
    use ShopUserLoginTrait;

    /**
     * @test
     */
    public function it_shows_address_book(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFiles(['channel.yml', 'customer.yml', 'country.yml', 'address.yml']);
        $this->logInUser('oliver@queen.com', '123password', $addressClient);

        $addressBook = $this->showAddressBook($addressClient);

        $this->assertTrue($addressBook->valid());
        $this->assertResponseContent($addressBook, 'address_book/show_address_book_response', 'json');
    }

    /**
     * @test
     */
    public function it_returns_a_not_found_exception_if_there_is_no_logged_in_user(): void
    {
        $addressClient = $this->createAddressClient();

        $this->loadFixturesFromFile('channel.yml');

        try {
            $this->showAddressBook($addressClient);

            $thrown = false;
        } catch (ApiException $exception) {
            Assert::assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());

            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    private function showAddressBook(AddressApi $addressClient): LoggedInCustomerAddressBook
    {
        return $addressClient->addressBook();
    }
}
