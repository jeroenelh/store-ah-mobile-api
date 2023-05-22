<?php

namespace Microit\StoreAhApi;

use Microit\StoreAhApi\Models\Category;
use Microit\StoreAhApi\Models\Image;
use Microit\StoreAhApi\Models\Product;
use Nyholm\Psr7\Stream;

class AH
{
    protected ClientConnection $clientConnection;
    public function __construct()
    {
        $connection = ClientConnection::getInstance();
        assert($connection instanceof ClientConnection);
        $this->clientConnection = $connection;
    }

    /**
     * @return Category[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getCategories(): array
    {
        $request = $this->clientConnection->createRequest('get', 'mobile-services/v1/product-shelves/categories');
        $response = $this->clientConnection->getJsonResponse($request);

        $categories = [];
        /** @var object $categoryResponse */
        foreach ($response as $categoryResponse) {
            assert(is_int($categoryResponse->id));
            assert(is_string($categoryResponse->name));
            assert(is_string($categoryResponse->slugifiedName));
            assert(is_array($categoryResponse->images) && count($categoryResponse->images) && is_object($categoryResponse->images[0]));
            assert(is_string($categoryResponse->images[0]->url));
            assert(is_int($categoryResponse->images[0]->width));
            assert(is_int($categoryResponse->images[0]->height));

            $categories[] = (new Category(
                id: $categoryResponse->id,
                name: $categoryResponse->name,
                slug: $categoryResponse->slugifiedName,
                image: (new Image(
                    url: $categoryResponse->images[0]->url,
                    width: $categoryResponse->images[0]->width,
                    height: $categoryResponse->images[0]->height
                ))
            ));
        }

        return $categories;
    }

    public function getProductsOfCategory(Category $category)
    {
        $fields = [
            'query' => '',
            'sortOn' => '',
            'taxonomyId' => 1796,
            'page' => 0,
            'size' => 2,
        ];
        $request = $this->clientConnection->createRequest('get', 'mobile-services/product/search/v2?'.http_build_query($fields));

        $response = $this->clientConnection->getJsonResponse($request);
        foreach ($response->products as $product) {
            var_dump($this->processObjectToProduct($product));
        }
    }

    public function processObjectToProduct(object $object): Product
    {
        assert(is_int($object->webshopId));
        assert(is_string($object->title));
        assert(is_string($object->brand));

        return new Product(
            id: $object->webshopId,
            title: $object->title,
            brand: $object->brand
        );
    }
}
