<?php

namespace Microit\StoreAhApi;

use Exception;
use Microit\StoreBase\Collections\CategoryCollection;
use Microit\StoreBase\Collections\ProductCollection;
use Microit\StoreBase\Exceptions\InvalidResponseException;
use Microit\StoreBase\Models\Category;
use Microit\StoreBase\Models\Image;
use Microit\StoreBase\Models\Product;
use Psr\Http\Client\ClientExceptionInterface;

class StoreConnector
{
    protected HttpClient $httpClient;

    public function __construct()
    {
        $connection = HttpClient::getInstance();
        assert($connection instanceof HttpClient);
        $this->httpClient = $connection;
    }

    /**
     * @return CategoryCollection
     * @throws ClientExceptionInterface|InvalidResponseException
     */
    public function getCategories(): CategoryCollection
    {
        $request = $this->httpClient->createRequest('get', 'mobile-services/v1/product-shelves/categories');
        $response = $this->httpClient->getJsonResponse($request);

        $categories = new CategoryCollection();
        /** @var object $categoryResponse */
        foreach ($response as $categoryResponse) {
            assert(is_int($categoryResponse->id));
            assert(is_string($categoryResponse->name));
            assert(is_string($categoryResponse->slugifiedName));
            assert(is_array($categoryResponse->images) && count($categoryResponse->images) && is_object($categoryResponse->images[0]));
            assert(is_string($categoryResponse->images[0]->url));
            assert(is_int($categoryResponse->images[0]->width));
            assert(is_int($categoryResponse->images[0]->height));

            $categories->add(new Category(
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

    /**
     * @param Category $category
     * @return ProductCollection
     * @throws ClientExceptionInterface
     */
    public function getProductsOfCategory(Category $category): ProductCollection
    {
        $searchResults = $this->requestProductSearch(category: $category, size: 500);

        $products = new ProductCollection();

        foreach ($searchResults->elements as $product) {
            assert(is_object($product));
            $products->add($this->processObjectToProduct($product));
        }

        return $products;
    }

    /**
     * @return AHSearchResults
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function requestProductSearch(string $query = '', string $sortOn = '', Category $category = null, int $page = 0, int $size = 100): AHSearchResults
    {
        $fields = [
            'query' => $query,
            'sortOn' => $sortOn,
            'taxonomyId' => $category?->getId(),
            'page' => $page,
            'size' => $size,
        ];
        $request = $this->httpClient->createRequest('get', 'mobile-services/product/search/v2?'.http_build_query($fields));

        $response = $this->httpClient->getJsonResponse($request);
        assert(is_object($response));

        return AHSearchResults::process($response);
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
